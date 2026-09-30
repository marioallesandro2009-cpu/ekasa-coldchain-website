<?php

namespace Database\Seeders;

use App\Models\AboutContent;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Seeder;

class AboutContentSeeder extends Seeder
{
    use SeedsImages;

    public function run(): void
    {
        // DUMMY: contoh cerita perusahaan — ganti lewat admin (Cerita Perusahaan).
        $story = [
            'en' => '<p>Founded to solve a simple but costly problem — temperature-sensitive goods spoiling somewhere between the warehouse and the customer — we have spent over a decade building a cold chain network that businesses can rely on.</p><p>From our first single warehouse, we have grown into a network of storage facilities and a refrigerated fleet covering multiple cities, built around one principle: every degree matters, and so does every minute.</p><p>Today, food producers, pharmaceutical distributors and retailers trust us to keep their products safe from the first mile to the last — with the technology and the team to prove it.</p>',
            'id' => '<p>Didirikan untuk menjawab masalah yang sederhana namun berdampak besar — produk sensitif suhu yang rusak di antara gudang dan pelanggan — kami telah lebih dari satu dekade membangun jaringan cold chain yang bisa diandalkan bisnis.</p><p>Dari satu gudang pertama, kami tumbuh menjadi jaringan fasilitas penyimpanan dan armada berpendingin yang mencakup berbagai kota, dibangun di atas satu prinsip: setiap derajat penting, begitu juga setiap menit.</p><p>Kini, produsen makanan, distributor farmasi, dan peritel memercayakan produk mereka aman di tangan kami dari awal hingga akhir perjalanan — didukung teknologi dan tim yang terbukti.</p>',
        ];

        $content = AboutContent::current();
        $content->fill(['story' => $story])->markTranslationsReviewed('id')->save();

        $this->attachSeedImage($content, 'photo', 'about-team', 1200, 1500);
    }
}
