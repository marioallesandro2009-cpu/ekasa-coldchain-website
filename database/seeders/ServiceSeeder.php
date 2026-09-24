<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // slug, title [en, id], short description [en, id], icon
        $services = [
            // DUMMY - harus dikonfirmasi klien: temperature range.
            ['cold-storage', ['Cold Storage', 'Cold Storage'], ['Chiller & frozen storage, -25°C to 6°C', 'Penyimpanan chiller & frozen, -25°C hingga 6°C'], 'snowflake'],
            ['linehaul-transport', ['Linehaul Transport', 'Transportasi Linehaul'], ['Reefer & dry fleet for rent', 'Sewa armada reefer & dry'], 'truck'],
            ['hyperlocal-fulfilment', ['Hyperlocal Fulfilment', 'Hyperlocal Fulfilment'], ['Fast delivery from city hubs', 'Pengiriman cepat dari hub dalam kota'], 'map-pin'],
            ['logistic-partnership', ['Logistic Partnership', 'Kemitraan Logistik'], ['End-to-end supply chain solutions', 'Solusi rantai pasok menyeluruh'], 'handshake'],
            ['omnichannel-integration', ['Omnichannel Integration', 'Integrasi Omnichannel'], ['Connected to major marketplaces', 'Terhubung ke marketplace besar'], 'network'],
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
