<?php

namespace App\Http\Requests\Api\Application\Maps;

class ExportMapRequest extends GetMapRequest
{
    public function rules(): array
    {
        return [
            'format' => 'nullable|string|in:yaml,json',
        ];
    }
}
