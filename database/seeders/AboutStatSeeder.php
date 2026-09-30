<?php

namespace Database\Seeders;

use App\Models\AboutStat;
use Illuminate\Database\Seeder;

class AboutStatSeeder extends Seeder
{
    public function run(): void
    {
        // label [en, id], value, suffix, icon
        // DUMMY: contoh angka — ganti lewat admin (Statistik Tentang Kami).
        $stats = [
            [['Years in Operation', 'Tahun Beroperasi'], 12, '+', 'certificate'],
            [['Warehouses Nationwide', 'Gudang di Seluruh Indonesia'], 8, '+', 'warehouse'],
            [['On-Time Delivery Rate', 'Tingkat Pengiriman Tepat Waktu'], 99, '%', 'gauge'],
            [['Businesses Served', 'Bisnis Terlayani'], 500, '+', 'handshake'],
        ];

        foreach ($stats as $index => [$label, $value, $suffix, $icon]) {
            $stat = AboutStat::query()->where('label->en', $label[0])->first() ?? new AboutStat;

            $stat->fill([
                'label' => ['en' => $label[0], 'id' => $label[1]],
                'value' => $value,
                'suffix' => $suffix,
                'icon' => $icon,
                'use_count_up' => true,
                'sort_order' => $index + 1,
                'is_active' => true,
            ])->markTranslationsReviewed('id')->save();
        }
    }
}
