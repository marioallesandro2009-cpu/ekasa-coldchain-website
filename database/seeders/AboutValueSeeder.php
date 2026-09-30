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
                'network',
                ['Nationwide Network', 'Jaringan Nasional'],
                [
                    'Warehouses and city hubs positioned where your customers already are.',
                    'Gudang dan hub kota yang ditempatkan di lokasi yang sudah dekat dengan pelanggan Anda.',
                ],
            ],
            [
                'monitor-pulse',
                ['Real-Time Monitoring', 'Pemantauan Real-Time'],
                [
                    'Every shipment tracked and temperature-logged, so you always know where things stand.',
                    'Setiap pengiriman terlacak dan suhunya tercatat, jadi Anda selalu tahu posisinya.',
                ],
            ],
            [
                'headset',
                ['Customer-First Team', 'Tim yang Mengutamakan Pelanggan'],
                [
                    'A responsive team that treats your deadlines and your product with the same urgency you do.',
                    'Tim yang responsif dan memperlakukan tenggat waktu serta produk Anda dengan urgensi yang sama seperti Anda.',
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
