<?php

use App\Models\ContactMessage;
use Illuminate\Support\Facades\Http;

/**
 * Google Sheet harus terisi dari pesan terlama (ID terkecil) ke terbaru, juga saat beberapa pesan
 * terlambat masuk dan dikirim ulang lewat leads:sync-sheet.
 */
beforeEach(fn () => seedSite());

it('resends the leads that missed the sheet oldest first, by id', function () {
    config(['services.google_sheets.url' => 'https://script.google.test/exec', 'services.google_sheets.secret' => 's3cret']);
    Http::fake(['script.google.test/*' => Http::response(['ok' => true])]);

    // created_at is deliberately in the opposite order of the ids (and two of them tie).
    foreach ([[1, 5], [2, 5], [3, 20]] as [$id, $minutesAgo]) {
        $lead = new ContactMessage(['name' => "Lead {$id}", 'email' => "lead{$id}@klien.test", 'message' => 'Pesan uji urutan Sheet.', 'locale' => 'id']);
        $lead->id = $id;
        $lead->created_at = now()->subMinutes($minutesAgo);
        $lead->updated_at = $lead->created_at;
        $lead->save();
    }

    $this->artisan('leads:sync-sheet')->assertSuccessful();

    $sent = [];
    Http::assertSent(function ($request) use (&$sent): bool {
        $sent[] = $request['id'];

        return true;
    });

    expect($sent)->toBe([1, 2, 3])
        ->and(ContactMessage::query()->whereNull('sheet_synced_at')->count())->toBe(0);
});
