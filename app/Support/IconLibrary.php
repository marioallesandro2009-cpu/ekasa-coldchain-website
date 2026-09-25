<?php

namespace App\Support;

use App\Models\LibraryIcon;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

/**
 * The admin icon library (Pustaka Ikon, table library_icons): the Ekasa stock
 * (resources/icons/ekasa/manifest.php, copied in by sync()) plus uploaded SVG
 * icons. Rendering reads a cached key => markup map, so a page costs one cache
 * hit, never a query.
 */
final class IconLibrary
{
    private const CACHE_KEY = 'icons.library.v1';

    /** Drawing elements kept from an uploaded SVG, with the attributes each may keep. */
    private const ELEMENTS = [
        'path' => ['d'],
        'circle' => ['cx', 'cy', 'r'],
        'ellipse' => ['cx', 'cy', 'rx', 'ry'],
        'rect' => ['x', 'y', 'width', 'height', 'rx', 'ry'],
        'line' => ['x1', 'y1', 'x2', 'y2'],
        'polyline' => ['points'],
        'polygon' => ['points'],
    ];

    private const MAX_ELEMENTS = 30;

    /** @var array<string, array{inner: string, strokeWidth: string}>|null */
    private static ?array $map = null;

    /** @var array<string, mixed>|null */
    private static ?array $manifest = null;

    /**
     * @return array{categories: array<string, array{en: string, id: string}>, icons: array<string, array{0: array{0: string, 1: string}, 1: string}>, review: list<string>}
     */
    public static function manifest(): array
    {
        return self::$manifest ??= require resource_path('icons/ekasa/manifest.php');
    }

    /**
     * Category key => Indonesian admin label.
     *
     * @return array<string, string>
     */
    public static function categories(): array
    {
        return array_map(fn (array $label): string => $label['id'], self::manifest()['categories']);
    }

    /**
     * Copy the Ekasa stock into the library. Drawings always follow the files;
     * labels, category and the on/off switch are only set for new rows, so the
     * admin's changes stay. Icons listed under "review" start switched off.
     * Uploaded icons are never touched.
     */
    public static function sync(): void
    {
        $manifest = self::manifest();
        $order = 0;

        foreach ($manifest['icons'] as $key => [$label, $category]) {
            $icon = LibraryIcon::query()->firstOrNew(['key' => $key]);

            if (! $icon->exists) {
                $icon->fill([
                    'label_en' => $label[0],
                    'label_id' => $label[1],
                    'category' => $category,
                    'is_active' => ! in_array($key, $manifest['review'], true),
                ]);
            }

            $icon->fill([
                'svg' => trim((string) file_get_contents(resource_path("icons/ekasa/{$key}.svg"))),
                'source' => LibraryIcon::SOURCE_EKASA,
                'sort_order' => ++$order,
            ])->save();
        }
    }

    /**
     * Rendering data of one library icon, or null (then App\Support\Icon falls
     * back to the files).
     *
     * @return array{inner: string, strokeWidth: string}|null
     */
    public static function find(string $key): ?array
    {
        return self::map()[$key] ?? null;
    }

    /**
     * Any library icon (switched off ones too: content that uses them keeps its
     * icon) or a stock file.
     */
    public static function has(?string $key): bool
    {
        return $key !== null && $key !== ''
            && (isset(self::map()[$key]) || array_key_exists($key, self::manifest()['icons']));
    }

    /**
     * Selectable icons (switched on) as key => "Label ID (key)", in library order.
     * Before the library is filled (migrated but not seeded), the stock manifest.
     *
     * @return array<string, string>
     */
    public static function options(?string $search = null): array
    {
        $options = [];

        foreach (self::rows() as $row) {
            if ($row['active']) {
                $options[$row['key']] = "{$row['label']} ({$row['key']})";
            }
        }

        if (self::rows() === []) {
            foreach (self::manifest()['icons'] as $key => [$label]) {
                if (! in_array($key, self::manifest()['review'], true)) {
                    $options[$key] = "{$label[1]} ({$key})";
                }
            }
        }

        if ($search !== null && trim($search) !== '') {
            $needle = mb_strtolower(trim($search));
            $options = array_filter($options, fn (string $label): bool => str_contains(mb_strtolower($label), $needle));
        }

        return $options;
    }

    /**
     * Options with a small inline preview, for Select::allowHtml().
     *
     * @return array<string, string>
     */
    public static function htmlOptions(?string $search = null): array
    {
        $html = [];

        foreach (self::options($search) as $key => $label) {
            $html[$key] = self::optionHtml($key, $label);
        }

        return $html;
    }

    public static function optionHtml(string $key, ?string $label = null): string
    {
        $label ??= self::options()[$key] ?? $key;

        return '<span style="display:inline-flex;align-items:center;gap:.5rem">'
            .self::svg($key, 'width:1.25rem;height:1.25rem;flex:none;color:var(--primary-600)')
            .'<span>'.e($label).'</span></span>';
    }

