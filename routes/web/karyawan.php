<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login-karyawan', [\App\Http\Controllers\Auth\EmployeeSessionController::class, 'create'])
    ->name('employee.login');

Route::post('/login-karyawan', [\App\Http\Controllers\Auth\EmployeeSessionController::class, 'store']);

Route::middleware('auth:employee')->group(function () {
    // Dashboard always accessible
    Route::get('/karyawan/dashboard', function () {
        $employee = auth()->user();
        $companyId = $employee->company_id;

        return Inertia::render('Karyawan/Dashboard', [
            'stats' => [
                'customer_ditagih' => \App\Models\CustInternet::whereHas('customer', fn($q) => $q->where('company_id', $companyId))->where('internet_status', 'active')->count(),
                'tagihan_bulan_ini' => \App\Models\CustInternetInvc::whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))->whereMonth('created_at', now()->month)->count(),
                'pembayaran_collection' => \App\Models\CustInternetPayment::whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))->count(),
            ],
        ]);
    })->name('employee.dashboard');

    Route::middleware('permission:profil-saya.list')->group(function () {
        Route::get('/karyawan/profil-saya', function () {
            return Inertia::render('Karyawan/ProfilSaya');
        });
    });

    Route::middleware('permission:karyawan-customer.list')->group(function () {
        Route::get('/karyawan/customer', function () {
            return Inertia::render('Karyawan/Customer');
        });
    });

    Route::middleware('permission:karyawan-langganan.list')->group(function () {
        Route::get('/karyawan/langganan-customer', function () {
            return Inertia::render('Karyawan/LanggananCustomer');
        });
    });

    Route::middleware('permission:karyawan-tagihan.list')->group(function () {
        Route::get('/karyawan/tagihan', function () {
            return Inertia::render('Karyawan/Tagihan');
        });
    });

    Route::middleware('permission:karyawan-insentif.list')->group(function () {
        Route::get('/karyawan/insentif-saya', function () {
            return Inertia::render('Karyawan/InsentifSaya');
        });
    });

    Route::middleware('permission:karyawan-riwayat-pembayaran.list')->group(function () {
        Route::get('/karyawan/riwayat-pembayaran', function () {
            return Inertia::render('Karyawan/RiwayatPembayaran');
        });
    });
});
