<?php

declare(strict_types=1);

namespace Simplify\Toolkit\Services;

/**
 * Java .properties files such as server.properties: read them, and change only chosen keys while every other
 * line, comment and order stays as it was.
 */
final class PropertiesFile
{
    /** @return array<string, string> */
    public static function parse(string $content): array
    {
        $rows = [];
        $pending = '';
        foreach (preg_split('/\r\n|\n|\r/', $content) as $line) {
            $line = $pending !== '' ? $pending.ltrim($line) : $line;
            $pending = '';
            if (preg_match('/^\s*[#!]/', $line)) {
                continue;
            }
            $slashes = strlen($line) - strlen(rtrim($line, '\\'));
            if ($slashes % 2 === 1) {
                $pending = substr($line, 0, -1);

                continue;
            }
            if (preg_match('/^\s*([^\s=:]+)\s*[=:]?\s*(.*)$/', $line, $match)) {
                $rows[$match[1]] = self::decode($match[2]);
            }
        }

        return $rows;
    }

    /**
     * Replaces the given keys, keeps everything else, and adds keys that were missing at the end.
     *
     * @param  array<string, string>  $values
     */
    public static function merge(string $original, array $values): string
    {
        $result = [];
        $seen = [];
        $continuation = false;
        $skip = false;
        foreach (preg_split('/\r\n|\n|\r/', $original) as $line) {
            if (! $continuation) {
                $skip = false;
                if (preg_match('/^\s*([^#!\s=:]+)\s*(?:[=:]|\s)\s*/', $line, $match) && array_key_exists($match[1], $values)) {
                    $key = $match[1];
                    $skip = true;
                    if (! isset($seen[$key])) {
                        $result[] = $key.'='.self::encode((string) $values[$key]);
                        $seen[$key] = true;
                    }
                }
            }
            if (! $skip) {
                $result[] = $line;
            }
            $slashes = strlen($line) - strlen(rtrim($line, '\\'));
            $continuation = $slashes % 2 === 1 && ! preg_match('/^\s*[#!]/', $line);
        }
        foreach ($values as $key => $value) {
            if (! isset($seen[$key])) {
                $result[] = $key.'='.self::encode((string) $value);
            }
        }

        return rtrim(implode("\n", $result), "\n")."\n";
    }

    /** A value as Java writes it: escaped line breaks and backslashes, and \uXXXX for anything outside ASCII. */
    public static function encode(string $value): string
    {
        $value = str_replace(['\\', "\r", "\n", "\t"], ['\\\\', '\\r', '\\n', '\\t'], $value);
        $value = (string) preg_replace_callback('/^ +/', fn (array $match) => str_repeat('\\ ', strlen($match[0])), $value);

        return (string) preg_replace_callback('/[^\x20-\x7e]/u', function (array $match): string {
            $units = unpack('n*', mb_convert_encoding($match[0], 'UTF-16BE', 'UTF-8'));

            return implode('', array_map(fn (int $unit) => sprintf('\\u%04x', $unit), $units));
        }, $value);
    }

    public static function decode(string $value): string
    {
        return (string) preg_replace_callback('/\\\\((?:u[a-fA-F0-9]{4}(?:\\\\u[a-fA-F0-9]{4})*)|.)/s', static function (array $match): string {
            if (preg_match('/^u[a-fA-F0-9]{4}/', $match[1])) {
                preg_match_all('/u([a-fA-F0-9]{4})/', $match[1], $units);
                $binary = implode('', array_map(static fn (string $hex) => pack('n', hexdec($hex)), $units[1]));

                return mb_convert_encoding($binary, 'UTF-8', 'UTF-16BE');
            }

            return match ($match[1]) {
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'f' => "\f",
                default => $match[1],
            };
        }, $value);
    }
}
