<?php

use App\Http\Controllers\AdminCompanyController;
use App\Http\Controllers\AdminSaasController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\SaasConfigController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/operator-saas/login', function () {
    return Inertia::render('OperatorSaas/Login');
});

Route::get('/operator-saas/perusahaan/select-search', [CompanyController::class, 'selectSearch'])
    ->name('operator-saas.perusahaan.select-search');

Route::middleware(['auth:web', 'ensure.user.active:web'])->group(function () {
    Route::get('/operator-saas/dashboard', function () {
        return Inertia::render('OperatorSaas/Dashboard');
    })->name('operator-saas.dashboard');

    Route::get('/operator-saas/admin-perusahaan', [AdminCompanyController::class, 'index'])
        ->name('operator-saas.admin-perusahaan.index');
    Route::post('/operator-saas/admin-perusahaan', [AdminCompanyController::class, 'store'])
        ->name('operator-saas.admin-perusahaan.store');
    Route::put('/operator-saas/admin-perusahaan/{adminCompany}', [AdminCompanyController::class, 'update'])
        ->name('operator-saas.admin-perusahaan.update');
    Route::delete('/operator-saas/admin-perusahaan/{adminCompany}', [AdminCompanyController::class, 'destroy'])
        ->name('operator-saas.admin-perusahaan.destroy');
    Route::post('/operator-saas/admin-perusahaan/{id}/restore', [AdminCompanyController::class, 'restore'])
        ->name('operator-saas.admin-perusahaan.restore');
    Route::post('/operator-saas/admin-perusahaan/bulk-delete', [AdminCompanyController::class, 'bulkDelete'])
        ->name('operator-saas.admin-perusahaan.bulk-delete');
    Route::post('/operator-saas/admin-perusahaan/bulk-status', [AdminCompanyController::class, 'bulkToggleStatus'])
        ->name('operator-saas.admin-perusahaan.bulk-status');

    Route::get('/operator-saas/perusahaan', [CompanyController::class, 'index'])
        ->name('operator-saas.perusahaan.index');
    Route::post('/operator-saas/perusahaan', [CompanyController::class, 'store'])
        ->name('operator-saas.perusahaan.store');
    Route::put('/operator-saas/perusahaan/{company}', [CompanyController::class, 'update'])
        ->name('operator-saas.perusahaan.update');
    Route::delete('/operator-saas/perusahaan/{company}', [CompanyController::class, 'destroy'])
        ->name('operator-saas.perusahaan.destroy');
    Route::post('/operator-saas/perusahaan/{id}/restore', [CompanyController::class, 'restore'])
        ->name('operator-saas.perusahaan.restore');
    Route::post('/operator-saas/perusahaan/bulk-delete', [CompanyController::class, 'bulkDelete'])
        ->name('operator-saas.perusahaan.bulk-delete');
    Route::post('/operator-saas/perusahaan/bulk-status', [CompanyController::class, 'bulkToggleStatus'])
        ->name('operator-saas.perusahaan.bulk-status');


    Route::get('/operator-saas/role-perusahaan', function () {
        return Inertia::render('OperatorSaas/RolePerusahaan');
    });

    Route::get('/operator-saas/pemetaan-admin-perusahaan', function () {
        return Inertia::render('OperatorSaas/RoleAdminPerusahaan');
    });

    Route::get('/operator-saas/role-admin-perusahaan', function () {
        return Inertia::render('OperatorSaas/RoleAdminPerusahaan');
    });

    Route::get('/operator-saas/konfigurasi', [SaasConfigController::class, 'index'])
        ->name('operator-saas.konfigurasi.index');
    Route::post('/operator-saas/konfigurasi', [SaasConfigController::class, 'store'])
        ->name('operator-saas.konfigurasi.store');
    Route::put('/operator-saas/konfigurasi/{saasConfig}', [SaasConfigController::class, 'update'])
        ->name('operator-saas.konfigurasi.update');
    Route::delete('/operator-saas/konfigurasi/{saasConfig}', [SaasConfigController::class, 'destroy'])
        ->name('operator-saas.konfigurasi.destroy');
    Route::post('/operator-saas/konfigurasi/bulk-delete', [SaasConfigController::class, 'bulkDelete'])
        ->name('operator-saas.konfigurasi.bulk-delete');

    Route::get('/operator-saas/role-saas', function () {
        return Inertia::render('OperatorSaas/RoleSaaS');
    });

    Route::get('/operator-saas/admin-saas', [AdminSaasController::class, 'index'])
        ->name('operator-saas.admin-saas.index');
    Route::post('/operator-saas/admin-saas', [AdminSaasController::class, 'store'])
        ->name('operator-saas.admin-saas.store');
    Route::put('/operator-saas/admin-saas/{adminSaas}', [AdminSaasController::class, 'update'])
        ->name('operator-saas.admin-saas.update');
    Route::delete('/operator-saas/admin-saas/{adminSaas}', [AdminSaasController::class, 'destroy'])
        ->name('operator-saas.admin-saas.destroy');
    Route::post('/operator-saas/admin-saas/{id}/restore', [AdminSaasController::class, 'restore'])
        ->name('operator-saas.admin-saas.restore');
    Route::post('/operator-saas/admin-saas/bulk-delete', [AdminSaasController::class, 'bulkDelete'])
        ->name('operator-saas.admin-saas.bulk-delete');
    Route::post('/operator-saas/admin-saas/bulk-status', [AdminSaasController::class, 'bulkToggleStatus'])
        ->name('operator-saas.admin-saas.bulk-status');

    Route::get('/operator-saas/admin-role-saas', function () {
        return Inertia::render('OperatorSaas/AdminRoleSaaS');
    });
});
