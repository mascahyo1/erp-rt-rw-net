<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing/Home');
})->name('landing.home');

Route::get('/tentang-kami', function () {
    return Inertia::render('Landing/TentangKami');
});

Route::get('/hubungi-kami', function () {
    $configs = \App\Models\SaasConfig::whereIn('key', [
        'contact.phone', 'contact.whatsapp', 'contact.email', 'contact.address', 'contact.working_schedule',
    ])->pluck('value', 'key');

    return Inertia::render('Landing/HubungiKami', [
        'contact' => [
            'phone' => $configs['contact.phone'] ?? '+62 812-3456-7890',
            'whatsapp' => $configs['contact.whatsapp'] ?? '+62 812-3456-7890',
            'email' => $configs['contact.email'] ?? 'support@rtrwnet.id',
            'address' => $configs['contact.address'] ?? 'Jl. Teknologi No. 10, Jakarta Selatan',
            'working_schedule' => $configs['contact.working_schedule'] ?? "Senin — Jumat: 08:00 — 20:00 WIB\nSabtu: 09:00 — 15:00 WIB",
        ],
    ]);
});

Route::get('/syarat-dan-ketentuan', function () {
    $email = \App\Models\SaasConfig::where('key', 'contact.email_terms')->value('value');

    return Inertia::render('Landing/SyaratKetentuan', [
        'emailTerms' => $email ?: 'legal@rtrwnet.id',
    ]);
});

Route::get('/kebijakan-privasi', function () {
    $email = \App\Models\SaasConfig::where('key', 'contact.email_privacy')->value('value');

    return Inertia::render('Landing/KebijakanPrivasi', [
        'emailPrivacy' => $email ?: 'privacy@rtrwnet.id',
    ]);
});

Route::get('/login-operator-saas', [AuthenticatedSessionController::class, 'create'])
    ->name('operator-saas.login');

Route::post('/login-operator-saas', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:5,1');

Route::post('/logout-operator-saas', [AuthenticatedSessionController::class, 'destroy'])
    ->name('operator-saas.logout');

Route::get('/login-perusahaan', [\App\Http\Controllers\Auth\AdminCompanySessionController::class, 'create'])
    ->name('operator-perusahaan.login');

Route::post('/login-perusahaan', [\App\Http\Controllers\Auth\AdminCompanySessionController::class, 'store'])
    ->middleware('throttle:5,1');

Route::post('/logout-perusahaan', [\App\Http\Controllers\Auth\AdminCompanySessionController::class, 'destroy'])
    ->name('operator-perusahaan.logout');

Route::get('/login-pelanggan', [\App\Http\Controllers\Auth\CustomerSessionController::class, 'create'])
    ->name('customer.login');

Route::post('/login-pelanggan', [\App\Http\Controllers\Auth\CustomerSessionController::class, 'store'])
    ->middleware('throttle:5,1');

Route::post('/logout-pelanggan', [\App\Http\Controllers\Auth\CustomerSessionController::class, 'destroy'])
    ->name('customer.logout');

Route::post('/daftar-pelanggan', [\App\Http\Controllers\Auth\CustomerSessionController::class, 'register'])
    ->name('customer.register');

Route::post('/logout-karyawan', [\App\Http\Controllers\Auth\EmployeeSessionController::class, 'destroy'])
    ->name('employee.logout');

// Public company search endpoint (for login pages)
Route::get('/api/companies/search', function (\Illuminate\Http\Request $request) {
    $q = trim((string) $request->query('q', ''));
    $page = max(1, (int) $request->query('page', 1));
    $perPage = 10;

    $query = \App\Models\Company::query()->where('is_active', true);
    if ($q !== '') {
        $query->where(function ($w) use ($q) {
            $w->where('name', 'like', '%' . $q . '%')
              ->orWhere('email', 'like', '%' . $q . '%')
              ->orWhere('address', 'like', '%' . $q . '%');
        });
    }

    $total = (clone $query)->count();
    $items = $query->orderBy('name')
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get(['id', 'name', 'email', 'address'])
        ->map(fn($c) => [
            'id' => $c->id,
            'nama' => $c->name,
            'email' => $c->email,
            'kota' => $c->address, // address used as 'kota' for backwards compat
        ]);

    return response()->json([
        'data' => $items,
        'hasMore' => ($page * $perPage) < $total,
        'page' => $page,
        'total' => $total,
    ]);
})->name('api.companies.search');
