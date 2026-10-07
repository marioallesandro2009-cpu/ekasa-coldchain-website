<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\GoogleSheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/**
 * Karyawan keluar: mencabut akses admin (panel, sesi, penerima pesan), tanggal berakhir, dan perintah
 * admin:revoke. Email dan Excel yang sudah diterima di kotak surat orang itu di luar jangkauan situs.
 */
beforeEach(fn () => seedSite());

function anAdmin(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

it('closes the panel and the leads to a revoked administrator at once', function () {
    $admin = anAdmin();
    $leaver = anAdmin(['email' => 'koko@perusahaan.test']);

    $this->actingAs($leaver)->get('/admin/contact-messages')->assertOk();
    auth()->logout();

    $leaver->revokeAccess($admin);
    $leaver->refresh();

    expect($leaver->hasAdminAccess())->toBeFalse()
        ->and($leaver->accessStatus())->toBe(User::ACCESS_REVOKED)
        ->and($leaver->access_revoked_at)->not->toBeNull();

    $this->actingAs($leaver)->get('/admin')->assertForbidden();
    $this->actingAs($leaver)->get('/admin/contact-messages')->assertForbidden();

    $lead = ContactMessage::query()->create(['name' => 'Budi', 'email' => 'budi@klien.test', 'message' => 'Butuh cold storage 20 pallet.', 'locale' => 'id']);
    expect(Gate::forUser($leaver)->denies('view', $lead))->toBeTrue()
        ->and(Gate::forUser($leaver)->denies('viewAny', ContactMessage::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $lead))->toBeTrue();
});

it('ends the login sessions and the remember token of the revoked account, and only of that account', function () {
    $admin = anAdmin();
    $leaver = anAdmin(['remember_token' => 'token-lama']);

    foreach ([['a', $leaver->id], ['b', $leaver->id], ['c', $admin->id]] as [$id, $userId]) {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'ip_address' => '127.0.0.1', 'user_agent' => 'x', 'payload' => 'x', 'last_activity' => time()]);
    }

    $leaver->revokeAccess($admin);

    expect(DB::table('sessions')->where('user_id', $leaver->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $admin->id)->count())->toBe(1)
        ->and($leaver->fresh()->remember_token)->toBeNull();
});

it('takes the email of the revoked account off the recipient list, ignoring case, and leaves the others', function () {
    $admin = anAdmin();
    $leaver = anAdmin(['email' => 'koko@perusahaan.test']);
    SiteSetting::current()->update(['contact_recipients' => ['leads@perusahaan.test', 'KOKO@Perusahaan.test', 'cs@perusahaan.test']]);

    expect($leaver->revokeAccess($admin))->toBeTrue()
        ->and(SiteSetting::current()->contactRecipients())->toBe(['leads@perusahaan.test', 'cs@perusahaan.test']);

    $other = anAdmin(['email' => 'bukan-penerima@perusahaan.test']);
    expect($other->revokeAccess($admin))->toBeFalse()
        ->and(SiteSetting::current()->contactRecipients())->toBe(['leads@perusahaan.test', 'cs@perusahaan.test']);
});

it('gives the access back, but not the place on the recipient list', function () {
    $admin = anAdmin();
    $leaver = anAdmin(['email' => 'koko@perusahaan.test']);
    SiteSetting::current()->update(['contact_recipients' => ['leads@perusahaan.test', 'koko@perusahaan.test']]);
    $leaver->revokeAccess($admin);

    $leaver->restoreAccess($admin);

    expect($leaver->fresh()->hasAdminAccess())->toBeTrue()
        ->and(SiteSetting::current()->contactRecipients())->toBe(['leads@perusahaan.test']);

    $this->actingAs($leaver->fresh())->get('/admin')->assertOk();
});

it('ends the access by itself on the end date', function () {
    $future = anAdmin(['access_expires_at' => now()->addDay()]);
    $past = anAdmin(['access_expires_at' => now()->subMinute()]);

    expect($future->accessStatus())->toBe(User::ACCESS_ACTIVE)
        ->and($past->accessStatus())->toBe(User::ACCESS_EXPIRED)
        ->and($past->hasAdminAccess())->toBeFalse();

    $this->actingAs($past)->get('/admin')->assertForbidden();
    auth()->logout();
    $this->actingAs($future)->get('/admin')->assertOk();
});

