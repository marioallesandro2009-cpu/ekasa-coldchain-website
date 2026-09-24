<?php

namespace App\Support;

/**
 * Lucide icon names selectable in the admin. Keep in sync with
 * resources/js/lib/serviceIcons.ts (the frontend only bundles these icons).
 */
final class ServiceIcons
{
    public const OPTIONS = [
        'snowflake' => 'Snowflake',
        'thermometer-snowflake' => 'Thermometer Snowflake',
        'refrigerator' => 'Refrigerator',
        'warehouse' => 'Warehouse',
        'container' => 'Container',
        'truck' => 'Truck',
        'route' => 'Route',
        'map-pin' => 'Map Pin',
        'zap' => 'Zap',
        'package' => 'Package',
        'package-check' => 'Package Check',
        'boxes' => 'Boxes',
        'handshake' => 'Handshake',
        'network' => 'Network',
        'workflow' => 'Workflow',
        'shopping-cart' => 'Shopping Cart',
        'store' => 'Store',
        'globe' => 'Globe',
        'shield-check' => 'Shield Check',
        'clock' => 'Clock',
        'building-2' => 'Building',
        'layers' => 'Layers',
    ];

    public const DEFAULT = 'package';
}
