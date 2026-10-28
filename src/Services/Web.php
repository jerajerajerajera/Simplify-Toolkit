<?php

declare(strict_types=1);

namespace Simplify\Mods\Services;

use Closure;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Requests to outside services (listed in the README), with short time-outs. Tests replace $transport.
 */
final class Web
{
    /** @var (Closure(string, array<string, string>): ?string)|null returns the body, or null on failure */
    public static ?Closure $transport = null;

    public const USER_AGENT = 'Simplify-Mods (+https://github.com/jerajerajerajera/simplify-mods)';

    /**
     * @param  array<string, string>  $headers
     * @return array<mixed>|null null when the service did not answer or did not answer with JSON
     */
    public static function json(string $url, array $headers = []): ?array
    {
        $body = self::get($url, ['Accept' => 'application/json', ...$headers]);
        $data = $body === null ? null : json_decode($body, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Several JSON requests at once. A site that fails or is slow gives null instead of failing the rest.
     *
     * @param  array<string, array{0: string, 1: array<string, string>}>  $requests  name => [url, headers]
     * @return array<string, array<mixed>|null>
     */
    public static function many(array $requests): array
    {
        $requests = array_filter($requests, fn (array $request) => self::https($request[0]) !== null);
        if (self::$transport !== null) {
            return array_map(function (array $request): ?array {
                $data = json_decode((string) (self::$transport)($request[0], $request[1]), true);

                return is_array($data) ? $data : null;
            }, $requests);
        }
        if ($requests === []) {
            return [];
        }
        try {
            $responses = Http::pool(fn (Pool $pool) => array_map(
                fn (string $name) => $pool->as($name)->acceptJson()
                    ->withHeaders(['User-Agent' => self::USER_AGENT, ...$requests[$name][1]])
                    ->connectTimeout(3)->timeout(8)->get($requests[$name][0]),
                array_map('strval', array_keys($requests)),
            ));
        } catch (Throwable) {
            return array_map(fn () => null, $requests);
        }
        $out = [];
        foreach ($requests as $name => $_) {
            $response = $responses[$name] ?? null;
            $data = $response instanceof Response && $response->successful() ? $response->json() : null;
            $out[$name] = is_array($data) ? $data : null;
        }

        return $out;
    }

    /** @param  array<string, string>  $headers */
    public static function get(string $url, array $headers = [], int $maxBytes = 8388608): ?string
    {
        if (self::https($url) === null) {
            return null;
        }
        if (self::$transport !== null) {
            return (self::$transport)($url, $headers);
        }
        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT, ...$headers])
                ->connectTimeout(3)->timeout(10)
                ->withOptions(['allow_redirects' => ['max' => 3, 'protocols' => ['https']]])
                ->get($url);
        } catch (Throwable) {
            return null;
        }
        $body = $response->successful() ? $response->body() : null;

        return $body !== null && strlen($body) <= $maxBytes ? $body : null;
    }

    /** The URL when it is a plain https link, else null. */
    public static function https(mixed $url): ?string
    {
        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https'
            && parse_url($url, PHP_URL_USER) === null ? $url : null;
    }
}
