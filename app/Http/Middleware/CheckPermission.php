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
        $user = $this->resolveUser();

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

    private function resolveUser()
    {
        foreach (['web', 'admin-company', 'employee', 'customer'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return auth()->guard($guard)->user();
            }
        }

        return auth()->user();
    }
}
