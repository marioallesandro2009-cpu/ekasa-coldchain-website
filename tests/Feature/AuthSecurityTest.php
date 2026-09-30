<?php

use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\LoginThrottle;
use App\Support\Turnstile;
use Database\Seeders\AdminUserSeeder;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

const GOOD_PASSWORD = 'Str0ng-Passw0rd!';

beforeEach(function () {
    seedSite();
    RateLimiter::clear('contact');
    Carbon::setTestNow(Carbon::parse('2026-10-01 10:00:00'));
    Filament::setCurrentPanel('admin');
});

afterEach(fn () => Carbon::setTestNow());

function adminWith(string $email = 'owner@example.test', string $password = GOOD_PASSWORD): User
{
    return User::factory()->create(['email' => $email, 'password' => $password]);
}

function tryLogin(string $email, string $password, array $extra = [])
{
    return Livewire::test(Login::class)
        ->fillForm(['email' => $email, 'password' => $password, ...$extra])
        ->call('authenticate');
}

// ---------------------------------------------------------------- C-1: login lockout

it('locks an account for 15 minutes after 5 failed logins from one IP, even with the right password', function () {
    adminWith();

    foreach (range(1, 5) as $i) {
        tryLogin('owner@example.test', 'wrong-password-'.$i)->assertHasErrors(['data.email']);
    }

    expect(auth()->check())->toBeFalse();

    // Past Filament's own 1-minute window, but still inside our 15-minute lockout.
    Carbon::setTestNow(now()->addMinutes(2));
    tryLogin('owner@example.test', GOOD_PASSWORD)->assertNoRedirect();
    expect(auth()->check())->toBeFalse();

    Carbon::setTestNow(now()->addMinutes(14));
    tryLogin('owner@example.test', GOOD_PASSWORD)->assertRedirect();
    expect(auth()->check())->toBeTrue();
});

it('does not lock another account that logs in from the same IP', function () {
    adminWith();
    adminWith('second@example.test');

    foreach (range(1, 5) as $i) {
        tryLogin('owner@example.test', 'wrong-password-'.$i);
    }

    Carbon::setTestNow(now()->addMinutes(2));
    tryLogin('second@example.test', GOOD_PASSWORD)->assertRedirect();
    expect(auth()->check())->toBeTrue();
});

it('gives the same generic error for an unknown email and a wrong password, and for an account without access', function () {
    adminWith();
    User::factory()->nonAdmin()->create(['email' => 'reader@example.test', 'password' => GOOD_PASSWORD]);

    $messages = collect([
        tryLogin('nobody@example.test', GOOD_PASSWORD),
        tryLogin('owner@example.test', 'wrong-password'),
        tryLogin('reader@example.test', GOOD_PASSWORD),
    ])->map(fn ($component) => $component->errors()->first('data.email'))->unique();

    expect($messages)->toHaveCount(1)->and($messages->first())->not->toBeEmpty();
    expect(auth()->check())->toBeFalse();
});

it('clears the failure count after a successful login', function () {
    adminWith();

    foreach (range(1, 3) as $i) {
        tryLogin('owner@example.test', 'wrong-password-'.$i);
    }
    tryLogin('owner@example.test', GOOD_PASSWORD)->assertRedirect();
    auth()->logout();

    Carbon::setTestNow(now()->addMinutes(2));

    // 3 more failures would be the 6th in total without the reset.
    foreach (range(1, 3) as $i) {
        tryLogin('owner@example.test', 'wrong-password-'.$i);
    }
    Carbon::setTestNow(now()->addMinutes(2));
    tryLogin('owner@example.test', GOOD_PASSWORD)->assertRedirect();
    expect(auth()->check())->toBeTrue();
});

it('counts failures per email across IPs and per IP across emails', function () {
    // One email attacked from many IPs: 10 failures lock it for a brand new IP too.
    foreach (range(1, 10) as $i) {
        (new LoginThrottle("203.0.113.{$i}", 'Owner@Example.test'))->fail();
    }
    expect((new LoginThrottle('198.51.100.1', 'owner@example.test'))->secondsUntilAvailable())->toBeGreaterThan(0)
        ->and((new LoginThrottle('198.51.100.1', 'someone-else@example.test'))->secondsUntilAvailable())->toBe(0);

    // One IP trying many emails (password spraying): 30 failures lock every email from that IP.
    foreach (range(1, 30) as $i) {
        (new LoginThrottle('192.0.2.50', "user{$i}@example.test"))->fail();
    }
    expect((new LoginThrottle('192.0.2.50', 'brand-new@example.test'))->secondsUntilAvailable())->toBeGreaterThan(0)
        ->and((new LoginThrottle('192.0.2.51', 'brand-new@example.test'))->secondsUntilAvailable())->toBe(0);
});

