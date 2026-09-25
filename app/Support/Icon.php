<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Inline SVG icons: the admin icon library first (App\Support\IconLibrary: the
 * Ekasa stock and uploaded icons), then the files: the original Ekasa content icons
 * (resources/icons/ekasa, stroke 1.75) and the Lucide interface icons
 * (resources/svg/lucide, ISC, stroke 2).
 */
final class Icon
{
    /** @var array<string, array{inner: string, strokeWidth: string}> */
    private static array $cache = [];

    /**
     * Inner markup (paths) of an icon.
     */
    public static function inner(string $name): string
    {
        return self::load($name)['inner'];
    }

    /**
     * Stroke width the icon was drawn for.
     */
    public static function strokeWidth(string $name): string
    {
        return self::load($name)['strokeWidth'];
    }

    /**
     * @return array{inner: string, strokeWidth: string}
     */
    private static function load(string $name): array
    {
        if (! preg_match('/^[a-z0-9-]+$/', $name)) {
            throw new InvalidArgumentException("Invalid icon name [{$name}].");
        }

        return self::$cache[$name] ??= self::read($name);
    }

    /**
     * Forget the parsed icons (after the library changed).
     */
    public static function flush(): void
    {
        self::$cache = [];
    }

    /**
     * @return array{inner: string, strokeWidth: string}
     */
    private static function read(string $name): array
    {
        if ($icon = IconLibrary::find($name)) {
            return $icon;
        }

        foreach ([resource_path("icons/ekasa/{$name}.svg"), resource_path("svg/lucide/{$name}.svg")] as $path) {
            if (! is_file($path)) {
                continue;
            }

            $svg = (string) file_get_contents($path);
            $inner = preg_match('#<svg[^>]*>(.*)</svg>#s', $svg, $match) ? trim((string) preg_replace('/\s+/', ' ', $match[1])) : '';
            $strokeWidth = preg_match('#<svg[^>]*\sstroke-width="([\d.]+)"#', $svg, $width) ? $width[1] : '2';

            return ['inner' => $inner, 'strokeWidth' => $strokeWidth];
        }

        throw new InvalidArgumentException("Icon [{$name}] not found in resources/icons/ekasa or resources/svg/lucide.");
    }
}
