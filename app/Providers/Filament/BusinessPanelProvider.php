<?php

namespace App\Providers\Filament;

use App\Enums\NavigationGroup;
use App\Filament\Business\Auth\BusinessEditProfile;
use Filament\Navigation\NavigationGroup as FilamentNavigationGroup;
use App\Filament\Business\Auth\BusinessLogin;
use App\Filament\Business\Widgets\CurrentLocationWidget;
use App\Filament\Business\Widgets\DashboardKpisWidget;
use App\Filament\Business\Widgets\ExecutiveKpisWidget;
use App\Filament\Business\Widgets\ExpiryAlertsWidget;
use App\Filament\Business\Widgets\LowStockWidget;
use App\Filament\Business\Widgets\MyRegisterSalesWidget;
use App\Filament\Business\Widgets\PendingPurchaseOrdersWidget;
use App\Filament\Business\Widgets\PendingStockTransfersWidget;
use App\Filament\Business\Widgets\RecentCreditSalesWidget;
use App\Filament\Business\Widgets\SalesRevenueTrendChart;
use App\Filament\Business\Widgets\StockExpiryTimelineChart;
use App\Filament\Business\Widgets\TopProductsChart;
use App\Filament\Business\Widgets\TopProductsWidget;
use App\Http\Middleware\SetLocale;
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
            ->brandLogo(fn () => tenant('logo') ? asset('storage/'.tenant('logo')) : null)
            ->brandLogoHeight('2.5rem')
            ->favicon(fn () => tenant('logo') ? asset('storage/'.tenant('logo')) : null)
            ->login(BusinessLogin::class)
            ->authGuard('business')
            ->profile(BusinessEditProfile::class)
            ->viteTheme('resources/css/filament/business/theme.css')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Business/Resources'), for: 'App\Filament\Business\Resources')
            ->discoverPages(in: app_path('Filament/Business/Pages'), for: 'App\Filament\Business\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationGroups(
                // Built manually (rather than passing NavigationGroup::class
                // directly) so each group's label is a lazy closure. Passing
                // the enum class makes Filament resolve ->getLabel() once,
                // eagerly, at panel-boot time — before SetLocale's middleware
                // has run for the request — permanently baking in whatever
                // the default locale was, regardless of the user's actual
                // preference.
                collect(NavigationGroup::cases())
                    ->mapWithKeys(fn (NavigationGroup $case) => [
                        $case->name => FilamentNavigationGroup::make(fn () => $case->getLabel()),
                    ])
                    ->all()
            )
            ->widgets([
                CurrentLocationWidget::class,
                DashboardKpisWidget::class,
                ExecutiveKpisWidget::class,
                SalesRevenueTrendChart::class,
                TopProductsChart::class,
                StockExpiryTimelineChart::class,
                TopProductsWidget::class,
                LowStockWidget::class,
                ExpiryAlertsWidget::class,
                RecentCreditSalesWidget::class,
                MyRegisterSalesWidget::class,
                PendingPurchaseOrdersWidget::class,
                PendingStockTransfersWidget::class,
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
                SetLocale::class,
            ]);
    }
}
