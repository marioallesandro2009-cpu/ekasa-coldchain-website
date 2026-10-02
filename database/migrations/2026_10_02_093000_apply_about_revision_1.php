<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Revisi 1 (klien): tambah paragraf kualitas/teknologi/armada/24 jam di cerita perusahaan dan
     * ganti tiga kartu nilai contoh. Hanya baris yang masih berisi teks contoh yang diubah.
     */
    public function up(): void
    {
        $json = fn (array $v): string => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        foreach ([
            'Nationwide Network' => [
                'icon' => 'reefer-truck',
                'title' => [
                    'en' => 'Latest Fleet',
                    'id' => 'Armada Terbaru',
                ],
                'description' => [
                    'en' => 'Newest vehicles and engines, with insulated boxes on a full frame so every product stays protected.',
                    'id' => 'Kendaraan dan mesin paling baru, dengan boks berrangka dan berinsulasi agar setiap produk tetap aman.',
                ],
            ],
            'Real-Time Monitoring' => [
                'icon' => 'thermometer',
                'title' => [
                    'en' => 'Temperature Control Tower',
                    'id' => 'Temperature Control Tower',
                ],
                'description' => [
                    'en' => 'Every shipment watched from our control tower, reaching the target temperature faster.',
                    'id' => 'Setiap pengiriman dipantau dari control tower kami, dengan waktu mencapai suhu target yang lebih cepat.',
                ],
            ],
            'Customer-First Team' => [
                'icon' => 'service-24h',
                'title' => [
                    'en' => '24-Hour Operation',
                    'id' => 'Operasional 24 Jam',
                ],
                'description' => [
                    'en' => 'Our team and fleet run around the clock, so your goods keep moving at any hour.',
                    'id' => 'Tim dan armada kami beroperasi sepanjang waktu, jadi barang Anda terus bergerak kapan saja.',
                ],
            ],
        ] as $old => $new) {
            DB::table('about_values')->where('title->en', $old)->update([
                'icon' => $new['icon'],
                'title' => $json($new['title']),
                'description' => $json($new['description']),
            ]);
        }

        $extra = ['en' => '<p>We put quality first, backed by the most up-to-date logistics technology and fleet: a temperature control tower, boxes built on a full frame so products stay safe, the newest vehicles and engines, and the fastest time to reach the target temperature. We operate 24 hours.</p>', 'id' => '<p>Kami mengutamakan kualitas dengan teknologi logistik dan armada paling mutakhir: temperature control tower, boks berrangka agar produk pasti aman, kendaraan dan mesin terbaru, serta waktu mencapai suhu target tercepat. Kami beroperasi 24 jam.</p>'];
        $row = DB::table('about_contents')->first();

        if ($row && $row->story) {
            $story = json_decode($row->story, true) ?: [];

            if (! str_contains($story['en'] ?? '', 'control tower')) {
                foreach ($extra as $locale => $html) {
                    $story[$locale] = ($story[$locale] ?? '').$html;
                }

                DB::table('about_contents')->where('id', $row->id)->update(['story' => $json($story)]);
            }
        }
    }

    public function down(): void
    {
        // Teks bisa diubah dari admin; tidak dikembalikan otomatis.
    }
};