// ---------------------------------------------------------------- C-2: passwords, admin:create, seeder, profile

it('applies one strong password rule', function () {
    $check = fn (string $password): bool => validator(['p' => $password], ['p' => [Password::default()]])->passes();

    expect($check(GOOD_PASSWORD))->toBeTrue()
        ->and($check('short1!A'))->toBeFalse()
        ->and($check('alllowercase-123!'))->toBeFalse()
        ->and($check('NoNumbers-Here!!'))->toBeFalse()
        ->and($check('NoSymbols12345AB'))->toBeFalse();
});

it('creates an administrator with admin:create and a hidden, strong password', function () {
    $this->artisan('admin:create', ['--email' => 'New@Example.test', '--name' => 'Nama Baru'])
        ->expectsQuestion('Password (min. 12 karakter: huruf besar & kecil, angka, simbol)', GOOD_PASSWORD)
        ->expectsQuestion('Ulangi password', GOOD_PASSWORD)
        ->assertSuccessful();

    $user = User::query()->where('email', 'new@example.test')->firstOrFail();

    expect($user->is_admin)->toBeTrue()
        ->and($user->name)->toBe('Nama Baru')
        ->and(password_verify(GOOD_PASSWORD, $user->password))->toBeTrue();
});

it('admin:create refuses weak or mismatching passwords and existing accounts', function () {
    $this->artisan('admin:create', ['--email' => 'weak@example.test', '--name' => 'X'])
        ->expectsQuestion('Password (min. 12 karakter: huruf besar & kecil, angka, simbol)', 'password')
        ->expectsQuestion('Ulangi password', 'password')
        ->assertFailed();

    $this->artisan('admin:create', ['--email' => 'weak@example.test', '--name' => 'X'])
        ->expectsQuestion('Password (min. 12 karakter: huruf besar & kecil, angka, simbol)', GOOD_PASSWORD)
        ->expectsQuestion('Ulangi password', GOOD_PASSWORD.'x')
        ->assertFailed();

    expect(User::query()->where('email', 'weak@example.test')->exists())->toBeFalse();

    adminWith('taken@example.test');
    $this->artisan('admin:create', ['--email' => 'taken@example.test'])->assertFailed();
    $this->artisan('admin:create', ['--email' => 'ghost@example.test', '--reset-password' => true])->assertFailed();
});

it('admin:create --reset-password changes the password of an existing account only', function () {
    $user = adminWith('reset@example.test');

    $this->artisan('admin:create', ['--email' => 'reset@example.test', '--reset-password' => true])
        ->expectsQuestion('Password (min. 12 karakter: huruf besar & kecil, angka, simbol)', 'Another-Str0ng-Pass!')
        ->expectsQuestion('Ulangi password', 'Another-Str0ng-Pass!')
        ->assertSuccessful();

    expect(password_verify('Another-Str0ng-Pass!', $user->refresh()->password))->toBeTrue()
        ->and(User::query()->count())->toBe(1);
});

it('never seeds an administrator in production, with a weak password, or over an existing account', function () {
    config(['site.admin.email' => 'owner@example.test', 'site.admin.password' => 'Owner-Secret-2026!']);

    $seed = fn () => $this->artisan('db:seed', ['--class' => AdminUserSeeder::class, '--force' => true])->assertSuccessful();

    app()['env'] = 'production';
    $seed();
    expect(User::query()->count())->toBe(0);

    app()['env'] = 'testing';
    config(['site.admin.password' => 'password']);
    $seed();
    expect(User::query()->count())->toBe(0);

    config(['site.admin.password' => 'Owner-Secret-2026!']);
    $seed();
    $hash = User::query()->firstOrFail()->password;

    config(['site.admin.password' => 'A-Different-Passw0rd!']);
    $seed();
    expect(User::query()->firstOrFail()->password)->toBe($hash);
});

it('gives administrators a profile page that enforces the strong password rule', function () {
    $this->actingAs(adminWith('profile@example.test'));

    $this->get('/admin/profile')->assertOk();

    Livewire::test(EditProfile::class)
        ->fillForm(['name' => 'Profile', 'email' => 'profile@example.test', 'password' => 'weakpass', 'passwordConfirmation' => 'weakpass', 'currentPassword' => GOOD_PASSWORD])
        ->call('save')
        ->assertHasFormErrors(['password']);
});

// ---------------------------------------------------------------- C-6: admin path

