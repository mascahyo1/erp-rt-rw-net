<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ========================
// Landing Page Routes
// ========================
Route::get('/', function () {
    return Inertia::render('Landing/Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/tentang-kami', function () {
    return Inertia::render('Landing/TentangKami');
});

Route::get('/hubungi-kami', function () {
    return Inertia::render('Landing/HubungiKami');
});

Route::get('/syarat-dan-ketentuan', function () {
    return Inertia::render('Landing/SyaratKetentuan');
});

Route::get('/kebijakan-privasi', function () {
    return Inertia::render('Landing/KebijakanPrivasi');
});

Route::get('/login-operator-saas', function () {
    return Inertia::render('Landing/LoginOperatorSaaS');
});

Route::get('/login-perusahaan', function () {
    return Inertia::render('Landing/LoginPerusahaan');
});

Route::get('/login-pelanggan', function () {
    return Inertia::render('Landing/LoginPelanggan');
});

// ========================
// Operator SaaS Routes
// ========================
Route::get('/operator-saas/login', function () {
    return Inertia::render('OperatorSaas/Login');
});

Route::get('/operator-saas/dashboard', function () {
    return Inertia::render('OperatorSaas/Dashboard');
});

Route::get('/operator-saas/admin-perusahaan', function () {
    return Inertia::render('OperatorSaas/AdminPerusahaan');
});

Route::get('/operator-saas/perusahaan', function () {
    return Inertia::render('OperatorSaas/Perusahaan');
});

Route::get('/operator-saas/pemetaan-admin-perusahaan', function () {
    return Inertia::render('OperatorSaas/PemetaanAdminPerusahaan');
});

Route::get('/operator-saas/role-saas', function () {
    return Inertia::render('OperatorSaas/RoleSaaS');
});

Route::get('/operator-saas/admin-saas', function () {
    return Inertia::render('OperatorSaas/AdminSaaS');
});

Route::get('/operator-saas/admin-role-saas', function () {
    return Inertia::render('OperatorSaas/AdminRoleSaaS');
});

// ========================
// Dashboard (existing)
// ========================
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
