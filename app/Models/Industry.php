<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasSampleFlag;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * An industry served (industri block), with an icon from the icon library.
 *
 * @property int $id
 * @property array<string, string|null>|null $name
 * @property array<string, string|null>|null $description
 * @property string|null $icon
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_sample
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['name', 'description', 'icon', 'sort_order', 'is_active', 'is_sample', 'translation_status'])]
class Industry extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasSampleFlag, HasTranslations, Sortable;

    public const TRANSLATABLE = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
