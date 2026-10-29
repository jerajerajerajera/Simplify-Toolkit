<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

use App\Models\User;
use Simplify\Toolkit\Models\ApiKey;
use Simplify\Toolkit\Services\Sources\CurseForge;

/**
 * API keys of outside services. A user's own key goes before the key an admin saved for the whole panel.
 */
final class Keys
{
    public const CURSEFORGE = 'curseforge';

    public static function curseForge(?User $user = null): string
    {
        return self::get(self::CURSEFORGE, $user) ?? self::get(self::CURSEFORGE, null) ?? '';
    }

    /** The saved key, null without one. Without a user: the key for the whole panel. */
    public static function get(string $service, ?User $user): ?string
    {
        $key = ApiKey::query()->where('service', $service)->where('user_id', $user?->id)->latest('id')->first();

        return $key !== null && $key->secret !== '' ? $key->secret : null;
    }

    public static function has(string $service, ?User $user): bool
    {
        return ApiKey::query()->where('service', $service)->where('user_id', $user?->id)->exists();
    }

    public static function save(string $service, ?User $user, string $secret): void
    {
        self::forget($service, $user);
        ApiKey::query()->create(['service' => $service, 'user_id' => $user?->id, 'secret' => $secret]);
    }

    public static function forget(string $service, ?User $user): void
    {
        ApiKey::query()->where('service', $service)->where('user_id', $user?->id)->delete();
    }

    /** Whether CurseForge accepts the key: it answers the Minecraft game only with a valid one. */
    public static function curseForgeWorks(string $key): bool
    {
        $answer = Web::json(CurseForge::API.'games/432', ['x-api-key' => $key]);

        return (int) ($answer['data']['id'] ?? 0) === 432;
    }
}
