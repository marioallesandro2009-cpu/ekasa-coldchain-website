<?php

namespace App\Support;

/**
 * WCAG 2 contrast checks for the brand colors chosen in the admin.
 */
final class ColorContrast
{
    /** WCAG AA for normal text. */
    public const AA = 4.5;

    /** Text on the dark CTA (--color-cta-dark-text = neutral 950 in app.css). */
    public const CTA_DARK_TEXT = '#12161E';

    /**
     * Text/background pairs the site actually uses: [text, background, description].
     * Keys are SiteSetting::COLOR_DEFAULTS keys, or one of the derived colors below.
     */
    private const PAIRS = [
        ['cta-dark-text', 'brand-soft', 'Teks tombol CTA di latar gelap (hero, navbar)'],
        ['cta-dark-text', 'cta-dark-hover', 'Teks tombol CTA di latar gelap saat hover'],
        ['white', 'brand', 'Teks tombol CTA di latar terang'],
        ['white', 'cta-light-hover', 'Teks tombol CTA di latar terang saat hover'],
        ['white', 'dark', 'Teks putih di navbar, menu mobile, footer, dan section gelap'],
        ['highlight', 'dark', 'Label layanan di hero, indikator aktif, dan eyebrow di latar gelap'],
        ['brand', 'white', 'Teks hijau (eyebrow, tombol outline) di latar putih'],
        ['brand', 'surface', 'Teks hijau (eyebrow) di latar abu-abu muda'],
        ['ink', 'white', 'Teks utama di latar putih'],
        ['ink', 'surface', 'Teks utama di latar abu-abu muda'],
        ['ink-muted', 'white', 'Teks sekunder di latar putih'],
        ['ink-muted', 'surface', 'Teks sekunder di latar abu-abu muda (mega menu saat disorot)'],
    ];

    /**
     * Pairs below WCAG AA, with their ratio.
     *
     * @param  array<string, string>  $colors
     * @return list<array{label: string, ratio: float}>
     */
    public static function failures(array $colors): array
    {
        // Same mixes as --color-cta-*-hover in app.css (color-mix in srgb, 80%).
        $colors += [
            'white' => '#FFFFFF',
            'cta-dark-text' => self::CTA_DARK_TEXT,
            'cta-dark-hover' => self::mix($colors['brand-soft'], '#FFFFFF', 0.8),
            'cta-light-hover' => self::mix($colors['brand'], '#000000', 0.8),
        ];
        $failures = [];

        foreach (self::PAIRS as [$text, $background, $label]) {
            $ratio = self::ratio($colors[$text], $colors[$background]);

            if ($ratio < self::AA) {
                $failures[] = ['label' => $label, 'ratio' => round($ratio, 2)];
            }
        }

        return $failures;
    }

    /**
     * sRGB mix: $weight of $a, the rest $b (like CSS color-mix in srgb).
     */
    public static function mix(string $a, string $b, float $weight): string
    {
        $channels = array_map(
            fn (string $x, string $y): string => sprintf('%02X', (int) round(hexdec($x) * $weight + hexdec($y) * (1 - $weight))),
            str_split(ltrim($a, '#'), 2),
            str_split(ltrim($b, '#'), 2),
        );

        return '#'.implode('', $channels);
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
