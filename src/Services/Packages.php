<?php

declare(strict_types=1);

namespace Simplify\Mods\Services;

use App\Exceptions\Repository\FileExistsException;
use App\Facades\Activity;
use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use Illuminate\Support\Facades\Cache;
use Simplify\Mods\Models\Package;
use Simplify\Mods\Models\ServerChoice;
use Simplify\Mods\Services\Sources\Catalog;
use Simplify\Mods\Services\Sources\Versions;
use Throwable;

/**
 * The plugins or mods of one server: what it runs, what is installed, and installing, switching off and removing.
 * Files only change through Wings; removed and replaced files go to a backup folder instead of being deleted.
 */
final class Packages
{
    public const BACKUPS = '.simplify-mods-backups';

    public function __construct(private readonly Server $server, private readonly Catalog $catalog) {}

    public static function for(Server $server): self
    {
        return new self($server, new Catalog(Keys::curseForge(user())));
    }

    /**
     * What the server runs. loader and minecraft are '' when neither the egg nor the owner says; the page then asks.
     *
     * @return array{kind: string, loader: string, minecraft: string, directory: string, detected: bool}
     */
    public function context(): array
    {
        $kind = ServerKind::forServer($this->server);
        $choice = ServerChoice::query()->where('server_id', $this->server->id)->first();
        $loader = $kind['loader'];
        $minecraft = $kind['version'];
        if (($loader === '' || $minecraft === '') && $kind['modpack'] !== '') {
            $pack = $this->modpack($kind['modpack']);
            $loader = $loader ?: $pack['loader'];
            $minecraft = $minecraft ?: $pack['minecraft'];
        }
        if ($minecraft === '' && $kind['kind'] === 'plugins' && ! $kind['proxy']) {
            $minecraft = $this->runningVersion();
        }
        $detected = $loader !== '' && $minecraft !== '';
        $loader = $loader ?: (string) $choice?->loader;
        $minecraft = $minecraft ?: (string) $choice?->minecraft;

        return [
            'kind' => $kind['kind'],
            'loader' => $loader,
            'minecraft' => $minecraft,
            'directory' => $kind['kind'] === 'plugins' ? '/plugins' : '/mods',
            'detected' => $detected,
        ];
    }

    /** Saves what the owner chose when the egg does not say. */
    public function choose(string $loader, string $minecraft): void
    {
        abort_unless(in_array($loader, [...ServerKind::MOD_LOADERS, ...ServerKind::PLUGIN_LOADERS, ''], true), 422);
        abort_unless($minecraft === '' || preg_match('/^\d+\.\d+(\.\d+)?$/', $minecraft) === 1, 422);
        ServerChoice::query()->updateOrCreate(['server_id' => $this->server->id], ['loader' => $loader, 'minecraft' => $minecraft]);
    }

    /** Paper and Purpur set to "latest": the version they really run, from the file Paper writes on start. */
    private function runningVersion(): string
    {
        return (string) Cache::remember('simplify-mods.running.'.$this->server->uuid, 300, function (): string {
            try {
                $history = json_decode($this->files()->getContent('/version_history.json', 65536), true);
            } catch (Throwable) {
                return '';
            }

            return preg_match('/\(MC: (\d+\.\d+(?:\.\d+)?)\)/', (string) ($history['currentVersion'] ?? ''), $match) ? $match[1] : '';
        });
    }

