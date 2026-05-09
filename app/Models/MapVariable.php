<?php

namespace App\Models;

use App\Contracts\Validatable;
use App\Traits\HasValidation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $map_id
 * @property string $name
 * @property string $description
 * @property string $env_variable
 * @property string $default_value
 * @property bool $user_viewable
 * @property bool $user_editable
 * @property string[] $rules
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property int|null $sort
 * @property-read Map|null $map
 * @property-read bool $required
 * @property-read Collection<int, ServerVariable> $serverVariable
 * @property-read int|null $server_variable_count
 *
 * @method static \Database\Factories\EggVariableFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable whereDefaultValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable whereEnvVariable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable whereMapId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable whereRules($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable whereUserEditable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MapVariable whereUserViewable($value)
 *
 * @property string|null $server_value This variable is only present on the object if you've loaded this model using the server relationship.
 */
class MapVariable extends Model implements Validatable
{
    use HasFactory;
    use HasValidation { getRules as getValidationRules; }

    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'map_variable';

    /**
     * Reserved environment variable names.
     */
    public const RESERVED_ENV_NAMES = ['P_SERVER_UUID', 'P_SERVER_ALLOCATION_LIMIT', 'SERVER_MEMORY', 'SERVER_IP', 'SERVER_PORT', 'ENV', 'HOME', 'USER', 'STARTUP', 'MODIFIED_STARTUP', 'SERVER_UUID', 'UUID', 'INTERNAL_IP', 'HOSTNAME', 'TERM', 'LANG', 'PWD', 'TZ', 'TIMEZONE'];

    /**
     * The table associated with the model.
     */
    protected $table = 'map_variables';

    /**
     * Fields that are not mass assignable.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /** @var array<string, string[]> */
    public static array $validationRules = [
        'map_id' => ['exists:maps,id'],
        'sort' => ['nullable'],
        'name' => ['required', 'string', 'between:1,255'],
        'description' => ['string'],
        'default_value' => ['string'],
        'user_viewable' => ['boolean'],
        'user_editable' => ['boolean'],
        'rules' => ['array'],
        'rules.*' => ['string'],
    ];

    /**
     * Implement language verification by overriding Eloquence's gather rules function.
     *
     * @return array<string|string[]>
     */
    public static function getRules(): array
    {
        $rules = self::getValidationRules();

        $rules['env_variable'] = ['required', 'alphaDash', 'between:1,255', 'notIn:' . implode(',', MapVariable::RESERVED_ENV_NAMES)];

        return $rules;
    }

    protected $attributes = [
        'user_editable' => 0,
        'user_viewable' => 0,
        'rules' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'map_id' => 'integer',
            'user_viewable' => 'bool',
            'user_editable' => 'bool',
            'rules' => 'array',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function getRequiredAttribute(): bool
    {
        return in_array('required', $this->rules);
    }

    public function map(): HasOne
    {
        return $this->hasOne(Map::class);
    }

    /**
     * Return server variables associated with this variable.
     */
    public function serverVariable(): HasMany
    {
        return $this->hasMany(ServerVariable::class, 'variable_id');
    }
}