    /**
     * Full inline <svg> for the admin (previews), or an empty string.
     */
    public static function svg(string $key, string $style = ''): string
    {
        try {
            $inner = Icon::inner($key);
            $strokeWidth = Icon::strokeWidth($key);
        } catch (InvalidArgumentException) {
            return '';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            .' stroke-width="'.e($strokeWidth).'" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"'
            .($style !== '' ? ' style="'.e($style).'"' : '').'>'.$inner.'</svg>';
    }

    /**
     * Keys an uploaded icon may not use: the Lucide interface icons (menu, x...)
     * would otherwise be replaced across the site.
     *
     * @return list<string>
     */
    public static function reservedKeys(): array
    {
        return array_map(fn (string $file): string => basename($file, '.svg'), glob(resource_path('svg/lucide/*.svg')) ?: []);
    }

    /**
     * Clean an uploaded SVG: only simple drawing elements and their geometry are
     * kept (no scripts, styles, links, text or images); colours become
     * currentColor so the icon follows the site's colours. The drawing must use
     * a 24 x 24 viewBox, like every icon in the library.
     *
     * @throws InvalidArgumentException with an admin-facing (Indonesian) message
     */
    public static function sanitize(string $svg): string
    {
        $svg = trim($svg);

        if ($svg === '' || strlen($svg) > 100_000) {
            throw new InvalidArgumentException('Isi SVG kosong atau terlalu besar (maks. 100 KB).');
        }

        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $svg)) {
            throw new InvalidArgumentException('SVG dengan DOCTYPE/ENTITY tidak diterima.');
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($svg, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->documentElement;

        if (! $loaded || ! $root instanceof DOMElement || $root->localName !== 'svg') {
            throw new InvalidArgumentException('File ini bukan SVG yang valid.');
        }

        $viewBox = preg_split('/[\s,]+/', trim($root->getAttribute('viewBox'))) ?: [];

        if (array_map('floatval', $viewBox) !== [0.0, 0.0, 24.0, 24.0] || count($viewBox) !== 4) {
            throw new InvalidArgumentException('SVG harus memakai viewBox="0 0 24 24" (ukuran ikon standar 24 x 24).');
        }

        $width = $root->getAttribute('stroke-width');
        $strokeWidth = is_numeric($width) && (float) $width >= 0.5 && (float) $width <= 3 ? rtrim(rtrim(number_format((float) $width, 2, '.', ''), '0'), '.') : '1.75';

        $elements = [];
        self::collect($root, $elements);

        if ($elements === []) {
            throw new InvalidArgumentException('SVG tidak berisi bentuk yang bisa dipakai (path, circle, rect, line, polyline, polygon, ellipse).');
        }

        if (count($elements) > self::MAX_ELEMENTS) {
            throw new InvalidArgumentException('SVG terlalu rumit (lebih dari '.self::MAX_ELEMENTS.' bentuk). Ikon sebaiknya sederhana.');
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="'
            .$strokeWidth.'" stroke-linecap="round" stroke-linejoin="round">'.implode('', $elements).'</svg>';
    }

    public static function flush(): void
    {
        self::$map = null;
        Cache::forget(self::CACHE_KEY);
        Icon::flush();
    }

    /**
     * Drawing elements of a node and its <g> groups (a group transform is not
     * supported: such files are rejected rather than drawn wrongly).
     *
     * @param  list<string>  $elements
     */
    private static function collect(DOMElement $node, array &$elements): void
    {
        foreach ($node->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $name = $child->localName;

            if ($name === 'g') {
                if ($child->hasAttribute('transform')) {
                    throw new InvalidArgumentException('SVG memakai transform pada grup; simpan ulang ikon tanpa transform.');
                }

                self::collect($child, $elements);

                continue;
            }

            if (! isset(self::ELEMENTS[$name])) {
                continue; // title, desc, defs, style, script, text, image, use...: dropped
            }

            if ($child->hasAttribute('transform')) {
                throw new InvalidArgumentException('SVG memakai transform; simpan ulang ikon tanpa transform.');
            }

            $attributes = '';

            foreach (self::ELEMENTS[$name] as $attribute) {
                $value = trim($child->getAttribute($attribute));

                if ($value === '') {
                    continue;
                }

                // Geometry only: numbers, path commands, separators.
                if (! preg_match('/^[0-9eE.,+\-\sMmLlHhVvCcSsQqTtAaZz]+$/', $value)) {
                    throw new InvalidArgumentException("Nilai {$attribute} pada <{$name}> tidak valid.");
                }

                $attributes .= ' '.$attribute.'="'.e(preg_replace('/\s+/', ' ', $value)).'"';
            }

            // Solid shapes (fill without stroke) cannot be shown as outline icons.
            if ($child->getAttribute('fill') !== '' && ! in_array($child->getAttribute('fill'), ['none', 'currentColor'], true)
                && in_array($child->getAttribute('stroke'), ['', 'none'], true)) {
                throw new InvalidArgumentException('Ikon berisi bentuk padat (fill). Pustaka ini memakai ikon garis (stroke), seperti ikon Ekasa.');
            }

            if ($attributes !== '') {
                $elements[] = "<{$name}{$attributes}/>";
            }
        }
    }

    /**
     * @return array<string, array{inner: string, strokeWidth: string}>
     */
    private static function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        $map = [];

        foreach (self::rows() as $row) {
            $map[$row['key']] = ['inner' => $row['inner'], 'strokeWidth' => $row['strokeWidth']];
        }

        return self::$map = $map;
    }

    /**
     * Cached library rows (empty before the table exists).
     *
     * @return list<array{key: string, label: string, active: bool, inner: string, strokeWidth: string}>
     */
    private static function rows(): array
    {
        if (! self::tableReady()) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, fn (): array => array_values(LibraryIcon::query()->ordered()->get()
            ->map(fn (LibraryIcon $icon): array => [
                'key' => $icon->key,
                'label' => $icon->label(),
                'active' => $icon->is_active,
                'inner' => preg_match('#<svg[^>]*>(.*)</svg>#s', $icon->svg, $match) ? trim((string) preg_replace('/\s+/', ' ', $match[1])) : '',
                'strokeWidth' => preg_match('#<svg[^>]*\sstroke-width="([\d.]+)"#', $icon->svg, $width) ? $width[1] : '1.75',
            ])->all()));
    }

    private static function tableReady(): bool
    {
        static $ready = null;

        try {
            return $ready ??= Schema::hasTable('library_icons');
        } catch (Throwable) {
            return false;
        }
    }
}