it('never locks the last working administrator or the person acting out', function () {
    $only = anAdmin();

    expect($only->accessChangeBlockedReason(null))->toContain('satu-satunya admin');
    expect($only->accessChangeBlockedReason($only))->toContain('akun Anda sendiri');

    $second = anAdmin();

    expect($second->accessChangeBlockedReason($only))->toBeNull()
        ->and($only->accessChangeBlockedReason($second))->toBeNull();

    // An expired or revoked colleague is not "a working administrator".
    $second->revokeAccess($only);
    expect($only->fresh()->accessChangeBlockedReason(null))->toContain('satu-satunya admin');
});

it('lets an administrator revoke, restore and limit accounts from the panel', function () {
    $admin = anAdmin();
    $leaver = anAdmin(['email' => 'koko@perusahaan.test']);
    SiteSetting::current()->update(['contact_recipients' => ['leads@perusahaan.test', 'koko@perusahaan.test']]);

    $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('Akun Admin')->assertSee('koko@perusahaan.test');

    Livewire::test(ListUsers::class)->callTableAction('revoke', $leaver);
    expect($leaver->fresh()->accessStatus())->toBe(User::ACCESS_REVOKED)
        ->and(SiteSetting::current()->contactRecipients())->toBe(['leads@perusahaan.test']);

    Livewire::test(ListUsers::class)->callTableAction('restore', $leaver);
    expect($leaver->fresh()->hasAdminAccess())->toBeTrue();

    $date = now('Asia/Jakarta')->addDays(10)->toDateString();
    Livewire::test(ListUsers::class)->callTableAction('expiry', $leaver, ['access_expires_at' => $date]);
    expect($leaver->fresh()->access_expires_at->timezone('Asia/Jakarta')->toDateString())->toBe($date)
        ->and($leaver->fresh()->hasAdminAccess())->toBeTrue();

    Livewire::test(ListUsers::class)->callTableAction('expiry', $leaver, ['access_expires_at' => null]);
    expect($leaver->fresh()->access_expires_at)->toBeNull();
});

it('refuses from the panel to revoke yourself or the last administrator', function () {
    $admin = anAdmin();

    $this->actingAs($admin);
    Livewire::test(ListUsers::class)->callTableAction('revoke', $admin);

    expect($admin->fresh()->hasAdminAccess())->toBeTrue();
});

it('keeps accounts that are not administrators away from the account list', function () {
    $this->actingAs(User::factory()->nonAdmin()->create())->get('/admin/users')->assertForbidden();
    auth()->logout();

    $revoked = anAdmin();
    $revoked->revokeAccess(anAdmin());
    $this->actingAs($revoked)->get('/admin/users')->assertForbidden();
});

it('revokes and restores from the command line, with a confirmation', function () {
    $admin = anAdmin(['email' => 'admin@perusahaan.test']);
    $leaver = anAdmin(['email' => 'Koko@Perusahaan.test']);
    SiteSetting::current()->update(['contact_recipients' => ['koko@perusahaan.test', 'leads@perusahaan.test']]);

    $this->artisan('admin:revoke', ['email' => 'tidak-ada@perusahaan.test'])->expectsOutputToContain('tidak ditemukan')->assertFailed();

    $this->artisan('admin:revoke', ['email' => 'koko@perusahaan.test'])
        ->expectsConfirmation('Cabut akses admin koko@perusahaan.test? Sesi login-nya berakhir dan emailnya dihapus dari daftar penerima pesan.', 'no')
        ->assertFailed();
    expect($leaver->fresh()->hasAdminAccess())->toBeTrue();

    $this->artisan('admin:revoke', ['email' => 'koko@perusahaan.test', '--force' => true])->expectsOutputToContain('dicabut')->assertSuccessful();
    expect($leaver->fresh()->accessStatus())->toBe(User::ACCESS_REVOKED)
        ->and(SiteSetting::current()->contactRecipients())->toBe(['leads@perusahaan.test']);

    $this->artisan('admin:revoke', ['email' => 'koko@perusahaan.test', '--force' => true])->expectsOutputToContain('sudah dicabut')->assertSuccessful();
    $this->artisan('admin:revoke', ['email' => 'admin@perusahaan.test', '--force' => true])->expectsOutputToContain('satu-satunya admin')->assertFailed();

    $this->artisan('admin:revoke', ['email' => 'koko@perusahaan.test', '--restore' => true])->expectsOutputToContain('dipulihkan')->assertSuccessful();
    expect($leaver->fresh()->hasAdminAccess())->toBeTrue()
        ->and($admin->fresh()->hasAdminAccess())->toBeTrue();
});

