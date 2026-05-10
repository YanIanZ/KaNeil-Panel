<?php

namespace App\Filament\Components\Actions;

use App\Enums\MapFormat;
use App\Enums\TablerIcon;
use App\Models\Map;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\Alignment;

class ExportMapAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'export';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tooltip(trans('filament-actions::export.modal.actions.export.label'));

        $this->icon(TablerIcon::Download);

        $this->tableIcon(TablerIcon::Download);

        $this->authorize(fn () => user()?->can('export map'));

        $this->modalHeading(fn (Map $map) => trans('filament-actions::export.modal.actions.export.label') . '  ' . $map->name);

        $this->modalIcon($this->icon);

        $this->schema([
            TextEntry::make('label')
                ->hiddenLabel()
                ->state(fn (Map $map) => trans('admin/map.export.modal', ['map' => $map->name])),
        ]);

        $this->modalFooterActionsAlignment(Alignment::Center);

        $this->modalFooterActions([
            Action::make('exclude_json')
                ->label(trans('admin/map.export.as', ['format' => 'json']))
                ->url(fn (Map $map) => route('api.application.maps.maps.export', ['map' => $map, 'format' => MapFormat::JSON->value]), true)
                ->close(),
            Action::make('exclude_yaml')
                ->label(trans('admin/map.export.as', ['format' => 'yaml']))
                ->url(fn (Map $map) => route('api.application.maps.maps.export', ['map' => $map, 'format' => MapFormat::YAML->value]), true)
                ->close(),
        ]);
    }
}
