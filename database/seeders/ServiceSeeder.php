<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // slug, title [en, id], short description [en, id] (max. 60 characters), icon
        // DUMMY - daftar dan deskripsi layanan harus dikonfirmasi klien (including the
        // temperature range of Cold Storage).
        $services = [
            ['cold-storage', ['Cold Storage', 'Cold Storage'], ['Chilled and frozen rooms from -25°C to 6°C', 'Ruang chiller dan frozen, -25°C hingga 6°C'], 'snowflake'],
            ['linehaul-transport', ['Linehaul Transport', 'Transportasi Linehaul'], ['Refrigerated and dry trucks between cities', 'Truk berpendingin dan non-pendingin antarkota'], 'reefer-truck'],
            ['hyperlocal-fulfilment', ['Hyperlocal Fulfilment', 'Hyperlocal Fulfilment'], ['Quick drop-offs from hubs inside the city', 'Antar cepat dari hub di dalam kota'], 'map-pin'],
            ['logistic-partnership', ['Logistic Partnership', 'Kemitraan Logistik'], ['One team for storage, transport and delivery', 'Satu tim untuk gudang, transportasi, dan pengiriman'], 'chain-link'],
            ['omnichannel-integration', ['Omnichannel Integration', 'Integrasi Omnichannel'], ['Orders from every sales channel in one flow', 'Pesanan dari semua kanal penjualan dalam satu alur'], 'network'],
        ];

        foreach ($services as $index => [$slug, $title, $description, $icon]) {
            // The Indonesian texts are written by hand: marked as checked ("Sudah dicek").
            Service::query()->updateOrCreate(['slug' => $slug], [
                'title' => ['en' => $title[0], 'id' => $title[1]],
                'short_description' => ['en' => $description[0], 'id' => $description[1]],
                'icon' => $icon,
                'anchor' => $slug,
                // Main grid of the mega menu; Omnichannel sits in the row below.
                'is_featured' => $slug !== 'omnichannel-integration',
                'sort_order' => $index + 1,
                'is_active' => true,
            ])->markTranslationsReviewed('id')->save();
        }
    }
}
