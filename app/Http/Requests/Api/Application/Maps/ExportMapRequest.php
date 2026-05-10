<?php

namespace App\Http\Requests\Api\Application\Maps;

class ExportMapRequest extends GetEggRequest
{
    public function rules(): array
    {
        return [
            'format' => 'nullable|string|in:yaml,json',
        ];
    }
}
