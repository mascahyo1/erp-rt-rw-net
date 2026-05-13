<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth:admin-company')->group(function () {
    Route::get('/operator-perusahaan/dashboard', function () {
        $companyId = auth()->user()->company_id;

        return Inertia::render('OperatorPerusahaan/Dashboard', [
            'stats' => [
                'total_customer' => \App\Models\Customer::where('company_id', $companyId)->count(),
                'customer_aktif' => \App\Models\Customer::where('company_id', $companyId)->where('is_active', true)->count(),
                'karyawan_aktif' => \App\Models\Employee::where('company_id', $companyId)->where('is_active', true)->count(),
                'langganan_aktif' => \App\Models\CustInternet::whereHas('customer', fn($q) => $q->where('company_id', $companyId))->where('internet_status', 'active')->count(),
                'tagihan_bulan_ini' => \App\Models\CustInternetInvc::whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            ],
        ]);
    })->name('operator-perusahaan.dashboard');

    Route::prefix('operator-perusahaan')->name('operator-perusahaan.')->group(function () {
        Route::get('/customer', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'index'])
            ->name('customer.index');
        Route::post('/customer', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'store'])
            ->name('customer.store');
        Route::put('/customer/{customer}', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'update'])
            ->name('customer.update');
        Route::delete('/customer/{customer}', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'destroy'])
            ->name('customer.destroy');
        Route::patch('/customer/{id}/restore', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'restore'])
            ->name('customer.restore');
        Route::post('/customer/bulk-delete', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'bulkDelete'])
            ->name('customer.bulkDelete');
        Route::post('/customer/bulk-status', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'bulkToggleStatus'])
            ->name('customer.bulkStatus');

        Route::get('/karyawan', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'index'])
            ->name('karyawan.index');
        Route::post('/karyawan', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'store'])
            ->name('karyawan.store');
        Route::put('/karyawan/{employee}', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'update'])
            ->name('karyawan.update');
        Route::delete('/karyawan/{employee}', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'destroy'])
            ->name('karyawan.destroy');
        Route::patch('/karyawan/{id}/restore', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'restore'])
            ->name('karyawan.restore');
        Route::post('/karyawan/bulk-delete', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'bulkDelete'])
            ->name('karyawan.bulkDelete');
        Route::post('/karyawan/bulk-status', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'bulkToggleStatus'])
            ->name('karyawan.bulkStatus');

        Route::get('/admin-perusahaan', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'index'])
            ->name('admin-perusahaan.index');
        Route::post('/admin-perusahaan', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'store'])
            ->name('admin-perusahaan.store');
        Route::put('/admin-perusahaan/{adminCompany}', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'update'])
            ->name('admin-perusahaan.update');
        Route::delete('/admin-perusahaan/{adminCompany}', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'destroy'])
            ->name('admin-perusahaan.destroy');
        Route::patch('/admin-perusahaan/{id}/restore', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'restore'])
            ->name('admin-perusahaan.restore');
        Route::post('/admin-perusahaan/bulk-delete', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'bulkDelete'])
            ->name('admin-perusahaan.bulkDelete');
        Route::post('/admin-perusahaan/bulk-status', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'bulkToggleStatus'])
            ->name('admin-perusahaan.bulkStatus');
    });

    Route::get('/operator-perusahaan/perusahaan-saya', function () {
        return Inertia::render('OperatorPerusahaan/PerusahaanSaya');
    });

    Route::get('/operator-perusahaan/daftar-paket', function () {
        return Inertia::render('OperatorPerusahaan/DaftarPaket');
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

    Route::get('/operator-perusahaan/insentif', function () {
        return Inertia::render('OperatorPerusahaan/Insentif');
    });

    Route::get('/operator-perusahaan/riwayat-insentif', function () {
        return Inertia::render('OperatorPerusahaan/RiwayatInsentif');
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

    Route::get('/operator-perusahaan/profil-saya', function () {
        return Inertia::render('OperatorPerusahaan/ProfilSaya');
    });

    Route::get('/operator-perusahaan/role-web-karyawan', function () {
        return Inertia::render('OperatorPerusahaan/RoleWebKaryawan');
    });

    Route::get('/operator-perusahaan/admin-role-web-karyawan', function () {
        return Inertia::render('OperatorPerusahaan/AdminRoleWebKaryawan');
    });
});
