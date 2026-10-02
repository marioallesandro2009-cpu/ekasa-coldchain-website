<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\FrontendData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    seedSite();
    RateLimiter::clear('contact');
    Mail::fake();

    SiteSetting::current()->update(['contact_recipients' => ['sales@gmail.test', 'boss@gmail.test'], 'email' => 'public@ekasa.test']);
    FrontendData::flush();
});

function validLead(array $overrides = []): array
{
    return [
        'name' => 'Budi Santoso',
        'email' => 'budi@klien.test',
        'phone' => '+62 812 3456 7890',
        'company' => 'PT Klien',
        'service_id' => Service::query()->active()->value('id'),
        'message' => 'Kami butuh cold storage untuk 20 pallet frozen.',
        ...$overrides,
    ];
}

it('renders a real HTML form (works without JavaScript) with csrf and a honeypot', function () {
    $html = $this->get('/id')->getContent();

    expect($html)->toContain('method="POST"')
        ->and($html)->toContain('action="/id/kontak"')
        ->and($html)->toContain('name="_token"')
        ->and($html)->toContain('name="website"')
        ->and($html)->toContain('name="phone"')
        ->and($html)->toContain('name="service_id"');

    $this->get('/')->assertSee('action="/contact"', false);
});

it('saves a valid lead and emails every recipient with Reply-To set to the visitor', function () {
    $this->post('/contact', validLead())->assertRedirect('/#contact')->assertSessionHas('contact_status');

    $lead = ContactMessage::query()->firstOrFail();

    expect($lead->name)->toBe('Budi Santoso')
        ->and($lead->email)->toBe('budi@klien.test')
        ->and($lead->locale)->toBe('en')
        ->and($lead->status)->toBe('new')
        ->and($lead->email_failed)->toBeFalse()
        ->and($lead->service_title)->not->toBeNull()
        ->and($lead->ip)->not->toBeNull();

    Mail::assertSent(ContactMessageReceived::class, 2);

    foreach (['sales@gmail.test', 'boss@gmail.test'] as $address) {
        Mail::assertSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail): bool => $mail->hasTo($address)
            && $mail->hasReplyTo('budi@klien.test', 'Budi Santoso'));
    }
});

it('answers fetch requests with JSON and follows the visitor language in the subject', function () {
    $this->postJson('/id/kontak', validLead())->assertOk()->assertJson(['ok' => true]);

    expect(ContactMessage::query()->firstOrFail()->locale)->toBe('id');

    Mail::assertSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail): bool => $mail->hasSubject('Pesan baru dari Budi Santoso'));
});

it('rejects invalid input with clear messages and stores nothing', function () {
    $this->postJson('/id/kontak', ['name' => '', 'email' => 'bukan-email', 'message' => 'pendek'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'message'])
        ->assertJsonPath('errors.email.0', 'Alamat email tidak valid.');

    $this->post('/contact', ['name' => 'A', 'email' => 'nope', 'message' => 'x'])
        ->assertRedirect('/#contact')
        ->assertSessionHasErrors(['email', 'message'], null, 'contact');

    expect(ContactMessage::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

it('silently drops honeypot submissions: looks successful, stores and sends nothing', function () {
    $this->post('/contact', validLead(['website' => 'http://spam.test']))
        ->assertRedirect('/#contact')
        ->assertSessionHas('contact_status');

    expect(ContactMessage::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

it('rate limits the sixth submission from the same IP', function () {
    foreach (range(1, 5) as $i) {
        $this->postJson('/contact', validLead())->assertOk();
    }

    $this->postJson('/contact', validLead())->assertStatus(429)->assertJsonStructure(['message']);
    $this->post('/contact', validLead())->assertRedirect()->assertSessionHas('contact_error');

    expect(ContactMessage::query()->count())->toBe(5);
});

it('keeps the lead and shows success when the mail server fails', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP down'));

    $this->postJson('/contact', validLead())->assertOk()->assertJson(['ok' => true]);

    $lead = ContactMessage::query()->firstOrFail();

    expect($lead->email_failed)->toBeTrue();
});

it('sends to the new recipients after the admin changes the list', function () {
    SiteSetting::current()->update(['contact_recipients' => ['pribadi@gmail.test']]);

    $this->postJson('/contact', validLead())->assertOk();

    Mail::assertSent(ContactMessageReceived::class, 1);
    Mail::assertSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail): bool => $mail->hasTo('pribadi@gmail.test'));
    Mail::assertNotSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail): bool => $mail->hasTo('sales@gmail.test'));
});

it('falls back to the public email while no recipients are set, and flags a lead with nobody to email', function () {
    SiteSetting::current()->update(['contact_recipients' => []]);

    $this->postJson('/contact', validLead())->assertOk();

    Mail::assertSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail): bool => $mail->hasTo('public@ekasa.test'));
    expect(SiteSetting::current()->usesFallbackRecipient())->toBeTrue();

    SiteSetting::current()->update(['email' => null]);
    $this->postJson('/contact', validLead())->assertOk();

    expect(ContactMessage::query()->latest('id')->firstOrFail()->email_failed)->toBeTrue();
});

it('strips line breaks from the name so it cannot inject email headers', function () {
    $this->postJson('/contact', validLead(['name' => "Budi\r\nBcc: evil@x.test"]))->assertOk();

    expect(ContactMessage::query()->firstOrFail()->name)->not->toContain("\n")->not->toContain("\r");
});

