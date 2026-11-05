<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Enums\SubuserPermission;
use App\Models\Server;

/** The quick commands above the console of a Minecraft server, on a phone. */
final class QuickCommands
{
    /** A command ending in a space waits for the rest (shown with "…"). */
    public const LIST = ['list', 'say ', 'time set day', 'weather clear', 'save-all'];

    /** Java Minecraft servers (no proxy), for whoever may send commands. */
    public static function shownFor(Server $server): bool
    {
        $kind = ServerKind::forServer($server);

        return $kind['minecraft'] && ! $kind['proxy'] && (bool) user()?->can(SubuserPermission::ControlConsole, $server);
    }
}
