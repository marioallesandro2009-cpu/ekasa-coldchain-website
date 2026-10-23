<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Industry;
use App\Models\Location;
use App\Models\RentalScheme;
use App\Models\TransportRoute;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Seeder;

/**
 * Shared data of the service detail pages. DUMMY: every value below is a
 * plausible example for a cold chain company, written for this project, and is
 * marked "Contoh" in the admin (is_sample) until the client replaces it.
 */
class ServiceDataSeeder extends Seeder
{
    use SeedsImages;

    public function run(): void
    {
        $this->fleet();
        $this->locations();
        $this->rentalSchemes();
        $this->routes();
        $this->industries();
        $this->faqs();
    }

    private function fleet(): void
    {
        $this->call(FleetVehicleSeeder::class);
    }

    private function locations(): void
    {
        // Revisi 1: titik kantor Ekasa (Meruya). alamat dari klien; titik peta diperkirakan dari OpenStreetMap, geser dari admin bila kurang pas.
        // Pool: sengaja belum ada isinya.
        Location::query()->updateOrCreate(['name' => 'Kantor Pusat'], [
            'kind' => 'office',
            'city' => 'Jakarta Barat',
            'area' => 'Meruya Utara, Kembangan',
            'address' => 'Jl. Penyelesaian Tomang III Jl. Kav. DKI No.1 Blk 10A, RT.8/RW.10, Meruya Utara, Kec. Kembangan, Kota Jakarta Barat, DKI Jakarta 11620',
            'lat' => -6.1998,
            'lng' => 106.7287,
            'temperature_zones' => [],
            'opening_hours' => ['en' => '8am - 6pm', 'id' => '08.00 - 18.00'],
            'sort_order' => 0,
            'is_active' => true,
            'is_sample' => false,
        ])->markTranslationsReviewed('id')->save();
    }

    private function rentalSchemes(): void
    {
        // DUMMY scheme contents.
        $schemes = [
            [['Unit only', 'Unit saja'], ['The truck for your own driver and route.', 'Truk untuk sopir dan rute Anda sendiri.'],
                ["Refrigerated truck ready to run\nRegular servicing\nTemperature logger", "Truk berpendingin siap jalan\nServis berkala\nPencatat suhu"], ['monthly', 'dedicated']],
            [['Unit + driver', 'Unit + sopir'], ['The truck with a trained cold chain driver.', 'Truk beserta sopir yang terlatih menangani rantai dingin.'],
                ["Everything in Unit only\nTrained driver\nTrip reports", "Semua yang ada di Unit saja\nSopir terlatih\nLaporan perjalanan"], ['per_trip', 'monthly', 'dedicated']],
            [['All-in', 'All-in'], ['Truck, driver, fuel and tolls in one price.', 'Truk, sopir, bahan bakar, dan tol dalam satu harga.'],
                ["Everything in Unit + driver\nFuel and tolls\nReplacement truck if needed", "Semua yang ada di Unit + sopir\nBahan bakar dan tol\nTruk pengganti bila perlu"], ['per_trip', 'monthly', 'dedicated']],
        ];

        foreach ($schemes as $index => [$name, $description, $includes, $durations]) {
            $scheme = RentalScheme::query()->firstOrNew(['name->en' => $name[0]]);
            $scheme->fill([
                'name' => ['en' => $name[0], 'id' => $name[1]],
                'description' => ['en' => $description[0], 'id' => $description[1]],
                'includes' => ['en' => $includes[0], 'id' => $includes[1]],
                'durations' => $durations,
                'sort_order' => $index + 1,
                'is_active' => true,
                'is_sample' => true,
            ])->markTranslationsReviewed('id')->save();
        }
    }

    private function routes(): void
    {
        // DUMMY travel times from Jakarta.
        $routes = [['Bandung', 3, 5], ['Cirebon', 4, 6], ['Bandar Lampung', 7, 10], ['Semarang', 7, 10], ['Yogyakarta', 9, 12], ['Surabaya', 12, 16]];

        foreach ($routes as $index => [$destination, $min, $max]) {
            TransportRoute::query()->updateOrCreate(['origin' => 'Jakarta', 'destination' => $destination], [
                'eta_hours_min' => $min,
                'eta_hours_max' => $max,
                'sort_order' => $index + 1,
                'is_active' => true,
                'is_sample' => true,
            ]);
        }
    }