    /**
     * The loader and Minecraft version of a modpack, from its site (Modrinth) or its files (FTB).
     *
     * @return array{loader: string, minecraft: string}
     */
    private function modpack(string $modpack): array
    {
        return Cache::remember('simplify-mods.modpack.'.$this->server->uuid, 3600, function () use ($modpack): array {
            $variables = $this->variables();
            if ($modpack === 'modrinth' && preg_match('/^\w{1,64}$/', $variables['VERSION_ID'] ?? '') === 1) {
                $version = Web::json('https://api.modrinth.com/v2/version/'.$variables['VERSION_ID']) ?? [];
                $loader = (string) (array_values(array_intersect(ServerKind::MOD_LOADERS, (array) ($version['loaders'] ?? [])))[0] ?? '');

                return ['loader' => $loader, 'minecraft' => (string) ($version['game_versions'][0] ?? '')];
            }
            if ($modpack === 'ftb') {
                foreach (['neoforge' => '/libraries/net/neoforged/neoforge', 'forge' => '/libraries/net/minecraftforge/forge', 'fabric' => '/.fabric'] as $loader => $path) {
                    try {
                        if ($this->files()->getDirectory($path)) {
                            return ['loader' => $loader, 'minecraft' => ''];
                        }
                    } catch (Throwable) {
                    }
                }
            }

            return ['loader' => '', 'minecraft' => ''];
        });
    }

    /** @return array<string, string> */
    private function variables(): array
    {
        $variables = [];
        foreach ($this->server->variables as $variable) {
            $variables[$variable->env_variable] = (string) ($variable->server_value ?? $variable->default_value);
        }

        return $variables;
    }

    /**
     * The .jar files in the plugins or mods folder, with what Simplify Mods knows about the ones it installed.
     *
     * @return list<array<string, mixed>>
     */
    public function installed(): array
    {
        $context = $this->context();
        $tracked = Package::query()->where('server_id', $this->server->id)->get()->keyBy('filename');
        $items = [];
        foreach ($this->files()->getDirectory($context['directory']) as $file) {
            $name = (string) ($file['name'] ?? '');
            if (! ($file['file'] ?? true) || preg_match('/\.jar(\.disabled)?$/i', $name) !== 1) {
                continue;
            }
            $base = (string) preg_replace('/\.disabled$/i', '', $name);
            /** @var Package|null $package */
            $package = $tracked[$base] ?? null;
            [$title, $version] = Versions::fromFilename($name);
            $items[] = [
                'file' => $name,
                'enabled' => ! str_ends_with(strtolower($name), '.disabled'),
                'title' => $package?->title ?? $title,
                'version' => $package?->version ?: $version,
                'source' => $package?->source ?? '',
                'key' => $package?->key ?? '',
                'icon' => $package?->icon ?? '',
                'page' => $package?->page ?? '',
                'size' => (int) ($file['size'] ?? 0),
            ];
        }
        usort($items, fn (array $a, array $b) => strcasecmp($a['title'], $b['title']));

        return $items;
    }

    /**
     * Downloads the chosen release into the plugins or mods folder. A version of the same plugin installed before is
     * moved to the backup folder.
     *
     * @param  array<string, mixed>  $card  title, icon and page from the search result
     * @return array<string, mixed> the release that was installed
     */
    public function install(string $key, bool $anyway, array $card = []): array
    {
        $context = $this->context();
        abort_unless(Catalog::parseKey($key) !== null, 422, __('simplify-mods::packages.unknown'));
        abort_if($context['loader'] === '' || $context['minecraft'] === '', 422, __('simplify-mods::packages.choose_first'));
        $release = $this->catalog->releases([$key], $context['kind'], $context['loader'], $context['minecraft'])[$key] ?? null;
        abort_unless(is_array($release), 422, __('simplify-mods::packages.unknown'));
        abort_unless($release['installable'], 422, __('simplify-mods::sources.'.$release['reason'], ['site' => $release['label']]));
        abort_unless($release['fits'] || $anyway, 409, __('simplify-mods::packages.not_for_version', ['version' => $context['minecraft']]));
        $missing = $this->missingDependencies($release['needs']);
        abort_if($missing !== [], 422, __('simplify-mods::packages.needs', ['names' => implode(', ', $missing)]));

        $old = Package::query()->where('server_id', $this->server->id)->where('key', $key)->first();
        if ($old !== null && $old->filename !== $release['filename']) {
            $this->archive($context['directory'], [$old->filename, $old->filename.'.disabled']);
        }
        $this->files()->pull($release['url'], $context['directory'], ['filename' => $release['filename'], 'foreground' => true])->throw();
        Activity::event('server:file.pull')->subject($this->server)
            ->property('directory', $context['directory'])->property('url', $release['url'])->log();
        Package::query()->updateOrCreate(['server_id' => $this->server->id, 'key' => $key], [
            'source' => $release['source'],
            'directory' => $context['directory'],
            'filename' => $release['filename'],
            'title' => mb_substr((string) ($card['title'] ?? $old?->title ?? Versions::fromFilename($release['filename'])[0]), 0, 100),
            'version' => $release['version'],
            'version_id' => $release['version_id'],
            'icon' => Web::https($card['icon'] ?? null) ?? ($old?->icon ?? ''),
            'page' => Web::https($card['page'] ?? null) ?? Web::https($release['page']) ?? ($old?->page ?? ''),
            'fits' => $release['fits'],
        ]);

        return $release;
    }

