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
            ['label' => ['Delivery Points', 'Titik Pengiriman'], 'icon' => 'map-pin', 'text_value' => ['Multi', 'Multi'], 'use_count_up' => false],
            ['label' => ['Warehouses', 'Gudang'], 'icon' => 'warehouse', 'text_value' => ['Coming soon', 'Segera hadir'], 'use_count_up' => false],
            ['label' => ['Temperature Range', 'Rentang Suhu'], 'icon' => 'thermometer-snowflake', 'text_value' => ['-25°C to 6°C', '-25°C hingga 6°C'], 'note' => ['Customizable to each vendor', 'Dapat disesuaikan dengan vendor'], 'use_count_up' => false],
            ['label' => ['Trusted Brands', 'Klien Terpercaya'], 'icon' => 'badge', 'value' => 20, 'suffix' => '+', 'use_count_up' => true],
        ];

        foreach ($stats as $index => $stat) {
            $model = HeroStat::query()->where('label->en', $stat['label'][0])->first() ?? new HeroStat;

            $model->fill([
                'label' => ['en' => $stat['label'][0], 'id' => $stat['label'][1]],
                'value' => $stat['value'] ?? null,
                'prefix' => null,
                'suffix' => $stat['suffix'] ?? null,
                'note' => isset($stat['note']) ? ['en' => $stat['note'][0], 'id' => $stat['note'][1]] : null,
                'text_value' => isset($stat['text_value']) ? ['en' => $stat['text_value'][0], 'id' => $stat['text_value'][1]] : null,
                'icon' => $stat['icon'],
                'use_count_up' => $stat['use_count_up'],
                'sort_order' => $index + 1,
                'is_active' => true,
            ])->markTranslationsReviewed('id')->save();
        }
    }
}
