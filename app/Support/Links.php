<?php

namespace App\Support;

/**
 * Links to the home page sections that also work from other pages: "#contact"
 * on the home page, "/#contact" (or "/id#contact") everywhere else, so the
 * navbar, mega menu and buttons are the same on every page.
 */
final class Links
{
    public static function onHome(): bool
    {
        return request()->routeIs('home', '*.home');
    }

    public static function section(string $id): string
    {
        return (self::onHome() ? '' : Locales::path(Locales::current())).'#'.$id;
    }

    /**
     * Where the #contact form posts to: /contact, /id/kontak.
     */
    public static function contactAction(): string
    {
        $locale = Locales::current();

        return Locales::path($locale, '/'.config("site.locales.{$locale}.segments.contact", 'contact'));
    }

    /**
     * A service: its detail page once published, else its home page anchor.
     *
     * @param  array{url?: string|null, anchor: string}  $service
     */
    public static function service(array $service): string
    {
        return $service['url'] ?? self::section($service['anchor']);
    }
}
