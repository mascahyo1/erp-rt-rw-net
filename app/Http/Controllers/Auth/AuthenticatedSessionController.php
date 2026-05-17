<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminSaas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::guard('admin-saas')->check()) {
            return redirect()->route('operator-saas.dashboard');
        }

        return Inertia::render('Landing/LoginOperatorSaaS');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = AdminSaas::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Akun anda dinonaktifkan. Hubungi admin.',
            ]);
        }

        Auth::guard('admin-saas')->login($user, $request->boolean('remember'));

        return redirect()->route('operator-saas.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin-saas')->logout();

        $request->session()->regenerateToken();

        return redirect()->route('landing.home');
    }
}
