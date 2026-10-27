<?php

namespace Database\Seeders;

use App\Models\Post;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Seeder;

/**
 * Three articles about the business itself (fleet, linehaul, the four services),
 * with company photos as covers. Standalone so a live site can add them with
 * php artisan db:seed --class=BusinessPostSeeder --force
 *
 * Each article is created once and never overwritten afterwards: once the client
 * edits one in the admin, running this again leaves it alone.
 */
class BusinessPostSeeder extends Seeder
{
    use SeedsImages;

    public function run(): void
    {
        // slug, cover image, published N hours ago, title [en, id], excerpt [en, id], content [en, id]
        $posts = [
            [
                'kenalan-dengan-ekasa-cold-chain',
                'service-cold-storage-hero',
                0,
                ['Meet EKASA Cold Chain: Four Services, One Unbroken Cold Chain', 'Kenalan dengan EKASA Cold Chain: Empat Layanan, Satu Rantai Dingin'],
                [
                    'Cold storage, linehaul, hyperlocal fulfilment and logistics partnership: how our services fit together.',
                    'Cold storage, linehaul, hyperlocal fulfilment, dan kemitraan logistik: bagaimana layanan kami saling melengkapi.',
                ],
                [
                    <<<'HTML'
<p>EKASA Cold Chain is a B2B cold chain logistics company based in Jakarta. Our idea is simple: your product should stay at the right temperature from the moment it leaves your warehouse until it reaches your customer — freshness across Indonesia.</p>
<h2>Cold Storage</h2>
<p>Temperature-controlled warehouse space, from frozen to chilled and cool, with temperatures that are monitored and recorded. You can start with a few pallets and grow into a dedicated room.</p>
<h2>Linehaul Transport</h2>
<p>Refrigerated trucks between cities on planned routes, with the box cooled before loading and the temperature logged on the way. Dry trucks and containers are available for goods that do not need cooling.</p>
<h2>Hyperlocal Fulfilment</h2>
<p>Fast deliveries inside the city from a nearby hub, for small and frequent orders that cannot wait.</p>
<h2>Logistic Partnership</h2>
<p>For businesses that prefer one partner for the whole flow: storage, transport and delivery planned together, with one point of contact.</p>
<h2>Who we work with</h2>
<p>Food producers, seafood and dairy suppliers, pharmaceutical distributors and retailers — anyone whose product loses value when the cold chain breaks. Tell us what needs to stay cold and we will suggest the simplest setup that works.</p>
HTML,
                    <<<'HTML'
<p>EKASA Cold Chain adalah perusahaan logistik rantai dingin B2B yang berbasis di Jakarta. Gagasan kami sederhana: produk Anda harus tetap berada pada suhu yang tepat sejak keluar dari gudang hingga sampai ke pelanggan — Freshness Across Indonesia.</p>
<h2>Cold Storage</h2>
<p>Ruang gudang bersuhu terkendali, dari frozen hingga chiller dan sejuk, dengan suhu yang dipantau dan dicatat. Anda bisa mulai dari beberapa palet dan berkembang hingga ruang khusus.</p>
<h2>Transportasi Linehaul</h2>
<p>Truk berpendingin antarkota pada rute yang direncanakan, dengan boks didinginkan sebelum dimuat dan suhu dicatat selama perjalanan. Truk dry dan kontainer juga tersedia untuk barang yang tidak perlu didinginkan.</p>
<h2>Hyperlocal Fulfilment</h2>
<p>Pengiriman cepat di dalam kota dari hub terdekat, untuk pesanan kecil dan sering yang tidak bisa menunggu.</p>
<h2>Kemitraan Logistik</h2>
<p>Untuk bisnis yang ingin satu mitra untuk seluruh alur: penyimpanan, transportasi, dan pengiriman direncanakan bersama, dengan satu penanggung jawab.</p>
<h2>Siapa yang kami layani</h2>
<p>Produsen makanan, pemasok hasil laut dan susu, distributor farmasi, dan peritel — siapa pun yang produknya kehilangan nilai ketika rantai dingin terputus. Ceritakan apa yang perlu tetap dingin, dan kami akan menyarankan pengaturan paling sederhana yang berhasil.</p>
HTML,
                ],
            ],
            [
                'memilih-armada-dari-l300-sampai-tronton',
                'fleet-fuso-fighter-side',
                24,
                ['From L300 to Tronton: Choosing the Right Truck for Your Load', 'Dari L300 sampai Tronton: Memilih Truk yang Tepat untuk Muatan Anda'],
                [
                    'Five unit types in two categories — cold reefer and dry/container. Here is how to pick.',
                    'Lima jenis unit dalam dua kategori — cold reefer dan dry/kontainer. Begini cara memilihnya.',
                ],
                [
                    <<<'HTML'
<p>A bigger truck is not always a better one. The right unit depends on how much you move, how far, and whether the load needs to stay cold. Our fleet has two categories.</p>
<h2>Cold Reefer Trucks</h2>
<p>Refrigerated box trucks for chilled and frozen loads:</p>
<ul>
<li><strong>L300</strong> — compact, for narrow city streets and small, frequent drops.</li>
<li><strong>CDE</strong> — medium size, for city runs with mixed chilled and frozen goods.</li>
<li><strong>CDD</strong> — six wheels, for larger volumes between cities.</li>
<li><strong>Fuso Fighter</strong> — heavy class, for long-haul loads that must stay cold all the way.</li>
</ul>
<h2>Dry Truck &amp; Container</h2>
<p>The <strong>Tronton</strong> (chassis and container) carries dry, bulk logistics loads of up to 40 tons per trip — the choice when volume matters more than temperature.</p>
<h2>A quick way to choose</h2>
<ul>
<li>Small, frequent deliveries in the city: L300 or CDE.</li>
<li>Larger loads between cities: CDD or Fuso Fighter.</li>
<li>Very large dry volumes: Tronton.</li>
</ul>
<p>Not sure? Send us your product, volume and route and we will recommend a unit — or a mix of units — before you commit.</p>
HTML,
                    <<<'HTML'
<p>Truk yang lebih besar belum tentu lebih baik. Unit yang tepat bergantung pada berapa banyak yang Anda kirim, sejauh apa, dan apakah muatan perlu tetap dingin. Armada kami terdiri dari dua kategori.</p>
<h2>Truk Cold Reefer</h2>
<p>Truk boks berpendingin untuk muatan chiller dan frozen:</p>
<ul>
<li><strong>L300</strong> — ringkas, untuk jalan sempit dalam kota dan pengiriman kecil yang sering.</li>
<li><strong>CDE</strong> — ukuran sedang, untuk rute dalam kota dengan barang chiller dan frozen campuran.</li>
<li><strong>CDD</strong> — enam roda, untuk volume lebih besar antarkota.</li>
<li><strong>Fuso Fighter</strong> — kelas berat, untuk muatan jarak jauh yang harus tetap dingin sepanjang jalan.</li>
</ul>
<h2>Truk Dry &amp; Kontainer</h2>
<p><strong>Tronton</strong> (chassis dan kontainer) membawa muatan logistik kering dalam jumlah besar hingga 40 ton per perjalanan — pilihan ketika volume lebih penting daripada suhu.</p>
<h2>Cara cepat memilih</h2>
<ul>
<li>Pengiriman kecil dan sering di dalam kota: L300 atau CDE.</li>
<li>Muatan lebih besar antarkota: CDD atau Fuso Fighter.</li>
<li>Volume kering yang sangat besar: Tronton.</li>
</ul>
<p>Belum yakin? Kirimkan produk, volume, dan rute Anda, dan kami akan merekomendasikan unit — atau kombinasi unit — sebelum Anda memutuskan.</p>
HTML,
                ],
            ],
            [
                'linehaul-berpendingin-jaga-suhu-sepanjang-rute',
                'service-linehaul-hero',
                48,
                ['How Refrigerated Linehaul Keeps Your Load Cold on the Road', 'Bagaimana Linehaul Berpendingin Menjaga Muatan Tetap Dingin di Perjalanan'],
                [
                    'Pre-cooling, loading discipline and temperature logging: what happens on a cold chain trip.',
                    'Pra-pendinginan, disiplin pemuatan, dan pencatatan suhu: apa yang terjadi dalam satu perjalanan cold chain.',
                ],
                [
                    <<<'HTML'
<p>Long distance is where a cold chain is tested. A truck can be refrigerated and still deliver warm goods if the trip is not handled carefully. These are the habits that keep a linehaul trip safe.</p>
<h2>1. Cool the box before loading</h2>
<p>Refrigeration units are built to hold a temperature, not to pull a warm box down with a full load inside. We pre-cool the box first, so the first carton goes into a space that is already at the right temperature.</p>
<h2>2. Load quickly, and in the right order</h2>
<p>Doors open means cold air out. Goods are staged and ready, loaded fast, and arranged so that air can still circulate around the cargo.</p>
<h2>3. Log the temperature</h2>
<p>A logger on every trip records the temperature from loading until the last carton comes out, so you have a record to show if anyone asks.</p>
<h2>4. Plan the route, not just the destination</h2>
<p>Regular routes from Jakarta to cities in Java and Sumatra are planned with realistic travel times, so cargo does not wait longer than it should at either end.</p>
<h2>5. Match the unit to the load</h2>
<p>From a CDD to a Fuso Fighter, the right size means fewer trips and less time with the doors open. Rent the unit alone, with a trained driver, or all-in — whichever fits how you work.</p>
HTML,
                    <<<'HTML'
<p>Jarak jauh adalah ujian bagi rantai dingin. Truk bisa berpendingin namun tetap mengantarkan barang yang hangat bila perjalanannya tidak ditangani dengan cermat. Berikut kebiasaan yang menjaga perjalanan linehaul tetap aman.</p>
<h2>1. Dinginkan boks sebelum dimuat</h2>
<p>Unit pendingin dibuat untuk menahan suhu, bukan menurunkan suhu boks hangat yang sudah penuh muatan. Kami mendinginkan boks lebih dulu, sehingga karton pertama masuk ke ruang yang sudah bersuhu tepat.</p>
<h2>2. Muat dengan cepat dan berurutan</h2>
<p>Pintu terbuka berarti udara dingin keluar. Barang disiapkan dan siap, dimuat dengan cepat, dan ditata agar udara tetap bisa mengalir di sekitar muatan.</p>
<h2>3. Catat suhunya</h2>
<p>Pencatat suhu di setiap perjalanan merekam suhu sejak pemuatan hingga karton terakhir diturunkan, sehingga Anda punya catatan untuk ditunjukkan bila diperlukan.</p>
<h2>4. Rencanakan rute, bukan hanya tujuan</h2>
<p>Rute rutin dari Jakarta ke kota-kota di Jawa dan Sumatra direncanakan dengan waktu tempuh yang realistis, agar muatan tidak menunggu lebih lama dari semestinya di kedua ujung.</p>
<h2>5. Sesuaikan unit dengan muatan</h2>
<p>Dari CDD hingga Fuso Fighter, ukuran yang tepat berarti lebih sedikit perjalanan dan lebih singkat waktu pintu terbuka. Sewa unit saja, dengan sopir terlatih, atau all-in — mana yang cocok dengan cara kerja Anda.</p>
HTML,
                ],
            ],
        ];

        foreach ($posts as [$slug, $image, $hoursAgo, $title, $excerpt, $content]) {
            if (Post::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $post = new Post;
            $post->fill([
                'slug' => $slug,
                'slugs' => ['en' => $slug, 'id' => $slug],
                'title' => ['en' => $title[0], 'id' => $title[1]],
                'excerpt' => ['en' => $excerpt[0], 'id' => $excerpt[1]],
                'content' => ['en' => $content[0], 'id' => $content[1]],
                'status' => Post::PUBLISHED,
                'published_at' => now()->subHours($hoursAgo),
                'is_active' => true,
            ])->markTranslationsReviewed('id')->save();

            $this->attachSeedImage($post, 'cover', $image, 1600, 900);
        }
    }
}
