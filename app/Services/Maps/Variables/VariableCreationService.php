<?php

namespace App\Services\Maps\Variables;

use App\Exceptions\Model\DataValidationException;
use App\Exceptions\Service\Map\Variable\BadValidationRuleException;
use App\Exceptions\Service\Map\Variable\ReservedVariableNameException;
use App\Models\EggVariable;
use App\Traits\Services\ValidatesValidationRules;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class VariableCreationService
{
    use ValidatesValidationRules;

    /**
     * VariableCreationService constructor.
     */
    public function __construct(private ValidationFactory $validator) {}

    /**
     * Return the validation factory instance to be used by rule validation
     * checking in the trait.
     */
    protected function getValidator(): ValidationFactory
    {
        return $this->validator;
    }

    /**
     * Create a new variable for a given Map.
     *
     * @param array{
     *     name?: string,
     *     description?: string,
     *     env_variable?: string,
     *     default_value?: string,
     *     rules?: string|string[],
     * } $data
     *
     * @throws DataValidationException
     * @throws BadValidationRuleException
     * @throws ReservedVariableNameException
     */
    public function handle(int $map, array $data): EggVariable
    {
        if (in_array(strtoupper(array_get($data, 'env_variable')), EggVariable::RESERVED_ENV_NAMES)) {
            throw new ReservedVariableNameException(sprintf('Cannot use the protected name %s for this environment variable.', array_get($data, 'env_variable')));
        }

        if (!empty($data['rules'] ?? [])) {
            $this->validateRules($data['rules']);
        }

        $options = array_get($data, 'options') ?? [];

        /** @var EggVariable $eggVariable */
        $eggVariable = EggVariable::query()->create([
            'map_id' => $map,
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'env_variable' => $data['env_variable'] ?? '',
            'default_value' => $data['default_value'] ?? '',
            'user_viewable' => in_array('user_viewable', $options),
            'user_editable' => in_array('user_editable', $options),
            'rules' => $data['rules'] ?? [],
        ]);

        return $eggVariable;
    }
}
