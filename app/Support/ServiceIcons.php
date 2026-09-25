<?php

namespace App\Support;

/**
 * Icons selectable for services and hero statistics: the switched-on icons of
 * the admin icon library (App\Support\IconLibrary), rendered by App\Support\Icon.
 */
final class ServiceIcons
{
    /** Icon for a new service (not the handshake: kept in the stock, never a default). */
    public const DEFAULT = 'box';

    /**
     * Key => admin label ("Label ID (key)"), in library order.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return IconLibrary::options();
    }

    public static function has(?string $key): bool
    {
        return IconLibrary::has($key);
    }
}
