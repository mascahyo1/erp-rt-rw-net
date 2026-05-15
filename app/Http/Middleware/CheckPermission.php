<?php

namespace App\Http\Middleware;

use App\Enums\Permissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * @param  string  $permission  The permission value, e.g. "admin-saas.list"
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = auth()->user();

        if (! $user) {
            abort(403, 'Authentication required.');
        }

        if (method_exists($user, 'canPermission') && $user->canPermission($permission)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->inertia() || $request->ajax()) {
            abort(403, 'Forbidden: Anda tidak memiliki izin akses.');
        }

        abort(403, 'Forbidden: Anda tidak memiliki izin akses.');
    }
}
