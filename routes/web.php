<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SeoController;
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
        ->group(function (): void {
            Route::get('/', HomeController::class)->name('home');
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

// Preview sheet of the icon stock, only on a local machine.
if (app()->environment('local')) {
    Route::view('/dev/icons', 'dev.icons')->name('dev.icons');
}
