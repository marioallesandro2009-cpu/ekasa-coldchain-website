<?php

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Support\FrontendData;
use App\Support\GoogleSheet;
use App\Support\LeadSpreadsheet;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Permintaan klien: form Kontak menanyakan kategori barang, jumlah (ton), ukuran kontainer (20 atau 40
 * feet) dan negara tujuan; semuanya ikut ke email, Excel, Google Sheet dan admin.
 */
beforeEach(function () {
    seedSite();
    RateLimiter::clear('contact');
    Mail::fake();
    SiteSetting::current()->update(['contact_recipients' => ['sales@gmail.test']]);
    FrontendData::flush();
});

function shipmentLead(array $overrides = []): array
{
    return [
        'name' => 'Budi Santoso',
        'email' => 'budi@klien.test',
        'service_id' => Service::query()->active()->value('id'),
        'message' => 'Kami butuh kirim ikan beku ke luar negeri.',
        ...$overrides,
    ];
}

it('shows the shipment questions in the form, in both languages', function () {
    $en = $this->get('/')->getContent();

    expect($en)->toContain('name="category"')
        ->and($en)->toContain('name="quantity_tons"')
        ->and($en)->toContain('name="container_size"')
        ->and($en)->toContain('name="destination_country"')
        ->and($en)->toContain('Fish &amp; seafood')
        ->and($en)->toContain('20 feet')
        ->and($en)->toContain('40 feet');

    $id = $this->get('/id')->getContent();

    expect($id)->toContain('Ikan &amp; hasil laut')
        ->and($id)->toContain('Ukuran kontainer')
        ->and($id)->toContain('Negara tujuan pengiriman');
});

it('stores the shipment details, reading a decimal comma as a decimal point', function () {
    $this->postJson('/contact', shipmentLead([
        'category' => 'seafood',
        'quantity_tons' => '12,5',
        'container_size' => '40ft',
        'destination_country' => '  Jepang ',
    ]))->assertOk();

    $lead = ContactMessage::query()->firstOrFail();

    expect($lead->category)->toBe('seafood')
        ->and((float) $lead->quantity_tons)->toBe(12.5)
        ->and($lead->container_size)->toBe('40ft')
        ->and($lead->destination_country)->toBe('Jepang')
        ->and($lead->quantityLabel())->toBe('12,5 ton');
});

it('keeps the questions optional', function () {
    $this->postJson('/contact', shipmentLead())->assertOk();

    $lead = ContactMessage::query()->firstOrFail();

    expect($lead->category)->toBeNull()
        ->and($lead->quantity_tons)->toBeNull()
        ->and($lead->container_size)->toBeNull()
        ->and($lead->destination_country)->toBeNull()
        ->and($lead->quantityLabel())->toBeNull();
});

it('rejects a category, a container or a quantity that makes no sense', function () {
    // Five tries at most: the form allows five sends per quarter hour per IP, invalid ones included.
    foreach ([
        ['category', 'hacker'],
        ['container_size', '30ft'],
        ['quantity_tons', 'banyak'],
        ['quantity_tons', '-5'],
        ['quantity_tons', '1000000'],
    ] as [$field, $value]) {
        $this->postJson('/contact', shipmentLead([$field => $value]))->assertStatus(422)->assertJsonValidationErrors([$field]);
    }

    expect(ContactMessage::query()->count())->toBe(0);
});

it('puts the shipment details in the email, the Excel file and the Google Sheet payload', function () {
    $this->postJson('/contact', shipmentLead([
        'category' => 'seafood',
        'quantity_tons' => '12.5',
        'container_size' => '20ft',
        'destination_country' => 'Jepang',
    ]))->assertOk();

    $lead = ContactMessage::query()->firstOrFail();

    $html = (new ContactMessageReceived($lead))->render();
    expect($html)->toContain('Ikan &amp; hasil laut')
        ->and($html)->toContain('12,5 ton')
        ->and($html)->toContain('20 feet')
        ->and($html)->toContain('Jepang');

    $values = array_combine(LeadSpreadsheet::HEADINGS, LeadSpreadsheet::values($lead));
    expect($values['Kategori'])->toBe('Ikan & hasil laut')
        ->and($values['Jumlah (ton)'])->toBe(12.5)
        ->and($values['Kontainer'])->toBe('20 feet')
        ->and($values['Negara tujuan'])->toBe('Jepang');

    // The sheet columns K..N come after the original ten, so an older sheet only needs four new columns.
    expect(array_slice(LeadSpreadsheet::HEADINGS, 0, 10))->toBe(['ID', 'Diterima (WIB)', 'Nama', 'Email', 'Telepon', 'Perusahaan', 'Negara', 'Layanan', 'Pesan', 'Bahasa'])
        ->and(count(LeadSpreadsheet::HEADINGS))->toBe(14);

    $payload = GoogleSheet::payload($lead);
    expect($payload['category'])->toBe('Ikan & hasil laut')
        ->and($payload['quantity_tons'])->toBe(12.5)
        ->and($payload['container'])->toBe('20 feet')
        ->and($payload['destination_country'])->toBe('Jepang');
});
