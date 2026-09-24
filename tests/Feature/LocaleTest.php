<?php

use App\Models\HeroSlide;
use App\Models\HeroStat;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Support\FrontendData;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => seedSite());

it('serves English at the root and Indonesian under /id', function () {
    $this->get('/')->assertOk()->assertSee('<html lang="en">', false);
    $this->get('/id')->assertOk()->assertSee('<html lang="id">', false);
});

it('does not pick the language from the browser', function () {
    $this->withHeader('Accept-Language', 'id-ID,id;q=0.9')
        ->get('/')
        ->assertOk()
        ->assertSee('<html lang="en">', false);
});

it('links the language switch to the same page in the other language', function () {
    $en = $this->get('/')->getContent();
    $id = $this->get('/id')->getContent();

    expect($en)
        ->toMatch('#href="/id"\s+hreflang="id"\s+lang="id"\s+aria-label="Baca dalam Bahasa Indonesia"#')
        ->toMatch('#<span aria-current="true"[^>]*>\s*<span aria-hidden="true">EN</span>#')
        ->and($id)
        ->toMatch('#href="/"\s+hreflang="en"\s+lang="en"\s+aria-label="Read in English"#')
        ->toMatch('#<span aria-current="true"[^>]*>\s*<span aria-hidden="true">ID</span>#');

    // Desktop navbar and mobile menu.
    expect(substr_count($en, 'data-lang-link'))->toBe(2);
});

it('keeps admin, robots.txt, sitemap.xml and llms.txt without a language prefix', function () {
    $this->get('/robots.txt')->assertOk();
    $this->get('/sitemap.xml')->assertOk();
    $this->get('/llms.txt')->assertOk();
    $this->get('/admin/login')->assertOk();

    $this->get('/id/robots.txt')->assertNotFound();
    $this->get('/id/admin')->assertNotFound();
});

it('translates the interface texts and formats numbers for the active language', function () {
    HeroStat::query()->where('label->en', 'Delivery Points')->update(['value' => 1250]);
    FrontendData::flush();

    $en = $this->get('/')->getContent();
    $id = $this->get('/id')->getContent();

    expect($en)->toContain('Skip to content')->toContain('Explore Services')->toContain('1,250')
        ->and($id)->toContain('Langsung ke konten')->toContain('Lihat Layanan')->toContain('aria-label="Buka menu"')
        ->toContain('1.250')->not->toContain('1,250');
});

it('shows the admin content in Indonesian under /id', function () {
    $this->get('/id')
        ->assertOk()
        ->assertSee('<title>PT Ekasa Cold Chain | Logistik Rantai Dingin di Indonesia</title>', false)
        ->assertSee('Jaga produk Anda tetap segar dengan')
        ->assertSee('Logistik Terkendali Suhu')
        ->assertSee('Minta Penawaran')
        ->assertSee('Transportasi Linehaul')
        ->assertSee('Rentang Suhu')
        ->assertSee('-25°C hingga 6°C')
        ->assertDontSee('Keep your products fresh with');
});

it('falls back to English when the Indonesian text is empty', function () {
    $settings = SiteSetting::current();
    $settings->update(['hero_headline' => ['en' => 'Cold chain you can trust', 'id' => '']]);
    HeroSlide::query()->where('label->en', 'Linehaul Transport')->firstOrFail()
        ->update(['label' => ['en' => 'Linehaul Transport', 'id' => null]]);

    $this->get('/id')
        ->assertOk()
        ->assertSee('Cold chain you can trust')
        ->assertSee('Cold Storage, Linehaul Transport, Hyperlocal Fulfilment, Kemitraan Logistik');

    expect($settings->refresh()->missingTranslations('id'))->toBe(['hero_headline']);
});

it('clears the cached data of every language when the admin saves', function () {
    FrontendData::all('en');
    FrontendData::all('id');

    Service::query()->firstOrFail()->update(['title' => ['en' => 'Cold Rooms', 'id' => 'Ruang Dingin']]);

    expect(Cache::has(config('site.cache_key').'.en'))->toBeFalse()
        ->and(Cache::has(config('site.cache_key').'.id'))->toBeFalse()
        ->and(FrontendData::services('id')[0]['title'])->toBe('Ruang Dingin');
});

it('outputs the canonical, hreflang and Open Graph locale for each language', function () {
    $base = rtrim(config('app.url'), '/');

    $en = $this->get('/')->getContent();
    $id = $this->get('/id')->getContent();

    foreach ([$en, $id] as $html) {
        expect($html)
            ->toContain('<link rel="alternate" hreflang="en" href="'.$base.'/">')
            ->toContain('<link rel="alternate" hreflang="id" href="'.$base.'/id">')
            ->toContain('<link rel="alternate" hreflang="x-default" href="'.$base.'/">');
    }

    expect($en)->toContain('<link rel="canonical" href="'.$base.'/">')
        ->toContain('<meta property="og:locale" content="en_US">')
        ->toContain('<meta property="og:locale:alternate" content="id_ID">')
        ->and($id)->toContain('<link rel="canonical" href="'.$base.'/id">')
        ->toContain('<meta property="og:locale" content="id_ID">')
        ->toContain('<meta property="og:locale:alternate" content="en_US">')
        ->toContain('<meta name="description" content="PT Ekasa Cold Chain melayani pergudangan');

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $id, $ld);
    $page = collect(json_decode($ld[1], true)['@graph'])->firstWhere('@type', 'WebPage');

    expect($page['inLanguage'])->toBe('id')
        ->and($page['url'])->toBe($base.'/id')
        ->and($page['name'])->toBe('PT Ekasa Cold Chain | Logistik Rantai Dingin di Indonesia');
});

it('lists every language version in the sitemap and llms.txt', function () {
    $base = rtrim(config('app.url'), '/');
    $xml = simplexml_load_string($this->get('/sitemap.xml')->getContent());
    $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');
    $xml->registerXPathNamespace('x', 'http://www.w3.org/1999/xhtml');

    $locs = array_map('strval', $xml->xpath('//s:url/s:loc'));
    $links = array_map(fn ($link) => (string) $link['hreflang'].' '.$link['href'], $xml->xpath('//s:url[1]/x:link'));

    expect($locs)->toBe([$base.'/', $base.'/id'])
        ->and($links)->toBe(['en '.$base.'/', 'id '.$base.'/id', 'x-default '.$base.'/'])
        ->and($this->get('/llms.txt')->getContent())
        ->toContain('- English: '.$base.'/')
        ->toContain('- Bahasa Indonesia: '.$base.'/id');
});
