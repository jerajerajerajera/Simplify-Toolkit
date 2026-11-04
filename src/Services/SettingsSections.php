<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Models\Server;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Sections other add-ons put on Toolkit's Settings page of a server, for example Server Maker's Software and
 * Performance. A source is any object tagged 'simplify.server-settings-sections' in the container, with:
 *
 * - serverSettingsKey(): string                  a short key, unique per add-on, such as 'simplify-servermaker'
 * - serverSettingsSections(Server): list<Section> its sections; [] when they do not apply to this server or person
 * - serverSettingsFill(Server): array             the sections' state, relative to the sections
 * - serverSettingsSave(Server, array): bool       saves the state Save gives it; false when it could not, or a
 *                                                 ValidationException (keys data.sections.<key>.<field>) for a field
 * - serverSettingsVariables(Server): list<string> (optional) egg variables its sections handle, left out of Startup
 *
 * Each section needs a heading; an icon (Section::icon) shows in the list of sections.
 */
final class SettingsSections
{
    public const TAG = 'simplify.server-settings-sections';

    /** @return array<string, object> by key */
    private static function sources(): array
    {
        $sources = [];
        foreach (app()->tagged(self::TAG) as $source) {
            if (is_object($source) && method_exists($source, 'serverSettingsKey') && method_exists($source, 'serverSettingsSections')) {
                $sources[(string) $source->serverSettingsKey()] = $source;
            }
        }

        return $sources;
    }

    /**
     * Fresh components on every call: a form takes its components as its own, so they are never shared.
     *
     * @return array<string, list<Section>>
     */
    private static function sections(Server $server): array
    {
        $all = [];
        foreach (self::sources() as $key => $source) {
            try {
                $sections = array_values(array_filter((array) $source->serverSettingsSections($server), fn ($section) => $section instanceof Section));
            } catch (Throwable $error) {
                report($error);
                $sections = [];
            }
            foreach ($sections as $index => $section) {
                // With Simplify the sections share one card, so none gets a box of its own (no box inside a box).
                $section->extraAttributes(['id' => self::id($key, $index)], merge: true)->contained(! class_exists('Simplify\\Core\\Addons\\Addon'));
            }
            if ($sections !== []) {
                $all[$key] = $sections;
            }
        }

        return $all;
    }

    private static function id(string $key, int $index): string
    {
        return 'settings-'.preg_replace('/[^a-z0-9-]+/', '-', strtolower($key)).'-'.$index;
    }

    /** @return list<Component> one group per add-on, with its state at data.sections.<key> */
    public static function components(Server $server): array
    {
        $groups = [];
        foreach (self::sections($server) as $key => $sections) {
            $groups[] = Group::make($sections)->statePath($key)->columnSpanFull();
        }

        return $groups === [] ? [] : [Group::make($groups)->statePath('sections')->columnSpanFull()];
    }

    /** @return array<string, array<string, mixed>> */
    public static function fill(Server $server): array
    {
        $sources = self::sources();
        $state = [];
        foreach (array_keys(self::sections($server)) as $key) {
            try {
                $state[$key] = method_exists($sources[$key], 'serverSettingsFill') ? (array) $sources[$key]->serverSettingsFill($server) : [];
            } catch (Throwable $error) {
                report($error);
                $state[$key] = [];
            }
        }

        return $state;
    }

    /** @param array<string, mixed> $state */
    public static function save(Server $server, string $key, array $state): bool
    {
        $source = self::sources()[$key] ?? null;
        if ($source === null || ! method_exists($source, 'serverSettingsSave')) {
            return true;
        }
        try {
            return (bool) $source->serverSettingsSave($server, $state);
        } catch (ValidationException $error) {
            // A field of the section was refused: the page shows the message at that field.
            throw $error;
        } catch (Throwable $error) {
            report($error);

            return false;
        }
    }

    /** @return list<string> the egg variables that add-on sections handle */
    public static function variables(Server $server): array
    {
        $variables = [];
        foreach (self::sources() as $source) {
            if (method_exists($source, 'serverSettingsVariables')) {
                try {
                    array_push($variables, ...array_map('strval', (array) $source->serverSettingsVariables($server)));
                } catch (Throwable $error) {
                    report($error);
                }
            }
        }

        return $variables;
    }

    /** @return list<array{id: string, label: string, icon: string}> */
    public static function contents(Server $server): array
    {
        $list = [];
        foreach (self::sections($server) as $key => $sections) {
            foreach ($sections as $index => $section) {
                $icon = $section->getIcon();
                $heading = $section->getHeading();
                $list[] = ['id' => self::id($key, $index), 'label' => $heading instanceof Htmlable ? strip_tags($heading->toHtml()) : (string) $heading, 'icon' => is_string($icon) ? $icon : 'tabler-puzzle'];
            }
        }

        return $list;
    }
}
