<?php

use App\Models\ContactMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    seedSite();
    Carbon::setTestNow(Carbon::parse('2026-10-01 10:00:00'));
});

afterEach(fn () => Carbon::setTestNow());

function leadAged(string $ago, string $name): ContactMessage
{
    $lead = ContactMessage::query()->create([
        'name' => $name, 'email' => strtolower($name).'@klien.test', 'message' => 'Butuh cold storage untuk 20 pallet.',
        'locale' => 'id', 'ip' => '203.0.113.9', 'user_agent' => 'Mozilla/5.0 Test',
    ]);
    $lead->forceFill(['created_at' => Carbon::parse($ago)])->saveQuietly();

    return $lead->refresh();
}

it('erases IP and user-agent after 60 days and deletes the lead after 12 months (H-3)', function () {
    $fresh = leadAged('2026-09-21', 'Fresh');        // 10 days
    $old = leadAged('2026-07-30', 'Old');            // 63 days
    $ancient = leadAged('2025-09-01', 'Ancient');    // 13 months

    $this->artisan('leads:prune')->expectsOutputToContain('Dihapus: 1, IP/user-agent dihapus: 1')->assertSuccessful();

    expect(ContactMessage::query()->find($ancient->id))->toBeNull();

    expect($fresh->refresh())->ip->toBe('203.0.113.9')->user_agent->toBe('Mozilla/5.0 Test');

    $old->refresh();
    expect($old->ip)->toBeNull()->and($old->user_agent)->toBeNull()
        ->and($old->name)->toBe('Old')->and($old->email)->toBe('old@klien.test')->and($old->message)->not->toBeEmpty();
});

it('is safe to run again and again', function () {
    leadAged('2026-07-30', 'Old');
    leadAged('2025-09-01', 'Ancient');

    $this->artisan('leads:prune')->assertSuccessful();
    $this->artisan('leads:prune')->expectsOutputToContain('Dihapus: 0, IP/user-agent dihapus: 0')->assertSuccessful();

    expect(ContactMessage::query()->count())->toBe(1);
});

it('changes nothing with --dry-run, and each step can be switched off with 0', function () {
    $old = leadAged('2026-07-30', 'Old');
    $ancient = leadAged('2025-09-01', 'Ancient');

    $this->artisan('leads:prune', ['--dry-run' => true])->expectsOutputToContain('[dry-run] Dihapus: 1, IP/user-agent dihapus: 1')->assertSuccessful();
    expect(ContactMessage::query()->count())->toBe(2)->and($old->refresh()->ip)->not->toBeNull();

    config(['site.leads.delete_after_months' => 0]);
    $this->artisan('leads:prune')->assertSuccessful();
    expect(ContactMessage::query()->find($ancient->id))->not->toBeNull()->and($old->refresh()->ip)->toBeNull();

    $fresh = leadAged('2026-07-30', 'Another');
    config(['site.leads.anonymize_after_days' => 0]);
    $this->artisan('leads:prune')->assertSuccessful();
    expect($fresh->refresh()->ip)->not->toBeNull();
});

it('is on the scheduler, and the periods come from .env with the agreed defaults', function () {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toContain('leads:prune');

    $site = require base_path('config/site.php');
    expect($site['leads'])->toBe(['anonymize_after_days' => 60, 'delete_after_months' => 12]);
});
