<?php

use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\HeroSlides\Pages\CreateHeroSlide;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\PlaceholderImage;
use App\Support\Turnstile;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    seedSite();
    Carbon::setTestNow(Carbon::parse('2026-10-01 10:00:00'));
    Filament::setCurrentPanel('admin');
    RateLimiter::clear('contact');

    $this->securityLog = storage_path('logs/security-test.log');
    @unlink($this->securityLog);
    config(['logging.channels.security' => ['driver' => 'single', 'path' => $this->securityLog, 'level' => 'info']]);
    Log::forgetChannel('security');
});

afterEach(function () {
    Carbon::setTestNow();
    @unlink($this->securityLog);
});

function securityLines(): string
{
    return is_file(test()->securityLog) ? (string) file_get_contents(test()->securityLog) : '';
}

// ---------------------------------------------------------------- J-1: error pages

it('shows the on-brand page for a missing URL, in the visitor\'s language, without any technical detail', function () {
    $en = $this->get('/no-such-page')->assertNotFound()->getContent();
    $id = $this->get('/id/halaman-tidak-ada')->assertNotFound()->getContent();

    expect($en)->toContain('<html lang="en">')->toContain('Page not found')->toContain('Back to home')->toContain('href="/"')
        ->toContain('noindex')->not->toContain('<script')
        ->and($id)->toContain('<html lang="id">')->toContain('Halaman tidak ditemukan')->toContain('Kembali ke beranda')->toContain('href="/id"');
});

it('has a page for 403, 419, 429, 500 and 503, in both languages', function (int $code, string $en, string $id) {
    Route::middleware('web')->get('/_err/{code}', fn (int $code) => abort($code));
    Route::middleware('web')->get('/id/_err/{code}', fn (int $code) => abort($code));

    $this->get("/_err/{$code}")->assertStatus($code)->assertSee($en)->assertSee((string) $code);
    $this->get("/id/_err/{$code}")->assertStatus($code)->assertSee($id);
})->with([
    'forbidden' => [403, 'Access denied', 'Akses ditolak'],
    'page expired' => [419, 'Session expired', 'Sesi kedaluwarsa'],
    'too many requests' => [429, 'Too many requests', 'Terlalu banyak permintaan'],
    'server error' => [500, 'Something went wrong', 'Terjadi kesalahan'],
    'maintenance' => [503, 'Back soon', 'Segera kembali'],
]);

it('never leaks exception details on a 500 when debugging is off (J-1)', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/_boom', fn () => throw new RuntimeException('SECRET db password=hunter2 at /var/www/app'));

    $html = $this->get('/_boom')->assertStatus(500)->getContent();

    expect($html)->toContain('Something went wrong')
        ->not->toContain('hunter2')->not->toContain('RuntimeException')->not->toContain('/var/www')->not->toContain('vendor/');
});

it('renders without touching the database (so it still works when the database is the problem)', function () {
    DB::enableQueryLog();

    foreach ([403, 404, 419, 429, 500, 503] as $code) {
        view('errors.page', ['code' => $code])->render();
    }

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toBe([]);
});

// ---------------------------------------------------------------- J-2: security log

it('logs logins, failures, lockouts and logouts without passwords or full email addresses', function () {
    User::factory()->create(['email' => 'owner@example.test', 'password' => 'Str0ng-Passw0rd!']);
    $try = fn (string $password) => Livewire::test(Login::class)->fillForm(['email' => 'owner@example.test', 'password' => $password])->call('authenticate');

    foreach (range(1, 5) as $i) {
        $try('wrong-secret-'.$i);
    }
    $try('Str0ng-Passw0rd!');

    $log = securityLines();
    expect($log)->toContain('login.failed')->toContain('login.lockout')->toContain('login.blocked')
        ->toContain('o***@example.test')
        ->not->toContain('owner@example.test')->not->toContain('wrong-secret')->not->toContain('Str0ng-Passw0rd!');

    Carbon::setTestNow(now()->addMinutes(20));
    $try('Str0ng-Passw0rd!')->assertRedirect();
    auth()->logout();

    expect(securityLines())->toContain('login.success')->toContain('logout');
});

it('logs changes to accounts, but not routine updates', function () {
    $user = User::factory()->create();
    expect(securityLines())->toContain('user.created');

    @unlink($this->securityLog);
    $user->update(['remember_token' => 'abc']);
    expect(securityLines())->toBe('');

    $user->update(['password' => 'An0ther-Str0ng-Pass!']);
    $user->forceFill(['is_admin' => false])->save();
    $log = securityLines();
    expect($log)->toContain('user.updated')->toContain('"fields":["password"]')->toContain('"fields":["is_admin"]')
        ->not->toContain('An0ther');

    $user->delete();
    expect(securityLines())->toContain('user.deleted');
});

it('logs a deleted lead by id only', function () {
    $lead = ContactMessage::query()->create(['name' => 'Budi Santoso', 'email' => 'budi@klien.test', 'message' => 'Butuh cold storage 20 pallet.', 'locale' => 'id']);

    $lead->delete();

    expect(securityLines())->toContain('lead.deleted')->toContain('"lead":'.$lead->id)
        ->not->toContain('Budi')->not->toContain('klien.test')->not->toContain('cold storage');
});

it('logs an upload made by an administrator, and nothing for seeding', function () {
    Storage::fake('public');
    config(['media-library.disk_name' => 'public', 'site.image_conversions' => 'off']);

    expect(securityLines())->toBe('');

    $this->actingAs(User::factory()->create());
    $path = tempnam(sys_get_temp_dir(), 'png');
    PlaceholderImage::create($path, 320, 180, 'test');

    Livewire::test(CreateHeroSlide::class)
        ->fillForm(['label' => ['en' => 'Cold Storage'], 'image_alt' => ['en' => 'Cold room'], 'image' => UploadedFile::fake()->createWithContent('slide.png', (string) file_get_contents($path)), 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(securityLines())->toContain('upload.added')->toContain('"collection":"image"')->toContain('"mime":"image/png"')->not->toContain('slide.png');
});

it('logs what happened to a lead email, and blocked or bypassed checks, without personal data', function () {
    $lead = ['name' => 'Budi', 'email' => 'budi@klien.test', 'message' => 'Butuh cold storage untuk 20 pallet.'];
    SiteSetting::current()->update(['contact_recipients' => ['boss@gmail.test']]);

    Mail::shouldReceive('to')->andThrow(new RuntimeException('550 mailbox boss@gmail.test rejected'));
    $this->postJson('/contact', $lead)->assertOk();

    $this->postJson('/contact', $lead + ['website' => 'http://spam.test'])->assertOk();

    config(['security.turnstile' => ['enabled' => true, 'site_key' => 'k', 'secret_key' => 's']]);
    Http::fake(['challenges.cloudflare.com/*' => Http::response('', 503)]);
    expect(Turnstile::passes('token'))->toBeTrue();

    $log = securityLines();
    expect($log)->toContain('contact.email_failed')->toContain('RuntimeException')
        ->toContain('"reason":"honeypot"')->toContain('turnstile.fail_open')
        ->not->toContain('boss@gmail.test')->not->toContain('mailbox')->not->toContain('budi@klien.test')->not->toContain('cold storage');
});

it('keeps the security log separate, always at info level, for 90 days by default', function () {
    $channel = require base_path('config/logging.php');

    expect($channel['channels']['security']['driver'])->toBe('daily')
        ->and($channel['channels']['security']['level'])->toBe('info')
        ->and($channel['channels']['security']['max_files'])->toBe(90)
        ->and($channel['channels']['security']['path'])->toEndWith('security.log');
});
