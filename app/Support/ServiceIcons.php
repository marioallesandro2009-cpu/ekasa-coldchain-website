<?php

namespace App\Support;

/**
 * Icons selectable for services and hero statistics: the original Ekasa icon
 * stock (resources/icons/ekasa/manifest.php), rendered by App\Support\Icon.
 */
final class ServiceIcons
{
    /** Icon for a new service (not the handshake: kept in the stock, never a default). */
    public const DEFAULT = 'box';

    /** @var array<string, string>|null */
    private static ?array $options = null;

    /**
     * Key => admin label ("Label ID (key)"), in manifest order.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        if (self::$options !== null) {
            return self::$options;
        }

        /** @var array{icons: array<string, array{0: array{0: string, 1: string}, 1: string}>} $manifest */
        $manifest = require resource_path('icons/ekasa/manifest.php');
        $options = [];

        foreach ($manifest['icons'] as $key => [$label]) {
            $options[$key] = "{$label[1]} ({$key})";
        }

        return self::$options = $options;
    }

    public static function has(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::options());
    }
}
