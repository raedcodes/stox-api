<?php

namespace App\Providers\Filament;

use App\Enums\NavigationGroup;
use App\Filament\Business\Auth\BusinessLogin;
use App\Filament\Business\Widgets\DashboardKpisWidget;
use App\Filament\Business\Widgets\ExpiryAlertsWidget;
use App\Filament\Business\Widgets\LowStockWidget;
use App\Filament\Business\Widgets\TopProductsWidget;
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
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class BusinessPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('business')
            ->path('app')
            ->brandName(fn () => tenant('name') ?? 'Stox')
            ->login(BusinessLogin::class)
            ->profile()
            ->viteTheme('resources/css/filament/business/theme.css')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Business/Resources'), for: 'App\Filament\Business\Resources')
            ->discoverPages(in: app_path('Filament/Business/Pages'), for: 'App\Filament\Business\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationGroups(NavigationGroup::class)
            ->widgets([
                DashboardKpisWidget::class,
                TopProductsWidget::class,
                LowStockWidget::class,
                ExpiryAlertsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                InitializeTenancyBySubdomain::class,
                PreventAccessFromCentralDomains::class,
            ])
            ->persistentMiddleware([
                InitializeTenancyBySubdomain::class,
                PreventAccessFromCentralDomains::class,
            ])
            ->resourceCreatePageRedirect('index')
            ->resourceEditPageRedirect('index')
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
