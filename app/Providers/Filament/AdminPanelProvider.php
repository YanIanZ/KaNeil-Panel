<?php

namespace App\Providers\Filament;

use App\Enums\TablerIcon;
use App\Filament\Admin\Pages\ListLogs;
use App\Filament\Admin\Pages\ViewLogs;
use App\Services\Helpers\PluginService;
use Boquizo\FilamentLogViewer\FilamentLogViewerPlugin;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\Support\Colors\Color;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = parent::panel($panel)
            ->id('admin')
            ->path('admin')
            ->homeUrl('/')
            ->brandName('KaNeil')
            ->brandLogo(asset('kaneil.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('kaneil.ico'))
            ->colors([
                'primary' => Color::hex('#D4AF37'),
                'danger' => Color::hex('#8B0000'),
                'success' => Color::hex('#2F5233'),
                'warning' => Color::Amber,
                'info' => Color::Cyan,
                'gray' => Color::Slate,
            ])
            ->viteTheme('resources/css/filament/kaneil/theme.css')
            ->breadcrumbs(false)
            ->sidebarCollapsibleOnDesktop(fn () => !$panel->hasTopNavigation())
            ->userMenuItems([
                Action::make('exit_admin')
                    ->label(fn () => trans('profile.exit_admin'))
                    ->url(fn () => Filament::getPanel('app')->getUrl())
                    ->icon(TablerIcon::ArrowBack),
            ])
            ->navigationGroups([
                NavigationGroup::make('Management')
                    ->collapsible(false),
                NavigationGroup::make('Templates')
                    ->collapsible(false),
                NavigationGroup::make('System'),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->plugins([
                FilamentLogViewerPlugin::make()
                    ->authorize(fn () => user()->can('view panelLog'))
                    ->listLogs(ListLogs::class)
                    ->viewLog(ViewLogs::class)
                    ->navigationLabel(fn () => trans('admin/log.navigation.panel_logs'))
                    ->navigationGroup('System')
                    ->navigationIcon(TablerIcon::FileInfo),
            ]);

        /** @var PluginService $pluginService */
        $pluginService = app(PluginService::class); // @phpstan-ignore myCustomRules.forbiddenGlobalFunctions

        $pluginService->loadPanelPlugins($panel);

        return $panel;
    }
}
