<?php

use App\Models\HeroSlide;
use App\Models\HeroStat;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\TrustLogo;
use App\Support\FrontendData;
use App\Support\PlaceholderImage;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => seedSite());

it('renders the home page with title, meta description and headline in the raw HTML', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('<title>PT Ekasa Cold Chain | Cold Chain Logistics in Indonesia</title>', false)
        ->assertSee('<meta name="description" content="PT Ekasa Cold Chain handles temperature-controlled warehousing', false)
        ->assertSee('<html lang="en">', false)
        ->assertSee('<meta property="og:locale" content="en_US">', false)
        ->assertSee('<link rel="canonical"', false)
        ->assertSee('Keep your products fresh with')
        ->assertSee('Fresher Tomorrow, Further Together');
});

it('puts the navbar, mega menu links, final statistics and a single h1 in the HTML', function () {
    $html = $this->get('/')->getContent();

    foreach (['Home', 'Services', 'Locations', 'About Us', 'Clients', 'Blog', 'Contact', 'Get a Quote', 'Explore Services'] as $text) {
        expect($html)->toContain($text);
    }

    // Navbar: Home, Services, Locations, About Us, Clients, Blog, Contact; no Insights or Careers sections.
    preg_match_all('#data-spy="([a-z-]+)"#', $html, $spy);
    expect(array_values(array_unique($spy[1])))->toBe(['home', 'services', 'locations', 'about', 'clients', 'blog', 'contact'])
        ->and($html)->not->toContain('id="insights"')
        ->not->toContain('id="careers"');

    expect($html)
        // Published services link to their detail page; drafts to their home anchor.
        ->toContain('href="/services/cold-storage"')
        ->toContain('href="#omnichannel-integration"')
        // Revisi 1: Delivery Points is "Multi", Warehouses "Coming soon", Trusted Brands 20+.
        ->toContain('Multi')
        ->toContain('Coming soon')
        ->toContain('20+')
        ->toContain('-25°C to 6°C')
        ->and(substr_count($html, '<h1'))->toBe(1);

    // The navbar's link is "Contact" (not "Contact Us"). "Contact Us" is the heading of
    // the #contact section itself (its channels column), so only the navbar is checked.
    $navbar = substr($html, (int) strpos($html, '<header'), (int) strpos($html, '</header>') - (int) strpos($html, '<header'));

    expect($navbar)->toContain('Contact')->not->toContain('Contact Us')
        ->and($html)->toContain('Contact Us');
});

it('preloads the first hero photo with the same srcset/sizes as the image', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toMatch('#<link rel="preload" as="image" href="[^"]+hero-truck[^"]*" imagesrcset="[^"]+" imagesizes="100vw" fetchpriority="high">#')
        ->toMatch('#<img\s+src="[^"]+hero-truck[^"]*"\s+srcset="[^"]+"\s+sizes="100vw"#');
});

it('outputs valid JSON-LD with the organization and the services', function () {
    $html = $this->get('/')->getContent();
    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match);

    $data = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
    $types = array_column($data['@graph'], '@type');

    expect($types)->toContain('Organization', 'WebSite', 'WebPage', 'ItemList')
        ->and(collect($data['@graph'])->firstWhere('@type', 'ItemList')['itemListElement'])->toHaveCount(5);
});

it('leaves empty contact fields out of the JSON-LD and adds them once filled', function () {
    $jsonLd = function (): array {
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $this->get('/')->getContent(), $match);

        return json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
    };

    $hasEmpty = function (array $data) use (&$hasEmpty): bool {
        foreach ($data as $value) {
            if ($value === null || $value === '' || $value === [] || (is_array($value) && $hasEmpty($value))) {
                return true;
            }
        }

        return false;
    };

    $empty = $jsonLd();
    $organization = collect($empty['@graph'])->firstWhere('@type', 'Organization');

    expect($organization)->not->toHaveKeys(['telephone'])
        ->not->toHaveKey('email')
        ->not->toHaveKey('sameAs')
        ->and($hasEmpty($empty))->toBeFalse();

    SiteSetting::current()->update([
        'phone' => '+62 21 1234 5678',
        'email' => 'hello@example.test',
        'instagram_url' => 'https://www.instagram.com/example',
    ]);

    $organization = collect($jsonLd()['@graph'])->firstWhere('@type', 'Organization');

    expect($organization['telephone'])->toBe('+62 21 1234 5678')
        ->and($organization['email'])->toBe('hello@example.test')
        ->and($organization['sameAs'])->toBe(['https://www.instagram.com/example']);
});

