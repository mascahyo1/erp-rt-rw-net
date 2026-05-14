<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

class DisableCsrfForDusk extends VerifyCsrfToken
{
    protected function shouldPassThrough($request): bool
    {
        return true;
    }
}
