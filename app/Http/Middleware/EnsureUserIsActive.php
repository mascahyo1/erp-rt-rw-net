<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                if (! $user->is_active) {
                    Auth::guard($guard)->logout();

                    // Hanya regenerate token — tidak invalidate session
                    // agar multi-login tidak terpengaruh.
                    if ($request->hasSession()) {
                        $request->session()->regenerateToken();
                    }

                    return redirect('/login-' . str_replace(['admin-saas', 'admin-company', 'employee', 'customer'], ['operator-saas', 'perusahaan', 'karyawan', 'pelanggan'], $guard))
                        ->with('error', 'Akun anda dinonaktifkan. Hubungi admin.');
                }
            }
        }

        return $next($request);
    }
}
