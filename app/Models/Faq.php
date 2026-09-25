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
 * A question and answer, picked per service in the faq block.
 *
 * @property int $id
 * @property array<string, string|null>|null $question
 * @property array<string, string|null>|null $answer
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_sample
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['question', 'answer', 'sort_order', 'is_active', 'is_sample', 'translation_status'])]
class Faq extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasSampleFlag, HasTranslations, Sortable;

    public const TRANSLATABLE = ['question', 'answer'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
