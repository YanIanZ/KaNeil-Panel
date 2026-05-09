<?php

namespace App\Models;

use App\Contracts\Validatable;
use App\Exceptions\Service\Map\HasChildrenException;
use App\Exceptions\Service\HasActiveServersException;
use App\Models\Traits\HasIcon;
use App\Traits\HasValidation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $ship_id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $config_from
 * @property string|null $config_stop
 * @property string|null $config_logs
 * @property string|null $config_startup
 * @property string|null $config_files
 * @property string|null $script_install
 * @property bool $script_is_privileged
 * @property string $script_entry
 * @property string $script_container
 * @property int|null $copy_script_from
 * @property string|null $uuid
 * @property string $author
 * @property string[]|null $features
 * @property array<string, string> $docker_images
 * @property string|null $update_url
 * @property string[]|null $file_denylist
 * @property bool $force_outgoing_ip
 * @property string[] $tags
 * @property array<string, string> $startup_commands
 * @property-read Collection<int, Map> $children
 * @property-read int|null $children_count
 * @property-read Map|null $configFrom
 * @property-read string $copy_script_container
 * @property-read string $copy_script_entry
 * @property-read string|null $copy_script_install
 * @property-read string|null $icon
 * @property-read string|null $inherit_config_files
 * @property-read string|null $inherit_config_logs
 * @property-read string|null $inherit_config_startup
 * @property-read string|null $inherit_config_stop
 * @property-read string[]|null $inherit_features
 * @property-read string[]|null $inherit_file_denylist
 * @property-read Collection<int, Mount> $mounts
 * @property-read int|null $mounts_count
 * @property-read Map|null $scriptFrom
 * @property-read Collection<int, Server> $servers
 * @property-read int|null $servers_count
 * @property-read Ship|null $ship
 * @property-read Collection<int, MapVariable> $variables
 * @property-read int|null $variables_count
 *
 * @method static \Database\Factories\EggFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereAuthor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereConfigFiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereConfigFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereConfigLogs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereConfigStartup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereConfigStop($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereCopyScriptFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereDockerImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereFeatures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereFileDenylist($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereForceOutgoingIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereScriptContainer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereScriptEntry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereScriptInstall($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereScriptIsPrivileged($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereShipId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereStartupCommands($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereUpdateUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Map whereUuid($value)
 */
class Map extends Model implements Validatable
{
    use HasFactory;
    use HasIcon;
    use HasValidation;

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal. Also used as name for api key permissions.
     */
    public const RESOURCE_NAME = 'map';

    /**
     * Defines the current map export version.
     */
    public const EXPORT_VERSION = 'PLCN_v3';

    /**
     * The table associated with the model.
     */
    protected $table = 'maps';

    /**
     * Fields that are not mass assignable.
     */
    protected $fillable = [
        'ship_id',
        'uuid',
        'name',
        'author',
        'description',
        'features',
        'docker_images',
        'force_outgoing_ip',
        'file_denylist',
        'config_files',
        'config_startup',
        'config_logs',
        'config_stop',
        'config_from',
        'startup_commands',
        'update_url',
        'script_is_privileged',
        'script_install',
        'script_entry',
        'script_container',
        'copy_script_from',
        'tags',
    ];

    /** @var array<array-key, string[]> */
    public static array $validationRules = [
        'ship_id' => ['required', 'integer', 'exists:ships,id'],
        'uuid' => ['required', 'string', 'size:36'],
        'name' => ['required', 'string', 'max:255'],
        'description' => ['string', 'nullable'],
        'features' => ['array', 'nullable'],
        'author' => ['required', 'string', 'email'],
        'file_denylist' => ['array', 'nullable'],
        'file_denylist.*' => ['string'],
        'docker_images' => ['required', 'array', 'min:1'],
        'docker_images.*' => ['required', 'string'],
        'startup_commands' => ['required', 'array', 'min:1'],
        'startup_commands.*' => ['required', 'string', 'distinct'],
        'config_from' => ['sometimes', 'bail', 'nullable', 'numeric', 'exists:maps,id'],
        'config_stop' => ['required_without:config_from', 'nullable', 'string', 'max:255'],
        'config_startup' => ['required_without:config_from', 'nullable', 'json'],
        'config_logs' => ['required_without:config_from', 'nullable', 'json'],
        'config_files' => ['required_without:config_from', 'nullable', 'json'],
        'update_url' => ['sometimes', 'nullable', 'string'],
        'force_outgoing_ip' => ['sometimes', 'boolean'],
        'tags' => ['array'],
    ];

    protected $attributes = [
        'features' => null,
        'file_denylist' => null,
        'config_stop' => null,
        'config_startup' => null,
        'config_logs' => null,
        'config_files' => null,
        'update_url' => null,
        'tags' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'ship_id' => 'integer',
            'config_from' => 'integer',
            'script_is_privileged' => 'boolean',
            'force_outgoing_ip' => 'boolean',
            'copy_script_from' => 'integer',
            'features' => 'array',
            'docker_images' => 'array',
            'file_denylist' => 'array',
            'startup_commands' => 'array',
            'tags' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $map) {
            $map->uuid ??= Str::uuid()->toString();

            return true;
        });

        static::deleting(function (self $map) {
            throw_if($map->servers()->count(), new HasActiveServersException(trans('exceptions.map.delete_has_servers')));

            throw_if($map->children()->count(), new HasChildrenException(trans('exceptions.map.has_children')));
        });
    }

    /**
     * Returns the install script for the map; if map is copying from another
     * it will return the copied script.
     */
    public function getCopyScriptInstallAttribute(): ?string
    {
        if (!empty($this->script_install) || empty($this->copy_script_from)) {
            return $this->script_install;
        }

        return $this->scriptFrom->script_install;
    }

    /**
     * Returns the entry command for the map; if map is copying from another
     * it will return the copied entry command.
     */
    public function getCopyScriptEntryAttribute(): string
    {
        if (!empty($this->script_entry) || empty($this->copy_script_from)) {
            return $this->script_entry;
        }

        return $this->scriptFrom->script_entry;
    }

    /**
     * Returns the install container for the map; if map is copying from another
     * it will return the copied install container.
     */
    public function getCopyScriptContainerAttribute(): string
    {
        if (!empty($this->script_container) || empty($this->copy_script_from)) {
            return $this->script_container;
        }

        return $this->scriptFrom->script_container;
    }

    /**
     * Return the file configuration for a map.
     */
    public function getInheritConfigFilesAttribute(): ?string
    {
        if (!is_null($this->config_files) || is_null($this->config_from)) {
            return $this->config_files;
        }

        return $this->configFrom->config_files;
    }

    /**
     * Return the startup configuration for a map.
     */
    public function getInheritConfigStartupAttribute(): ?string
    {
        if (!is_null($this->config_startup) || is_null($this->config_from)) {
            return $this->config_startup;
        }

        return $this->configFrom->config_startup;
    }

    /**
     * Return the log reading configuration for a map.
     */
    public function getInheritConfigLogsAttribute(): ?string
    {
        if (!is_null($this->config_logs) || is_null($this->config_from)) {
            return $this->config_logs;
        }

        return $this->configFrom->config_logs;
    }

    /**
     * Return the stop command configuration for a map.
     */
    public function getInheritConfigStopAttribute(): ?string
    {
        if (!is_null($this->config_stop) || is_null($this->config_from)) {
            return $this->config_stop;
        }

        return $this->configFrom->config_stop;
    }

    /**
     * Returns the features available to this map from the parent configuration if there are
     * no features defined for this map specifically and there is a parent map configured.
     *
     * @return ?string[]
     */
    public function getInheritFeaturesAttribute(): ?array
    {
        if (!is_null($this->features) || is_null($this->config_from)) {
            return $this->features;
        }

        return $this->configFrom->features;
    }

    /**
     * Returns the features available to this map from the parent configuration if there are
     * no features defined for this map specifically and there is a parent map configured.
     *
     * @return ?string[]
     */
    public function getInheritFileDenylistAttribute(): ?array
    {
        if (is_null($this->config_from)) {
            return $this->file_denylist;
        }

        return $this->configFrom->file_denylist;
    }

    public function ship(): BelongsTo
    {
        return $this->belongsTo(Ship::class);
    }

    public function mounts(): MorphToMany
    {
        return $this->morphToMany(Mount::class, 'mountable');
    }

    /**
     * Gets all servers associated with this map.
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'map_id');
    }

    /**
     * Gets all variables associated with this map.
     */
    public function variables(): HasMany
    {
        return $this->hasMany(MapVariable::class, 'map_id');
    }

    /**
     * Get the parent map from which to copy scripts.
     */
    public function scriptFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'copy_script_from');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'config_from');
    }

    /**
     * Get the parent map from which to copy configuration settings.
     */
    public function configFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'config_from');
    }

    public function getKebabName(): string
    {
        return str($this->name)->kebab()->lower()->trim()->split('/[^\w\-]/')->join('');
    }
}
