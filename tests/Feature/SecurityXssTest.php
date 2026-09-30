<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Mail\ContactMessageReceived;
use App\Models\AboutContent;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => seedSite());

const EVIL_HTML = '<p>Halo <strong>dunia</strong></p><script>alert(1)</script><img src="x" onerror="alert(2)"><a href="javascript:alert(3)">klik</a><iframe src="https://evil.test"></iframe>';

it('sanitizes rich text of a blog post before it reaches the page (B-1)', function () {
    $post = Post::query()->published()->firstOrFail();
    $post->update(['content' => ['en' => EVIL_HTML], 'status' => Post::PUBLISHED]);

    $html = $this->get($post->refresh()->detailPath('en'))->assertOk()->getContent();

    expect($html)->toContain('<strong>dunia</strong>')
        ->not->toContain('<script>alert')
        ->not->toContain('onerror')
        ->not->toContain('javascript:alert')
        ->not->toContain('<iframe');
});

it('sanitizes the About story before it reaches the home page (B-1)', function () {
    AboutContent::query()->firstOrFail()->update(['story' => ['en' => EVIL_HTML]]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('<strong>dunia</strong>')
        ->not->toContain('<script>alert(1)')
        ->not->toContain('onerror="alert(2)"')
        ->not->toContain('javascript:alert(3)')
        ->not->toContain('evil.test');
});

it('keeps visitor text in the lead email from becoming links, images or HTML (B-2)', function () {
    $lead = ContactMessage::query()->create([
        'name' => 'Budi | [klik](https://evil.test/nama)',
        'email' => 'budi@klien.test',
        'phone' => '[tel](https://evil.test/telp)',
        'company' => 'PT <b>Klien</b>',
        'message' => "[Klaim hadiah](https://evil.test/login)\n![p](https://evil.test/pixel.png)\n<script>alert(1)</script> <a href=\"https://evil.test/a\">x</a> **tebal**",
        'locale' => 'id',
    ]);

    $html = (new ContactMessageReceived($lead))->render();

    // Literal text such as "[x](https://evil.test)" is fine (it is shown as typed); what must
    // never exist is a real <a>, <img> or <script> element pointing at the visitor's URL.
    expect(preg_match('#<a\s[^>]*href="https://evil\.test#i', $html))->toBe(0)
        ->and(preg_match('#<img\s[^>]*src="https://evil\.test#i', $html))->toBe(0);

    expect($html)
        ->not->toContain('<script>alert')
        ->not->toContain('<b>Klien</b>')
        // The mailto button for the sender's own (validated) address is the only visitor-derived link.
        ->toContain('mailto:budi@klien.test');
});

it('accepts only http(s) social links in the settings (B-3)', function () {
    $this->actingAs(User::factory()->create());

    foreach (['ftp://files.test/x', 'javascript:alert(1)', 'data:text/html,x'] as $bad) {
        Livewire::test(ManageSiteSettings::class)
            ->fillForm(['instagram_url' => $bad])
            ->call('save')
            ->assertHasFormErrors(['instagram_url']);
    }

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['instagram_url' => 'https://www.instagram.com/ekasa', 'linkedin_url' => 'https://www.linkedin.com/company/ekasa'])
        ->call('save')
        ->assertHasNoFormErrors();
});
