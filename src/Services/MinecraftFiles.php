<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Facades\Activity;
use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

/**
 * server.properties and eula.txt of one server, read and written through Wings like Pelican's file manager does.
 */
final class MinecraftFiles
{
    public const PROPERTIES = '/server.properties';

    public const EULA = '/eula.txt';

    private const MAX_SIZE = 1048576;

    public function __construct(private readonly Server $server) {}

    /** The file's text, '' when it does not exist yet (Minecraft makes it on the first start). */
    public function read(string $path): string
    {
        try {
            return $this->files()->getContent($path, self::MAX_SIZE);
        } catch (FileNotFoundException) {
            return '';
        }
    }

    /**
     * Changes only the given keys of server.properties.
     *
     * @param  array<string, string>  $values
     */
    public function saveProperties(array $values): void
    {
        $this->write(self::PROPERTIES, PropertiesFile::merge($this->read(self::PROPERTIES), $values));
    }

    public function acceptEula(): void
    {
        $this->write(self::EULA, PropertiesFile::merge($this->read(self::EULA), ['eula' => 'true']));
    }

    public function saveIcon(string $png): void
    {
        $this->write(ServerIcon::FILE, $png);
    }

    public function removeIcon(): void
    {
        $this->files()->deleteFiles('/', [ltrim(ServerIcon::FILE, '/')])->throw();
        Activity::event('server:file.delete')->subject($this->server)->property('directory', '/')->property('files', [ltrim(ServerIcon::FILE, '/')])->log();
    }

    private function write(string $path, string $content): void
    {
        $this->files()->putContent($path, $content)->throw();
        // The Minecraft page shows its own reminder after saving.
        RestartReminder::quietly(fn () => Activity::event('server:file.write')->subject($this->server)->property('file', $path)->log());
    }

    private function files(): DaemonFileRepository
    {
        return app(DaemonFileRepository::class)->setServer($this->server);
    }
}
