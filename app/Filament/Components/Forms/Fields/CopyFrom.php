<?php

namespace App\Filament\Components\Forms\Fields;

use App\Models\Map;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Livewire\Component;

class CopyFrom extends Select
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(trans('admin/map.copy_from'));

        $this->placeholder(trans('admin/map.none'));

        $this->preload();

        $this->searchable();

        $this->live();
    }

    public function process(): static
    {
        $this->helperText(trans('admin/map.copy_from_help'));

        $this->relationship('configFrom', 'name', ignoreRecord: true);

        $this->afterStateUpdated(function ($state, Set $set) {
            $set('copy_script_from', $state);
            if ($state === null) {
                $set('config_stop', '');
                $set('config_startup', '{}');
                $set('config_files', '{}');
                $set('config_logs', '{}');

                return;
            }
            $map = Map::find($state);
            $set('config_stop', $map->config_stop);
            $set('config_startup', $map->config_startup);
            $set('config_files', $map->config_files);
            $set('config_logs', $map->config_logs);
        });

        return $this;
    }

    public function script(): static
    {
        $this->relationship('scriptFrom', 'name', ignoreRecord: true);

        $this->afterStateUpdated(function ($state, Set $set, Component $livewire) {
            if ($state === null) {
                $set('script_container', 'ghcr.io/kaneil-maps/installers:debian');
                $set('script_entry', 'bash');
                $livewire->dispatch('setContent', content: '');

                return;
            }
            $map = Map::find($state);
            $set('script_container', $map->script_container);
            $set('script_entry', $map->script_entry);
            $livewire->dispatch('setContent', content: $map->script_install);
        });

        return $this;
    }
}
