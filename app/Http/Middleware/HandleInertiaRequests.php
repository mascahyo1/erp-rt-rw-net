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

        foreach (['admin-saas', 'admin-company', 'employee', 'customer'] as $guard) {
            if (auth()->guard($guard)->check()) {
                $user = auth()->guard($guard)->user();
                break;
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
