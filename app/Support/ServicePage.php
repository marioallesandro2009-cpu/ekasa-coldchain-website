<?php

namespace App\Support;

use App\Models\Faq;
use App\Models\Industry;
use App\Models\PageSection;
use App\Models\Service;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * View data of a service detail page in one language: the resolved blocks
 * (hidden and empty blocks dropped), SEO values and JSON-LD. Block texts use the
 * Indonesian version only when the block is marked "Versi ID sudah dicek".
 * The special blocks are resolved by App\Support\ServiceSpecialBlocks.
 */
final class ServicePage
{
    /** Block types rendered by resources/views/components/blocks/{type}.blade.php. */
    public const RENDERED = ['hero_layanan', 'pita_spesifikasi', 'keunggulan', 'teks_gambar', 'cara_kerja', 'industri', 'galeri', 'faq', 'cta_penutup', 'zona_suhu', 'lokasi_gudang', 'katalog_armada', 'skema_sewa', 'diagram_rute', 'alur_end_to_end'];

    /** Blocks on a dark band; the others alternate white and light grey. */
    private const DARK = ['pita_spesifikasi', 'cta_penutup'];

    /**
     * @param  array<string, mixed>  $settings  FrontendData settings in this language
     * @return array{service: array<string, mixed>, blocks: list<array<string, mixed>>, faqs: list<array{question: string, answer: string}>, paths: array<string, string>}
     */
    public static function build(Service $service, array $settings, string $locale): array
    {
        $service->loadMissing('media');
        $blocks = [];
        $faqs = [];
        $anchors = [];
        $light = 0;

        $sourceBlocks = (array) $service->blocks;
        $hasHero = collect($sourceBlocks)->contains(fn (array $block): bool => $block['type'] === 'hero_layanan' && ! ($block['data']['hidden'] ?? false));

        // Every page needs its title (h1): a default hero when the admin has none.
        if (! $hasHero) {
            array_unshift($sourceBlocks, ServiceBlocks::make('hero_layanan', ['show_key_points' => true]));
        }

        foreach ($sourceBlocks as $block) {
            $type = $block['type'];
            $data = $block['data'];

            if (($data['hidden'] ?? false) || ! in_array($type, self::RENDERED, true)) {
                continue;
            }

            $resolved = self::resolve($type, $data, $service, $settings, $locale);

            if ($resolved === null) {
                continue;
            }

            // Section id: the admin anchor, else the block type, unique on the page.
            $anchor = Str::slug((string) ($data['anchor'] ?? '')) ?: str_replace('_', '-', $type);
            $id = $anchor;

            for ($n = 2; in_array($id, $anchors, true); $n++) {
                $id = "{$anchor}-{$n}";
            }

            $anchors[] = $id;

            if ($type === 'faq') {
                $faqs = [...$faqs, ...self::faqItems($data, $locale)];
            }

            $blocks[] = [
                'type' => $type,
                'id' => $id,
                'variant' => $type === 'hero_layanan' ? 'dark' : (in_array($type, self::DARK, true) ? 'dark' : ($light++ % 2 === 0 ? 'white' : 'surface')),
                ...$resolved,
            ];
        }

        // "Scroll down" target of the hero: the first block after it.
        foreach ($blocks as $i => $block) {
            if ($block['type'] === 'hero_layanan') {
                $blocks[$i]['nextId'] = $blocks[$i + 1]['id'] ?? null;
            }
        }

        $paths = [];

        foreach (Locales::all() as $pathLocale) {
            $paths[$pathLocale] = $service->detailPath($pathLocale);
        }

        $hero = MediaPresenter::image($service->getFirstMedia('hero'), 'hero_lg', 'hero_sm', 1920, 800, (string) $service->translate('title', $locale));

        return [
            'service' => [
                'id' => $service->id,
                'title' => (string) $service->translate('title', $locale),
                'description' => $service->translate('short_description', $locale),
                'intro' => $service->translate('intro', $locale),
                'seoTitle' => $service->translate('seo_title', $locale),
                'seoDescription' => $service->translate('seo_description', $locale),
                'icon' => ServiceIcons::has($service->icon) ? $service->icon : ServiceIcons::DEFAULT,
                'heroImage' => $hero,
                'updatedAt' => $service->updated_at?->toAtomString(),
            ],
            'blocks' => $blocks,
            'faqs' => $faqs,
            'paths' => $paths,
        ];
    }

