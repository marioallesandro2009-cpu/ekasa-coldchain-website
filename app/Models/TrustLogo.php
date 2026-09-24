<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Concerns\Sortable;
use App\Support\MediaPresenter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A client logo in the hero trust row ("Trusted by").
 *
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable(['name', 'sort_order', 'is_active'])]
class TrustLogo extends Model implements HasMedia
{
    use FlushesFrontendCache, InteractsWithMedia, RegistersWebpConversions, Sortable;

    /** Logos shown in the trust row, at most. */
    public const MAX_VISIBLE = 5;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile()->acceptsMimeTypes(SiteSetting::LOGO_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Shown 24px high: a 400px wide WebP copy is plenty (SVG stays as it is).
        if ($media?->mime_type !== 'image/svg+xml') {
            $this->addWebpConversion('logo_webp', 400, 'logo');
        }
    }

    /**
     * Logos without a file are skipped on the frontend.
     *
     * @return array<string, mixed>|null
     */
    public function toFrontend(): ?array
    {
        $logo = MediaPresenter::logo($this->getFirstMedia('logo'), 'logo_webp');

        return $logo ? ['name' => $this->name, 'logo' => $logo] : null;
    }
}
