<?php

namespace App\Support;

use Filament\Forms\Components\RichEditor\RichContentRenderer;

/**
 * Output side of the admin's rich text: everything a RichEditor field saved is
 * rendered through Filament's sanitizer (Symfony HtmlSanitizer) before it reaches
 * a `{!! !!}` in a view, so a tampered save request cannot put a script on the site.
 */
final class Html
{
    public static function rich(?string $html): string
    {
        return filled($html) ? RichContentRenderer::make($html)->toHtml() : '';
    }
}
