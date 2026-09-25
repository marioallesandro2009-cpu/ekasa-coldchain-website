<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    use SeedsImages;

    public function run(): void
    {
        $settings = SiteSetting::current();

        $settings->update([
            'company_name' => 'PT Ekasa Cold Chain',
            'legal_name' => 'PT Ekasa Cold Chain',
            'company_description' => [
                'en' => 'PT Ekasa Cold Chain is a B2B cold chain logistics company in Indonesia.',
                'id' => 'PT Ekasa Cold Chain adalah perusahaan logistik rantai dingin B2B di Indonesia.',
            ],
            'area_served' => 'Indonesia',
            'cta_label' => ['en' => 'Get a Quote', 'id' => 'Minta Penawaran'],
            // Brand tagline. The Indonesian translation must be confirmed by the client.
            'hero_badge' => ['en' => 'Fresher Tomorrow, Further Together', 'id' => 'Segar Hari Esok, Melangkah Bersama'],
            'hero_headline' => ['en' => 'Keep your products fresh with', 'id' => 'Jaga produk Anda tetap segar dengan'],
            'hero_subtext' => [
                'en' => 'Temperature-controlled storage, transport, and fulfilment across Indonesia, keeping every shipment at the right temperature from origin to shelf.',
                'id' => 'Penyimpanan, transportasi, dan fulfilment bersuhu terkendali di seluruh Indonesia, menjaga setiap kiriman pada suhu yang tepat dari gudang asal hingga ke rak toko.',
            ],
            'hero_slide_duration' => 5,
            'mega_menu_title' => ['en' => 'Need a custom solution?', 'id' => 'Butuh solusi khusus?'],
            'mega_menu_link_label' => ['en' => 'Talk to our team', 'id' => 'Hubungi tim kami'],
            // Optional initial value from WHATSAPP_NUMBER in .env (the button is hidden when empty).
            'whatsapp_number' => config('site.whatsapp_number') ?: null,
            'seo_title' => [
                'en' => 'PT Ekasa Cold Chain | Cold Chain Logistics in Indonesia',
                'id' => 'PT Ekasa Cold Chain | Logistik Rantai Dingin di Indonesia',
            ],
            'seo_description' => [
                'en' => 'PT Ekasa Cold Chain handles temperature-controlled warehousing, refrigerated transport and fulfilment for businesses across Indonesia. Request a quote from our team.',
                'id' => 'PT Ekasa Cold Chain melayani pergudangan bersuhu terkendali, transportasi berpendingin, dan fulfilment untuk bisnis di seluruh Indonesia. Minta penawaran dari tim kami.',
            ],
            // Terms kept in English or with a fixed translation when drafting translations.
            'translation_glossary' => [
                ['en' => 'Cold Storage', 'id' => null],
                ['en' => 'Cold Chain', 'id' => null],
                ['en' => 'Linehaul', 'id' => null],
                ['en' => 'Reefer', 'id' => null],
                ['en' => 'Hyperlocal Fulfilment', 'id' => null],
                ['en' => 'Fulfilment', 'id' => null],
                ['en' => 'Chiller', 'id' => null],
                ['en' => 'Frozen', 'id' => null],
                ['en' => 'Omnichannel', 'id' => null],
                ['en' => 'Marketplace', 'id' => null],
                ['en' => 'B2B', 'id' => null],
                ['en' => 'Supply chain', 'id' => 'rantai pasok'],
                ['en' => 'Get a Quote', 'id' => 'Minta Penawaran'],
            ],
        ]);

        $settings->markTranslationsReviewed('id')->save();

        // Client logo (horizontal) and symbol; the white version is derived from the color one.
        $this->attachSeedImage($settings, 'logo_white', 'logo-white', 597, 229);
        $this->attachSeedImage($settings, 'logo_color', 'logo-color', 597, 229);
        $this->attachSeedImage($settings, 'favicon', 'favicon', 512, 512);
        $this->attachSeedImage($settings, 'mega_menu_image', 'menu-services', 800, 600);
        $this->attachSeedImage($settings, 'og_image', 'hero-cold-storage', 1920, 1080);
    }
}