    /**
     * Data of one block, or null when it has nothing to show.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>|null
     */
    private static function resolve(string $type, array $data, Service $service, array $settings, string $locale): ?array
    {
        $useId = $locale !== Locales::default() && (bool) ($data['id_reviewed'] ?? false);
        $text = function (mixed $value) use ($useId, $locale): ?string {
            if (! is_array($value)) {
                return null;
            }

            $chosen = trim((string) ($useId ? ($value[$locale] ?? '') : ''));
            $chosen = $chosen !== '' ? $chosen : trim((string) ($value[Locales::default()] ?? ''));

            return $chosen !== '' ? $chosen : null;
        };
        $lines = fn (mixed $value): array => array_values(array_filter(array_map('trim', explode("\n", (string) $text($value)))));
        $heading = [
            'eyebrow' => $text($data['eyebrow'] ?? null),
            'title' => $text($data['title'] ?? null),
            'description' => $text($data['description'] ?? null),
        ];
        $button = fn (?string $label, string $target): array => [
            'label' => $label ?? (string) $settings['ctaLabel'],
            'href' => $target === 'whatsapp' && $settings['whatsappUrl'] ? $settings['whatsappUrl'] : Links::section(PageSection::CONTACT),
            'external' => $target === 'whatsapp' && (bool) $settings['whatsappUrl'],
        ];

        switch ($type) {
            case 'hero_layanan':
                return [
                    ...$heading,
                    'title' => $heading['title'] ?? (string) $service->translate('title', $locale),
                    'description' => $heading['description'] ?? $service->translate('intro', $locale),
                    'keyPoints' => ($data['show_key_points'] ?? true) ? $service->keyPoints($locale) : [],
                    'image' => MediaPresenter::image($service->getFirstMedia('hero'), 'hero_lg', 'hero_sm', 1920, 800, (string) $service->translate('title', $locale)),
                    'cta' => $button($text($data['cta_label'] ?? null), (string) ($data['cta_target'] ?? 'contact')),
                    'secondaryLabel' => $text($data['secondary_label'] ?? null),
                ];

            case 'pita_spesifikasi':
                $items = [];

                foreach ((array) ($data['items'] ?? []) as $item) {
                    $label = $text($item['label'] ?? null);

                    if (! is_numeric($item['value'] ?? null) || $label === null) {
                        continue;
                    }

                    $raw = rtrim(rtrim((string) $item['value'], '0'), '.');
                    $raw = str_contains((string) $item['value'], '.') ? $raw : (string) $item['value'];
                    $decimals = str_contains($raw, '.') ? strlen(substr($raw, strpos($raw, '.') + 1)) : 0;
                    $value = (float) $raw;

                    $items[] = [
                        'value' => $value,
                        'decimals' => $decimals,
                        'formatted' => Number::format($value, $decimals, $decimals, $locale),
                        'prefix' => (string) ($item['prefix'] ?? ''),
                        // A unit word gets a space ("4.000 kg"); symbols stay attached ("-25°C", "24/7").
                        'suffix' => preg_match('/^\p{L}/u', $suffix = trim((string) ($item['suffix'] ?? ''))) ? ' '.$suffix : $suffix,
                        'label' => $label,
                        // Counting up from 0 only reads well for positive numbers.
                        'countUp' => (bool) ($item['count_up'] ?? false) && $value > 0,
                    ];
                }

                return $items === [] ? null : [...$heading, 'items' => $items];

            case 'keunggulan':
                $items = [];

                foreach ((array) ($data['items'] ?? []) as $item) {
                    $title = $text($item['title'] ?? null);

                    if ($title !== null) {
                        $items[] = ['icon' => IconLibrary::has($item['icon'] ?? null) ? $item['icon'] : null, 'title' => $title, 'text' => $text($item['text'] ?? null)];
                    }
                }

                return $items === [] ? null : [...$heading, 'items' => $items];

            case 'cara_kerja':
                $steps = [];

                foreach ((array) ($data['steps'] ?? []) as $step) {
                    $title = $text($step['title'] ?? null);

                    if ($title !== null) {
                        $steps[] = ['title' => $title, 'text' => $text($step['text'] ?? null)];
                    }
                }

                return $steps === [] ? null : [...$heading, 'steps' => $steps];

            case 'teks_gambar':
                $image = self::galleryImage($service, $data['image'] ?? null);
                $body = $text($data['text'] ?? null);

                if ($body === null && $image === null) {
                    return null;
                }

                return [
                    ...$heading,
                    'text' => $body,
                    'points' => $lines($data['points'] ?? null),
                    'image' => $image,
                    'imageLeft' => ($data['image_position'] ?? 'right') === 'left',
                ];

            case 'industri':
                $ids = array_map('intval', (array) ($data['industry_ids'] ?? []));
                $industries = Industry::query()->active()->when($ids !== [], fn ($query) => $query->whereKey($ids))->ordered()->get();

                if ($ids !== []) {
                    $industries = $industries->sortBy(fn (Industry $industry): int => (int) array_search($industry->id, $ids, true));
                }

                $items = $industries->map(fn (Industry $industry): array => [
                    'icon' => IconLibrary::has($industry->icon) ? $industry->icon : null,
                    'name' => (string) $industry->translate('name', $locale),
                    'description' => $industry->translate('description', $locale),
                ])->values()->all();

                return $items === [] ? null : [...$heading, 'items' => $items];

            case 'galeri':
                $uuids = array_values(array_filter((array) ($data['images'] ?? [])));
                $media = $service->getMedia('gallery');

                if ($uuids !== []) {
                    $media = $media->filter(fn (Media $item): bool => in_array($item->uuid, $uuids, true))
                        ->sortBy(fn (Media $item): int => (int) array_search($item->uuid, $uuids, true));
                }

                $images = $media->map(fn (Media $item): ?array => MediaPresenter::image($item, 'lg', 'sm', 1200, 600, (string) $service->translate('title', $locale)))
                    ->filter()->values()->all();

                return $images === [] ? null : [...$heading, 'images' => $images];

            case 'faq':
                $items = self::faqItems($data, $locale);

                return $items === [] ? null : [...$heading, 'items' => $items];

            case 'cta_penutup':
                if ($heading['title'] === null) {
                    return null;
                }

                return [...$heading, 'cta' => $button($text($data['button_label'] ?? null), (string) ($data['target'] ?? 'contact'))];
        }

        return ServiceSpecialBlocks::resolve($type, $data, $heading, $text, $settings, $locale);
    }

    /**
     * The questions picked in a faq block, in the picked order (active ones only).
     *
     * @param  array<string, mixed>  $data
     * @return list<array{question: string, answer: string}>
     */
    private static function faqItems(array $data, string $locale): array
    {
        $ids = array_map('intval', (array) ($data['faq_ids'] ?? []));

        if ($ids === []) {
            return [];
        }

        $items = [];

        foreach (Faq::query()->active()->whereKey($ids)->get()->sortBy(fn (Faq $faq): int => (int) array_search($faq->id, $ids, true)) as $faq) {
            $question = (string) $faq->translate('question', $locale);
            $answer = (string) $faq->translate('answer', $locale);

            if ($question !== '' && $answer !== '') {
                $items[] = ['question' => $question, 'answer' => $answer];
            }
        }

        return $items;
    }

    /**
     * A gallery photo of the service by media uuid.
     *
     * @return array<string, mixed>|null
     */
    private static function galleryImage(Service $service, mixed $uuid): ?array
    {
        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        $media = $service->getMedia('gallery')->first(fn (Media $item): bool => $item->uuid === $uuid);

        return MediaPresenter::image($media, 'lg', 'sm', 1200, 600, (string) $service->translate('title'));
    }
}
