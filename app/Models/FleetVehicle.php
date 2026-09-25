<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasSampleFlag;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A truck of the fleet (katalog_armada block, Linehaul card photo). The side
 * view is the client's file and is never edited: focus_x/focus_y/zoom only set
 * how it is cropped on screen (CSS object-position and scale, percent).
 *
 * @property int $id
 * @property string $model
 * @property string|null $brand
 * @property string $type
 * @property array<string, string|null>|null $description
 * @property int|null $temp_min
 * @property int|null $temp_max
 * @property int|null $capacity_kg
 * @property string|null $box_length
 * @property string|null $box_width
 * @property string|null $box_height
 * @property string|null $volume_m3
 * @property array<string, string|null>|null $notes
 * @property int $focus_x
 * @property int $focus_y
 * @property int $zoom
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_sample
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['model', 'brand', 'type', 'description', 'temp_min', 'temp_max', 'capacity_kg', 'box_length', 'box_width', 'box_height', 'volume_m3', 'notes', 'focus_x', 'focus_y', 'zoom', 'sort_order', 'is_active', 'is_sample', 'translation_status'])]
class FleetVehicle extends Model implements HasMedia, HasTranslatableFields
{
    use FlushesFrontendCache, HasSampleFlag, HasTranslations, InteractsWithMedia, RegistersWebpConversions, Sortable;

    public const TRANSLATABLE = ['description', 'notes'];

    public const TYPES = ['reefer' => 'Reefer (berpendingin)', 'dry' => 'Dry (tanpa pendingin)'];

    /** Side views narrower than this look soft on large screens: the admin warns. */
    public const MIN_SIDE_WIDTH = 1600;

    protected function casts(): array
    {
        return [
            'temp_min' => 'integer',
            'temp_max' => 'integer',
            'capacity_kg' => 'integer',
            'box_length' => 'decimal:2',
            'box_width' => 'decimal:2',
            'box_height' => 'decimal:2',
            'volume_m3' => 'decimal:2',
            'focus_x' => 'integer',
            'focus_y' => 'integer',
            'zoom' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('side')->singleFile()->acceptsMimeTypes(SiteSetting::LOGO_MIMES);
        $this->addMediaCollection('photos')->acceptsMimeTypes(SiteSetting::IMAGE_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addWebpConversion('lg', 1200, ['side', 'photos']);
        $this->addWebpConversion('sm', 600, ['side', 'photos']);
    }

    /**
     * Volume from the box dimensions when it is not filled in.
     */
    public function volume(): ?float
    {
        if ($this->volume_m3 !== null) {
            return (float) $this->volume_m3;
        }

        if ($this->box_length === null || $this->box_width === null || $this->box_height === null) {
            return null;
        }

        return round((float) $this->box_length * (float) $this->box_width * (float) $this->box_height, 1);
    }

    /**
     * Width of the uploaded side view (stored on upload), for the admin warning.
     */
    public function sideWidth(): ?int
    {
        $width = $this->getFirstMedia('side')?->getCustomProperty('width');

        return is_numeric($width) ? (int) $width : null;
    }

    public function name(): string
    {
        return trim($this->brand.' '.$this->model);
    }
}
