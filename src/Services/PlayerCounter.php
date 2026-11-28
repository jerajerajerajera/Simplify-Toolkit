<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Models\Server;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Boy132's plugin "Player Counter", when it is installed: an egg linked to one of its game queries takes its player
 * count from there, so the admin sets each game in one place only. Read through its own model, which asks the server.
 *
 * @see https://github.com/pelican-dev/plugins/tree/main/player-counter
 */
final class PlayerCounter
{
    private const MODEL = 'Boy132\\PlayerCounter\\Models\\GameQuery';

    /** @var array<int, array{type: string, offset: int|null, variable: string|null}>|null */
    private static ?array $eggs = null;

    public static function installed(): bool
    {
        try {
            return class_exists(self::MODEL) && self::hasTables();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * The eggs that have a game query in Player Counter, with its type and port; [] when it is not installed.
     *
     * @return array<int, array{type: string, offset: int|null, variable: string|null}>
     */
    public static function eggs(): array
    {
        if (self::$eggs === null) {
            self::$eggs = [];
            try {
                if (class_exists(self::MODEL) && self::hasTables()) {
                    $rows = DB::table('egg_game_query')->join('game_queries', 'game_queries.id', '=', 'egg_game_query.game_query_id')
                        ->get(['egg_game_query.egg_id', 'game_queries.query_type', 'game_queries.query_port_offset', 'game_queries.query_port_variable']);
                    foreach ($rows as $row) {
                        self::$eggs[(int) $row->egg_id] = [
                            'type' => (string) $row->query_type,
                            'offset' => $row->query_port_offset === null ? null : (int) $row->query_port_offset,
                            'variable' => $row->query_port_variable === null ? null : (string) $row->query_port_variable,
                        ];
                    }
                }
            } catch (Throwable) {
                self::$eggs = [];
            }
        }

        return self::$eggs;
    }

    /**
     * The players of a server whose egg has a game query in Player Counter, as Player Counter counts them; [] when it
     * does not answer. Null when Player Counter has no game query for the egg.
     *
     * @return array{players: int, max_players: int, version: string}|array{}|null
     */
    public static function get(Server $server): ?array
    {
        if (! isset(self::eggs()[(int) $server->egg_id])) {
            return null;
        }
        try {
            /** @var class-string<Model> $model */
            $model = self::MODEL;
            $query = $model::query()->whereHas('eggs', fn ($eggs) => $eggs->whereKey($server->egg_id))->first();
            $answer = $query !== null && method_exists($query, 'runQuery') ? $query->runQuery($server) : null;
            if (! is_array($answer) || ! isset($answer['current_players'])) {
                return [];
            }

            return ['players' => max(0, (int) $answer['current_players']), 'max_players' => max(0, (int) ($answer['max_players'] ?? 0)), 'version' => ''];
        } catch (Throwable) {
            return [];
        }
    }

    /** For tests: forget what was read from the database. */
    public static function forget(): void
    {
        self::$eggs = null;
    }

    private static function hasTables(): bool
    {
        return Schema::hasTable('egg_game_query') && Schema::hasTable('game_queries');
    }
}
