<?php

namespace Database\Seeders;

use App\Models\AboutValue;
use Illuminate\Database\Seeder;

class AboutValueSeeder extends Seeder
{
    public function run(): void
    {
        // icon, title [en, id], description [en, id]
        // DUMMY: contoh kartu nilai — ganti lewat admin (Nilai & Keunggulan).
        $values = [
            [
                'shield-check',
                ['Reliable Cold Chain', 'Cold Chain yang Andal'],
                [
                    'Consistent, monitored temperatures from storage to delivery — no gaps, no surprises.',
                    'Suhu yang konsisten dan terpantau dari penyimpanan hingga pengiriman — tanpa celah, tanpa kejutan.',
                ],
            ],
            [
                'reefer-truck',
                ['Latest Fleet', 'Armada Terbaru'],
                [
                    'Newest vehicles and engines, with insulated boxes on a full frame so every product stays protected.',
                    'Kendaraan dan mesin paling baru, dengan boks berrangka dan berinsulasi agar setiap produk tetap aman.',
                ],
            ],
            [
                'thermometer',
                ['Temperature Control Tower', 'Temperature Control Tower'],
                [
                    'Every shipment watched from our control tower, reaching the target temperature faster.',
                    'Setiap pengiriman dipantau dari control tower kami, dengan waktu mencapai suhu target yang lebih cepat.',
                ],
            ],
            [
                'service-24h',
                ['24-Hour Operation', 'Operasional 24 Jam'],
                [
                    'Our team and fleet run around the clock, so your goods keep moving at any hour.',
                    'Tim dan armada kami beroperasi sepanjang waktu, jadi barang Anda terus bergerak kapan saja.',
                ],
            ],
        ];

        foreach ($values as $index => [$icon, $title, $description]) {
            $value = AboutValue::query()->where('title->en', $title[0])->first() ?? new AboutValue;

            $value->fill([
                'icon' => $icon,
                'title' => ['en' => $title[0], 'id' => $title[1]],
                'description' => ['en' => $description[0], 'id' => $description[1]],
                'sort_order' => $index + 1,
                'is_active' => true,
            ])->markTranslationsReviewed('id')->save();
        }
    }
}
