<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasSampleFlag;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\Locales;
use App\Support\MediaPresenter;
use App\Support\TemperatureZones;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A warehouse or city hub (lokasi_gudang block): position on the hexagon map
 * (lat/lng), temperature zones (App\Support\TemperatureZones keys), contact.
 *
 * @property int $id
 * @property string $name
 * @property string $kind
 * @property string $city
 * @property string|null $area
 * @property string|null $address
 * @property string|null $lat
 * @property string|null $lng
 * @property list<string>|null $temperature_zones
 * @property array<string, string|null>|null $commodities
 * @property array<string, string|null>|null $capacity
 * @property array<string, string|null>|null $opening_hours
 * @property string|null $phone
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_sample
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['name', 'kind', 'city', 'area', 'address', 'lat', 'lng', 'temperature_zones', 'commodities', 'capacity', 'opening_hours', 'phone', 'sort_order', 'is_active', 'is_sample', 'translation_status'])]
class Location extends Model implements HasMedia, HasTranslatableFields
{
    use FlushesFrontendCache, HasSampleFlag, HasTranslations, InteractsWithMedia, RegistersWebpConversions, Sortable;

    public const TRANSLATABLE = ['commodities', 'capacity', 'opening_hours'];

    public const KINDS = ['warehouse' => 'Gudang', 'hub' => 'Hub kota'];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:6',
            'lng' => 'decimal:6',
            'temperature_zones' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile()->acceptsMimeTypes(SiteSetting::IMAGE_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addWebpConversion('lg', 1200, 'photo');
        $this->addWebpConversion('sm', 600, 'photo');
    }

    /**
     * Google Maps link: the coordinates when known, else the address.
     */
    public function mapsUrl(): string
    {
        $query = $this->lat !== null && $this->lng !== null
            ? $this->lat.','.$this->lng
            : trim($this->name.', '.$this->address.', '.$this->city, ', ');

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'kind' => $this->kind,
            'kindLabel' => __($this->kind === 'hub' ? 'City hub' : 'Warehouse'),
            'city' => trim($this->city.($this->area ? ', '.$this->area : '')),
            'address' => $this->address,
            'zones' => array_values(array_filter(array_map(
                fn (string $key): ?string => TemperatureZones::label($key, Locales::current()),
                (array) $this->temperature_zones,
            ))),
            'commodities' => $this->translate('commodities'),
            'capacity' => $this->translate('capacity'),
            // Freeform text (not structured day/time), so it is shown on the card
            // but deliberately left out of LocalBusiness JSON-LD — see
            // StructuredData::locations().
            'hours' => $this->translate('opening_hours'),
            'phone' => $this->phone,
            'lat' => $this->lat !== null ? (float) $this->lat : null,
            'lng' => $this->lng !== null ? (float) $this->lng : null,
            'mapsUrl' => $this->mapsUrl(),
            'photo' => MediaPresenter::image($this->getFirstMedia('photo'), 'lg', 'sm', 1200, 600, $this->name),
        ];
    }
}