it('renders the lead email in Indonesian with the visitor details', function () {
    $lead = ContactMessage::query()->create(validLead() + ['locale' => 'en', 'service_title' => 'Cold Storage']);

    $html = (new ContactMessageReceived($lead))->render();

    expect($html)->toContain('Pesan baru dari website')
        ->and($html)->toContain('Budi Santoso')
        ->and($html)->toContain('budi@klien.test')
        ->and($html)->toContain('Cold Storage')
        ->and($html)->toContain('Kami butuh cold storage');
});

it('lets the admin change the recipient list from Pengaturan Situs and rejects bad addresses', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['contact_recipients' => "baru@gmail.test\n  kedua@gmail.test  \n"])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(SiteSetting::current()->contact_recipients)->toBe(['baru@gmail.test', 'kedua@gmail.test']);

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['contact_recipients' => "ok@gmail.test\nbukan-email"])
        ->call('save')
        ->assertHasFormErrors(['contact_recipients']);
});

it('lists leads in the admin, warns while no recipients are set, and marks a lead as read', function () {
    $this->actingAs(User::factory()->create());
    $lead = ContactMessage::query()->create(validLead() + ['locale' => 'id', 'service_title' => 'Cold Storage']);

    Livewire::test(ListContactMessages::class)
        ->assertCanSeeTableRecords([$lead])
        ->assertDontSee('Belum ada penerima leads diatur');

    SiteSetting::current()->update(['contact_recipients' => []]);

    Livewire::test(ListContactMessages::class)->assertSee('Belum ada penerima leads diatur');

    expect(ContactMessageResource::getNavigationBadge())->toBe('1');

    Livewire::test(ViewContactMessage::class, ['record' => $lead->getKey()])->callAction('mark_read');

    expect($lead->refresh()->status)->toBe('read')
        ->and(ContactMessageResource::getNavigationBadge())->toBeNull();
});

it('keeps every column, filter and action of the leads table', function () {
    $this->actingAs(User::factory()->create());
    $lead = ContactMessage::query()->create(validLead() + ['locale' => 'en']);

    $table = Livewire::test(ListContactMessages::class);

    foreach (['name', 'email', 'message', 'service_title', 'status', 'email_failed', 'locale', 'created_at'] as $column) {
        $table->assertTableColumnExists($column);
    }

    foreach (['status', 'locale', 'service_id', 'email_failed', 'created_at'] as $filter) {
        $table->assertTableFilterExists($filter);
    }

    $table->assertTableActionExists('view')->assertTableBulkActionExists('mark_read')->assertTableBulkActionExists('delete');

    // Bulk "Tandai dibaca" works, and the status filter finds the lead again.
    $table->callTableBulkAction('mark_read', [$lead]);
    expect($lead->refresh()->status)->toBe('read');

    $table->filterTable('status', 'new')->assertCanNotSeeTableRecords([$lead]);
    $table->filterTable('status', 'read')->assertCanSeeTableRecords([$lead]);
});

it('stores the country and shows it in the form, the email and the admin', function () {
    $html = $this->get('/')->getContent();
    expect($html)->toContain('name="country"');

    $this->postJson('/contact', validLead(['country' => ' Singapore ']))->assertOk();

    $lead = ContactMessage::query()->latest('id')->first();
    expect($lead->country)->toBe('Singapore');

    Mail::assertSent(ContactMessageReceived::class, fn (ContactMessageReceived $mail): bool => str_contains($mail->render(), 'Singapore'));
});

it('adds the lead to the Google Sheet when configured, and only marks it synced on success', function () {
    config(['services.google_sheets.url' => 'https://script.google.test/exec', 'services.google_sheets.secret' => 's3cret']);
    Http::fake(['script.google.test/*' => Http::response(['ok' => true])]);

    $this->postJson('/contact', validLead(['country' => 'Malaysia']))->assertOk();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://script.google.test/exec'
        && $request['secret'] === 's3cret'
        && $request['country'] === 'Malaysia'
        && $request['email'] === 'budi@klien.test'
        && ! array_key_exists('ip', $request->data()));

    expect(ContactMessage::query()->latest('id')->first()->sheet_synced_at)->not->toBeNull();
});

it('keeps the lead and answers the visitor when the Google Sheet fails, then retries with leads:sync-sheet', function () {
    config(['services.google_sheets.url' => 'https://script.google.test/exec']);
    // A second Http::fake() would not replace the first stub, so the answers go in one sequence.
    Http::fake(['script.google.test/*' => Http::sequence()->push('<html>login</html>', 200)->push(['ok' => true])]);

    $this->postJson('/contact', validLead())->assertOk()->assertJson(['ok' => true]);

    $lead = ContactMessage::query()->latest('id')->first();
    expect($lead->sheet_synced_at)->toBeNull();
    Mail::assertSent(ContactMessageReceived::class);

    $this->artisan('leads:sync-sheet')->assertSuccessful();

    expect($lead->fresh()->sheet_synced_at)->not->toBeNull();
});

it('does nothing for the Google Sheet when no URL is set', function () {
    config(['services.google_sheets.url' => null]);
    Http::fake();

    $this->postJson('/contact', validLead())->assertOk();

    Http::assertNothingSent();
    $this->artisan('leads:sync-sheet')->assertSuccessful();
});
