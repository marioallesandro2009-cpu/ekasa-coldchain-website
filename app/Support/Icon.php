<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Inline Lucide icons (ISC licence) from resources/svg/lucide/*.svg.
 */
final class Icon
{
    /** @var array<string, string> */
    private static array $cache = [];

    /**
     * Inner markup (paths) of an icon.
     */
    public static function inner(string $name): string
    {
        if (! preg_match('/^[a-z0-9-]+$/', $name)) {
            throw new InvalidArgumentException("Invalid icon name [{$name}].");
        }

        return self::$cache[$name] ??= self::read($name);
    }

    private static function read(string $name): string
    {
        $path = resource_path("svg/lucide/{$name}.svg");

        if (! is_file($path)) {
            throw new InvalidArgumentException("Icon [{$name}] not found in resources/svg/lucide.");
        }

        $svg = (string) file_get_contents($path);

        return preg_match('#<svg[^>]*>(.*)</svg>#s', $svg, $match)
            ? trim((string) preg_replace('/\s+/', ' ', $match[1]))
            : '';
    }
}
