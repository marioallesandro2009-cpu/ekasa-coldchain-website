<?php

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Models\ContactMessage;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(fn () => seedSite());

function aLead(): ContactMessage
{
    return ContactMessage::query()->create([
        'name' => 'Budi', 'email' => 'budi@klien.test', 'message' => 'Butuh cold storage untuk 20 pallet.', 'locale' => 'id',
    ]);
}

it('lets an administrator into the panel and keeps every other account out (D-1)', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertOk();
    $this->actingAs(User::factory()->create())->get('/admin/contact-messages')->assertOk();

    auth()->logout();

    $this->actingAs(User::factory()->nonAdmin()->create())->get('/admin')->assertForbidden();
    $this->actingAs(User::factory()->nonAdmin()->create())->get('/admin/contact-messages')->assertForbidden();
    $this->actingAs(User::factory()->nonAdmin()->create())->get('/admin/settings')->assertForbidden();
});

it('protects the leads with a policy, not only with panel access (D-1)', function () {
    $lead = aLead();
    $admin = User::factory()->create();
    $other = User::factory()->nonAdmin()->create();

    foreach (['viewAny', 'deleteAny'] as $ability) {
        expect(Gate::forUser($admin)->allows($ability, ContactMessage::class))->toBeTrue()
            ->and(Gate::forUser($other)->denies($ability, ContactMessage::class))->toBeTrue();
    }

    foreach (['view', 'update', 'delete'] as $ability) {
        expect(Gate::forUser($admin)->allows($ability, $lead))->toBeTrue()
            ->and(Gate::forUser($other)->denies($ability, $lead))->toBeTrue();
    }

    // Nobody creates a lead in the admin; they only come from the public form.
    expect(Gate::forUser($admin)->denies('create', ContactMessage::class))->toBeTrue();

    $this->actingAs($other);
    Livewire::test(ListContactMessages::class)->assertForbidden();
});

it('cannot make an account an administrator through mass assignment (D)', function () {
    $user = User::query()->create(['name' => 'X', 'email' => 'x@example.test', 'password' => 'Secret-Passw0rd!', 'is_admin' => true]);

    expect($user->refresh()->is_admin)->toBeFalse();
});

it('creates accounts without access by default and flags only the seeded administrator (D)', function () {
    $plain = User::query()->create(['name' => 'Plain', 'email' => 'plain@example.test', 'password' => 'Secret-Passw0rd!']);
    expect($plain->refresh()->is_admin)->toBeFalse();

    config(['site.admin.email' => 'owner@example.test', 'site.admin.password' => 'Owner-Secret-2026!']);
    $this->seed(AdminUserSeeder::class);

    expect(User::query()->where('email', 'owner@example.test')->firstOrFail()->is_admin)->toBeTrue();
});
