<?php

namespace App\Providers\Filament;

use App\Support\FrontendData;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn (): string => self::brandName())
            // Brand scale from resources/css/app.css, one step lighter than Filament's
            // default mapping: Filament draws buttons and links with 600, which is then
            // exactly the site's brand 700 (#006C5E, teal — hue nudged from the
            // original green to sit closer to the real EKASA logo's blue/cyan).
            ->colors([
                'primary' => [
                    50 => '#EEFAF7',
                    100 => '#DFF2EE',
                    200 => '#C0E5DE',
                    300 => '#94D3C7',
                    400 => '#219E8F',
                    500 => '#008475',
                    600 => '#006C5E',
                    700 => '#005146',
                    800 => '#00342D',
                    900 => '#001C18',
                    950 => '#00120F',
                ],
            ])
            ->navigationGroups(['Konten Beranda', 'Data Layanan'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * "<company name> Admin", from Pengaturan Situs (cached front page data).
     */
    private static function brandName(): string
    {
        return FrontendData::settings()['companyName'].' Admin';
    }
}