it('takes the Google Sheet away from whoever is removed from the recipient list in Pengaturan Situs', function () {
    config(['services.google_sheets.url' => 'https://script.google.test/exec', 'services.google_sheets.secret' => 's3cret']);
    Http::fake(['script.google.test/*' => Http::response(['ok' => true, 'revoked' => true])]);

    $this->actingAs(anAdmin());
    SiteSetting::current()->update(['contact_recipients' => ['leads@perusahaan.test', 'Koko@Gmail.test', 'cs@perusahaan.test']]);

    // Koko is removed (the list is edited with another case); a new address is added; the others stay.
    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['contact_recipients' => "leads@perusahaan.test\ncs@perusahaan.test\nbaru@perusahaan.test"])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(SiteSetting::current()->contactRecipients())->toBe(['leads@perusahaan.test', 'cs@perusahaan.test', 'baru@perusahaan.test']);

    Http::assertSentCount(1);
    Http::assertSent(fn ($request): bool => $request->url() === 'https://script.google.test/exec'
        && $request['action'] === 'revoke'
        && $request['email'] === 'koko@gmail.test'
        && $request['secret'] === 's3cret');

    // Saving without removing anyone sends nothing.
    Http::fake();
    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['contact_recipients' => "LEADS@perusahaan.test\ncs@perusahaan.test\nbaru@perusahaan.test\ntambahan@perusahaan.test"])
        ->call('save');
    Http::assertNothingSent();
});

it('saves the recipient list even when the Google Sheet cannot be reached or runs an old script', function () {
    config(['services.google_sheets.url' => 'https://script.google.test/exec']);
    // One sequence (a second Http::fake() would not replace the first): old script, server error, old script, new script.
    Http::fake(['script.google.test/*' => Http::sequence()
        ->push(['ok' => true])
        ->push('<html>error</html>', 500)
        ->push(['ok' => true])
        ->push(['ok' => true, 'revoked' => true])]);

    $this->actingAs(anAdmin());
    SiteSetting::current()->update(['contact_recipients' => ['a@perusahaan.test', 'b@perusahaan.test', 'c@perusahaan.test']]);

    Livewire::test(ManageSiteSettings::class)->fillForm(['contact_recipients' => 'a@perusahaan.test'])->call('save')->assertHasNoFormErrors();

    expect(SiteSetting::current()->contactRecipients())->toBe(['a@perusahaan.test']);

    // An old script answers ok but without revoked=true: that is not a success.
    expect(GoogleSheet::revokeAccess('x@perusahaan.test'))->toBeFalse()
        ->and(GoogleSheet::revokeAccess('x@perusahaan.test'))->toBeTrue()
        ->and(GoogleSheet::revokeAccess('bukan-email'))->toBeFalse();
});

it('does not call Google when the Sheet is not connected', function () {
    config(['services.google_sheets.url' => null]);
    Http::fake();

    $this->actingAs(anAdmin());
    SiteSetting::current()->update(['contact_recipients' => ['a@perusahaan.test', 'b@perusahaan.test']]);

    Livewire::test(ManageSiteSettings::class)->fillForm(['contact_recipients' => 'a@perusahaan.test'])->call('save')->assertHasNoFormErrors();

    Http::assertNothingSent();
    expect(anAdmin()->revokeSheetAccess())->toBeNull();
});

it('also closes the Google Sheet when an administrator account is revoked from the panel', function () {
    config(['services.google_sheets.url' => 'https://script.google.test/exec', 'services.google_sheets.secret' => 's3cret']);
    Http::fake(['script.google.test/*' => Http::response(['ok' => true, 'revoked' => true])]);

    $admin = anAdmin();
    $leaver = anAdmin(['email' => 'koko@perusahaan.test']);

    $this->actingAs($admin);
    Livewire::test(ListUsers::class)->callTableAction('revoke', $leaver);

    expect($leaver->fresh()->accessStatus())->toBe(User::ACCESS_REVOKED);
    Http::assertSent(fn ($request): bool => $request['action'] === 'revoke' && $request['email'] === 'koko@perusahaan.test');
});
