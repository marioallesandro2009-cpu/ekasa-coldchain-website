<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A home page section. The key is the section id (#services, #about, ...) and is
 * fixed in code; the texts, navbar label, order and background come from the admin.
 *
 * @property int $id
 * @property string $key
 * @property array<string, string|null>|null $nav_label
 * @property bool $show_in_nav
 * @property array<string, string|null>|null $eyebrow
 * @property array<string, string|null>|null $title
 * @property array<string, string|null>|null $description
 * @property string $background
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['key', 'nav_label', 'show_in_nav', 'eyebrow', 'title', 'description', 'background', 'sort_order', 'is_active', 'translation_status'])]
class PageSection extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, Sortable;

    public const TRANSLATABLE = ['nav_label', 'eyebrow', 'title', 'description'];

    /** Section that holds the Services mega menu in the navbar. */
    public const SERVICES = 'services';

    /** Section reused for the /locations index page's own SEO title/description. */
    public const LOCATIONS = 'locations';

    /** Target of every "Get a Quote" button. */
    public const CONTACT = 'contact';

    public const BACKGROUNDS = [
        'white' => 'Putih',
        'surface' => 'Abu-abu muda',
        'dark' => 'Gelap',
    ];

    protected function casts(): array
    {
        return [
            'show_in_nav' => 'boolean',
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
            'id' => $this->key,
            'navLabel' => $this->translate('nav_label') ?? $this->translate('eyebrow') ?? (string) $this->translate('title'),
            'showInNav' => $this->show_in_nav,
            'mega' => $this->key === self::SERVICES,
            'eyebrow' => $this->translate('eyebrow'),
            'title' => (string) $this->translate('title'),
            'description' => $this->translate('description'),
            'background' => array_key_exists($this->background, self::BACKGROUNDS) ? $this->background : 'white',
        ];
    }
}
