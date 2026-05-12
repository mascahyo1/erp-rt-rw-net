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
    return Inertia::render('Landing/HubungiKami');
});

Route::get('/syarat-dan-ketentuan', function () {
    return Inertia::render('Landing/SyaratKetentuan');
});

Route::get('/kebijakan-privasi', function () {
    return Inertia::render('Landing/KebijakanPrivasi');
});

Route::get('/login-operator-saas', [AuthenticatedSessionController::class, 'create'])
    ->name('operator-saas.login');

Route::post('/login-operator-saas', [AuthenticatedSessionController::class, 'store']);

Route::post('/logout-operator-saas', [AuthenticatedSessionController::class, 'destroy'])
    ->name('operator-saas.logout');

Route::get('/login-perusahaan', [\App\Http\Controllers\Auth\AdminCompanySessionController::class, 'create'])
    ->name('operator-perusahaan.login');

Route::post('/login-perusahaan', [\App\Http\Controllers\Auth\AdminCompanySessionController::class, 'store']);

Route::post('/logout-perusahaan', [\App\Http\Controllers\Auth\AdminCompanySessionController::class, 'destroy'])
    ->name('operator-perusahaan.logout');

Route::get('/login-pelanggan', [\App\Http\Controllers\Auth\CustomerSessionController::class, 'create'])
    ->name('customer.login');

Route::post('/login-pelanggan', [\App\Http\Controllers\Auth\CustomerSessionController::class, 'store']);

Route::post('/logout-pelanggan', [\App\Http\Controllers\Auth\CustomerSessionController::class, 'destroy'])
    ->name('customer.logout');

Route::post('/logout-karyawan', [\App\Http\Controllers\Auth\EmployeeSessionController::class, 'destroy'])
    ->name('employee.logout');
