<?php

namespace App\Support;

use App\Models\FleetVehicle;
use App\Models\Location;
use App\Models\PageSection;
use App\Models\RentalScheme;
use App\Models\TransportRoute;
use Closure;
use Illuminate\Support\Number;

/**
 * View data of the special service blocks (zona_suhu, lokasi_gudang,
 * katalog_armada, skema_sewa, diagram_rute, alur_end_to_end). Each returns
 * null when it has nothing to show, so the block is not rendered.
 */
final class ServiceSpecialBlocks
{
    /** Map area when there are no locations: Jabodetabek (lng/lat). */
    private const DEFAULT_AREA = ['west' => 106.45, 'east' => 107.15, 'north' => -5.98, 'south' => -6.55];

    /** Smallest map width in degrees of longitude (about 40 km). */
    private const MIN_SPAN = 0.38;

    /** Faint place names for orientation on the map (lng, lat). */
    private const PLACES = [
        'Jakarta' => [106.827, -6.175],
        'Bekasi' => [106.99, -6.24],
        'Tangerang' => [106.63, -6.18],
        'Depok' => [106.82, -6.40],
        'Bogor' => [106.80, -6.59],
        'Cikarang' => [107.15, -6.28],
    ];

    /**
     * Jakarta Bay coastline, simplified (lng, lat, west to east). Land is south of
     * it. An illustration for the hexagon dot map, not a survey.
     */
    private const COAST = [
        [106.30, -5.99], [106.45, -6.02], [106.55, -6.03], [106.62, -6.02], [106.68, -6.04],
        [106.72, -6.07], [106.76, -6.10], [106.80, -6.11], [106.84, -6.11], [106.88, -6.10],
        [106.92, -6.09], [106.96, -6.08], [107.00, -6.04], [107.03, -5.98], [107.08, -5.94],
        [107.15, -5.97], [107.30, -6.00],
    ];

    /**
     * @param  array<string, mixed>  $data
     * @param  array{eyebrow: string|null, title: string|null, description: string|null}  $heading
     * @param  Closure(mixed): ?string  $text
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>|null
     */
    public static function resolve(string $type, array $data, array $heading, Closure $text, array $settings, string $locale): ?array
    {
        return match ($type) {
            'zona_suhu' => self::zones($data, $heading, $text),
            'lokasi_gudang' => self::locations($data, $heading, $locale),
            'katalog_armada' => self::fleet($data, $heading, $text, $settings, $locale),
            'skema_sewa' => self::schemes($data, $heading, $locale),
            'diagram_rute' => self::routes($data, $heading, $locale),
            'alur_end_to_end' => self::flow($data, $heading, $text),
            default => null,
        };
    }

    /**
     * Temperature zones on one scale (percent positions for the thermometer).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $heading
     * @param  Closure(mixed): ?string  $text
     * @return array<string, mixed>|null
     */
    private static function zones(array $data, array $heading, Closure $text): ?array
    {
        $zones = [];

        foreach ((array) ($data['zones'] ?? []) as $zone) {
            $label = $text($zone['label'] ?? null);

            if ($label === null || ! is_numeric($zone['min'] ?? null) || ! is_numeric($zone['max'] ?? null)) {
                continue;
            }

            $zones[] = [
                'key' => array_key_exists($zone['zone'] ?? '', TemperatureZones::ZONES) ? $zone['zone'] : 'chiller',
                'min' => (int) min($zone['min'], $zone['max']),
                'max' => (int) max($zone['min'], $zone['max']),
                'label' => $label,
                'commodities' => $text($zone['commodities'] ?? null),
            ];
        }

        if ($zones === []) {
            return null;
        }

        // Scale in steps of 5 °C with some room above and below.
        $low = (int) (floor(min(array_column($zones, 'min')) / 5) * 5) - 5;
        $high = (int) (ceil(max(array_column($zones, 'max')) / 5) * 5) + 5;
        $span = max(1, $high - $low);
        $percent = fn (int $value): float => round(($value - $low) / $span * 100, 2);

        foreach ($zones as $i => $zone) {
            $zones[$i]['bottom'] = $percent($zone['min']);
            $zones[$i]['top'] = $percent($zone['max']);
        }

        // Coldest first on the scale (bottom), the cards list them top down.
        usort($zones, fn (array $a, array $b): int => $b['max'] <=> $a['max']);
        $step = $span > 40 ? 10 : 5;
        $ticks = [];

        for ($value = $low; $value <= $high; $value += $step) {
            $ticks[] = ['value' => $value, 'position' => $percent($value)];
        }

        return [...$heading, 'zones' => $zones, 'ticks' => $ticks];
    }

