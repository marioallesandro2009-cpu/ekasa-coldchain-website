<?php

namespace App\Support;

/**
 * Temperature zones a location can offer (locations.temperature_zones) and the
 * defaults of the "zona_suhu" block. DUMMY ranges: to be confirmed by the client.
 */
final class TemperatureZones
{
    /** key => [label [en, id], min °C, max °C] */
    public const ZONES = [
        'frozen' => [['Frozen', 'Beku (frozen)'], -25, -18],
        'chiller' => [['Chiller', 'Dingin (chiller)'], 0, 6],
        'cool' => [['Cool', 'Sejuk (cool)'], 8, 15],
        'ambient' => [['Ambient', 'Suhu ruang (ambient)'], 16, 25],
    ];

    /**
     * Key => admin label ("Beku (frozen), -25 s/d -18 °C").
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::ZONES as $key => [$label, $min, $max]) {
            $options[$key] = "{$label[1]}, {$min} s/d {$max} °C";
        }

        return $options;
    }

    public static function label(string $key, string $locale): ?string
    {
        $zone = self::ZONES[$key] ?? null;

        return $zone ? $zone[0][$locale === 'id' ? 1 : 0] : null;
    }
}
