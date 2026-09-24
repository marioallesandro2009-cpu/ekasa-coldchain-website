<?php

/**
 * The original icon stock in resources/icons/ekasa: every file follows the
 * drawing rules and every manifest entry has a file (and the other way round).
 */
function iconManifest(): array
{
    return require resource_path('icons/ekasa/manifest.php');
}

it('draws every icon with the shared style rules', function () {
    $files = glob(resource_path('icons/ekasa/*.svg'));

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        $name = basename($file);
        $svg = simplexml_load_file($file);

        expect($svg)->not->toBeFalse("{$name} is not valid XML")
            ->and($svg->getName())->toBe('svg')
            ->and((string) $svg['viewBox'])->toBe('0 0 24 24', $name)
            ->and((string) $svg['fill'])->toBe('none', $name)
            ->and((string) $svg['stroke'])->toBe('currentColor', $name)
            ->and((string) $svg['stroke-width'])->toBe('1.75', $name)
            ->and((string) $svg['stroke-linecap'])->toBe('round', $name)
            ->and((string) $svg['stroke-linejoin'])->toBe('round', $name)
            ->and(preg_match('/^[a-z0-9]+(-[a-z0-9]+)*\.svg$/', $name))->toBe(1, "{$name} is not kebab-case");

        $children = $svg->children();
        expect(count($children))->toBeLessThanOrEqual(6, "{$name} has more than 6 elements");

        foreach ($children as $child) {
            expect($child->getName())->toBeIn(['path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse'], $name)
                ->and($child->attributes()->count())->toBeGreaterThan(0)
                ->and(isset($child['fill']) || isset($child['stroke']) || isset($child['style']) || isset($child['transform']))->toBeFalse("{$name}: styles belong on <svg>");
        }

        // No text, gradients, scripts or external references.
        expect(file_get_contents($file))->not->toMatch('/<(text|linearGradient|radialGradient|script|style|image|use)\b|href=/i');
    }
});

it('lists every icon file in the manifest with labels in both languages', function () {
    $manifest = iconManifest();
    $files = array_map(fn (string $file): string => basename($file, '.svg'), glob(resource_path('icons/ekasa/*.svg')));

    expect(array_keys($manifest['icons']))->toEqualCanonicalizing($files);

    foreach ($manifest['icons'] as $key => [$label, $category]) {
        expect($label)->toHaveCount(2)
            ->and($label[0])->not->toBeEmpty()
            ->and($label[1])->not->toBeEmpty()
            ->and(array_keys($manifest['categories']))->toContain($category);
    }
});

it('serves the preview sheet only on a local machine', function () {
    expect(app('router')->has('dev.icons'))->toBe(app()->environment('local'));
});
