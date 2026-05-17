<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = null;
        $path = '/' . trim($request->path(), '/') . '/';

        // Deteksi guard berdasarkan URL prefix agar multi-login tidak bentrok.
        // Bila user login di banyak portal sekaligus, tiap portal pakai
        // guard-nya sendiri — sidebar + permission sesuai portal yang diakses.
        $guardMap = [
            '/operator-saas/'        => 'admin-saas',
            '/operator-perusahaan/'  => 'admin-company',
            '/karyawan/'             => 'employee',
            '/customer/'             => 'customer',
        ];

        $matchedGuard = null;
        foreach ($guardMap as $prefix => $guard) {
            if (str_starts_with($path, $prefix)) {
                $matchedGuard = $guard;
                break;
            }
        }

        if ($matchedGuard && auth()->guard($matchedGuard)->check()) {
            $user = auth()->guard($matchedGuard)->user();
        } else {
            // Fallback: cek semua guard (halaman landing, login, dsb.)
            foreach (['admin-saas', 'admin-company', 'employee', 'customer'] as $guard) {
                if (auth()->guard($guard)->check()) {
                    $user = auth()->guard($guard)->user();
                    break;
                }
            }
        }

        if ($user instanceof \App\Models\AdminCompany) {
            $user->load('company');
        }

        $sessionErrors = $request->session()->get('errors');
        $errors = $sessionErrors ? $sessionErrors->getBag('default')->toArray() : [];

        $permissions = [];
        if ($user && method_exists($user, 'getAllPermissionNames')) {
            $permissions = $user->getAllPermissionNames();
        }

        \Log::info('HandleInertiaRequests', [
            'path' => $path,
            'matched_guard' => $matchedGuard,
            'user_type' => $user ? get_class($user) : 'guest',
            'user_name' => $user?->name ?? 'guest',
            'perm_count' => count($permissions),
        ]);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'permissions' => $permissions,
            'errors' => $errors,
        ];
    }
}
