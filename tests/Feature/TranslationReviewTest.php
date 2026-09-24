<?php

use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Widgets\TranslationStatusOverview;
use App\Models\Service;
use App\Models\User;
use App\Support\Translation\Translator;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    seedSite();
    config(['services.translation' => ['driver' => null, 'key' => null, 'url' => null]]);
});

function coldStorage(): Service
{
    return Service::query()->where('slug', 'cold-storage')->firstOrFail();
}

it('shows a checked Indonesian text on /id, and the English text for a draft', function () {
    $service = coldStorage();
    $service->update(['short_description' => ['en' => 'Chilled and frozen rooms', 'id' => 'Ruang dingin dan beku']]);

    // Seeded texts are checked; the new text keeps its "reviewed" status.
    $this->get('/id')->assertSee('Ruang dingin dan beku');

    $service->update(['translation_status' => ['id' => ['title' => 'reviewed', 'short_description' => 'draft']]]);

    $this->get('/id')->assertDontSee('Ruang dingin dan beku')->assertSee('Chilled and frozen rooms');
    expect($service->refresh()->draftTranslations('id'))->toBe(['short_description']);
});

it('hides the draft button when no translation service is configured', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin/services/'.coldStorage()->getRouteKey().'/edit')
        ->assertOk()
        ->assertSee('Sudah dicek')
        ->assertDontSee('Buat draf dari EN');

    expect(Translator::enabled())->toBeFalse();
});

it('drafts a translation with the glossary, which stays hidden on the site until checked', function () {
    config(['services.translation' => ['driver' => 'deepl', 'key' => 'test-key:fx', 'url' => null]]);
    Http::fake([
        'api-free.deepl.com/*' => Http::response(['translations' => [['text' => 'Penyimpanan <keep>Chiller</keep> &amp; <keep>Frozen</keep> untuk <keep>Cold Storage</keep>']]]),
    ]);
    $this->actingAs(User::factory()->create());
    $service = coldStorage();

    $this->get('/admin/services/'.$service->getRouteKey().'/edit')->assertSee('Buat draf dari EN');

    Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
        ->fillForm(['short_description' => ['en' => 'Chiller & frozen storage for Cold Storage']])
        ->callAction(TestAction::make('draftTranslation')->schemaComponent('short_description.id', schema: 'form'))
        ->assertSet('data.short_description.id', 'Penyimpanan Chiller & Frozen untuk Cold Storage')
        ->assertSet('data.translation_status.id.short_description', 'draft')
        ->call('save')
        ->assertHasNoErrors();

    Http::assertSent(fn (Request $request): bool => $request['target_lang'] === 'ID'
        && $request['tag_handling'] === 'xml'
        && $request['text'] === '<keep>Chiller</keep> &amp; <keep>frozen</keep> storage for <keep>Cold Storage</keep>');

    expect($service->refresh()->translationState('short_description', 'id'))->toBe('draft');
    $this->get('/id')->assertSee('Chiller &amp; frozen storage for Cold Storage', false);
});

it('replaces glossary terms that have a fixed translation', function () {
    $glossary = [['en' => 'Supply chain', 'id' => 'rantai pasok'], ['en' => 'Cold Storage', 'id' => null]];

    expect(Translator::protectTerms('End-to-end supply chain & Cold Storage', 'en', 'id', $glossary, 'google'))
        ->toBe('End-to-end <span translate="no">rantai pasok</span> &amp; <span translate="no">Cold Storage</span>')
        ->and(Translator::unprotect('Solusi <span translate="no">rantai pasok</span> &amp; <span translate="no">Cold Storage</span>'))
        ->toBe('Solusi rantai pasok & Cold Storage');
});

it('counts empty and unchecked Indonesian texts on the dashboard', function () {
    coldStorage()->update([
        'title' => ['en' => 'Cold Storage', 'id' => ''],
        'translation_status' => ['id' => ['short_description' => 'draft']],
    ]);
    $this->actingAs(User::factory()->create());

    expect(TranslationStatusOverview::counts('id'))->toBe([1, 1]);

    Livewire::test(TranslationStatusOverview::class)
        ->assertSee('Teks ID kosong')
        ->assertSee('Draf ID perlu dicek')
        ->assertSee('Tandai "Sudah dicek" agar tampil di situs');

    $this->get('/admin')->assertOk()->assertSee('Terjemahan');
});

it('loads the stored review status into the form and saves it back', function () {
    $this->actingAs(User::factory()->create());
    $service = coldStorage();

    Livewire::test(EditService::class, ['record' => $service->getRouteKey()])
        ->assertSet('data.translation_status.id.title', 'reviewed')
        ->set('data.translation_status.id.title', 'draft')
        ->call('save')
        ->assertHasNoErrors();

    expect($service->refresh()->translationState('title', 'id'))->toBe('draft')
        ->and($service->translationState('short_description', 'id'))->toBe('reviewed');
});
