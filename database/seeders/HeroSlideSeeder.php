<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Seeder;

class HeroSlideSeeder extends Seeder
{
    use SeedsImages;

    public function run(): void
    {
        // label [en, id], image, alt text [en, id]
        $slides = [
            [['Cold Storage', 'Cold Storage'], 'hero-truck', ['Refrigerated truck on a mountain highway', 'Truk berpendingin di jalan raya pegunungan']],
            [['Linehaul Transport', 'Transportasi Linehaul'], 'hero-linehaul', ['Refrigerated truck on an intercity route', 'Truk berpendingin di jalur antarkota']],
            [['Hyperlocal Fulfilment', 'Hyperlocal Fulfilment'], 'hero-hyperlocal', ['Fast delivery from a city fulfilment hub', 'Pengiriman cepat dari hub fulfilment dalam kota']],
            [['Logistic Partnership', 'Kemitraan Logistik'], 'hero-partnership', ['Logistics partners reviewing a supply chain plan', 'Mitra logistik meninjau rencana rantai pasok']],
        ];

        foreach ($slides as $index => [$label, $image, $alt]) {
            $slide = HeroSlide::query()->where('label->en', $label[0])->first() ?? new HeroSlide;

            $slide->fill([
                'label' => ['en' => $label[0], 'id' => $label[1]],
                'image_alt' => ['en' => $alt[0], 'id' => $alt[1]],
                'sort_order' => $index + 1,
                'is_active' => true,
            ])->markTranslationsReviewed('id')->save();

            $this->attachSeedImage($slide, 'image', $image, 1920, 1080);
        }
    }
}
