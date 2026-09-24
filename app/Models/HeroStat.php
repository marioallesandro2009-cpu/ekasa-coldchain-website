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
 * @property int $id
 * @property string|null $value
 * @property string|null $prefix
 * @property string|null $suffix
 * @property array<string, string|null>|null $text_value
 * @property array<string, string|null>|null $label
 * @property string|null $icon
 * @property bool $use_count_up
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['value', 'prefix', 'suffix', 'text_value', 'label', 'icon', 'use_count_up', 'sort_order', 'is_active', 'translation_status'])]
class HeroStat extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, Sortable;

    public const TRANSLATABLE = ['label', 'text_value'];

    protected function casts(): array
    {
        return [
            'use_count_up' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        $raw = $this->value !== null ? (string) $this->value : null;

        // Drop trailing zeros of the decimal part only ("600.00" -> "600", "99.50" -> "99.5").
        if ($raw !== null && str_contains($raw, '.')) {
            $raw = rtrim(rtrim($raw, '0'), '.');
        }

        $value = $raw !== null ? (float) $raw : null;

        return [
            'id' => $this->id,
            'value' => $value,
            'decimals' => $raw !== null && str_contains($raw, '.') ? strlen(substr($raw, strpos($raw, '.') + 1)) : 0,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'textValue' => $this->translate('text_value'),
            'label' => $this->translate('label'),
            'icon' => $this->icon && array_key_exists($this->icon, ServiceIcons::OPTIONS) ? $this->icon : null,
            // Count up needs a numeric value; otherwise the static text is shown.
            'useCountUp' => $this->use_count_up && $value !== null,
        ];
    }
}
