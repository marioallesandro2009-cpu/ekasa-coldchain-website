<?php

namespace App\Support;

/**
 * WCAG 2 contrast checks for the brand colors chosen in the admin.
 */
final class ColorContrast
{
    /** WCAG AA for normal text. */
    public const AA = 4.5;

    /**
     * Text/background pairs the site actually uses: [text, background, description].
     * Keys are SiteSetting::COLOR_DEFAULTS keys, or "white".
     */
    private const PAIRS = [
        ['navy-dark', 'accent', 'Teks tombol CTA di atas warna accent'],
        ['navy-dark', 'accent-hover', 'Teks tombol CTA saat hover (accent hover)'],
        ['white', 'navy', 'Teks putih di navbar dan section navy'],
        ['white', 'navy-dark', 'Teks putih di hero dan footer'],
        ['ice', 'navy', 'Teks ice (eyebrow) di section navy'],
        ['navy', 'surface', 'Teks navy (eyebrow) di latar abu-abu muda'],
        ['ink', 'white', 'Teks utama di latar putih'],
        ['ink', 'surface', 'Teks utama di latar abu-abu muda'],
        ['ink-muted', 'white', 'Teks sekunder di latar putih'],
    ];

    /**
     * Pairs below WCAG AA, with their ratio.
     *
     * @param  array<string, string>  $colors
     * @return list<array{label: string, ratio: float}>
     */
    public static function failures(array $colors): array
    {
        $colors['white'] = '#FFFFFF';
        $failures = [];

        foreach (self::PAIRS as [$text, $background, $label]) {
            $ratio = self::ratio($colors[$text], $colors[$background]);

            if ($ratio < self::AA) {
                $failures[] = ['label' => $label, 'ratio' => round($ratio, 2)];
            }
        }

        return $failures;
    }

    public static function ratio(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    private static function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        $channels = array_map(function (string $pair): float {
            $c = hexdec($pair) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split($hex, 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
