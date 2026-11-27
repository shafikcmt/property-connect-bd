<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnforceAdminMfa;
use App\Support\InitialsAvatarProvider;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/** Developer ERP (ADR-0019): a separate panel so a developer's staff never see the platform admin. */
class ErpPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('erp')
            ->path('erp')
            ->login()
            ->brandName(fn (): string => auth()->user()?->developer?->name ?? 'Developer ERP')
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->navigationGroups(['Projects', 'Sales', 'Money'])
            ->profile()
            ->multiFactorAuthentication(AppAuthentication::make()->recoverable())
            ->colors(['primary' => Color::hex('#0E8F6E'), 'gray' => Color::Slate])
            ->databaseNotifications()
            ->discoverResources(in: app_path('Filament/Erp/Resources'), for: 'App\\Filament\\Erp\\Resources')
            ->discoverPages(in: app_path('Filament/Erp/Pages'), for: 'App\\Filament\\Erp\\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Erp/Widgets'), for: 'App\\Filament\\Erp\\Widgets')
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
                EnforceAdminMfa::class,
            ]);
    }
}
