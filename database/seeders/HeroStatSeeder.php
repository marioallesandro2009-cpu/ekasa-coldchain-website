<?php

namespace Database\Seeders;

use App\Models\HeroStat;
use Illuminate\Database\Seeder;

class HeroStatSeeder extends Seeder
{
    /**
     * DUMMY - harus dikonfirmasi klien: all figures (including the temperature
     * range) are examples; replace them from the admin panel.
     */
    public function run(): void
    {
        $stats = [
            ['label' => ['Delivery Points', 'Titik Pengiriman'], 'icon' => 'map-pin', 'value' => 600, 'suffix' => '+', 'use_count_up' => true],
            ['label' => ['Warehouses', 'Gudang'], 'icon' => 'warehouse', 'value' => 15, 'suffix' => '+', 'use_count_up' => true],
            ['label' => ['Temperature Range', 'Rentang Suhu'], 'icon' => 'thermometer-snowflake', 'text_value' => ['-25°C to 6°C', '-25°C hingga 6°C'], 'use_count_up' => false],
            ['label' => ['Trusted Brands', 'Klien Terpercaya'], 'icon' => 'handshake', 'value' => 100, 'suffix' => '+', 'use_count_up' => true],
        ];

        foreach ($stats as $index => $stat) {
            $model = HeroStat::query()->where('label->en', $stat['label'][0])->first() ?? new HeroStat;

            $model->fill([
                'label' => ['en' => $stat['label'][0], 'id' => $stat['label'][1]],
                'value' => $stat['value'] ?? null,
                'prefix' => null,
                'suffix' => $stat['suffix'] ?? null,
                'text_value' => isset($stat['text_value']) ? ['en' => $stat['text_value'][0], 'id' => $stat['text_value'][1]] : null,
                'icon' => $stat['icon'],
                'use_count_up' => $stat['use_count_up'],
                'sort_order' => $index + 1,
                'is_active' => true,
            ])->markTranslationsReviewed('id')->save();
        }
    }
}