it('shows only active slides and services, in their sort order', function () {
    Service::query()->where('slug', 'omnichannel-integration')->update(['is_active' => false]);
    HeroSlide::query()->where('label->en', 'Linehaul Transport')->firstOrFail()->update(['is_active' => false]);
    Service::query()->where('slug', 'cold-storage')->firstOrFail()->update(['sort_order' => 0]);
    FrontendData::flush();

    $data = FrontendData::all('en');

    expect(array_column($data['services'], 'title'))
        ->toBe(['Cold Storage', 'Logistic Partnership', 'Linehaul Transport', 'Hyperlocal Fulfilment'])
        ->and(array_column($data['heroSlides'], 'label'))
        ->toBe(['Cold Storage', 'Hyperlocal Fulfilment', 'Logistic Partnership']);

    $this->get('/')->assertDontSee('Omnichannel Integration')->assertDontSee('Show Linehaul Transport');
});

it('reflects admin changes immediately (cache is flushed on save)', function () {
    FrontendData::all();

    HeroSlide::query()->where('label->en', 'Cold Storage')->firstOrFail()->update(['label' => 'Frozen Storage']);
    HeroStat::query()->where('label->en', 'Warehouses')->firstOrFail()->update(['value' => 20]);

    $this->get('/')->assertSee('Frozen Storage')->assertSee('20+');
});

it('renders a static hero with a single active slide', function () {
    HeroSlide::query()->where('label->en', '!=', 'Cold Storage')->update(['is_active' => false]);
    FrontendData::flush();

    $this->get('/')
        ->assertOk()
        ->assertSee('Cold Storage')
        ->assertDontSee('x-data="hero(', false)
        ->assertDontSee('aria-label="Choose a service"', false);
});

it('renders without errors when no slide is active', function () {
    HeroSlide::query()->update(['is_active' => false]);
    FrontendData::flush();

    $this->get('/')
        ->assertOk()
        ->assertSee('Keep your products fresh with')
        ->assertDontSee('rel="preload" as="image"', false)
        ->assertDontSee('class="hero-photo', false);
});

it('falls back to the original image when conversions are off (same payload shape)', function () {
    config(['site.image_conversions' => 'off']);
    FrontendData::flush();

    $image = FrontendData::all()['heroSlides'][0]['image'];

    expect($image['large']['url'])->toBe($image['small']['url'])
        ->and($image['large']['width'])->toBeGreaterThan(0);
});

it('puts only the shared, capped featuredServices in the mega menu grid — no others row', function () {
    // Bounded by "mega-backdrop" (the sibling element right after </nav>): the
    // mobile menu further down the page always lists every service unfiltered,
    // so an unbounded substr would false-positive on it.
    $panel = function (string $html): string {
        $start = (int) strpos($html, 'id="mega-menu-services"');
        $end = strpos($html, 'mega-backdrop');

        return substr($html, $start, $end - $start);
    };

    $html = $this->get('/')->getContent();

    // Same rule as the home teaser (TAHAP 3, Bagian 2): only featuredServices, no
    // "others" section — a service marked featured past the cap of 4 (by
    // sort_order) never appears here either, matching FrontendData::build().
    expect(Service::query()->where('is_featured', true)->count())->toBe(4)
        ->and($panel($html))->not->toContain('border-t border-ink/10')
        ->toContain('Logistic Partnership');

    Service::query()->where('slug', 'omnichannel-integration')->update(['is_featured' => true]);
    FrontendData::flush();

    $html = $this->get('/')->getContent();

    expect($panel($html))->not->toContain('border-t border-ink/10')
        ->not->toContain('Omnichannel Integration');
});

it('caches only the front page data, never the rendered HTML', function () {
    $this->get('/')->assertOk();

    $cached = Cache::get(config('site.cache_key').'.en');

    expect($cached)->toBeArray()
        ->and(array_keys($cached))->toBe(['settings', 'services', 'locations', 'featuredServices', 'heroSlides', 'heroStats', 'sections', 'recentPosts', 'about', 'trustLogos', 'clientLogos'])
        ->and(serialize($cached))->not->toContain('<html')
        ->not->toContain('/build/assets/');
});