    /**
     * Locations on a hexagon dot map of the area around every active location.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $heading
     * @return array<string, mixed>|null
     */
    private static function locations(array $data, array $heading, string $locale): ?array
    {
        $ids = array_map('intval', (array) ($data['location_ids'] ?? []));
        $kind = in_array($data['kind'] ?? 'all', array_keys(Location::KINDS), true) ? $data['kind'] : null;
        $all = Location::query()->active()->ordered()->with('media')->get();
        $picked = $ids !== []
            ? $all->filter(fn (Location $location): bool => in_array($location->id, $ids, true))->sortBy(fn (Location $location): int => (int) array_search($location->id, $ids, true))
            : $all->when($kind, fn ($collection) => $collection->where('kind', $kind));

        if ($picked->isEmpty()) {
            return null;
        }

        // Area: around every active location with coordinates (with a margin and at
        // least MIN_SPAN wide), else the default region (Jabodetabek).
        $located = $all->filter(fn (Location $location): bool => $location->lat !== null && $location->lng !== null);
        $area = self::DEFAULT_AREA;

        if ($located->isNotEmpty()) {
            $lngs = $located->map(fn (Location $location): float => (float) $location->lng);
            $lats = $located->map(fn (Location $location): float => (float) $location->lat);
            $centerLng = ($lngs->min() + $lngs->max()) / 2;
            $centerLat = ($lats->min() + $lats->max()) / 2;
            $halfLng = max(self::MIN_SPAN, $lngs->max() - $lngs->min() + 0.16) / 2;
            $halfLat = max(self::MIN_SPAN * 0.62, $lats->max() - $lats->min() + 0.14) / 2;
            $area = ['west' => $centerLng - $halfLng, 'east' => $centerLng + $halfLng, 'north' => $centerLat + $halfLat, 'south' => $centerLat - $halfLat];
        }

        $width = 1000;
        $height = (int) round($width * ($area['north'] - $area['south']) / ($area['east'] - $area['west']));
        $project = fn (float $lng, float $lat): array => [
            round(($lng - $area['west']) / ($area['east'] - $area['west']) * $width, 1),
            round(($area['north'] - $lat) / ($area['north'] - $area['south']) * $height, 1),
        ];

        // Land = the area below the coastline.
        $land = ['M0 '.$height];

        foreach (self::COAST as [$lng, $lat]) {
            [$x, $y] = $project($lng, $lat);
            $land[] = 'L'.$x.' '.max(0, min($height, $y));
        }

        $land[] = "L{$width} {$height} Z";

        $zoneLabels = fn (Location $location): array => array_values(array_filter(array_map(
            fn (string $key): ?string => TemperatureZones::label($key, $locale),
            (array) $location->temperature_zones,
        )));

        $items = $picked->values()->map(function (Location $location) use ($project, $width, $height, $zoneLabels, $locale): array {
            $point = $location->lat !== null && $location->lng !== null ? $project((float) $location->lng, (float) $location->lat) : null;

            return [
                'id' => $location->id,
                'name' => $location->name,
                'kind' => __($location->kind === 'hub' ? 'City hub' : 'Warehouse', [], $locale),
                'city' => trim($location->city.($location->area ? ', '.$location->area : '')),
                'address' => $location->address,
                'zones' => $zoneLabels($location),
                'commodities' => $location->translate('commodities', $locale),
                'capacity' => $location->translate('capacity', $locale),
                'hours' => $location->translate('opening_hours', $locale),
                'phone' => $location->phone,
                'lat' => $location->lat !== null ? (float) $location->lat : null,
                'lng' => $location->lng !== null ? (float) $location->lng : null,
                'mapsUrl' => $location->mapsUrl(),
                'photo' => MediaPresenter::image($location->getFirstMedia('photo'), 'lg', 'sm', 1200, 600, $location->name),
                // Marker position on the SVG map (percent of its box).
                'x' => $point ? round($point[0] / $width * 100, 2) : null,
                'y' => $point ? round($point[1] / $height * 100, 2) : null,
            ];
        })->all();

        // Place names inside the area (away from the edges), for orientation.
        $places = [];

        foreach (self::PLACES as $name => [$lng, $lat]) {
            [$x, $y] = $project($lng, $lat);

            if ($x > 60 && $x < $width - 60 && $y > 30 && $y < $height - 20) {
                $places[] = ['name' => $name, 'x' => $x, 'y' => $y];
            }
        }

        return [...$heading, 'items' => $items, 'map' => ['width' => $width, 'height' => $height, 'land' => implode(' ', $land), 'places' => $places]];
    }