    /**
     * Required Modrinth projects that are not installed yet, by name.
     *
     * @param  list<string>  $needs
     * @return list<string>
     */
    private function missingDependencies(array $needs): array
    {
        $installed = Package::query()->where('server_id', $this->server->id)->pluck('key')->all();
        $missing = array_values(array_filter($needs, fn (string $id) => ! in_array('modrinth:'.$id, $installed, true)));
        if ($missing === []) {
            return [];
        }
        $projects = Web::json('https://api.modrinth.com/v2/projects?'.http_build_query(['ids' => json_encode($missing)])) ?? [];
        $names = array_column(array_filter($projects, 'is_array'), 'title', 'id');

        return array_map(fn (string $id) => (string) ($names[$id] ?? $id), $missing);
    }

    /** Off = renamed to .jar.disabled, which the server skips on start. */
    public function toggle(string $file, bool $on): void
    {
        $directory = ltrim($this->context()['directory'], '/');
        $base = (string) preg_replace('/\.disabled$/i', '', self::checkName($file));
        $target = $on ? $base : $base.'.disabled';
        if ($target === $file) {
            return;
        }
        $this->files()->renameFiles('/', [['from' => $directory.'/'.$file, 'to' => $directory.'/'.$target]])->throw();
        Activity::event('server:file.rename')->subject($this->server)->property('directory', '/'.$directory)
            ->property('files', [['from' => $file, 'to' => $target]])->log();
    }

    /** Moves the file to the backup folder and forgets it. */
    public function remove(string $file): void
    {
        $context = $this->context();
        $this->archive($context['directory'], [self::checkName($file)]);
        $base = (string) preg_replace('/\.disabled$/i', '', $file);
        Package::query()->where('server_id', $this->server->id)->where('filename', $base)->delete();
    }

    /** @param  list<string>  $names  files that do not exist are skipped */
    private function archive(string $directory, array $names): void
    {
        $existing = array_column($this->files()->getDirectory($directory), 'name');
        $names = array_values(array_intersect($names, $existing));
        if ($names === []) {
            return;
        }
        try {
            $this->files()->createDirectory(self::BACKUPS, '/')->throw();
        } catch (FileExistsException) {
        }
        $moves = array_map(fn (string $name) => ['from' => ltrim($directory, '/').'/'.$name, 'to' => self::BACKUPS.'/'.date('Ymd-His').'-'.$name], $names);
        $this->files()->renameFiles('/', $moves)->throw();
        Activity::event('server:file.rename')->subject($this->server)->property('directory', '/')->property('files', $moves)->log();
    }

    private static function checkName(string $file): string
    {
        abort_unless(preg_match('/^[a-zA-Z0-9._+ -]{1,160}\.jar(\.disabled)?$/', $file) === 1, 422);

        return $file;
    }

    private function files(): DaemonFileRepository
    {
        return app(DaemonFileRepository::class)->setServer($this->server);
    }
}
