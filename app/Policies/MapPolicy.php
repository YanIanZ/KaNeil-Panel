<?php

namespace App\Policies;

class MapPolicy
{
    use DefaultAdminPolicies;

    protected string $modelName = 'map';
}
