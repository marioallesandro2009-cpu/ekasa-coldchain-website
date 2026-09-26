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
use App\Support\ServiceIcons;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A service: mega menu entry, home page card (Utama = is_featured, Pendukung
 * otherwise) and a detail page built from blocks (Filament Builder, see
 * App\Support\ServiceBlocks). "slug" is the internal key; the URL uses "slugs"
 * (one per language).
 *
 * @property int $id
 * @property array<string, string|null>|null $title
 * @property string $slug
 * @property array<string, string|null>|null $slugs
 * @property array<string, string|null>|null $short_description
 * @property array<string, string|null>|null $intro
 * @property array<string, string|null>|null $key_points
 * @property array<string, string|null>|null $seo_title
 * @property array<string, string|null>|null $seo_description
 * @property string $detail_status
 * @property list<array{type: string, data: array<string, mixed>}>|null $blocks
 * @property int|null $card_fleet_vehicle_id
 * @property int $card_focus_x
 * @property int $card_focus_y
 * @property bool $card_photo_light
 * @property string|null $icon
 * @property string|null $anchor
 * @property bool $is_featured
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_sample
 * @property array<string, array<string, string>>|null $translation_status
 * @property-read FleetVehicle|null $cardFleetVehicle
 */
#[Fillable(['title', 'slug', 'slugs', 'short_description', 'intro', 'key_points', 'seo_title', 'seo_description', 'detail_status', 'blocks', 'card_fleet_vehicle_id', 'card_focus_x', 'card_focus_y', 'card_photo_light', 'icon', 'anchor', 'is_featured', 'sort_order', 'is_active', 'is_sample', 'translation_status'])]
class Service extends Model implements HasMedia, HasTranslatableFields
{
    use FlushesFrontendCache, HasSampleFlag, HasTranslations, InteractsWithMedia, RegistersWebpConversions, Sortable;

    public const TRANSLATABLE = ['title', 'short_description', 'intro', 'key_points', 'seo_title', 'seo_description'];

    public const DRAFT = 'draft';

    public const PUBLISHED = 'published';

    public const STATUSES = [self::DRAFT => 'Draf (halaman detail belum tampil)', self::PUBLISHED => 'Terbit'];

    public const MAX_KEY_POINTS = 3;

    protected function casts(): array
    {
        return [
            'slugs' => 'array',
            'blocks' => 'array',
            'card_focus_x' => 'integer',
            'card_focus_y' => 'integer',
            'card_photo_light' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $service): void {
            $service->slug = $service->slug ?: Str::slug((string) $service->translate('title', Locales::default()));

            // A missing URL slug comes from the title in that language (else the English one).
            $slugs = (array) $service->slugs;

            foreach (Locales::all() as $locale) {
                if (blank($slugs[$locale] ?? null)) {
                    $slugs[$locale] = Str::slug((string) ($service->translation('title', $locale) ?? $service->translate('title', Locales::default()))) ?: $service->slug;
                }
            }

            $service->slugs = $slugs;
        });
    }

    /**
     * @return BelongsTo<FleetVehicle, $this>
     */
    public function cardFleetVehicle(): BelongsTo
    {
        return $this->belongsTo(FleetVehicle::class, 'card_fleet_vehicle_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('hero')->singleFile()->acceptsMimeTypes(SiteSetting::IMAGE_MIMES);
        $this->addMediaCollection('card')->singleFile()->acceptsMimeTypes(SiteSetting::IMAGE_MIMES);
        $this->addMediaCollection('gallery')->acceptsMimeTypes(SiteSetting::IMAGE_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Detail page hero: full width, like the home page slides.
        $this->addWebpConversion('hero_lg', 1920, 'hero');
        $this->addWebpConversion('hero_sm', 800, 'hero');
        $this->addWebpConversion('lg', 1200, ['card', 'gallery']);
        $this->addWebpConversion('sm', 600, ['card', 'gallery']);
    }

    public function slugFor(?string $locale = null): string
    {
        $locale ??= Locales::current();

        return (string) ($this->slugs[$locale] ?? $this->slugs[Locales::default()] ?? $this->slug);
    }

    public function isPublished(): bool
    {
        return $this->detail_status === self::PUBLISHED;
    }

    /**
     * Key points (one per line in the admin), at most three.
     *
     * @return list<string>
     */
    public function keyPoints(?string $locale = null): array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", (string) $this->translate('key_points', $locale)))));

        return array_slice($lines, 0, self::MAX_KEY_POINTS);
    }

    /**
     * Path of the detail page in a language: /services/{slug}, /id/layanan/{slug}.
     */
    public function detailPath(?string $locale = null): string
    {
        $locale ??= Locales::current();
        $segment = (string) config("site.locales.{$locale}.segments.services", 'services');

        return Locales::path($locale, "/{$segment}/".$this->slugFor($locale));
    }

    /**
     * Path of the services index page in a language: /services, /id/layanan.
     */
    public static function indexPath(?string $locale = null): string
    {
        $locale ??= Locales::current();
        $segment = (string) config("site.locales.{$locale}.segments.services", 'services');

        return Locales::path($locale, "/{$segment}");
    }

    /**
     * Card photo (4:3): the service's card photo, or the side view of a fleet truck
     * cropped on screen around the focus point (the file itself is never edited).
     *
     * @return array<string, mixed>|null
     */
    public function cardImage(): ?array
    {
        $alt = (string) $this->translate('title');
        $vehicle = $this->cardFleetVehicle;

        $image = $vehicle
            ? MediaPresenter::image($vehicle->getFirstMedia('side'), 'lg', 'sm', 1200, 600, $alt)
            : MediaPresenter::image($this->getFirstMedia('card'), 'lg', 'sm', 1200, 600, $alt);

        if (! $image) {
            return null;
        }

        return [...$image, 'focus' => $vehicle ? "{$this->card_focus_x}% {$this->card_focus_y}%" : '50% 50%'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->translate('title'),
            'description' => $this->translate('short_description'),
            'icon' => ServiceIcons::has($this->icon) ? $this->icon : ServiceIcons::DEFAULT,
            'anchor' => ltrim((string) ($this->anchor ?: $this->slug), '#'),
            'featured' => $this->is_featured,
            'keyPoints' => $this->keyPoints(),
            'image' => $this->cardImage(),
            // Whether the card photo is bright/busy: the /services index card's
            // giant ordinal number needs dark ink there instead of pale white.
            'photoLight' => $this->card_photo_light,
            // Detail page link once it is published (and the page route exists).
            'url' => $this->isPublished() && Route::has('services.show') ? $this->detailPath() : null,
        ];
    }
}
