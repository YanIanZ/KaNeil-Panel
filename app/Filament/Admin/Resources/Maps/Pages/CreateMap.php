<?php

namespace App\Filament\Admin\Resources\Maps\Pages;

use App\Enums\EditorLanguages;
use App\Enums\TablerIcon;
use App\Filament\Admin\Resources\Maps\MapResource;
use App\Filament\Components\Forms\Fields\CopyFrom;
use App\Filament\Components\Forms\Fields\MonacoEditor;
use App\Models\MapVariable;
use App\Traits\Filament\CanCustomizeHeaderActions;
use App\Traits\Filament\CanCustomizeHeaderWidgets;
use App\Traits\Filament\CanCustomizeTabs;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class CreateMap extends CreateRecord
{
    use CanCustomizeHeaderActions;
    use CanCustomizeHeaderWidgets;
    use CanCustomizeTabs;

    protected static string $resource = MapResource::class;

    protected static bool $canCreateAnother = false;

    /** @return array<Action|ActionGroup> */
    protected function getDefaultHeaderActions(): array
    {
        return [
            Action::make('create')
                ->hiddenLabel()
                ->action('create')
                ->keyBindings(['mod+s'])
                ->tooltip(trans('filament-panels::resources/pages/create-record.form.actions.create.label'))
                ->icon(TablerIcon::FilePlus),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }

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
                ->columns(['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 4])
                ->schema([
                    TextInput::make('name')
                        ->label(trans('admin/map.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 2])
                        ->helperText(trans('admin/map.name_help')),
                    TextInput::make('author')
                        ->label(trans('admin/map.author'))
                        ->maxLength(255)
                        ->required()
                        ->email()
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 2])
                        ->helperText(trans('admin/map.author_help')),
                    Textarea::make('description')
                        ->label(trans('admin/map.description'))
                        ->rows(2)
                        ->columnSpanFull()
                        ->helperText(trans('admin/map.description_help')),
                    KeyValue::make('startup_commands')
                        ->label(trans('admin/map.startup_commands'))
                        ->live()
                        ->columnSpanFull()
                        ->required()
                        ->reorderable()
                        ->addActionLabel(trans('admin/map.add_startup'))
                        ->keyLabel(trans('admin/map.startup_name'))
                        ->keyPlaceholder('Default')
                        ->valueLabel(trans('admin/map.startup_command'))
                        ->valuePlaceholder('java -Xms128M -XX:MaxRAMPercentage=95.0 -jar {{SERVER_JARFILE}}')
                        ->helperText(trans('admin/map.startup_help')),
                    TagsInput::make('file_denylist')
                        ->label(trans('admin/map.file_denylist'))
                        ->placeholder('denied-file.txt')
                        ->helperText(trans('admin/map.file_denylist_help'))
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 2]),
                    TagsInput::make('features')
                        ->label(trans('admin/map.features'))
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 1, 'lg' => 1]),
                    Toggle::make('force_outgoing_ip')
                        ->label(trans('admin/map.force_ip'))
                        ->hintIcon(TablerIcon::QuestionMark, trans('admin/map.force_ip_help')),
                    Hidden::make('script_is_privileged')
                        ->default(1),
                    TagsInput::make('tags')
                        ->label(trans('admin/map.tags'))
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 2]),
                    TextInput::make('update_url')
                        ->label(trans('admin/map.update_url'))
                        ->hintIcon(TablerIcon::QuestionMark, trans('admin/map.update_url_help'))
                        ->columnSpan(['default' => 1, 'sm' => 1, 'md' => 2, 'lg' => 2])
                        ->url(),
                    KeyValue::make('docker_images')
                        ->label(trans('admin/map.docker_images'))
                        ->live()
                        ->columnSpanFull()
                        ->required()
                        ->reorderable()
                        ->addActionLabel(trans('admin/map.add_image'))
                        ->keyLabel(trans('admin/map.docker_name'))
                        ->keyPlaceholder('Java 21')
                        ->valueLabel(trans('admin/map.docker_uri'))
                        ->valuePlaceholder('ghcr.io/kaneil-maps/yolks:java_21')
                        ->helperText(trans('admin/map.docker_help')),
                ]),
            Tab::make('process_management')
                ->label(trans('admin/map.tabs.process_management'))
                ->columns()
                ->schema([
                    CopyFrom::make('copy_process_from')
                        ->process(),
                    TextInput::make('config_stop')
                        ->label(trans('admin/map.stop_command'))
                        ->required()
                        ->maxLength(255)
                        ->helperText(trans('admin/map.stop_command_help')),
                    Textarea::make('config_startup')->rows(10)->json()
                        ->label(trans('admin/map.start_config'))
                        ->default('{}')
                        ->helperText(trans('admin/map.start_config_help')),
                    Textarea::make('config_files')->rows(10)->json()
                        ->label(trans('admin/map.config_files'))
                        ->default('{}')
                        ->helperText(trans('admin/map.config_files_help')),
                    Textarea::make('config_logs')->rows(10)->json()
                        ->label(trans('admin/map.log_config'))
                        ->default('{}')
                        ->helperText(trans('admin/map.log_config_help')),
                ]),
            Tab::make('map_variables')
                ->label(trans('admin/map.tabs.map_variables'))
                ->columnSpanFull()
                ->schema([
                    Repeater::make('variables')
                        ->hiddenLabel()
                        ->addActionLabel(trans('admin/map.add_new_variable'))
                        ->grid()
                        ->relationship('variables')
                        ->orderColumn()
                        ->collapsible()->collapsed()
                        ->columnSpan(2)
                        ->defaultItems(0)
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
                ->schema([
                    CopyFrom::make('copy_script_from')
                        ->script(),
                    TextInput::make('script_container')
                        ->label(trans('admin/map.script_container'))
                        ->required()
                        ->maxLength(255)
                        ->default('ghcr.io/kaneil-maps/installers:debian'),
                    Select::make('script_entry')
                        ->label(trans('admin/map.script_entry'))
                        ->selectablePlaceholder(false)
                        ->default('bash')
                        ->options([
                            'bash' => 'bash',
                            'ash' => 'ash',
                            '/bin/bash' => '/bin/bash',
                        ])
                        ->required(),
                    MonacoEditor::make('script_install')
                        ->label(trans('admin/map.script_install'))
                        ->language(EditorLanguages::shell)
                        ->columnSpanFull()
                        ->lazy(),
                ]),
        ];
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['uuid'] ??= Str::uuid()->toString();

        if (is_array($data['config_startup'])) {
            $data['config_startup'] = json_encode($data['config_startup']);
        }

        if (is_array($data['config_logs'])) {
            $data['config_logs'] = json_encode($data['config_logs']);
        }

        logger()->info('new map', $data);

        return parent::handleRecordCreation($data);
    }
}
