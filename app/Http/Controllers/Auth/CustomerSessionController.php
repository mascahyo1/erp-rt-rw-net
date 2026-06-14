<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerSessionController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.dashboard');
        }

        return Inertia::render('Landing/LoginPelanggan');
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

        if (! Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = Auth::guard('customer')->user();

        if ($user->company_id !== $data['company_id']) {
            Auth::guard('customer')->logout();

            throw ValidationException::withMessages([
                'email' => 'Email tidak terdaftar di perusahaan ini.',
            ]);
        }

        if (! $user->is_active) {
            Auth::guard('customer')->logout();

            throw ValidationException::withMessages([
                'email' => 'Akun anda dinonaktifkan. Hubungi admin.',
            ]);
        }

        return redirect()->route('customer.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->regenerateToken();

        return redirect()->route('landing.home');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:customers,email'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_id' => ['required', 'string', 'exists:companies,id'],
            'cf-turnstile-response' => ['required', new \App\Rules\Turnstile($request->ip())],
        ]);

        $customer = \App\Models\Customer::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_number' => $data['phone'],
            'phone_country_code' => '+62',
            'company_id' => $data['company_id'],
            'password' => bcrypt($data['password']),
            'is_active' => true,
        ]);

        Auth::guard('customer')->login($customer);

        return redirect()->route('customer.dashboard');
    }
}
