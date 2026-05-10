<?php

namespace App\Http\Requests\Api\Application\Maps;

use App\Http\Requests\Api\Application\ApplicationApiRequest;
use App\Models\Map;
use App\Services\Acl\Api\AdminAcl;

class GetMapsRequest extends ApplicationApiRequest
{
    protected ?string $resource = Map::RESOURCE_NAME;

    protected int $permission = AdminAcl::READ;
}
