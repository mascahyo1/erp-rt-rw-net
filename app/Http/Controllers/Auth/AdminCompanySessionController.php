<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminCompanySessionController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::guard('admin-company')->check()) {
            return redirect()->route('operator-perusahaan.dashboard');
        }

        return Inertia::render('Landing/LoginPerusahaan');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'string', 'exists:companies,id'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'cf-turnstile-response' => ['required', new \App\Rules\Turnstile($request->ip())],
        ]);

        $credentials = ['email' => $data['email'], 'password' => $data['password']];

        if (! Auth::guard('admin-company')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = Auth::guard('admin-company')->user();

        if ($user->company_id !== $data['company_id']) {
            Auth::guard('admin-company')->logout();

            throw ValidationException::withMessages([
                'email' => 'Email tidak terdaftar di perusahaan ini.',
            ]);
        }

        if (! $user->is_active) {
            Auth::guard('admin-company')->logout();

            throw ValidationException::withMessages([
                'email' => 'Akun anda dinonaktifkan. Hubungi admin.',
            ]);
        }

        if ($request->hasSession()) {
            $request->session()->put('auth_id', $user->getAuthIdentifier());
            $request->session()->put('auth_guard', 'admin-company');
            $request->session()->regenerate();
        }

        return redirect()->route('operator-perusahaan.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin-company')->logout();

        if ($request->hasSession()) {
            $request->session()->forget(['auth_id', 'auth_guard']);
            $request->session()->regenerateToken();
        }

        return redirect()->route('landing.home');
    }
}