    /**
     * Trucks with their specs; the bars are relative to the largest value.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $heading
     * @param  Closure(mixed): ?string  $text
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>|null
     */
    private static function fleet(array $data, array $heading, Closure $text, array $settings, string $locale): ?array
    {
        $ids = array_map('intval', (array) ($data['vehicle_ids'] ?? []));
        $vehicles = FleetVehicle::query()->active()->ordered()->with('media')->get();

        if ($ids !== []) {
            $vehicles = $vehicles->filter(fn (FleetVehicle $vehicle): bool => in_array($vehicle->id, $ids, true))
                ->sortBy(fn (FleetVehicle $vehicle): int => (int) array_search($vehicle->id, $ids, true))->values();
        }

        if ($vehicles->isEmpty()) {
            return null;
        }

        $maxCapacity = max(1, (int) $vehicles->max('capacity_kg'));
        $maxVolume = max(1.0, (float) $vehicles->map(fn (FleetVehicle $vehicle): ?float => $vehicle->volume())->max());
        // Number::format() is false when intl cannot format: plain digits then.
        $number = fn (float $value, int $decimals = 0): string => Number::format($value, $decimals, $decimals, $locale) ?: number_format($value, $decimals);
        $askLabel = $text($data['ask_label'] ?? null) ?? __('Ask about availability', [], $locale);

        $items = $vehicles->map(function (FleetVehicle $vehicle) use ($maxCapacity, $maxVolume, $number, $settings, $locale, $askLabel): array {
            $specs = [];

            if ($vehicle->temp_min !== null && $vehicle->temp_max !== null) {
                $specs[] = ['label' => __('Temperature range', [], $locale), 'value' => "{$vehicle->temp_min} … {$vehicle->temp_max} °C", 'bar' => null];
            }

            if ($vehicle->capacity_kg) {
                $specs[] = ['label' => __('Payload', [], $locale), 'value' => $number($vehicle->capacity_kg).' kg', 'bar' => round($vehicle->capacity_kg / $maxCapacity * 100, 1)];
            }

            if ($volume = $vehicle->volume()) {
                $specs[] = ['label' => __('Box volume', [], $locale), 'value' => $number($volume, 1).' m³', 'bar' => round($volume / $maxVolume * 100, 1)];
            }

            if ($vehicle->box_length !== null && $vehicle->box_width !== null && $vehicle->box_height !== null) {
                $specs[] = ['label' => __('Box (L × W × H)', [], $locale), 'value' => implode(' × ', array_map(fn ($value): string => $number((float) $value, 1), [$vehicle->box_length, $vehicle->box_width, $vehicle->box_height])).' m', 'bar' => null];
            }

            $name = $vehicle->name();
            $message = __('Hello, I would like to ask about the availability of the :truck.', ['truck' => $name], $locale);

            return [
                'id' => $vehicle->id,
                'name' => $name,
                'type' => $vehicle->type,
                'typeLabel' => __($vehicle->type === 'dry' ? 'Dry' : 'Refrigerated', [], $locale),
                'description' => $vehicle->translate('description', $locale),
                'notes' => $vehicle->translate('notes', $locale),
                'specs' => $specs,
                'image' => MediaPresenter::image($vehicle->getFirstMedia('side'), 'lg', 'sm', 1200, 600, $name),
                'focus' => "{$vehicle->focus_x}% {$vehicle->focus_y}%",
                'zoom' => max(100, $vehicle->zoom) / 100,
                'ask' => [
                    'label' => $askLabel,
                    'href' => $settings['whatsappUrl'] ? $settings['whatsappUrl'].'?text='.rawurlencode($message) : Links::section(PageSection::CONTACT),
                    'external' => (bool) $settings['whatsappUrl'],
                ],
            ];
        })->values()->all();

        return [
            ...$heading,
            'layout' => ($data['layout'] ?? 'showcase') === 'grid' ? 'grid' : 'showcase',
            'items' => $items,
            'types' => array_values(array_unique(array_column($items, 'type'))),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $heading
     * @return array<string, mixed>|null
     */
    private static function schemes(array $data, array $heading, string $locale): ?array
    {
        $ids = array_map('intval', (array) ($data['scheme_ids'] ?? []));
        $schemes = RentalScheme::query()->active()->ordered()->get();

        if ($ids !== []) {
            $schemes = $schemes->filter(fn (RentalScheme $scheme): bool => in_array($scheme->id, $ids, true))
                ->sortBy(fn (RentalScheme $scheme): int => (int) array_search($scheme->id, $ids, true));
        }

        $items = $schemes->values()->map(fn (RentalScheme $scheme): array => [
            'name' => (string) $scheme->translate('name', $locale),
            'description' => $scheme->translate('description', $locale),
            'includes' => $scheme->includeItems($locale),
            'durations' => array_values(array_filter(array_map(
                fn (string $key): ?string => RentalScheme::DURATIONS[$key][$locale === 'id' ? 1 : 0] ?? null,
                (array) $scheme->durations,
            ))),
        ])->filter(fn (array $item): bool => $item['name'] !== '')->values()->all();

        return $items === [] ? null : [...$heading, 'items' => $items];
    }

    /**
     * Hub-to-city routes for the transit map diagram.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $heading
     * @return array<string, mixed>|null
     */
    private static function routes(array $data, array $heading, string $locale): ?array
    {
        $ids = array_map('intval', (array) ($data['route_ids'] ?? []));
        $routes = TransportRoute::query()->active()->ordered()->get();

        if ($ids !== []) {
            $routes = $routes->filter(fn (TransportRoute $route): bool => in_array($route->id, $ids, true))
                ->sortBy(fn (TransportRoute $route): int => (int) array_search($route->id, $ids, true));
        }

        $items = $routes->values()->map(fn (TransportRoute $route): array => [
            'destination' => $route->destination,
            'eta' => $route->eta_hours_min
                ? __(':min–:max hours', ['min' => $route->eta_hours_min, 'max' => $route->eta_hours_max ?? $route->eta_hours_min], $locale)
                : null,
        ])->all();

        if ($items === []) {
            return null;
        }

        // Transit map: the hub on the left, one line per city, each city on its own row.
        // The lines leave the hub as a bundle, 8 apart, then turn 45° towards their row;
        // the outermost lines turn first, so the diagonals run side by side, never on top
        // of each other.
        $count = count($items);
        $rowHeight = 64;
        $height = max(2, $count) * $rowHeight;
        $hubY = $height / 2;
        $lines = [];

        foreach ($items as $i => $item) {
            $y = $rowHeight / 2 + $i * $rowHeight;
            $start = $hubY + ($i - ($count - 1) / 2) * 8;
            $rank = $y < $hubY ? $i : $count - 1 - $i;
            $bend = 210 + $rank * 12;
            $lines[] = ['y' => $y, 'path' => sprintf('M120 %s H%s L%s %s H760', $start, $bend, $bend + abs($y - $start), $y)];
        }

        return [...$heading, 'hub' => trim((string) ($data['hub'] ?? '')) ?: 'Jakarta', 'items' => $items, 'diagram' => ['height' => $height, 'hubY' => $hubY, 'lines' => $lines]];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $heading
     * @param  Closure(mixed): ?string  $text
     * @return array<string, mixed>|null
     */
    private static function flow(array $data, array $heading, Closure $text): ?array
    {
        $steps = [];

        foreach ((array) ($data['steps'] ?? []) as $step) {
            $title = $text($step['title'] ?? null);

            if ($title !== null) {
                $steps[] = ['icon' => IconLibrary::has($step['icon'] ?? null) ? $step['icon'] : null, 'title' => $title, 'text' => $text($step['text'] ?? null)];
            }
        }

        return $steps === [] ? null : [...$heading, 'steps' => $steps];
    }
}
