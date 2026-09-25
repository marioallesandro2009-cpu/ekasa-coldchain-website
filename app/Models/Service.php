<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\Locales;
use App\Support\ServiceIcons;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property array<string, string|null>|null $title
 * @property string $slug
 * @property array<string, string|null>|null $short_description
 * @property string|null $icon
 * @property string|null $anchor
 * @property bool $is_featured
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['title', 'slug', 'short_description', 'icon', 'anchor', 'is_featured', 'sort_order', 'is_active', 'translation_status'])]
class Service extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, Sortable;

    public const TRANSLATABLE = ['title', 'short_description'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $service): void {
            $service->slug = $service->slug ?: Str::slug((string) $service->translate('title', Locales::default()));
        });
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
        ];
    }
}
