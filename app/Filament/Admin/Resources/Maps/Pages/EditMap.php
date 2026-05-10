<?php

namespace App\Filament\Admin\Resources\Maps\Pages;

use App\Enums\EditorLanguages;
use App\Enums\TablerIcon;
use App\Filament\Admin\Resources\Maps\MapResource;
use App\Filament\Components\Actions\DeleteIcon;
use App\Filament\Components\Actions\ExportMapAction;
use App\Filament\Components\Actions\ImportMapAction;
use App\Filament\Components\Actions\UploadIcon;
use App\Filament\Components\Forms\Fields\CopyFrom;
use App\Filament\Components\Forms\Fields\MonacoEditor;
use App\Models\Map;
use App\Models\MapVariable;
use App\Traits\Filament\CanCustomizeHeaderActions;
use App\Traits\Filament\CanCustomizeHeaderWidgets;
use App\Traits\Filament\CanCustomizeTabs;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class EditMap extends EditRecord
{
    use CanCustomizeHeaderActions;
    use CanCustomizeHeaderWidgets;
    use CanCustomizeTabs;

    protected static string $resource = MapResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->tabs($this->getTabs())
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    /** @return Tab[] */
    protected function getDefaultTabs(): array
    {
        return [
            Tab::make('configuration')
                ->label(trans('admin/map.tabs.configuration'))
                ->columns(['default' => 2, 'sm' => 2, 'md' => 4, 'lg' => 6])
                ->icon(TablerIcon::Map)
                ->schema([
                    Grid::make(2)
                        ->columnStart(1)
                        ->schema([
                            Image::make('', 'icon')
                                ->hidden(fn ($record) => !$record->icon)
                                ->url(fn ($record) => $record->icon)
                                ->imageSize(150)
                                ->columnSpanFull()
                                ->alignJustify(),
                            UploadIcon::make(),
                            DeleteIcon::make()
                                ->iconStoragePath(Map::getIconStoragePath()),
                        ]),
                    TextInput::make('name')
                        ->label(trans('admin/map.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(['default' => 2, 'sm' => 2, 'md' => 3, 'lg' => 2])
                        ->helperText(trans('admin/map.name_help')),
                    Textarea::make('description')
                        ->label(trans('admin/map.description'))
                        ->rows(3)
                        ->columnSpan(['default' => 2, 'sm' => 2, 'md' => 4, 'lg' => 3])
                        ->helperText(trans('admin/map.description_help')),
                    TextInput::make('id')
                        ->label(trans('admin/map.map_id'))
                        ->columnSpan(1)
                        ->disabled(),
                    TextInput::make('uuid')
                        ->label(trans('admin/map.egg_uuid'))
                        ->disabled()
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 2])
                        ->helperText(trans('admin/map.uuid_help')),
                    TextInput::make('author')
                        ->label(trans('admin/map.author'))
                        ->required()
                        ->maxLength(255)
                        ->email()
                        ->disabled()
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 2])
                        ->helperText(trans('admin/map.author_help_edit')),
                    Toggle::make('force_outgoing_ip')
                        ->inline(false)
                        ->label(trans('admin/map.force_ip'))
                        ->columnSpan(1)
                        ->hintIcon(TablerIcon::QuestionMark, trans('admin/map.force_ip_help')),
                    KeyValue::make('startup_commands')
                        ->label(trans('admin/map.startup_commands'))
                        ->live()
                        ->columnSpanFull()
                        ->required()
                        ->reorderable()
                        ->addActionLabel(trans('admin/map.add_startup'))
                        ->keyLabel(trans('admin/map.startup_name'))
                        ->valueLabel(trans('admin/map.startup_command'))
                        ->helperText(trans('admin/map.startup_help')),
                    TagsInput::make('file_denylist')
                        ->label(trans('admin/map.file_denylist'))
                        ->placeholder('denied-file.txt')
                        ->helperText(trans('admin/map.file_denylist_help'))
                        ->columnSpan(['default' => 2, 'sm' => 2, 'md' => 2, 'lg' => 3]),
                    TextInput::make('update_url')
                        ->label(trans('admin/map.update_url'))
                        ->url()
                        ->hintIcon(TablerIcon::QuestionMark, trans('admin/map.update_url_help'))
                        ->columnSpan(['default' => 2, 'sm' => 2, 'md' => 2, 'lg' => 3]),
                    TagsInput::make('features')
                        ->label(trans('admin/map.features'))
                        ->columnSpan(['default' => 2, 'sm' => 2, 'md' => 2, 'lg' => 3]),
                    Hidden::make('script_is_privileged')
                        ->helperText('The docker images available to servers using this map.'),
                    TagsInput::make('tags')
                        ->label(trans('admin/map.tags'))
                        ->columnSpan(['default' => 2, 'sm' => 2, 'md' => 2, 'lg' => 3]),
                    KeyValue::make('docker_images')
                        ->label(trans('admin/map.docker_images'))
                        ->live()
                        ->columnSpanFull()
                        ->required()
                        ->reorderable()
                        ->addActionLabel(trans('admin/map.add_image'))
                        ->keyLabel(trans('admin/map.docker_name'))
                        ->valueLabel(trans('admin/map.docker_uri'))
                        ->helperText(trans('admin/map.docker_help')),
                ]),
            Tab::make('process_management')
                ->label(trans('admin/map.tabs.process_management'))
                ->columns()
                ->icon(TablerIcon::ServerCog)
                ->schema([
                    CopyFrom::make('copy_process_from')
                        ->process(),
                    TextInput::make('config_stop')
                        ->label(trans('admin/map.stop_command'))
                        ->maxLength(255)
                        ->helperText(trans('admin/map.stop_command_help')),
                    Textarea::make('config_startup')->rows(10)->json()
                        ->label(trans('admin/map.start_config'))
                        ->helperText(trans('admin/map.start_config_help')),
                    Textarea::make('config_files')->rows(10)->json()
                        ->label(trans('admin/map.config_files'))
                        ->dehydrateStateUsing(fn ($state) => blank($state) ? '{}' : $state)
                        ->helperText(trans('admin/map.config_files_help')),
                    Textarea::make('config_logs')->rows(10)->json()
                        ->label(trans('admin/map.log_config'))
                        ->helperText(trans('admin/map.log_config_help')),
                ]),
            Tab::make('map_variables')
                ->label(trans('admin/map.tabs.map_variables'))
                ->columnSpanFull()
                ->icon(TablerIcon::Variable)
                ->schema([
                    Repeater::make('variables')
                        ->hiddenLabel()
                        ->grid()
                        ->relationship('variables')
                        ->orderColumn()
                        ->collapsible()->collapsed()
                        ->addActionLabel(trans('admin/map.add_new_variable'))
                        ->itemLabel(fn (array $state) => $state['name'])
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $data['default_value'] ??= '';
                            $data['description'] ??= '';
                            $data['rules'] ??= [];
                            $data['user_viewable'] ??= '';
                            $data['user_editable'] ??= '';

                            return $data;
                        })
                        ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                            $data['default_value'] ??= '';
                            $data['description'] ??= '';
                            $data['rules'] ??= [];
                            $data['user_viewable'] ??= '';
                            $data['user_editable'] ??= '';

                            return $data;
                        })
                        ->schema([
                            TextInput::make('name')
                                ->label(trans('admin/map.name'))
                                ->live()
                                ->debounce(750)
                                ->maxLength(255)
                                ->columnSpanFull()
                                ->afterStateUpdated(fn (Set $set, $state) => $set('env_variable', str($state)->trim()->snake()->upper()->toString()))
                                ->unique(modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('map_id', $get('../../id')))
                                ->validationMessages([
                                    'unique' => trans('admin/map.error_unique'),
                                ])
                                ->required(),
                            Textarea::make('description')->label(trans('admin/map.description'))->columnSpanFull(),
                            TextInput::make('env_variable')
                                ->label(trans('admin/map.environment_variable'))
                                ->maxLength(255)
                                ->prefix('{{')
                                ->suffix('}}')
                                ->hintIcon(TablerIcon::Code, fn ($state) => "{{{$state}}}")
                                ->unique(modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('map_id', $get('../../id')))
                                ->rules(MapVariable::getRulesForField('env_variable'))
                                ->validationMessages([
                                    'unique' => trans('admin/map.error_unique'),
                                    'required' => trans('admin/map.error_required'),
                                    '*' => trans('admin/map.error_reserved'),
                                ])
                                ->required(),
                            TextInput::make('default_value')->label(trans('admin/map.default_value')),
                            Fieldset::make(trans('admin/map.user_permissions'))
                                ->schema([
                                    Checkbox::make('user_viewable')->label(trans('admin/map.viewable')),
                                    Checkbox::make('user_editable')->label(trans('admin/map.editable')),
                                ]),
                            TagsInput::make('rules')
                                ->label(trans('admin/map.rules'))
                                ->columnSpanFull()
                                ->reorderable()
                                ->suggestions([
                                    'required',
                                    'nullable',
                                    'string',
                                    'integer',
                                    'numeric',
                                    'boolean',
                                    'alpha',
                                    'alpha_dash',
                                    'alpha_num',
                                    'url',
                                    'email',
                                    'regex:',
                                    'min:',
                                    'max:',
                                    'between:',
                                    'between:1024,65535',
                                    'in:',
                                    'in:true,false',
                                ]),
                        ]),
                ]),
            Tab::make('install_script')
                ->label(trans('admin/map.tabs.install_script'))
                ->columns(3)
                ->icon(TablerIcon::FileDownload)
                ->schema([
                    CopyFrom::make('copy_script_from')
                        ->script(),
                    TextInput::make('script_container')
                        ->label(trans('admin/map.script_container'))
                        ->required()
                        ->maxLength(255)
                        ->placeholder('ghcr.io/kaneil-maps/installers:debian'),
                    Select::make('script_entry')
                        ->label(trans('admin/map.script_entry'))
                        ->selectablePlaceholder(false)
                        ->options([
                            'bash' => 'bash',
                            'ash' => 'ash',
                            '/bin/bash' => '/bin/bash',
                        ])
                        ->required(),
                    MonacoEditor::make('script_install')
                        ->hiddenLabel()
                        ->language(EditorLanguages::shell)
                        ->columnSpanFull(),
                ]),
        ];
    }

    /** @return array<Action|ActionGroup> */
    protected function getDefaultHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (Map $map): bool => $map->servers()->count() > 0)
                ->tooltip(fn (Map $map): string => $map->servers()->count() <= 0 ? trans('filament-actions::delete.single.label') : trans('admin/map.in_use')),
            ExportMapAction::make(),
            ImportMapAction::make()
                ->multiple(false),
            Action::make('save')
                ->hiddenLabel()
                ->action('save')
                ->keyBindings(['mod+s'])
                ->tooltip(trans('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->icon(TablerIcon::DeviceFloppy),
        ];
    }

    public function refreshForm(): void
    {
        $this->fillForm();
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
