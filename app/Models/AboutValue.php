<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\ServiceIcons;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A "why choose us" pillar card in the #about section: icon, title, description.
 *
 * @property int $id
 * @property string|null $icon
 * @property array<string, string|null>|null $title
 * @property array<string, string|null>|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['icon', 'title', 'description', 'sort_order', 'is_active', 'translation_status'])]
class AboutValue extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, Sortable;

    public const TRANSLATABLE = ['title', 'description'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'icon' => ServiceIcons::has($this->icon) ? $this->icon : null,
            'title' => (string) $this->translate('title'),
            'description' => $this->translate('description'),
        ];
    }
}
