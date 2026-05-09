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

Route::get('/operator-saas/role-perusahaan', function () {
    return Inertia::render('OperatorSaas/RolePerusahaan');
});

Route::get('/operator-saas/pemetaan-admin-perusahaan', function () {
    return Inertia::render('OperatorSaas/RoleAdminPerusahaan');
});

Route::get('/operator-saas/role-admin-perusahaan', function () {
    return Inertia::render('OperatorSaas/RoleAdminPerusahaan');
});

Route::get('/operator-saas/konfigurasi', function () {
    return Inertia::render('OperatorSaas/Konfigurasi');
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

// ========================
// Operator Perusahaan Routes
// ========================
Route::get('/operator-perusahaan/dashboard', function () {
    return Inertia::render('OperatorPerusahaan/Dashboard');
});

Route::get('/operator-perusahaan/perusahaan-saya', function () {
    return Inertia::render('OperatorPerusahaan/PerusahaanSaya');
});

Route::get('/operator-perusahaan/daftar-paket', function () {
    return Inertia::render('OperatorPerusahaan/DaftarPaket');
});

Route::get('/operator-perusahaan/customer', function () {
    return Inertia::render('OperatorPerusahaan/Customer');
});

Route::get('/operator-perusahaan/langganan-customer', function () {
    return Inertia::render('OperatorPerusahaan/LanggananCustomer');
});

Route::get('/operator-perusahaan/tagihan', function () {
    return Inertia::render('OperatorPerusahaan/Tagihan');
});

Route::get('/operator-perusahaan/riwayat-pembayaran', function () {
    return Inertia::render('OperatorPerusahaan/RiwayatPembayaran');
});

Route::get('/operator-perusahaan/admin-perusahaan', function () {
    return Inertia::render('OperatorPerusahaan/AdminPerusahaan');
});

Route::get('/operator-perusahaan/role-perusahaan', function () {
    return Inertia::render('OperatorPerusahaan/RolePerusahaan');
});

Route::get('/operator-perusahaan/admin-role-perusahaan', function () {
    return Inertia::render('OperatorPerusahaan/AdminRolePerusahaan');
});

Route::get('/operator-perusahaan/konfigurasi-perusahaan', function () {
    return Inertia::render('OperatorPerusahaan/KonfigurasiPerusahaan');
});

// ========================
// Customer Routes
// ========================
Route::get('/login-pelanggan', function () {
    return Inertia::render('Landing/LoginPelanggan');
});

Route::get('/customer/dashboard', function () {
    return Inertia::render('Customer/Dashboard');
});

Route::get('/customer/login-register', function () {
    return Inertia::render('Customer/LoginRegister');
});

Route::get('/customer/profil-saya', function () {
    return Inertia::render('Customer/ProfilSaya');
});

Route::get('/customer/paket-saya', function () {
    return Inertia::render('Customer/PaketSaya');
});

Route::get('/customer/tagihan-saya', function () {
    return Inertia::render('Customer/TagihanSaya');
});

Route::get('/customer/riwayat-pembayaran', function () {
    return Inertia::render('Customer/RiwayatPembayaran');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
