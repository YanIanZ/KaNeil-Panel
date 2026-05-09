<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Server;
use App\Models\Node;
use App\Models\Map;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServerCountWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Servers', Server::count())
                ->description('Total servers')
                ->descriptionIcon('tabler-server')
                ->color('primary'),
            Stat::make('Nodes', Node::count())
                ->description('Active nodes')
                ->descriptionIcon('tabler-server-2')
                ->color('info'),
            Stat::make('Maps', Map::count())
                ->description('Templates')
                ->descriptionIcon('tabler-map')
                ->color('warning'),
            Stat::make('Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('tabler-users')
                ->color('success'),
        ];
    }
}
