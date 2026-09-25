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
            // exactly the client green #0F6B46 (brand 700 on the site).
            ->colors([
                'primary' => [
                    50 => '#F0FAF4',
                    100 => '#E2F2E8',
                    200 => '#C6E4D3',
                    300 => '#A0D2B6',
                    400 => '#479D73',
                    500 => '#29835A',
                    600 => '#0F6B46',
                    700 => '#015032',
                    800 => '#01341F',
                    900 => '#001C0F',
                    950 => '#001209',
                ],
            ])
            ->navigationGroups(['Konten Beranda'])
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
