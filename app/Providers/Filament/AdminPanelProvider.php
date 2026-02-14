<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\UserMenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\MenuItem;
use Filament\Navigation\UserMenu;
use Filament\Support\Enums\MaxWidth;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandLogo(asset('images/ivory-logo.svg'))
            ->brandLogoHeight('80px')
            ->login()
            ->profile()
            ->colors([
                'primary' => '#8B1C52',
            ])
            ->defaultThemeMode(ThemeMode::Light)
            ->maxContentWidth(MaxWidth::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\ActivityTimeline::class,
                \App\Filament\Widgets\DashboardStats::class,
                \App\Filament\Widgets\AccountStatsWidget::class,
                \App\Filament\Widgets\RecentClaimsTable::class,
                \App\Filament\Widgets\CSRStatsWidget::class,
            ])
            ->navigationGroups([
                'Search',
                'Accounts & Members',
                'Dental Management',
                'Claims Management',
                'Reports',
                'Imports',
                'Lookup Tables',
                'Settings',
                'System',
            ])

            // ✅ Works with Filament v3 authentication
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn() => Filament::auth()->user()?->name ?? 'Profile')
                    ->icon('heroicon-o-user'),

                'role' => MenuItem::make()
                    ->label(fn() => 'Role: ' . (Filament::auth()->user()?->roles->first()?->name ?? 'N/A'))
                    ->icon('heroicon-o-identification'),

                'version' => MenuItem::make()
                    ->label('Version ' . (\App\Models\SystemVersion::current() ?? config('version.current')))
                    ->icon('heroicon-o-information-circle')
                    ->url('#'),

                'logout' => MenuItem::make()
                    ->label('Log out')
                    ->icon('heroicon-o-arrow-left-on-rectangle'),
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
                \App\Http\Middleware\MustSetPassword::class,
                'web',
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
