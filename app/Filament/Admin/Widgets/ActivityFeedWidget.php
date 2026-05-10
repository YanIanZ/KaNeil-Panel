<?php

namespace App\Filament\Admin\Widgets;

use App\Models\ActivityLog;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class ActivityFeedWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(ActivityLog::query()->orderByDesc('timestamp')->limit(50))
            ->columns([
                TextColumn::make('event')
                    ->label('Event')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(80),
                TextColumn::make('timestamp')
                    ->label('Time')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('timestamp', 'desc')
            ->paginated([10, 25, 50]);
    }
}
