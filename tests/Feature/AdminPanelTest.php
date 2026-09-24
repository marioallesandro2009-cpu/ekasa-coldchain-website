<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\PageSections\Pages\EditPageSection;
use App\Filament\Resources\PageSections\Pages\ListPageSections;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\ColorContrast;
use App\Support\FrontendData;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\PageSectionSeeder;
use Database\Seeders\ServiceSeeder;
use Livewire\Livewire;

it('redirects guests from /admin to the login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
    $this->get('/admin/settings')->assertRedirect('/admin/login');
});

it('seeds the administrator from the .env credentials', function () {
    config(['site.admin.email' => 'owner@example.test', 'site.admin.password' => 'secret-password']);

    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('email', 'owner@example.test')->firstOrFail();
    expect(password_verify('secret-password', $admin->password))->toBeTrue();
});

it('skips the administrator when no credentials are configured', function () {
    config(['site.admin.email' => null, 'site.admin.password' => null]);

    $this->seed(AdminUserSeeder::class);

    $this->assertDatabaseCount('users', 0);
});

it('renders every admin page', function () {
    $this->actingAs(User::factory()->create());

    foreach (['/admin', '/admin/settings', '/admin/services', '/admin/hero-slides', '/admin/hero-stats', '/admin/trust-logos', '/admin/services/create', '/admin/hero-slides/create', '/admin/hero-stats/create', '/admin/trust-logos/create'] as $url) {
        $this->get($url)->assertOk();
    }
});

it('shows the WebP status badge on the settings page', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin/settings')->assertSee('Konversi WebP:')->assertSee('Umum')->assertSee('SEO');
});

it('saves settings (including SEO fields) and flushes the front page cache', function () {
    $this->actingAs(User::factory()->create());
    FrontendData::all();

    Livewire::test(ManageSiteSettings::class)
        ->set('data.cta_label.en', 'Request a Quote')
        ->set('data.seo_title.en', 'PT Ekasa Cold Chain | Cold Chain Logistics')
        ->set('data.email', 'hello@example.test')
        ->set('data.linkedin_url', 'https://www.linkedin.com/company/example')
        ->call('save')
        ->assertHasNoErrors();

    expect(SiteSetting::current()->translate('cta_label', 'en'))->toBe('Request a Quote')
        ->and(FrontendData::settings('en')['seo']['title'])->toBe('PT Ekasa Cold Chain | Cold Chain Logistics')
        ->and(FrontendData::settings('en')['sameAs'])->toBe(['https://www.linkedin.com/company/example']);
});

it('reorders and toggles services from the table and updates the front page', function () {
    $this->actingAs(User::factory()->create());
    $this->seed(ServiceSeeder::class);
    FrontendData::all();

    $ids = Service::query()->ordered()->pluck('id')->map(fn ($id) => (string) $id)->reverse()->values()->all();
    Livewire::test(ListServices::class)->call('reorderTable', $ids);

    expect(FrontendData::services('en')[0]['title'])->toBe('Omnichannel Integration');

    $omnichannel = Service::query()->where('slug', 'omnichannel-integration')->firstOrFail();
    Livewire::test(ListServices::class)->call('updateTableColumnState', 'is_active', (string) $omnichannel->id, false);

    expect(array_column(FrontendData::services('en'), 'title'))->not->toContain('Omnichannel Integration');
});

it('renders the sections page without create or delete', function () {
    $this->actingAs(User::factory()->create());
    $this->seed(PageSectionSeeder::class);

    $this->get('/admin/page-sections')->assertOk()->assertSee('Our Services');
    $this->get('/admin/page-sections/'.PageSection::query()->value('id').'/edit')->assertOk();
    $this->get('/admin/page-sections/create')->assertNotFound();
});

