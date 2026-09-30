<?php

namespace Database\Seeders;

use App\Models\Post;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    use SeedsImages;

    public function run(): void
    {
        // slug, cover image, published X days ago, title [en, id], excerpt [en, id], content [en, id]
        // DUMMY: contoh artikel untuk mengisi #blog — ganti dengan tulisan asli lewat admin.
        $posts = [
            [
                'cold-chain-101',
                'blog-cold-chain',
                14,
                ['What Is Cold Chain Logistics? A Quick Guide', 'Apa Itu Logistik Cold Chain? Panduan Singkat'],
                [
                    'The basics of keeping temperature-sensitive goods safe from warehouse to doorstep.',
                    'Dasar-dasar menjaga produk yang sensitif suhu tetap aman dari gudang hingga ke tangan pelanggan.',
                ],
                [
                    '<p>Cold chain logistics is the unbroken series of storage and transport steps that keep a product within a safe temperature range, from the moment it leaves the factory until it reaches the end customer.</p><h2>Why it matters</h2><p>A single break in the chain — a warehouse that runs too warm, a truck without working refrigeration — can spoil an entire shipment. For food, pharmaceuticals and other perishables, that is a cost and a safety risk at once.</p><h2>The three pillars</h2><ul><li>Temperature-controlled storage</li><li>Refrigerated transport between cities</li><li>Fast, careful last-mile delivery</li></ul>',
                    '<p>Logistik cold chain adalah rangkaian penyimpanan dan transportasi tanpa putus yang menjaga produk tetap berada pada rentang suhu aman, sejak keluar dari pabrik hingga sampai ke pelanggan akhir.</p><h2>Kenapa ini penting</h2><p>Satu saja mata rantai yang putus — gudang yang terlalu hangat, truk tanpa pendingin yang berfungsi — bisa merusak seluruh pengiriman. Untuk makanan, obat-obatan, dan produk mudah rusak lainnya, ini adalah risiko biaya sekaligus keamanan.</p><h2>Tiga pilar utama</h2><ul><li>Penyimpanan bersuhu terkendali</li><li>Transportasi berpendingin antarkota</li><li>Pengiriman mil terakhir yang cepat dan hati-hati</li></ul>',
                ],
            ],
            [
                'choosing-right-warehouse-temperature',
                'blog-warehouse-temp',
                9,
                ['Choosing the Right Storage Temperature for Your Products', 'Memilih Suhu Penyimpanan yang Tepat untuk Produk Anda'],
                [
                    'Chilled, frozen or dry? A short guide to matching your product with the right zone.',
                    'Chiller, frozen, atau kering? Panduan singkat mencocokkan produk dengan zona yang tepat.',
                ],
                [
                    '<p>Not every product needs the same cold. Picking the wrong zone either wastes energy (and money) or risks spoilage.</p><h2>Frozen (-25°C to -18°C)</h2><p>Best for products meant to be stored for months: frozen seafood, ice cream, and other long-shelf-life goods.</p><h2>Chilled (0°C to 6°C)</h2><p>Fresh produce, dairy and most ready-to-eat items live here — cold enough to slow spoilage without freezing texture.</p><h2>Dry (ambient)</h2><p>Packaging, dry goods and anything shelf-stable at room temperature, kept separate to save cold storage space for what actually needs it.</p>',
                    '<p>Tidak semua produk butuh suhu dingin yang sama. Memilih zona yang salah bisa memboroskan energi (dan biaya) atau berisiko produk rusak.</p><h2>Frozen (-25°C hingga -18°C)</h2><p>Cocok untuk produk yang disimpan berbulan-bulan: makanan laut beku, es krim, dan barang dengan umur simpan panjang.</p><h2>Chiller (0°C hingga 6°C)</h2><p>Produk segar, susu, dan sebagian besar makanan siap saji berada di sini — cukup dingin untuk memperlambat kerusakan tanpa membekukan teksturnya.</p><h2>Kering (suhu ruang)</h2><p>Kemasan, barang kering, dan apa pun yang stabil di suhu ruang, dipisahkan agar ruang cold storage tersedia untuk yang benar-benar membutuhkannya.</p>',
                ],
            ],
            [
                'last-mile-delivery-tips',
                'blog-last-mile',
                5,
                ['5 Ways to Keep Last-Mile Deliveries On Time', '5 Cara Menjaga Pengiriman Mil Terakhir Tepat Waktu'],
                [
                    'The last leg of the journey is often the hardest to control — here is what actually helps.',
                    'Etape terakhir perjalanan sering kali paling sulit dikendalikan — berikut yang benar-benar membantu.',
                ],
                [
                    '<p>Last-mile delivery is where most delays happen — traffic, wrong addresses, narrow city streets. A few habits make a real difference.</p><h2>1. Plan routes around traffic patterns</h2><p>Not just the shortest route — the fastest one at that time of day.</p><h2>2. Keep hubs close to demand</h2><p>City hubs cut the distance (and time) between "order placed" and "order delivered".</p><h2>3. Confirm addresses before dispatch</h2><p>A quick check avoids a wasted trip.</p><h2>4. Track in real time</h2><p>Both the driver and the customer should know where the delivery is.</p><h2>5. Build in a buffer</h2><p>Unexpected delays happen — a small buffer keeps the promised window realistic.</p>',
                    '<p>Pengiriman mil terakhir adalah tempat sebagian besar keterlambatan terjadi — macet, alamat salah, jalan kota yang sempit. Beberapa kebiasaan berikut benar-benar membantu.</p><h2>1. Rencanakan rute sesuai pola lalu lintas</h2><p>Bukan sekadar rute terpendek — tapi yang tercepat pada jam tersebut.</p><h2>2. Jaga hub tetap dekat dengan permintaan</h2><p>Hub kota memperpendek jarak (dan waktu) antara "pesanan dibuat" dan "pesanan sampai".</p><h2>3. Konfirmasi alamat sebelum berangkat</h2><p>Pengecekan singkat menghindari perjalanan sia-sia.</p><h2>4. Lacak secara real-time</h2><p>Baik pengemudi maupun pelanggan perlu tahu posisi pengiriman.</p><h2>5. Sediakan waktu cadangan</h2><p>Keterlambatan tak terduga bisa terjadi — cadangan waktu kecil menjaga jendela pengiriman tetap realistis.</p>',
                ],
            ],
            [
                'reducing-food-waste-cold-storage',
                'blog-food-waste',
                2,
                ['How Proper Cold Storage Reduces Food Waste', 'Bagaimana Cold Storage yang Tepat Mengurangi Limbah Makanan'],
                [
                    'A well-run cold chain does not just protect quality — it protects the bottom line too.',
                    'Cold chain yang dikelola dengan baik tidak hanya menjaga kualitas — tapi juga menjaga biaya.',
                ],
                [
                    '<p>Food waste is not just a sustainability issue — for most businesses, it is a direct hit to margins. A well-run cold chain is one of the most effective ways to reduce it.</p><h2>Where waste actually happens</h2><p>Most spoilage happens in the gaps: a truck idling too long before loading, a warehouse door left open, a delivery delayed past a product\'s safe window.</p><h2>What helps</h2><ul><li>Consistent temperature monitoring, not spot checks</li><li>Faster handoffs between storage and transport</li><li>FIFO (first in, first out) stock rotation</li></ul><p>None of this is complicated — it just has to be consistent.</p>',
                    '<p>Limbah makanan bukan sekadar isu keberlanjutan — bagi kebanyakan bisnis, ini langsung memengaruhi margin. Cold chain yang dikelola dengan baik adalah salah satu cara paling efektif untuk menguranginya.</p><h2>Di mana limbah sebenarnya terjadi</h2><p>Sebagian besar kerusakan terjadi di celah-celah: truk yang terlalu lama menunggu sebelum dimuat, pintu gudang yang terbuka lama, pengiriman yang tertunda melewati batas aman produk.</p><h2>Yang membantu</h2><ul><li>Pemantauan suhu yang konsisten, bukan sekadar cek sesekali</li><li>Serah terima yang lebih cepat antara penyimpanan dan transportasi</li><li>Rotasi stok FIFO (first in, first out)</li></ul><p>Semua ini tidak rumit — hanya perlu dilakukan secara konsisten.</p>',
                ],
            ],
            [
                'choosing-3pl-partner',
                'blog-3pl-partner',
                0,
                ['What to Look for in a 3PL Logistics Partner', 'Yang Perlu Diperhatikan Saat Memilih Mitra Logistik 3PL'],
                [
                    'Outsourcing logistics is a big decision. Here is what separates a good partner from a risky one.',
                    'Mengalihdayakan logistik adalah keputusan besar. Berikut yang membedakan mitra yang baik dari yang berisiko.',
                ],
                [
                    '<p>Handing your logistics to a third party means trusting them with your product and your customer\'s experience. A few questions help separate a strong partner from a risky one.</p><h2>Do they have real cold chain infrastructure?</h2><p>Ask about warehouse temperature zones, fleet refrigeration, and how they handle a breakdown mid-route.</p><h2>Can they scale with you?</h2><p>A partner that fits your volume today should also have room to grow with you next year.</p><h2>How transparent is their tracking?</h2><p>You should be able to see where your goods are — not just be told they arrived.</p><blockquote>The right partner should feel like an extension of your own operations, not a black box.</blockquote>',
                    '<p>Menyerahkan logistik ke pihak ketiga berarti mempercayakan produk dan pengalaman pelanggan Anda kepada mereka. Beberapa pertanyaan berikut membantu membedakan mitra yang kuat dari yang berisiko.</p><h2>Apakah mereka punya infrastruktur cold chain yang nyata?</h2><p>Tanyakan tentang zona suhu gudang, pendingin armada, dan cara mereka menangani kerusakan di tengah rute.</p><h2>Bisakah mereka berkembang bersama Anda?</h2><p>Mitra yang cocok dengan volume Anda saat ini juga sebaiknya punya ruang untuk tumbuh bersama Anda tahun depan.</p><h2>Seberapa transparan pelacakan mereka?</h2><p>Anda seharusnya bisa melihat posisi barang Anda — bukan hanya diberi tahu bahwa barang sudah sampai.</p><blockquote>Mitra yang tepat akan terasa seperti perpanjangan tangan operasi Anda sendiri, bukan kotak hitam.</blockquote>',
                ],
            ],
        ];

        foreach ($posts as $index => [$slug, $image, $daysAgo, $title, $excerpt, $content]) {
            $post = Post::query()->where('slug', $slug)->first() ?? new Post;

            $post->fill([
                'slug' => $slug,
                'slugs' => ['en' => $slug, 'id' => $slug],
                'title' => ['en' => $title[0], 'id' => $title[1]],
                'excerpt' => ['en' => $excerpt[0], 'id' => $excerpt[1]],
                'content' => ['en' => $content[0], 'id' => $content[1]],
                'status' => Post::PUBLISHED,
                'published_at' => now()->subDays($daysAgo),
                'is_active' => true,
            ])->markTranslationsReviewed('id')->save();

            $this->attachSeedImage($post, 'cover', $image, 1600, 900);
        }

        // Fixed slugs above never collide with Str::slug(title), so this is just
        // a safety net if the seeder runs against data that predates it.
        unset($index);
    }
}
