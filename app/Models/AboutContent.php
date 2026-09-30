<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\MediaPresenter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Singleton: the #about section's story and photo. Edited via the Filament
 * page App\Filament\Pages\ManageAboutContent (like SiteSetting::current()).
 *
 * @property int $id
 * @property array<string, string|null>|null $story
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['story', 'translation_status'])]
class AboutContent extends Model implements HasMedia, HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, InteractsWithMedia, RegistersWebpConversions;

    public const TRANSLATABLE = ['story'];

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile()->acceptsMimeTypes(SiteSetting::IMAGE_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Portrait crop (4:5) for the section's split layout.
        $this->addWebpConversion('lg', 1200, 'photo');
        $this->addWebpConversion('sm', 600, 'photo');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function photo(): ?array
    {
        return MediaPresenter::image($this->getFirstMedia('photo'), 'lg', 'sm', 1200, 1500, (string) $this->translate('story'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return [
            'story' => $this->translate('story'),
            'photo' => $this->photo(),
        ];
    }
}