it('changes navbar labels, section texts and order from the admin', function () {
    seedSite();
    $this->actingAs(User::factory()->create());
    FrontendData::all();

    $about = PageSection::query()->where('key', 'about')->firstOrFail();
    Livewire::test(EditPageSection::class, ['record' => $about->getRouteKey()])
        ->fillForm([
            'nav_label' => ['en' => 'About', 'id' => 'Tentang Kami'],
            'title' => ['en' => 'About Ekasa', 'id' => 'Tentang Ekasa'],
            'description' => ['en' => 'Our story.', 'id' => 'Cerita kami.'],
            'translation_status' => ['id' => ['nav_label' => 'reviewed', 'title' => 'reviewed', 'description' => 'reviewed']],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $ids = PageSection::query()->ordered()->pluck('id')->map(fn ($id) => (string) $id)->reverse()->values()->all();
    Livewire::test(ListPageSections::class)->call('reorderTable', $ids);

    $clients = PageSection::query()->where('key', 'clients')->firstOrFail();
    Livewire::test(ListPageSections::class)->call('updateTableColumnState', 'show_in_nav', (string) $clients->id, false);

    $html = $this->get('/')->getContent();

    expect($html)->toContain('>About</a>')
        ->toContain('About Ekasa')
        ->toContain('Our story.')
        ->not->toContain('data-spy="clients"')
        ->and(strpos($html, 'id="contact"'))->toBeLessThan(strpos($html, 'id="services"'))
        ->and($this->get('/id')->getContent())
        ->toContain('Tentang Kami')
        ->toContain('Tentang Ekasa')
        ->toContain('Cerita kami.');
});

it('saves button and screen reader texts per language, falling back to the language defaults when empty', function () {
    seedSite();
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageSiteSettings::class)
        ->assertSet('data.ui_texts.en.whatsapp_button', null)
        ->set('data.ui_texts.en.hero_secondary_cta', 'See What We Do')
        ->set('data.ui_texts.en.footer_copyright', 'Copyright reserved.')
        ->set('data.ui_texts.id.skip_link', 'Lompat ke isi')
        ->set('data.company_description.en', 'Cold chain logistics company.')
        ->call('save')
        ->assertHasNoErrors();

    $this->get('/')
        ->assertSee('See What We Do')
        ->assertSee('Copyright reserved.')
        ->assertSee('Skip to content')
        ->assertDontSee('Explore Services');

    // Empty Indonesian texts use the Indonesian defaults from lang/id.json.
    $this->get('/id')
        ->assertSee('Lompat ke isi')
        ->assertSee('Lihat Layanan')
        ->assertSee('Hak cipta dilindungi undang-undang.');

    $this->get('/llms.txt')->assertSee('Cold chain logistics company.');

    SiteSetting::current()->update(['ui_texts' => ['en' => ['skip_link' => '']]]);
    expect(SiteSetting::current()->uiTexts('en')['skip_link'])->toBe('Skip to content')
        ->and(SiteSetting::current()->uiTexts('id')['skip_link'])->toBe('Langsung ke konten');
});

it('changes the brand colors with hex codes and outputs them as CSS variables', function () {
    seedSite();
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageSiteSettings::class)
        ->assertSet('data.colors.navy', '#0F2A4A')
        ->set('data.colors.navy', '#123456')
        ->set('data.colors.accent', '#e85d04')
        ->call('save')
        ->assertHasNoErrors();

    $this->get('/')
        ->assertSee('--color-navy:#123456;', false)
        ->assertSee('--color-accent:#E85D04;', false)
        ->assertSee('<meta name="theme-color" content="#123456">', false);
});

it('rejects colors that are not 6-digit hex codes', function (string $value) {
    seedSite();
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageSiteSettings::class)
        ->set('data.colors.ice', $value)
        ->call('save')
        ->assertHasErrors(['data.colors.ice']);
})->with(['red', '#FFF', '#GGGGGG', '0F2A4A']);

it('warns about low text contrast but still saves the colors', function () {
    seedSite();
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageSiteSettings::class)
        ->set('data.colors.accent', '#0A1D33')
        ->call('save')
        ->assertHasNoErrors()
        ->assertNotified('Kontras warna kurang');

    expect(SiteSetting::current()->brandColors()['accent'])->toBe('#0A1D33');
});

it('passes WCAG AA with the default colors', function () {
    expect(ColorContrast::failures(SiteSetting::COLOR_DEFAULTS))->toBe([]);
});

it('no longer has the hero chevron label', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin/settings')->assertOk()->assertDontSee('Tombol panah ke bawah di hero');

    expect(SiteSetting::current()->uiTexts())->not->toHaveKey('scroll_down');
});

it('toggles the main mega menu flag of a service from the table', function () {
    $this->actingAs(User::factory()->create());
    $this->seed(ServiceSeeder::class);

    $coldStorage = Service::query()->where('slug', 'cold-storage')->firstOrFail();
    Livewire::test(ListServices::class)->call('updateTableColumnState', 'is_featured', (string) $coldStorage->id, false);

    expect($coldStorage->refresh()->is_featured)->toBeFalse()
        ->and(collect(FrontendData::services())->firstWhere('title', 'Cold Storage')['featured'])->toBeFalse();
});

it('edits both languages side by side and flags a missing Indonesian version in the table', function () {
    $this->actingAs(User::factory()->create());
    $this->seed(ServiceSeeder::class);

    $service = Service::query()->where('slug', 'cold-storage')->firstOrFail();

    Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
        ->assertSet('data.title.en', 'Cold Storage')
        ->assertSet('data.title.id', 'Cold Storage')
        ->fillForm(['short_description' => ['en' => 'Frozen and chilled rooms', 'id' => '']])
        ->call('save')
        ->assertHasNoErrors();

    expect($service->refresh()->missingTranslations('id'))->toBe(['short_description']);

    Livewire::test(ListServices::class)
        ->assertTableColumnStateSet('translation_id', 'Belum diisi', $service)
        ->assertTableColumnStateSet('translation_id', 'Lengkap', Service::query()->where('slug', 'linehaul-transport')->firstOrFail());
});
