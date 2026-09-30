<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceIndexController;
use App\Http\Middleware\SetLocale;
use App\Support\Locales;
use Illuminate\Support\Facades\Route;

// Public pages in every language: English at the root (/), the others under
// their prefix (/id). Route names: "home" for English, "id.home" for Indonesian.
foreach (Locales::all() as $locale) {
    $prefix = Locales::prefix($locale);

    Route::middleware(SetLocale::class.':'.$locale)
        ->prefix($prefix)
        ->name($prefix === '' ? '' : $prefix.'.')
        ->group(function () use ($locale): void {
            Route::get('/', HomeController::class)->name('home');
            // Services index: /services, /id/layanan. Registered before the detail
            // route below so it is never swallowed as a slug ("services"/"layanan").
            Route::get('/'.config("site.locales.{$locale}.segments.services", 'services'), ServiceIndexController::class)
                ->name('services.index');
            // Service detail page: /services/{slug}, /id/layanan/{slug}.
            Route::get('/'.config("site.locales.{$locale}.segments.services", 'services').'/{slug}', ServiceController::class)
                ->where('slug', '[a-z0-9-]+')
                ->name('services.show');
            // Blog post detail: /blog/{slug}, /id/blog/{slug}. The blog itself has
            // no index page — it's the #blog section on the home page (one-page
            // site); this route only opens a single article's full content.
            Route::get('/'.config("site.locales.{$locale}.segments.blog", 'blog').'/{slug}', BlogController::class)
                ->where('slug', '[a-z0-9-]+')
                ->name('blog.show');
        });
}

// Machine-readable files for search engines and AI crawlers: no language prefix,
// written in the default language (English); the sitemap lists every language.
Route::middleware(SetLocale::class.':'.Locales::default())->group(function (): void {
    Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
    Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
    Route::get('/llms.txt', [SeoController::class, 'llms'])->name('llms');
    Route::get('/site.webmanifest', [SeoController::class, 'manifest'])->name('manifest');
});

// Preview sheets (icon stock, animation components), only on a local machine.
if (app()->environment('local')) {
    Route::view('/dev/icons', 'dev.icons')->name('dev.icons');
    Route::view('/dev/components', 'dev.components')->name('dev.components');
}
