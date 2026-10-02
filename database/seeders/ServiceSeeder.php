<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FleetVehicle;
use App\Models\Industry;
use App\Models\Service;
use App\Support\ServiceBlocks;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    use SeedsImages;

    /** Placeholder for the first gallery photo in seeded blocks. */
    private const GALLERY_PHOTO = '@gallery';

    public function run(): void
    {
        // Urutan = urutan tampil (Revisi 1: Logistic Partnership kartu besar). slug, title [en, id], short description [en, id] (max. 60 characters), icon
        // DUMMY - daftar dan deskripsi layanan harus dikonfirmasi klien (including the
        // temperature range of Cold Storage).
        $services = [
            ['logistic-partnership', ['Logistic Partnership', 'Kemitraan Logistik'], ['One team for storage, transport and delivery', 'Satu tim untuk gudang, transportasi, dan pengiriman'], 'chain-link'],
            ['linehaul-transport', ['Linehaul Transport', 'Transportasi Linehaul'], ['Refrigerated and dry trucks between cities', 'Truk berpendingin dan non-pendingin antarkota'], 'reefer-truck'],
            ['hyperlocal-fulfilment', ['Hyperlocal Fulfilment', 'Hyperlocal Fulfilment'], ['Quick drop-offs from hubs inside the city', 'Antar cepat dari hub di dalam kota'], 'map-pin'],
            ['cold-storage', ['Cold Storage', 'Cold Storage'], ['Chilled and frozen rooms from -25°C to 6°C', 'Ruang chiller dan frozen, -25°C hingga 6°C'], 'snowflake'],
            ['omnichannel-integration', ['Omnichannel Integration', 'Integrasi Omnichannel'], ['Orders from every sales channel in one flow', 'Pesanan dari semua kanal penjualan dalam satu alur'], 'network'],
        ];

        foreach ($services as $index => [$slug, $title, $description, $icon]) {
            $detail = $this->detail($slug);

            // The Indonesian texts are written by hand: marked as checked ("Sudah dicek").
            $service = Service::query()->updateOrCreate(['slug' => $slug], [
                'title' => ['en' => $title[0], 'id' => $title[1]],
                'short_description' => ['en' => $description[0], 'id' => $description[1]],
                'icon' => $icon,
                'anchor' => $slug,
                // Main grid of the mega menu; Omnichannel sits in the row below.
                'is_featured' => $slug !== 'omnichannel-integration',
                'is_coming_soon' => $slug === 'cold-storage',
                'sort_order' => $index + 1,
                'is_active' => true,
                ...$detail['fields'],
            ]);
            $service->markTranslationsReviewed('id')->save();

            foreach ($detail['images'] as $collection => $image) {
                $this->attachServiceImage($service, $collection, $image, 2400, 1600);
            }

            $this->linkGalleryPhoto($service);
        }
    }

    /**
     * teks_gambar block with the service's detail photo (filled in by linkGalleryPhoto).
     *
     * @param  array<string, string>  $eyebrow
     * @param  array<string, string>  $title
     * @param  array<string, string>  $text
     * @param  array<string, string>  $points
     * @return array{type: string, data: array<string, mixed>}
     */
    private function textImage(string $position, array $eyebrow, array $title, array $text, array $points): array
    {
        return ServiceBlocks::make('teks_gambar', [
            'eyebrow' => $eyebrow,
            'title' => $title,
            'text' => $text,
            'points' => $points,
            'image' => self::GALLERY_PHOTO,
            'image_position' => $position,
        ]);
    }

    /**
     * The teks_gambar block points at the first gallery photo (media uuid), known
     * only once the photo is attached; without seeded photos it has no image.
     */
    private function linkGalleryPhoto(Service $service): void
    {
        // Fresh media: the relation was loaded (empty) before the photos were attached.
        $uuid = $service->load('media')->getFirstMedia('gallery')?->uuid;
        $blocks = array_map(function (array $block) use ($uuid): array {
            if (($block['data']['image'] ?? null) === self::GALLERY_PHOTO) {
                $block['data']['image'] = $uuid;
            }

            return $block;
        }, (array) $service->blocks);

        $service->forceFill(['blocks' => $blocks])->saveQuietly();
    }

    /**
     * Detail page of a service. DUMMY: texts, numbers and block contents are
     * examples written for this project, to be confirmed by the client.
     *
     * @return array{fields: array<string, mixed>, images: array<string, string>}
     */
    private function detail(string $slug): array
    {
        $faq = fn (string ...$questions): array => Faq::query()->get()
            ->filter(fn (Faq $item): bool => in_array($item->translation('question', 'en'), $questions, true))
            ->sortBy(fn (Faq $item): int => (int) array_search($item->translation('question', 'en'), $questions, true))
            ->pluck('id')->values()->all();
        $industries = fn (string ...$names): array => Industry::query()->get()
            ->filter(fn (Industry $item): bool => in_array($item->translation('name', 'en'), $names, true))
            ->pluck('id')->values()->all();
        $t = fn (string $en, string $id): array => ['en' => $en, 'id' => $id];
        $cta = ServiceBlocks::make('cta_penutup', [
            'title' => $t('Tell us what needs to stay cold', 'Ceritakan barang yang harus tetap dingin'),
            'description' => $t('We reply with a plan and a quote within one working day.', 'Kami balas dengan rencana dan penawaran dalam satu hari kerja.'),
            'target' => 'contact',
        ]);
        $sample = ['is_sample' => true];

        return match ($slug) {
            'cold-storage' => [
                'fields' => [
                    ...$sample,
                    'slugs' => ['en' => 'cold-storage', 'id' => 'penyimpanan-dingin'],
                    'intro' => $t('Frozen, chilled and cool rooms in Jabodetabek, with the temperature logged around the clock.', 'Ruang frozen, chiller, dan sejuk di Jabodetabek, dengan suhu tercatat sepanjang waktu.'),
                    'key_points' => $t("Three temperature zones\nLogged 24/7\nFrom a single pallet", "Tiga zona suhu\nTercatat 24/7\nMulai dari satu palet"),
                    'seo_description' => $t('Frozen and chilled warehousing in Jabodetabek from -25 °C, logged 24/7, from a single pallet.', 'Gudang frozen dan chiller di Jabodetabek mulai -25 °C, suhu tercatat 24/7, mulai dari satu palet.'),
                    'detail_status' => Service::PUBLISHED,
                    // The card photo (service-cold-storage-card) is a well-lit daytime
                    // warehouse shot, and the giant number sits over its brightest, busiest
                    // corner (orange racking + boxes) — needs dark ink, not pale white.
                    // Confirmed by rendering both values and inspecting the actual photo;
                    // a prior pass flipped this to `false` based on a mischaracterization
                    // of the image as "dark, moody", which is why "01" kept going invisible.
                    'card_photo_light' => true,
                    'blocks' => [
                        ServiceBlocks::make('hero_layanan', ['show_key_points' => true]),
                        ServiceBlocks::make('pita_spesifikasi', ['items' => [
                            ['prefix' => '', 'value' => -25, 'suffix' => '°C', 'count_up' => false, 'label' => $t('Lowest temperature', 'Suhu terendah')],
                            ['prefix' => '', 'value' => 3, 'suffix' => '', 'count_up' => true, 'label' => $t('Temperature zones', 'Zona suhu')],
                            ['prefix' => '', 'value' => 8100, 'suffix' => '', 'count_up' => true, 'label' => $t('Pallet positions', 'Posisi palet')],
                            ['prefix' => '', 'value' => 24, 'suffix' => '/7', 'count_up' => false, 'label' => $t('Temperature logging', 'Pencatatan suhu')],
                        ]]),
                        $this->textImage('right',
                            $t('Inside the rooms', 'Di dalam ruang'),
                            $t('Every pallet has its place and its temperature', 'Setiap palet punya tempat dan suhunya'),
                            $t('Goods are checked on arrival, labelled and stored in the zone that suits them. Doors stay shut, the air stays steady and the log keeps running.', 'Barang dicek saat tiba, diberi label, lalu disimpan di zona yang sesuai. Pintu tetap tertutup, udara tetap stabil, dan pencatatan suhu terus berjalan.'),
                            $t("Checked on arrival\nStored by temperature zone\nFirst in, first out", "Dicek saat tiba\nDisimpan per zona suhu\nMasuk pertama, keluar pertama"),
                        ),
                        ServiceBlocks::make('zona_suhu', [
                            'eyebrow' => $t('Temperature zones', 'Zona suhu'),
                            'title' => $t('The right room for every product', 'Ruang yang tepat untuk setiap produk'),
                            'zones' => [
                                ['zone' => 'frozen', 'min' => -25, 'max' => -18, 'label' => $t('Frozen', 'Beku'), 'commodities' => $t('Frozen meat, seafood, ice cream', 'Daging beku, hasil laut, es krim')],
                                ['zone' => 'chiller', 'min' => 0, 'max' => 6, 'label' => $t('Chiller', 'Dingin'), 'commodities' => $t('Dairy, fresh meat, ready meals', 'Produk susu, daging segar, makanan siap saji')],
                                ['zone' => 'cool', 'min' => 8, 'max' => 15, 'label' => $t('Cool', 'Sejuk'), 'commodities' => $t('Fruit, chocolate, some medicines', 'Buah, cokelat, sebagian obat')],
                            ],
                        ]),
                        ServiceBlocks::make('keunggulan', [
                            'eyebrow' => $t('Why store with us', 'Mengapa menyimpan di kami'),
                            'title' => $t('Cold, steady and on record', 'Dingin, stabil, dan tercatat'),
                            'items' => [
                                ['icon' => 'thermometer', 'title' => $t('Steady temperature', 'Suhu stabil'), 'text' => $t('Rooms keep their range, with alarms before anything drifts.', 'Ruang menjaga rentangnya, dengan alarm sebelum suhu bergeser.')],
                                ['icon' => 'document-check', 'title' => $t('Records you can show', 'Catatan siap ditunjukkan'), 'text' => $t('Temperature logs for every room, ready for audits.', 'Log suhu setiap ruang, siap untuk audit.')],
                                ['icon' => 'boxes', 'title' => $t('Flexible space', 'Ruang fleksibel'), 'text' => $t('From one pallet to a dedicated room.', 'Dari satu palet hingga ruang khusus.')],
                                ['icon' => 'service-24h', 'title' => $t('Open around the clock', 'Buka sepanjang waktu'), 'text' => $t('Receiving and dispatch at any hour.', 'Penerimaan dan pengeluaran barang kapan saja.')],
                            ],
                        ]),
                        ServiceBlocks::make('lokasi_gudang', [
                            'eyebrow' => $t('Locations', 'Lokasi'),
                            'title' => $t('Warehouses close to your market', 'Gudang dekat dengan pasar Anda'),
                            'kind' => 'warehouse',
                        ]),
                        ServiceBlocks::make('industri', [
                            'title' => $t('Industries we store for', 'Industri yang kami layani'),
                            'industry_ids' => $industries('Meat & poultry', 'Seafood', 'Dairy', 'Fresh produce', 'Pharma & health'),
                        ]),
                        ServiceBlocks::make('faq', [
                            'title' => $t('Questions about cold storage', 'Pertanyaan seputar cold storage'),
                            'faq_ids' => $faq('What temperature ranges do you offer?', 'How is the temperature monitored?', 'Is there a minimum volume?', 'How do I get a quote?'),
                        ]),
                        $cta,
                    ],
                ],
                'images' => ['hero' => 'service-cold-storage-hero', 'card' => 'service-cold-storage-card', 'gallery' => 'service-cold-storage-detail'],
            ],
            'linehaul-transport' => [
                'fields' => [
                    ...$sample,
                    'slugs' => ['en' => 'linehaul-transport', 'id' => 'transportasi-linehaul'],
                    'intro' => $t('Refrigerated trucks between cities, planned by route and monitored for temperature on the way.', 'Truk berpendingin antarkota, direncanakan per rute dan dipantau suhunya selama perjalanan.'),
                    'key_points' => $t("Refrigerated box trucks\nRegular routes from Jakarta\nRent per trip or monthly", "Truk boks berpendingin\nRute rutin dari Jakarta\nSewa per trip atau bulanan"),
                    'seo_description' => $t('Refrigerated linehaul from Jakarta to cities in Java and Sumatra. Rent per trip, monthly or dedicated.', 'Linehaul berpendingin dari Jakarta ke kota-kota di Jawa dan Sumatra. Sewa per trip, bulanan, atau dedicated.'),
                    'detail_status' => Service::PUBLISHED,
                    // Card: the client's FE 71 side view, cropped 4:3 on screen only.
                    'card_fleet_vehicle_id' => FleetVehicle::query()->where('model', 'FE 71 Long')->value('id'),
                    'card_focus_x' => 70,
                    'card_focus_y' => 50,
                    // Bright white truck box: same reason as cold-storage above.
                    'card_photo_light' => true,
                    'blocks' => [
                        ServiceBlocks::make('hero_layanan', ['show_key_points' => true]),
                        ServiceBlocks::make('pita_spesifikasi', ['items' => [
                            ['prefix' => '', 'value' => -20, 'suffix' => '°C', 'count_up' => false, 'label' => $t('Down to', 'Hingga')],
                            ['prefix' => '', 'value' => 6, 'suffix' => '', 'count_up' => true, 'label' => $t('Regular routes', 'Rute rutin')],
                            ['prefix' => '', 'value' => 4000, 'suffix' => 'kg', 'count_up' => true, 'label' => $t('Largest payload', 'Muatan terbesar')],
                        ]]),
                        $this->textImage('left',
                            $t('On the road', 'Di perjalanan'),
                            $t('Loaded cold, delivered cold', 'Dimuat dingin, sampai tetap dingin'),
                            $t('The box is cooled before the first carton goes in, and the temperature is logged until the last one comes out at the other end.', 'Boks didinginkan sebelum karton pertama masuk, dan suhunya dicatat sampai karton terakhir diturunkan di tujuan.'),
                            $t("Box pre-cooled before loading\nLogger on every trip\nDrivers trained for cold cargo", "Boks didinginkan sebelum dimuat\nPencatat suhu di setiap perjalanan\nSopir terlatih untuk muatan dingin"),
                        ),
                        ServiceBlocks::make('katalog_armada', [
                            'eyebrow' => $t('Fleet', 'Armada'),
                            'title' => $t('Refrigerated box trucks', 'Truk boks berpendingin'),
                            'layout' => 'showcase',
                        ]),
                        ServiceBlocks::make('skema_sewa', [
                            'eyebrow' => $t('Rental', 'Sewa'),
                            'title' => $t('Pick how you rent', 'Pilih cara sewa Anda'),
                        ]),
                        ServiceBlocks::make('diagram_rute', [
                            'eyebrow' => $t('Routes', 'Rute'),
                            'title' => $t('Regular routes from Jakarta', 'Rute rutin dari Jakarta'),
                            'hub' => 'Jakarta',
                        ]),
                        ServiceBlocks::make('cara_kerja', [
                            'title' => $t('How a shipment runs', 'Cara pengiriman berjalan'),
                            'steps' => [
                                ['title' => $t('Plan', 'Rencanakan'), 'text' => $t('We agree on the route, temperature and pickup time.', 'Kami sepakati rute, suhu, dan waktu penjemputan.')],
                                ['title' => $t('Pre-cool', 'Dinginkan dulu'), 'text' => $t('The box is cooled to the target before loading.', 'Boks didinginkan ke suhu target sebelum dimuat.')],
                                ['title' => $t('Drive', 'Jalan'), 'text' => $t('The temperature is logged along the way.', 'Suhu dicatat sepanjang perjalanan.')],
                                ['title' => $t('Deliver', 'Serahkan'), 'text' => $t('Proof of delivery and the temperature log come to you.', 'Bukti serah terima dan log suhu dikirim kepada Anda.')],
                            ],
                        ]),
                        ServiceBlocks::make('faq', [
                            'title' => $t('Questions about linehaul', 'Pertanyaan seputar linehaul'),
                            'faq_ids' => $faq('Which cities do your trucks serve?', 'Can I rent a truck with a driver?', 'How is the temperature monitored?', 'How do I get a quote?'),
                        ]),
                        $cta,
                    ],
                ],
                'images' => ['hero' => 'service-linehaul-hero', 'gallery' => 'service-linehaul-detail'],
            ],
            'hyperlocal-fulfilment' => [
                'fields' => [
                    ...$sample,
                    'slugs' => ['en' => 'hyperlocal-fulfilment', 'id' => 'hyperlocal-fulfilment'],
                    'intro' => $t('Stock kept at a city hub and delivered cold to your customers, usually the same day.', 'Stok disimpan di hub kota dan diantar dingin ke pelanggan Anda, umumnya di hari yang sama.'),
                    'key_points' => $t("City hubs close to customers\nSame-day delivery\nCold boxes to the door", "Hub kota dekat pelanggan\nPengiriman di hari yang sama\nBoks dingin sampai ke pintu"),
                    'seo_description' => $t('Same-day cold delivery from city hubs in Jakarta, packed to keep chilled and frozen goods at temperature.', 'Pengiriman dingin di hari yang sama dari hub kota di Jakarta, dikemas agar produk chiller dan frozen tetap di suhunya.'),
                    'detail_status' => Service::PUBLISHED,
                    'blocks' => [
                        ServiceBlocks::make('hero_layanan', ['show_key_points' => true]),
                        ServiceBlocks::make('pita_spesifikasi', ['items' => [
                            ['prefix' => '', 'value' => 600, 'suffix' => '+', 'count_up' => true, 'label' => $t('Delivery points', 'Titik antar')],
                            ['prefix' => '', 'value' => 3, 'suffix' => '', 'count_up' => true, 'label' => $t('Hours on average to deliver', 'Jam rata-rata waktu antar')],
                            ['prefix' => '', 'value' => 16, 'suffix' => '', 'count_up' => true, 'label' => $t('Hub opening hours a day', 'Jam buka hub per hari')],
                        ]]),
                        $this->textImage('right',
                            $t('At the door', 'Sampai di pintu'),
                            $t('Packed for the last few kilometres', 'Dikemas untuk beberapa kilometer terakhir'),
                            $t('Each order leaves the hub in an insulated box with cold packs sized to what is inside, so it arrives as cold as it left.', 'Setiap pesanan keluar dari hub dalam boks berinsulasi dengan cold pack sesuai isinya, sehingga tiba sedingin saat berangkat.'),
                            $t("Insulated boxes\nCold packs sized to the order\nDelivered the same day", "Boks berinsulasi\nCold pack sesuai pesanan\nDiantar di hari yang sama"),
                        ),
                        ServiceBlocks::make('lokasi_gudang', [
                            'eyebrow' => $t('City hubs', 'Hub kota'),
                            'title' => $t('Close to your customers', 'Dekat dengan pelanggan Anda'),
                            'kind' => 'hub',
                        ]),
                        ServiceBlocks::make('keunggulan', [
                            'title' => $t('Fast without losing the cold', 'Cepat tanpa kehilangan dingin'),
                            'items' => [
                                ['icon' => 'courier-motorbike', 'title' => $t('Quick drop-offs', 'Antar cepat'), 'text' => $t('Short trips from a hub inside the city.', 'Perjalanan singkat dari hub di dalam kota.')],
                                ['icon' => 'box', 'title' => $t('Cold packing', 'Kemasan dingin'), 'text' => $t('Insulated boxes and cold packs sized to the order.', 'Boks berinsulasi dan cold pack sesuai ukuran pesanan.')],
                                ['icon' => 'map-pin', 'title' => $t('Live tracking', 'Lacak langsung'), 'text' => $t('Customers see where their order is.', 'Pelanggan bisa melihat posisi pesanannya.')],
                            ],
                        ]),
                        ServiceBlocks::make('cara_kerja', [
                            'title' => $t('From order to doorstep', 'Dari pesanan sampai ke pintu'),
                            'steps' => [
                                ['title' => $t('Stock at the hub', 'Stok di hub'), 'text' => $t('Your fast movers wait at the nearest hub.', 'Produk laris Anda siap di hub terdekat.')],
                                ['title' => $t('Pick and pack', 'Ambil dan kemas'), 'text' => $t('Orders are packed cold within minutes.', 'Pesanan dikemas dingin dalam hitungan menit.')],
                                ['title' => $t('Deliver', 'Antar'), 'text' => $t('A courier takes it to the door the same day.', 'Kurir mengantar ke pintu di hari yang sama.')],
                            ],
                        ]),
                        ServiceBlocks::make('faq', [
                            'title' => $t('Questions about city delivery', 'Pertanyaan seputar pengiriman dalam kota'),
                            'faq_ids' => $faq('How fast are city deliveries?', 'Is there a minimum volume?', 'How do I get a quote?'),
                        ]),
                        $cta,
                    ],
                ],
                'images' => ['hero' => 'service-hyperlocal-hero', 'card' => 'service-hyperlocal-card', 'gallery' => 'service-hyperlocal-detail'],
            ],
            'logistic-partnership' => [
                'fields' => [
                    ...$sample,
                    'slugs' => ['en' => 'logistic-partnership', 'id' => 'kemitraan-logistik'],
                    'intro' => $t('Storage, linehaul and last-mile delivery run by one team, with one contact and one report.', 'Penyimpanan, linehaul, dan pengiriman akhir dijalankan satu tim, dengan satu kontak dan satu laporan.'),
                    'key_points' => $t("One team end to end\nOne contact, one report\nPlanned around your volume", "Satu tim dari awal sampai akhir\nSatu kontak, satu laporan\nDirencanakan sesuai volume Anda"),
                    'seo_description' => $t('End-to-end cold chain partnership: storage, linehaul and last-mile delivery from one team.', 'Kemitraan rantai dingin menyeluruh: penyimpanan, linehaul, dan pengiriman akhir dari satu tim.'),
                    'detail_status' => Service::PUBLISHED,
                    'blocks' => [
                        ServiceBlocks::make('hero_layanan', ['show_key_points' => true]),
                        ServiceBlocks::make('alur_end_to_end', [
                            'eyebrow' => $t('End to end', 'Dari awal sampai akhir'),
                            'title' => $t('One flow from supplier to customer', 'Satu alur dari pemasok ke pelanggan'),
                            'steps' => [
                                ['icon' => 'box-truck', 'title' => $t('Inbound', 'Penerimaan'), 'text' => $t('Goods arrive and are checked for temperature.', 'Barang datang dan dicek suhunya.')],
                                ['icon' => 'warehouse', 'title' => $t('Storage', 'Penyimpanan'), 'text' => $t('Kept in the right zone until needed.', 'Disimpan di zona yang tepat sampai dibutuhkan.')],
                                ['icon' => 'boxes', 'title' => $t('Picking', 'Pengambilan'), 'text' => $t('Orders are picked and packed cold.', 'Pesanan diambil dan dikemas dingin.')],
                                ['icon' => 'reefer-truck', 'title' => $t('Linehaul', 'Linehaul'), 'text' => $t('Refrigerated trucks move stock between cities.', 'Truk berpendingin memindahkan stok antarkota.')],
                                ['icon' => 'map-pin', 'title' => $t('Last mile', 'Pengiriman akhir'), 'text' => $t('Delivered to stores and homes.', 'Diantar ke toko dan rumah.')],
                            ],
                        ]),
                        $this->textImage('left',
                            $t('Working together', 'Bekerja bersama'),
                            $t('One plan from warehouse to doorstep', 'Satu rencana dari gudang sampai ke pintu'),
                            $t('We sit down with your team, look at the forecast and set up storage, trucks and delivery around it, then review the numbers with you every month.', 'Kami duduk bersama tim Anda, melihat perkiraan permintaan, lalu mengatur gudang, truk, dan pengiriman sesuai perkiraan itu, dan meninjau angkanya bersama Anda setiap bulan.'),
                            $t("Shared forecast\nOne view of your stock\nMonthly review", "Perkiraan bersama\nSatu tampilan stok\nTinjauan bulanan"),
                        ),
                        ServiceBlocks::make('keunggulan', [
                            'title' => $t('Why one partner works better', 'Mengapa satu mitra lebih efektif'),
                            'items' => [
                                ['icon' => 'team', 'title' => $t('One team', 'Satu tim'), 'text' => $t('The same people plan storage and transport.', 'Orang yang sama merencanakan penyimpanan dan transportasi.')],
                                ['icon' => 'chart-up', 'title' => $t('One report', 'Satu laporan'), 'text' => $t('Stock, deliveries and temperatures in one place.', 'Stok, pengiriman, dan suhu dalam satu laporan.')],
                                ['icon' => 'headset', 'title' => $t('One contact', 'Satu kontak'), 'text' => $t('A dedicated person answers every question.', 'Satu orang khusus menjawab setiap pertanyaan.')],
                            ],
                        ]),
                        ServiceBlocks::make('industri', [
                            'title' => $t('Partners across industries', 'Mitra dari berbagai industri'),
                        ]),
                        ServiceBlocks::make('faq', [
                            'title' => $t('Questions about partnerships', 'Pertanyaan seputar kemitraan'),
                            'faq_ids' => $faq('What does a partnership include?', 'Which cities do your trucks serve?', 'How do I get a quote?'),
                        ]),
                        $cta,
                    ],
                ],
                'images' => ['hero' => 'service-partnership-hero', 'card' => 'service-partnership-card', 'gallery' => 'service-partnership-detail'],
            ],
            // Omnichannel: no detail page yet (draft), the common template is ready for it.
            default => [
                'fields' => [
                    'slugs' => ['en' => $slug, 'id' => 'integrasi-omnichannel'],
                    'detail_status' => Service::DRAFT,
                    'blocks' => ServiceBlocks::defaultLayout(),
                ],
                'images' => [],
            ],
        };
    }
}
