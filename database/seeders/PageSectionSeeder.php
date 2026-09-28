<?php

namespace Database\Seeders;

use App\Models\PageSection;
use Illuminate\Database\Seeder;

class PageSectionSeeder extends Seeder
{
    public function run(): void
    {
        // key, in the navbar, navbar label [en, id], eyebrow [en, id], title [en, id], background
        $sections = [
            ['services', true, ['Services', 'Layanan'], ['Services', 'Layanan'], ['Our Services', 'Layanan Kami'], 'surface'],
            ['locations', true, ['Locations', 'Lokasi'], ['Locations', 'Lokasi'], ['Our Locations', 'Lokasi Kami'], 'white'],
            ['about', true, ['About Us', 'Tentang Kami'], ['About Us', 'Tentang Kami'], ['About PT Ekasa Cold Chain', 'Tentang PT Ekasa Cold Chain'], 'surface'],
            ['clients', true, ['Clients', 'Klien'], ['Clients', 'Klien'], ['Our Clients', 'Klien Kami'], 'white'],
            [PageSection::CONTACT, true, ['Contact', 'Kontak'], ['Contact', 'Kontak'], ['Talk to Our Team', 'Bicara dengan Tim Kami'], 'dark'],
        ];

        $pair = fn (?array $texts): ?array => $texts ? ['en' => $texts[0], 'id' => $texts[1]] : null;

        foreach ($sections as $index => [$key, $inNav, $navLabel, $eyebrow, $title, $background]) {
            PageSection::query()->updateOrCreate(['key' => $key], [
                'nav_label' => $pair($navLabel),
                'show_in_nav' => $inNav,
                'eyebrow' => $pair($eyebrow),
                'title' => $pair($title),
                // DUMMY: the services/locations text is an example; the remaining
                // sections are placeholders until they are built.
                'description' => match ($key) {
                    PageSection::SERVICES => [
                        'en' => 'Storage, transport and delivery that keep your products at the right temperature, from the first mile to the last.',
                        'id' => 'Penyimpanan, transportasi, dan pengiriman yang menjaga produk Anda di suhu yang tepat, dari awal hingga akhir perjalanan.',
                    ],
                    PageSection::LOCATIONS => [
                        'en' => 'Where our warehouses and city hubs are, and how to reach them.',
                        'id' => 'Lokasi gudang dan hub kota kami, serta cara menghubunginya.',
                    ],
                    default => [
                        'en' => 'This section is a placeholder for the next stage.',
                        'id' => 'Bagian ini akan diisi pada tahap berikutnya.',
                    ],
                },
                'background' => $background,
                'sort_order' => $index + 1,
                'is_active' => true,
            ])->markTranslationsReviewed('id')->save();
        }
    }
}
