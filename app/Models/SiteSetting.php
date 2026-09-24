<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\Locales;
use App\Support\MediaPresenter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Singleton model: the site always has exactly one settings row.
 *
 * @property int $id
 * @property string $company_name
 * @property string|null $legal_name
 * @property array<string, string|null>|null $company_description
 * @property string $area_served
 * @property string|null $whatsapp_number
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $instagram_url
 * @property string|null $linkedin_url
 * @property array<string, string|null>|null $cta_label
 * @property array<string, string|null>|null $hero_badge
 * @property array<string, string|null>|null $hero_headline
 * @property array<string, string|null>|null $hero_subtext
 * @property int $hero_slide_duration
 * @property array<string, string|null>|null $mega_menu_title
 * @property array<string, string|null>|null $mega_menu_link_label
 * @property array<string, array<string, string|null>>|null $ui_texts
 * @property array<string, string|null>|null $colors
 * @property array<string, string|null>|null $seo_title
 * @property array<string, string|null>|null $seo_description
 * @property array<string, array<string, string>>|null $translation_status
 * @property list<array{en?: string|null, id?: string|null}>|null $translation_glossary
 */
#[Fillable([
    'company_name',
    'legal_name',
    'company_description',
    'area_served',
    'whatsapp_number',
    'email',
    'phone',
    'instagram_url',
    'linkedin_url',
    'cta_label',
    'hero_badge',
    'hero_headline',
    'hero_subtext',
    'hero_slide_duration',
    'mega_menu_title',
    'mega_menu_link_label',
    'ui_texts',
    'colors',
    'seo_title',
    'seo_description',
    'translation_status',
    'translation_glossary',
])]
class SiteSetting extends Model implements HasMedia, HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, InteractsWithMedia, RegistersWebpConversions;

    public const TRANSLATABLE = [
        'company_description',
        'cta_label',
        'hero_badge',
        'hero_headline',
        'hero_subtext',
        'mega_menu_title',
        'mega_menu_link_label',
        'seo_title',
        'seo_description',
    ];

    /**
     * Defaults for a new (empty) settings row; the seeder fills in the real texts.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'cta_label' => '{"en":"Get a Quote"}',
        'hero_badge' => '{"en":"Temperature-Controlled Logistics"}',
        'hero_headline' => '{"en":"Keep your products fresh with"}',
        'mega_menu_title' => '{"en":"Need a custom solution?"}',
        'mega_menu_link_label' => '{"en":"Talk to our team"}',
        'seo_title' => '{"en":"Company Name"}',
    ];

    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public const LOGO_MIMES = ['image/png', 'image/svg+xml', 'image/webp', 'image/jpeg'];

    public const FAVICON_MIMES = ['image/png', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'];

    /**
     * Brand colors (hex), editable in the admin. Each key overrides the CSS
     * token --color-<key> from resources/css/app.css at runtime.
     */
    public const COLOR_DEFAULTS = [
        'navy' => '#0F2A4A',
        'navy-light' => '#153A63',
        'navy-dark' => '#0A1D33',
        'ice' => '#4FB3E8',
        'accent' => '#F26A21',
        'accent-hover' => '#F47C3C',
        'surface' => '#F5F8FB',
        'ink' => '#1A2B3C',
        'ink-muted' => '#5B6B7B',
    ];

    public const HEX_PATTERN = '/^#[0-9A-Fa-f]{6}$/';

    /**
     * Button labels and screen reader labels, editable in the admin per language
     * (ui_texts: {"en": {key: text}, "id": {key: text}}). The value here is the
     * English source string; its translation in lang/<locale>.json is the default
     * for an empty field.
     */
    public const UI_TEXT_DEFAULTS = [
        'hero_secondary_cta' => 'Explore Services',
        'whatsapp_button' => 'Chat on WhatsApp',
        'trust_label' => 'Trusted by',
        'footer_copyright' => 'All rights reserved.',
        'skip_link' => 'Skip to content',
        'menu_open' => 'Open menu',
        'menu_close' => 'Close menu',
        'menu_dialog' => 'Menu',
        'nav_main' => 'Main',
        'nav_mobile' => 'Mobile',
        'language' => 'Language',
        'slides_group' => 'Choose a service',
        'slide_show' => 'Show',
    ];

    protected function casts(): array
    {
        return [
            'hero_slide_duration' => 'integer',
            'ui_texts' => 'array',
            'colors' => 'array',
            'translation_glossary' => 'json:unicode',
        ];
    }

    public static function current(): self
    {
        $settings = static::query()->oldest('id')->first();

        if (! $settings) {
            static::query()->create([]);
            $settings = static::query()->oldest('id')->firstOrFail();
        }

        return $settings;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo_white')->singleFile()->acceptsMimeTypes(self::LOGO_MIMES);
        $this->addMediaCollection('logo_color')->singleFile()->acceptsMimeTypes(self::LOGO_MIMES);
        $this->addMediaCollection('favicon')->singleFile()->acceptsMimeTypes(self::FAVICON_MIMES);
        $this->addMediaCollection('mega_menu_image')->singleFile()->acceptsMimeTypes(self::IMAGE_MIMES);
        $this->addMediaCollection('og_image')->singleFile()->acceptsMimeTypes(self::IMAGE_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addWebpConversion('menu_lg', 1200, 'mega_menu_image');
        $this->addWebpConversion('menu_sm', 600, 'mega_menu_image');

        // Logos keep their original PNG/SVG; a WebP copy is added for raster logos only.
        if ($media?->mime_type !== 'image/svg+xml') {
            $this->addWebpConversion('logo_webp', 600, ['logo_white', 'logo_color']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        $favicon = $this->getFirstMedia('favicon');
        $ogImage = $this->getFirstMedia('og_image');

        return [
            'companyName' => $this->company_name,
            // Registered name for the copyright and JSON-LD; falls back to the brand name.
            'legalName' => $this->legal_name ?: $this->company_name,
            'companyDescription' => $this->translate('company_description'),
            'areaServed' => $this->area_served,
            'texts' => $this->uiTexts(Locales::current()),
            'colors' => $this->brandColors(),
            'logoWhite' => MediaPresenter::logo($this->getFirstMedia('logo_white'), 'logo_webp'),
            'logoColor' => MediaPresenter::logo($this->getFirstMedia('logo_color'), 'logo_webp'),
            'favicon' => $favicon ? ['url' => $favicon->getUrl(), 'mime' => $favicon->mime_type] : null,
            'whatsappUrl' => self::whatsappUrl($this->whatsapp_number),
            'email' => $this->email,
            'phone' => $this->phone,
            'sameAs' => array_values(array_filter([$this->instagram_url, $this->linkedin_url])),
            'ctaLabel' => (string) $this->translate('cta_label'),
            'heroBadge' => (string) $this->translate('hero_badge'),
            'heroHeadline' => (string) $this->translate('hero_headline'),
            'heroSubtext' => $this->translate('hero_subtext'),
            'heroSlideDuration' => max(2, (int) $this->hero_slide_duration),
            'megaMenu' => [
                'image' => MediaPresenter::image(
                    $this->getFirstMedia('mega_menu_image'), 'menu_lg', 'menu_sm', 1200, 600, (string) $this->translate('mega_menu_title'),
                ),
                'title' => (string) $this->translate('mega_menu_title'),
                'linkLabel' => (string) $this->translate('mega_menu_link_label'),
            ],
            'seo' => [
                'title' => (string) $this->translate('seo_title'),
                'description' => $this->translate('seo_description') ?? $this->translate('hero_subtext'),
                // Original file: social networks do not all accept WebP.
                'image' => $ogImage ? MediaPresenter::original($ogImage) : null,
            ],
        ];
    }

    /**
     * UI texts in one language: the admin text, else the default from lang/<locale>.json.
     *
     * @return array<string, string>
     */
    public function uiTexts(?string $locale = null): array
    {
        $locale ??= Locales::default();
        $texts = [];

        foreach (array_keys(self::UI_TEXT_DEFAULTS) as $key) {
            $value = trim((string) ($this->ui_texts[$locale][$key] ?? ''));
            $texts[$key] = $value !== '' ? $value : self::defaultUiText($key, $locale);
        }

        return $texts;
    }

    /**
     * Glossary for drafting translations: rows with an English term and its fixed
     * Indonesian translation (empty = keep the English term).
     *
     * @return list<array{en: string, id: string|null}>
     */
    public function glossary(): array
    {
        $rows = [];

        foreach ((array) $this->translation_glossary as $row) {
            $en = trim((string) ($row['en'] ?? ''));

            if ($en !== '') {
                $id = trim((string) ($row['id'] ?? ''));
                $rows[] = ['en' => $en, 'id' => $id !== '' ? $id : null];
            }
        }

        return $rows;
    }

    public static function defaultUiText(string $key, string $locale): string
    {
        return (string) __(self::UI_TEXT_DEFAULTS[$key], [], $locale);
    }

    /**
     * Brand colors as uppercase "#RRGGBB"; missing or invalid values use the default.
     *
     * @return array<string, string>
     */
    public function brandColors(): array
    {
        $colors = [];

        foreach (self::COLOR_DEFAULTS as $key => $default) {
            $value = trim((string) ($this->colors[$key] ?? ''));
            $colors[$key] = preg_match(self::HEX_PATTERN, $value) ? strtoupper($value) : $default;
        }

        return $colors;
    }

    /**
     * wa.me link from a local ("0812...") or international ("+62 812...") number.
     */
    public static function whatsappUrl(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?: '';

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits !== '' ? 'https://wa.me/'.$digits : null;
    }
}
