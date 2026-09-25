<?php

use Illuminate\Support\Facades\Blade;

/**
 * Ported React Bits components (splitText, animatedList, spotlightCard): the
 * server-rendered HTML always holds the full content, the preview page is local.
 */
it('serves the components preview only on a local machine', function () {
    expect(app('router')->has('dev.components'))->toBe(app()->environment('local'));
});

it('keeps the full title in the HTML when a section heading splits it', function () {
    $html = Blade::render('<x-ui.section-heading split level="3" eyebrow="FAQ" title="Pertanyaan yang sering diajukan" description="Jawaban singkat." />');

    expect($html)
        ->toContain('<h3')
        ->toContain('x-data="splitText" data-split-text="h3"')
        ->toContain('>Pertanyaan yang sering diajukan</h3>')
        // The eyebrow and description fade in on their own, the wrapper does not.
        ->and(substr_count($html, 'x-data="animatedContent"'))->toBe(2)
        ->and($html)->not->toContain('split-word');
});

it('leaves section headings without split as they were', function () {
    $html = Blade::render('<x-ui.section-heading title="Layanan kami" />');

    expect($html)
        ->toContain('>Layanan kami</h2>')
        ->not->toContain('splitText')
        ->and(substr_count($html, 'x-data="animatedContent"'))->toBe(1);
});

it('hides animated content only under the js-anim class (full content without JS)', function () {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    preg_match_all('/^([^{}\n]*(?:split-text|animated-list)[^{}\n]*)\{[^}]*opacity:\s*0;/m', $css, $hiding);

    expect($hiding[1])->not->toBeEmpty();

    foreach ($hiding[1] as $selector) {
        expect(trim($selector))->toStartWith('.js-anim ');
    }

    // Spotlight hover effects only exist for devices that hover, without reduced motion.
    expect($css)->toContain('@media (hover: hover) and (prefers-reduced-motion: no-preference) {'."\n".'    .spotlight-card {');
});
