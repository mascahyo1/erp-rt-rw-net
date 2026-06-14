<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeSessionController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::guard('employee')->check()) {
            return redirect()->route('employee.dashboard');
        }

        return Inertia::render('Karyawan/Login');
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

        if (! Auth::guard('employee')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = Auth::guard('employee')->user();

        if ($user->company_id !== $data['company_id']) {
            Auth::guard('employee')->logout();

            throw ValidationException::withMessages([
                'email' => 'Email tidak terdaftar di perusahaan ini.',
            ]);
        }

        if (! $user->is_active) {
            Auth::guard('employee')->logout();

            throw ValidationException::withMessages([
                'email' => 'Akun anda dinonaktifkan. Hubungi admin.',
            ]);
        }

        return redirect()->route('employee.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('employee')->logout();

        $request->session()->regenerateToken();

        return redirect()->route('landing.home');
    }
}
