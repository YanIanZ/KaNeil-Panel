<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Base;
use Illuminate\Http\Request;

class GalleonAuthenticate extends Base
{
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('galleon.login');
    }
}