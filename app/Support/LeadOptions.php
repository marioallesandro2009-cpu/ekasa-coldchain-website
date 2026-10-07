<?php

namespace App\Support;

/**
 * The fixed choices of the shipment questions in the contact form (Kontak): goods category and
 * container size. The keys are what is stored; the labels follow the visitor's language. For the team
 * (email, Excel, Google Sheet) the Indonesian label is used.
 */
class LeadOptions
{
    /**
     * @return array<string, string> key => English label (translated through lang/id.json)
     */
    public static function categories(): array
    {
        return [
            'seafood' => 'Fish & seafood',
            'meat' => 'Meat & poultry',
            'produce' => 'Fruit & vegetables',
            'dairy' => 'Dairy & eggs',
            'frozen_food' => 'Frozen & processed food',
            'pharma' => 'Pharmaceuticals & vaccines',
            'other' => 'Other',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function containers(): array
    {
        return [
            '20ft' => '20 feet',
            '40ft' => '40 feet',
        ];
    }

    public static function categoryLabel(?string $key, ?string $locale = 'id'): ?string
    {
        $english = self::categories()[(string) $key] ?? null;

        return $english === null ? null : (string) __($english, [], $locale);
    }

    public static function containerLabel(?string $key, ?string $locale = 'id'): ?string
    {
        $english = self::containers()[(string) $key] ?? null;

        return $english === null ? null : (string) __($english, [], $locale);
    }

    /**
     * "2,5" and "2.5" both mean two and a half: the visitor may type a decimal comma.
     */
    public static function parseTons(mixed $value): ?float
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        $text = str_replace(',', '.', $text);

        return is_numeric($text) ? round((float) $text, 2) : null;
    }
}
