<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Enums\SubuserPermission;
use App\Models\Server;
use App\Models\User;
use Simplify\Toolkit\Models\Package;

/**
 * Toolkit's errors for Simplify Explain's question button (tag "simplify.problems", see Explain's README, "Explain your
 * plugin's errors"). Duck typed: without Explain nobody calls it.
 *
 * Ids: "simplify-toolkit:plugin-off:<package id>", a plugin switched off after a start; the lines are the server's
 * console lines saved at that moment.
 */
final class ToolkitProblems
{
    public const PREFIX = 'simplify-toolkit:plugin-off:';

    public static function pluginOffId(int $packageId): string
    {
        return self::PREFIX.$packageId;
    }

    public function canExplainProblem(string $id, User $user): bool
    {
        return $this->package($id, $user) !== null;
    }

    /** @return array{title: string, context: string, lines: list<string>}|null */
    public function explainProblem(string $id, User $user): ?array
    {
        $package = $this->package($id, $user);
        if ($package === null) {
            return null;
        }

        return [
            'title' => __('simplify-toolkit::packages.switched_off_after_start', ['name' => $package->title]),
            'context' => 'Simplify Toolkit switched off the server plugin "'.$package->title.'" ('.$package->filename
                .') because it failed to load on the last start of the game server. The lines come from the server\'s'
                .' console at that start, around the error.',
            'lines' => array_slice($package->off_lines ?? [], -100),
        ];
    }

    /** The switched-off package of this id, when this user may read the files of its server. */
    private function package(string $id, User $user): ?Package
    {
        if (! str_starts_with($id, self::PREFIX) || ! ctype_digit($ref = substr($id, strlen(self::PREFIX)))) {
            return null;
        }
        $package = Package::query()->find((int) $ref);
        if (! $package instanceof Package || $package->off_reason === '') {
            return null;
        }
        $server = Server::query()->find($package->server_id);

        return $server instanceof Server && $user->can(SubuserPermission::FileRead, $server) ? $package : null;
    }
}
