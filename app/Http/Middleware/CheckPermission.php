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

    /**
     * Deteksi user berdasarkan guard yang cocok dengan URL prefix.
     * Bila multi-login (beberapa portal dibuka bersamaan), permission
     * dicek terhadap user di portal yang sedang diakses — bukan portal lain.
     */
    private function resolveUser()
    {
        $path = '/' . trim(request()->path(), '/') . '/';

        $guardMap = [
            '/operator-saas/'        => 'admin-saas',
            '/operator-perusahaan/'  => 'admin-company',
            '/karyawan/'             => 'employee',
            '/customer/'             => 'customer',
        ];

        foreach ($guardMap as $prefix => $guard) {
            if (str_starts_with($path, $prefix) && auth()->guard($guard)->check()) {
                return auth()->guard($guard)->user();
            }
        }

        // Fallback: cek semua guard (halaman login, landing, dsb.)
        foreach (['admin-saas', 'admin-company', 'employee', 'customer'] as $guard) {
            if (auth()->guard($guard)->check()) {
                return auth()->guard($guard)->user();
            }
        }

        return auth()->user();
    }
}