    private function industries(): void
    {
        // DUMMY list. Icons from the approved batch 1 (batch 2 awaits approval).
        $industries = [
            [['Food & beverage', 'Makanan & minuman'], ['Packaged food and drinks that must stay cool.', 'Makanan dan minuman kemasan yang harus tetap dingin.'], 'box'],
            [['Meat & poultry', 'Daging & unggas'], ['Frozen and chilled cuts from plant to store.', 'Potongan beku dan dingin dari pabrik ke toko.'], 'snowflake'],
            [['Seafood', 'Hasil laut'], ['Fish and shellfish kept frozen end to end.', 'Ikan dan kerang tetap beku dari awal sampai akhir.'], 'thermometer-snowflake'],
            [['Dairy', 'Produk susu'], ['Milk, cheese and yoghurt at chiller temperature.', 'Susu, keju, dan yoghurt di suhu chiller.'], 'cold-cabinet'],
            [['Fresh produce', 'Buah & sayur'], ['Fruit and vegetables at the right cool range.', 'Buah dan sayur di rentang suhu sejuk yang tepat.'], 'thermometer'],
            [['Pharma & health', 'Farmasi & kesehatan'], ['Temperature-sensitive health products with records.', 'Produk kesehatan peka suhu, lengkap dengan catatan suhu.'], 'shield-check'],
        ];

        foreach ($industries as $index => [$name, $description, $icon]) {
            $industry = Industry::query()->firstOrNew(['name->en' => $name[0]]);
            $industry->fill([
                'name' => ['en' => $name[0], 'id' => $name[1]],
                'description' => ['en' => $description[0], 'id' => $description[1]],
                'icon' => $icon,
                'sort_order' => $index + 1,
                'is_active' => true,
                'is_sample' => true,
            ])->markTranslationsReviewed('id')->save();
        }
    }

    private function faqs(): void
    {
        // DUMMY questions and answers (the numbers are examples).
        $faqs = [
            [['What temperature ranges do you offer?', 'Rentang suhu apa saja yang tersedia?'],
                ['Frozen rooms from -25 to -18 °C, chiller rooms from 0 to 6 °C and cool rooms from 8 to 15 °C.', 'Ruang frozen -25 s/d -18 °C, ruang chiller 0 s/d 6 °C, dan ruang sejuk 8 s/d 15 °C.']],
            [['How is the temperature monitored?', 'Bagaimana suhu dipantau?'],
                ['Every room and truck has a temperature logger. You get the records with each delivery or on request.', 'Setiap ruang dan truk memakai pencatat suhu. Catatannya Anda terima di setiap pengiriman atau saat diminta.']],
            [['Is there a minimum volume?', 'Apakah ada volume minimum?'],
                ['No. We store from a single pallet; larger volumes get a monthly rate.', 'Tidak. Kami menyimpan mulai dari satu palet; volume besar mendapat tarif bulanan.']],
            [['Which cities do your trucks serve?', 'Kota mana saja yang dilayani truk Anda?'],
                ['Regular routes run from Jakarta to cities in Java and southern Sumatra. Other routes are planned on request.', 'Rute rutin berangkat dari Jakarta ke kota-kota di Jawa dan Sumatra bagian selatan. Rute lain bisa diatur sesuai permintaan.']],
            [['Can I rent a truck with a driver?', 'Apakah bisa sewa truk beserta sopir?'],
                ['Yes. Choose Unit + driver or All-in, per trip, monthly or dedicated.', 'Bisa. Pilih Unit + sopir atau All-in, per trip, bulanan, atau dedicated.']],
            [['How fast are city deliveries?', 'Seberapa cepat pengiriman dalam kota?'],
                ['Orders from a city hub are usually delivered the same day.', 'Pesanan dari hub kota umumnya sampai di hari yang sama.']],
            [['What does a partnership include?', 'Apa saja yang termasuk dalam kemitraan?'],
                ['Storage, linehaul and last-mile delivery planned by one team, with one contact and one report.', 'Penyimpanan, linehaul, dan pengiriman akhir yang direncanakan satu tim, dengan satu kontak dan satu laporan.']],
            [['How do I get a quote?', 'Bagaimana cara meminta penawaran?'],
                ['Use the Get a Quote button or chat with us on WhatsApp. Tell us the goods, volume and route.', 'Klik tombol Minta Penawaran atau hubungi kami lewat WhatsApp. Sebutkan jenis barang, volume, dan rutenya.']],
        ];

        foreach ($faqs as $index => [$question, $answer]) {
            $faq = Faq::query()->firstOrNew(['question->en' => $question[0]]);
            $faq->fill([
                'question' => ['en' => $question[0], 'id' => $question[1]],
                'answer' => ['en' => $answer[0], 'id' => $answer[1]],
                'sort_order' => $index + 1,
                'is_active' => true,
                'is_sample' => true,
            ])->markTranslationsReviewed('id')->save();
        }
    }
}
