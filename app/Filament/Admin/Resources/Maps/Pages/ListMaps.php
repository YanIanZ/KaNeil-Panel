<?php

namespace App\Filament\Admin\Resources\Maps\Pages;

use App\Enums\TablerIcon;
use App\Filament\Admin\Resources\Maps\MapResource;
use App\Filament\Components\Actions\ExportMapAction;
use App\Filament\Components\Actions\ImportMapAction;
use App\Filament\Components\Actions\UpdateMapAction;
use App\Filament\Components\Actions\UpdateMapBulkAction;
use App\Filament\Components\Tables\Filters\TagsFilter;
use App\Models\Map;
use App\Traits\Filament\CanCustomizeHeaderActions;
use App\Traits\Filament\CanCustomizeHeaderWidgets;
use Exception;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ListMaps extends ListRecords
{
    use CanCustomizeHeaderActions;
    use CanCustomizeHeaderWidgets;

    protected static string $resource = MapResource::class;

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        $defaultMapIcon = 'data:image/svg+xml;base64,' . base64_encode(file_get_contents(public_path('kaneil.svg')));

        return $table
            ->searchable(true)
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('id')
                    ->label('Id')
                    ->hidden(),
                ImageColumn::make('icon')
                    ->label('')
                    ->alignCenter()
                    ->circular()
                    ->getStateUsing(fn (Map $record) => $record->icon ?: $defaultMapIcon),
                TextColumn::make('name')
                    ->label(trans('admin/map.name'))
                    ->description(fn ($record): ?string => (strlen($record->description) > 120) ? substr($record->description, 0, 120).'...' : $record->description)
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('servers_count')
                    ->counts('servers')
                    ->label(trans('admin/map.servers')),
            ])
            ->recordActions([
                EditAction::make()
                    ->tooltip(trans('filament-actions::edit.single.label')),
                ExportMapAction::make()
                    ->tooltip(trans('filament-actions::export.modal.actions.export.label')),
                UpdateMapAction::make()
                    ->tooltip(trans_choice('admin/map.update', 1)),
                ReplicateAction::make()
                    ->tooltip(trans('filament-actions::replicate.single.label'))
                    ->modal(false)
                    ->excludeAttributes(['author', 'uuid', 'update_url', 'servers_count', 'created_at', 'updated_at'])
                    ->beforeReplicaSaved(function (Map $replica) {
                        $replica->author = user()?->email;
                        $replica->name .= ' Copy';
                        $replica->uuid = Str::uuid()->toString();
                    })
                    ->after(fn (Map $record, Map $replica) => $record->variables->each(fn ($variable) => $variable->replicate()->fill(['map_id' => $replica->id])->save()))
                    ->successRedirectUrl(fn (Map $replica) => EditMap::getUrl(['record' => $replica])),
            ])
            ->toolbarActions([
                ImportMapAction::make()
                    ->multiple(),
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make('exclude_bulk_delete')
                        ->before(function (Collection &$records) {
                            $mapsWithServers = $records->filter(fn (Map $map) => $map->servers_count > 0);

                            if ($mapsWithServers->isNotEmpty()) {
                                $mapNames = $mapsWithServers->map(fn (Map $map) => sprintf('%s (%d server%s)', $map->name, $map->servers_count, $map->servers_count > 1 ? 's' : ''))
                                    ->join(', ');
                                Notification::make()
                                    ->danger()
                                    ->title(trans('admin/map.cannot_delete', ['count' => $mapsWithServers->count()]))
                                    ->body(trans('admin/map.eggs_have_servers', ['maps' => $mapNames]))
                                    ->send();
                            }

                            $records = $records->filter(fn (Map $map) => $map->servers_count <= 0);

                            if ($records->isEmpty()) {
                                $this->halt();
                            }
                        }),
                    UpdateMapBulkAction::make('exclude_bulk_update')
                        ->before(function (Collection &$records) {
                            $mapsWithoutUpdateUrl = $records->filter(fn (Map $map) => $map->update_url === null);

                            if ($mapsWithoutUpdateUrl->isNotEmpty()) {
                                $mapNames = $mapsWithoutUpdateUrl->pluck('name')->join(', ');

                                Notification::make()
                                    ->warning()
                                    ->title(trans('admin/map.cannot_update', ['count' => $mapsWithoutUpdateUrl->count()]))
                                    ->body(trans('admin/map.no_update_url', ['maps' => $mapNames]))
                                    ->send();
                            }

                            $records = $records->filter(fn (Map $map) => $map->update_url !== null);

                            if ($records->isEmpty()) {
                                $this->halt();
                            }
                        }),
                ]),
            ])
            ->emptyStateIcon(TablerIcon::Maps)
            ->emptyStateDescription('')
            ->emptyStateHeading(trans('admin/map.no_maps'))
            ->filters([
                TagsFilter::make()
                    ->model(Map::class),
            ]);
    }
}
