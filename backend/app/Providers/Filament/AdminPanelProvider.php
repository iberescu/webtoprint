<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\OverviewStats;
use App\Filament\Widgets\RecentProducts;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Spec §16 admin panel. Resources for each entity are discovered from
 * modules/<Module>/Filament/Resources by Filament. Branding follows the
 * storefront's CloudLab navy (#2D5096) so the admin and customer-facing
 * surfaces feel like one product.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            // -------- branding ----------------------------------------------
            ->brandName('Web-to-Print')
            ->brandLogo(fn () => Blade::render(<<<'BLADE'
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-primary-600 text-white shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0H6.34m0 0-1.59-9.357c-.103-.605.354-1.143.964-1.143h11.572c.61 0 1.067.538.964 1.143L17.66 18z" /></svg>
                    </span>
                    <div class="flex flex-col leading-tight">
                        <span class="text-base font-extrabold tracking-tight text-primary-700 dark:text-primary-300">Web-to-Print</span>
                        <span class="text-[10px] font-medium uppercase tracking-widest text-gray-400">Admin console</span>
                    </div>
                </div>
            BLADE))
            ->favicon(asset('favicon.ico'))
            ->colors([
                // CloudLab navy — matches storefront brand-* scale.
                'primary' => [
                    50  => '#eef2fb',
                    100 => '#d6deef',
                    200 => '#abbcdf',
                    300 => '#7e99cf',
                    400 => '#5478bf',
                    500 => '#2D5096',
                    600 => '#264478',
                    700 => '#1f3661',
                    800 => '#172847',
                    900 => '#0f1a30',
                    950 => '#080f1c',
                ],
                'gray'    => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
                'info'    => Color::Sky,
            ])
            ->font('Inter')
            ->sidebarCollapsibleOnDesktop()
            // -------- nav groups --------------------------------------------
            ->navigationGroups([
                NavigationGroup::make('PIM')->icon('heroicon-o-cube'),
                NavigationGroup::make('Pricing')->icon('heroicon-o-banknotes'),
                NavigationGroup::make('Production')->icon('heroicon-o-printer'),
                NavigationGroup::make('Templates')->icon('heroicon-o-document-duplicate'),
                NavigationGroup::make('Settings')->icon('heroicon-o-cog-6-tooth'),
            ])
            // -------- footer hook -------------------------------------------
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => Blade::render(<<<'BLADE'
                    <div class="px-6 py-3 text-center text-xs text-gray-400">
                        Web-to-Print platform · {{ now()->year }}
                        ·
                        <a href="/api/documentation" class="hover:text-primary-500">API docs</a>
                        ·
                        <a href="/horizon" class="hover:text-primary-500">Queue</a>
                    </div>
                BLADE),
            )
            // -------- auth + discovery --------------------------------------
            ->authGuard('web')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverResources(in: base_path('modules/PIM/Filament/Resources'), for: 'Modules\\PIM\\Filament\\Resources')
            ->discoverResources(in: base_path('modules/Pricing/Filament/Resources'), for: 'Modules\\Pricing\\Filament\\Resources')
            ->discoverResources(in: base_path('modules/Designer/Filament/Resources'), for: 'Modules\\Designer\\Filament\\Resources')
            ->discoverResources(in: base_path('modules/Distribution/Filament/Resources'), for: 'Modules\\Distribution\\Filament\\Resources')
            ->discoverResources(in: base_path('modules/Templates/Filament/Resources'), for: 'Modules\\Templates\\Filament\\Resources')
            ->discoverResources(in: base_path('modules/Settings/Filament/Resources'), for: 'Modules\\Settings\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                OverviewStats::class,
                RecentProducts::class,
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
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
