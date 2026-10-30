<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Enums\ContainerStatus;
use App\Models\Server;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Who plays on a Minecraft server: the online names from the server list, and the whitelist, operators and bans from
 * the files the server keeps. Changes go through the console with Minecraft's own commands, so the server must run.
 */
final class Players
{
    /** Action => console command; the player's name follows it. */
    public const COMMANDS = [
        'whitelist' => 'whitelist add', 'unwhitelist' => 'whitelist remove', 'op' => 'op', 'deop' => 'deop',
        'kick' => 'kick', 'ban' => 'ban', 'unban' => 'pardon',
    ];

    /** The lists, by the name of their file. */
    public const LISTS = ['whitelist' => '/whitelist.json', 'operators' => '/ops.json', 'banned' => '/banned-players.json'];

    public function __construct(private readonly Server $server) {}

    /** A Java name, or a Bedrock name through Geyser with its "." or "*" in front. */
    public static function valid(string $name): bool
    {
        return preg_match('/^[.*]?\w{1,16}$/', $name) === 1;
    }

    public function running(): bool
    {
        try {
            return $this->server->retrieveStatus() === ContainerStatus::Running;
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array{online: list<string>, whitelist: list<array{name: string, id: string}>, operators: list<array{name: string, id: string}>, banned: list<array{name: string, id: string}>} */
    public function lists(): array
    {
        $files = new MinecraftFiles($this->server);
        $lists = ['online' => $this->running() ? (StatusPing::get($this->server)['names'] ?? []) : []];
        foreach (self::LISTS as $key => $file) {
            $list = json_decode($files->read($file), true);
            $lists[$key] = [];
            foreach (is_array($list) ? $list : [] as $player) {
                $name = is_array($player) ? (string) ($player['name'] ?? '') : '';
                if (self::valid($name)) {
                    $lists[$key][] = ['name' => $name, 'id' => preg_replace('/[^0-9a-f-]/i', '', (string) ($player['uuid'] ?? ''))];
                }
            }
            usort($lists[$key], fn (array $a, array $b) => strcasecmp($a['name'], $b['name']));
        }

        return $lists;
    }

    public function act(string $action, string $name): void
    {
        if (! isset(self::COMMANDS[$action]) || ! self::valid($name)) {
            throw new InvalidArgumentException('Unknown action or player name.');
        }
        if ($this->server->send(self::COMMANDS[$action].' '.$name)->getStatusCode() >= 300) {
            throw new RuntimeException('Wings did not take the command.');
        }
    }
}
