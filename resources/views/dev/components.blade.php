{{-- Preview of the ported React Bits components (local only, routes/web.php). DUMMY content. --}}
@php
    $services = [
        ['snowflake', 'Cold Storage', 'Ruang chiller dan frozen dengan suhu terpantau.'],
        ['reefer-truck', 'Linehaul Transport', 'Truk berpendingin antarkota.'],
        ['chain-link', 'Logistic Partnership', 'Satu tim untuk gudang, transportasi, dan pengiriman.'],
    ];
    $locations = [
        ['Cakung', 'Jakarta Timur', 'Chiller dan frozen'],
        ['Pulo Gadung', 'Jakarta Timur', 'Frozen'],
        ['Tanjung Priok', 'Jakarta Utara', 'Chiller, dekat pelabuhan'],
        ['Contoh lokasi 4', 'Bekasi', 'Chiller'],
        ['Contoh lokasi 5', 'Tangerang', 'Frozen'],
        ['Contoh lokasi 6', 'Surabaya', 'Chiller dan frozen'],
    ];
    $faq = [
        ['Berapa rentang suhu yang tersedia?', 'Contoh jawaban: ruang chiller 0 hingga 6°C dan ruang frozen hingga -25°C.'],
        ['Apakah suhu dipantau selama pengiriman?', 'Contoh jawaban: setiap truk berpendingin dilengkapi pencatat suhu.'],
        ['Berapa minimal jumlah palet?', 'Contoh jawaban: tidak ada minimal; hubungi tim kami untuk penawaran.'],
        ['Apakah melayani pengiriman di dalam kota?', 'Contoh jawaban: ya, lewat layanan Hyperlocal Fulfilment.'],
        ['Bagaimana cara meminta penawaran?', 'Contoh jawaban: klik tombol "Get a Quote" atau hubungi WhatsApp kami.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Komponen (pratinjau)</title>
    {{-- Same JS markers as the site layout: hiding classes only apply with JS and without reduced motion. --}}
    <script>
        (function (html) {
            html.classList.add('js');
            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) html.classList.add('js-anim');
            setTimeout(function () { if (!window.Alpine) html.classList.remove('js', 'js-anim'); }, 3000);
        })(document.documentElement);
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-ink antialiased">
    <x-ui.section variant="surface" class="min-h-[70vh]">
        <p class="text-sm font-bold tracking-[0.14em] text-brand uppercase">/dev/components · lokal saja</p>
        <h1 class="mt-3 text-3xl font-extrabold">Pratinjau splitText, spotlightCard, animatedList</h1>
        <ul class="mt-6 max-w-3xl list-disc space-y-2 pl-5 text-ink-muted">
            <li><strong>splitText</strong>: judul section masuk per kata saat terlihat (sekali). Gulir ke bawah.</li>
            <li><strong>spotlightCard</strong>: arahkan kursor ke kartu (cahaya mengikuti kursor, kartu naik 4 px). Mati di layar sentuh dan saat reduced motion.</li>
            <li><strong>animatedList</strong>: kartu lokasi dan FAQ muncul satu per satu saat masuk layar.</li>
            <li>Muat ulang halaman untuk mengulang animasi. Tanpa JavaScript atau dengan reduced motion, semua konten langsung tampil utuh.</li>
        </ul>
    </x-ui.section>

    {{-- splitText + spotlightCard on a dark background --}}
    <x-ui.section variant="dark">
        <x-ui.section-heading
            split
            tone="dark"
            eyebrow="Layanan"
            title="Rantai dingin dari gudang sampai ke tangan pelanggan"
            description="Judul ini memakai splitText; kartu di bawah memakai spotlightCard (warna putih 14%)."
        />
        <div class="mt-12 grid gap-6 md:grid-cols-3">
            @foreach ($services as [$icon, $title, $text])
                <a href="#" x-data="spotlightCard" class="spotlight-card block rounded-card border border-white/10 bg-white/5 p-8 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-highlight">
                    <x-ui.icon :name="$icon" class="size-8 text-highlight" />
                    <h3 class="mt-5 text-lg font-bold">{{ $title }}</h3>
                    <p class="mt-2 text-white/75">{{ $text }}</p>
                </a>
            @endforeach
        </div>
    </x-ui.section>

    {{-- splitText + animatedList (location cards, also spotlightCard in the light variant) --}}
    <x-ui.section variant="white">
        <x-ui.section-heading
            split
            eyebrow="Lokasi gudang"
            title="Gudang berpendingin di titik strategis"
            description="Kartu lokasi memakai animatedList (muncul bertahap) dan spotlightCard varian terang (brand 10%). Data contoh."
        />
        <div x-data="animatedList" data-animated-list class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($locations as [$name, $city, $type])
                <article x-data="spotlightCard" tabindex="0" class="spotlight-card is-light rounded-card border border-ink/10 bg-white p-6 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    <x-ui.icon name="map-pin" class="size-7 text-brand" />
                    <h3 class="mt-4 text-lg font-bold">{{ $name }}</h3>
                    <p class="mt-1 text-ink-muted">{{ $city }} · {{ $type }}</p>
                </article>
            @endforeach
        </div>
    </x-ui.section>

    {{-- splitText + animatedList (FAQ) --}}
    <x-ui.section variant="surface">
        <x-ui.section-heading split align="center" eyebrow="FAQ" title="Pertanyaan yang sering diajukan" />
        <div x-data="animatedList" data-animated-list class="mx-auto mt-12 max-w-3xl space-y-4">
            @foreach ($faq as [$question, $answer])
                <details class="group rounded-card border border-ink/10 bg-white p-5">
                    <summary class="flex cursor-pointer items-center justify-between gap-4 font-semibold">
                        {{ $question }}
                        <x-ui.icon name="chevron-down" class="size-5 shrink-0 text-ink-muted transition-transform group-open:rotate-180" />
                    </summary>
                    <p class="mt-3 text-ink-muted">{{ $answer }}</p>
                </details>
            @endforeach
        </div>
    </x-ui.section>
</body>
</html>
