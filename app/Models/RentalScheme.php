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
 * A fleet rental scheme (skema_sewa block). "includes" holds one item per line.
 *
 * @property int $id
 * @property array<string, string|null>|null $name
 * @property array<string, string|null>|null $description
 * @property array<string, string|null>|null $includes
 * @property list<string>|null $durations
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_sample
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['name', 'description', 'includes', 'durations', 'sort_order', 'is_active', 'is_sample', 'translation_status'])]
class RentalScheme extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasSampleFlag, HasTranslations, Sortable;

    public const TRANSLATABLE = ['name', 'description', 'includes'];

    /** key => [en, id] */
    public const DURATIONS = [
        'per_trip' => ['Per trip', 'Per trip'],
        'monthly' => ['Monthly', 'Bulanan'],
        'dedicated' => ['Dedicated', 'Dedicated'],
    ];

    protected function casts(): array
    {
        return [
            'durations' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The "includes" items in a language (one per line).
     *
     * @return list<string>
     */
    public function includeItems(?string $locale = null): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", (string) $this->translate('includes', $locale)))));
    }
}
