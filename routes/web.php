"<?php

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

require __DIR__.'/auth.php';"