it('takes the admin path from ADMIN_PATH and keeps a custom one out of robots.txt', function () {
    config(['security.admin_path' => 'beranda-x']);

    $panel = (new AdminPanelProvider(app()))->panel(Panel::make());
    expect($panel->getPath())->toBe('beranda-x');

    $robots = $this->get('/robots.txt')->getContent();
    expect($robots)->not->toContain('beranda-x')->not->toContain('Disallow: /admin')->toContain('Disallow: /livewire');

    config(['security.admin_path' => 'admin']);
    expect($this->get('/robots.txt')->getContent())->toContain('Disallow: /admin');
});

it('marks the admin pages noindex', function () {
    expect($this->get('/admin/login')->headers->get('X-Robots-Tag'))->toContain('noindex')
        ->and($this->get('/')->headers->has('X-Robots-Tag'))->toBeFalse();
});

// ---------------------------------------------------------------- C-5: Turnstile

function turnstileOn(): void
{
    config(['security.turnstile' => ['enabled' => true, 'site_key' => '1x00000000000000000000AA', 'secret_key' => '1x0000000000000000000000000000000AA']]);
}

it('lets everything through when Turnstile is off, misconfigured, or the environment is local', function () {
    Http::fake();

    expect(Turnstile::passes(null))->toBeTrue();

    config(['security.turnstile' => ['enabled' => true, 'site_key' => null, 'secret_key' => null]]);
    expect(Turnstile::passes(null))->toBeTrue();

    turnstileOn();
    app()['env'] = 'local';
    expect(Turnstile::passes(null))->toBeTrue();

    Http::assertNothingSent();
});

it('verifies the Turnstile token with Cloudflare and refuses a missing or rejected one', function () {
    turnstileOn();

    expect(Turnstile::passes(null))->toBeFalse()->and(Turnstile::passes(''))->toBeFalse();

    Http::fake(['challenges.cloudflare.com/*' => Http::sequence()
        ->push(['success' => true])
        ->push(['success' => false, 'error-codes' => ['invalid-input-response']])]);

    expect(Turnstile::passes('good-token', '203.0.113.9'))->toBeTrue();
    Http::assertSent(fn ($request) => $request['secret'] === '1x0000000000000000000000000000000AA'
        && $request['response'] === 'good-token' && $request['remoteip'] === '203.0.113.9');

    expect(Turnstile::passes('bad-token'))->toBeFalse();
});

it('fails open, with a log line, when Cloudflare is unreachable, erroring, or our secret key is wrong', function () {
    turnstileOn();
    Log::spy();

    Http::fake(['challenges.cloudflare.com/*' => Http::sequence()
        ->pushFailedConnection()
        ->pushStatus(503)
        ->push(['success' => false, 'error-codes' => ['invalid-input-secret']])]);

    expect(Turnstile::passes('token'))->toBeTrue()
        ->and(Turnstile::passes('token'))->toBeTrue()
        ->and(Turnstile::passes('token'))->toBeTrue();

    Log::shouldHaveReceived('warning')->atLeast()->once();
});

it('puts the Turnstile widget on the contact form only when it is on, and refuses a bad token', function () {
    $this->get('/')->assertDontSee('cf-turnstile', false);

    turnstileOn();
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('class="cf-turnstile"')->toContain('data-sitekey="1x00000000000000000000AA"')
        ->toContain('challenges.cloudflare.com/turnstile/v0/api.js');

    $lead = ['name' => 'Budi', 'email' => 'budi@klien.test', 'message' => 'Butuh cold storage untuk 20 pallet.'];

    Http::fake(['challenges.cloudflare.com/*' => Http::sequence()
        ->push(['success' => false])
        ->push(['success' => false])
        ->push(['success' => true])]);

    $this->postJson('/contact', $lead + ['cf-turnstile-response' => 'bad'])->assertStatus(422)->assertJsonStructure(['message']);
    $this->post('/contact', $lead + ['cf-turnstile-response' => 'bad'])->assertRedirect()->assertSessionHas('contact_error');
    expect(ContactMessage::query()->count())->toBe(0);

    $this->postJson('/contact', $lead + ['cf-turnstile-response' => 'good'])->assertOk();
    expect(ContactMessage::query()->count())->toBe(1);
});

it('asks for Turnstile on the admin login when it is on', function () {
    turnstileOn();
    adminWith();
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

    tryLogin('owner@example.test', GOOD_PASSWORD)->assertHasErrors(['data.turnstile']);
    expect(auth()->check())->toBeFalse();

    tryLogin('owner@example.test', GOOD_PASSWORD, ['turnstile' => 'good'])->assertRedirect();
    expect(auth()->check())->toBeTrue();
});

it('keeps the leads list reachable for an administrator (regression for the policy)', function () {
    $this->actingAs(adminWith())->get(ContactMessageResource::getUrl('index'))->assertOk();
});