it('links the CSS and JS files listed in the current Vite manifest', function () {
    $manifest = json_decode((string) file_get_contents(public_path('build/manifest.json')), true);
    $html = $this->get('/')->getContent();

    foreach (['resources/css/app.css', 'resources/js/app.js'] as $entry) {
        $file = $manifest[$entry]['file'];

        expect($html)->toContain('/build/'.$file)
            ->and(file_exists(public_path('build/'.$file)))->toBeTrue();
    }
});

/**
 * A trust logo with a real PNG file (built without GD).
 */
function trustLogo(string $name, bool $active = true): TrustLogo
{
    $path = tempnam(sys_get_temp_dir(), 'logo').'.png';
    PlaceholderImage::create($path, 120, 40, 'logo');

    $logo = TrustLogo::query()->create(['name' => $name, 'is_active' => $active]);
    $logo->addMedia($path)->toMediaCollection('logo');

    return $logo;
}

it('does not render the trust row without active logos', function () {
    // Neutralize the seeded (TrustLogoSeeder) logos: this test is about the
    // zero-active case specifically, not about how many the seeder ships with.
    TrustLogo::query()->update(['is_active' => false]);
    trustLogo('Hidden Client', active: false);
    FrontendData::flush();

    expect($this->get('/')->getContent())
        ->not->toContain('Trusted by')
        ->not->toContain('Hidden Client');
});

it('renders at most five active trust logos in admin order, with the editable label', function () {
    TrustLogo::query()->update(['is_active' => false]);

    foreach (range(1, 6) as $i) {
        trustLogo("Client {$i}");
    }
    TrustLogo::query()->where('name', 'Client 1')->update(['sort_order' => 99]);
    SiteSetting::current()->update(['ui_texts' => ['en' => ['trust_label' => 'Our clients']]]);
    FrontendData::flush();

    $html = $this->get('/')->getContent();

    // Bounded to the hero's own trust row (id="home" .. next section): the
    // #clients section below also renders every active logo, uncapped, by
    // design (App\Support\FrontendData's clientLogos vs trustLogos pair) —
    // an unbounded assertion would see both and miscount.
    $hero = substr($html, (int) strpos($html, 'id="home"'), strpos($html, 'id="services"') - strpos($html, 'id="home"'));

    expect($html)->toContain('Our clients')
        ->and($hero)->not->toContain('alt="Client 1"')
        ->and(substr_count($hero, 'alt="Client '))->toBe(5)
        ->and(strpos($hero, 'alt="Client 2"'))->toBeLessThan(strpos($hero, 'alt="Client 6"'));
});

it('shows the statistic icons chosen in the admin and keeps an empty slot for stats without one', function () {
    HeroStat::query()->where('label->en', 'Warehouses')->update(['icon' => null]);
    FrontendData::flush();

    $html = $this->get('/')->getContent();
    // Only the hero statistics bar: later sections (the #contact capability strip has
    // its own icons of the same class) are not part of it.
    $start = (int) strpos($html, '<dl');
    $bar = substr($html, $start, (int) strpos($html, '</dl>', $start) - $start);

    expect(substr_count($bar, 'size-5 text-highlight'))->toBe(3)
        ->and(substr_count($bar, '<span class="mb-2 block size-5 lg:shorter:mb-1.5" aria-hidden="true"></span>'))->toBe(1);
});

it('loads only the site bundle on public pages, without Livewire or Filament scripts', function () {
    foreach (['/', '/id'] as $url) {
        $html = $this->get($url)->getContent();

        preg_match_all('#<script[^>]*\ssrc="([^"]+)"#', $html, $scripts);

        expect($scripts[1])->toHaveCount(1)
            ->and($scripts[1][0])->toContain('/build/assets/app-')
            ->and($html)->not->toMatch('/livewire|filament/i');
    }
});

it('shows the WhatsApp button in the mobile menu only when a number is set', function () {
    SiteSetting::current()->update(['whatsapp_number' => null]);
    FrontendData::flush();

    expect($this->get('/')->getContent())->not->toContain('https://wa.me/');

    SiteSetting::current()->update(['whatsapp_number' => '0812-3456-7890']);
    FrontendData::flush();

    expect($this->get('/')->getContent())->toContain('href="https://wa.me/6281234567890"')->toContain('Chat on WhatsApp')
        ->and($this->get('/id')->getContent())->toContain('Chat via WhatsApp');
});
