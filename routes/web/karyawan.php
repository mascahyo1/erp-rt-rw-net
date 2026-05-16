<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login-karyawan', [\App\Http\Controllers\Auth\EmployeeSessionController::class, 'create'])
    ->name('employee.login');

Route::post('/login-karyawan', [\App\Http\Controllers\Auth\EmployeeSessionController::class, 'store']);

Route::middleware('auth:employee')->group(function () {
    Route::get('/karyawan/dashboard', function () {
        $employee = auth()->user();
        $companyId = $employee->company_id;

        return Inertia::render('Karyawan/Dashboard', [
            'stats' => [
                'customer_ditagih' => \App\Models\CustInternet::whereHas('customer', fn($q) => $q->where('company_id', $companyId))->where('internet_status', 'active')->count(),
                'tagihan_bulan_ini' => \App\Models\CustInternetInvc::whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))->whereMonth('created_at', now()->month)->count(),
                'pembayaran_collection' => \App\Models\CustInternetPayment::whereHas('custInternetInvc.custInternet.customer', fn($q) => $q->where('company_id', $companyId))->count(),
            ],
        ]);
    })->name('employee.dashboard');

    // Profil Saya
    Route::middleware('permission:profil-saya.list')->group(function () {
        Route::get('/karyawan/profil-saya', function () {
            return Inertia::render('Karyawan/ProfilSaya');
        });
    });

    // Customer (full CRUD)
    Route::middleware('permission:karyawan-customer.list')->group(function () {
        Route::get('/karyawan/customer', function () {
            return Inertia::render('Karyawan/Customer');
        });
    });
    Route::middleware('permission:karyawan-customer.create')->group(function () {
        Route::post('/karyawan/customer', function () {
            return Inertia::render('Karyawan/Customer');
        });
    });
    Route::middleware('permission:karyawan-customer.edit')->group(function () {
        Route::put('/karyawan/customer/{id}', function () {
            return Inertia::render('Karyawan/Customer');
        });
    });
    Route::middleware('permission:karyawan-customer.delete')->group(function () {
        Route::delete('/karyawan/customer/{id}', function () {
            return Inertia::render('Karyawan/Customer');
        });
    });
    Route::middleware('permission:karyawan-customer.restore')->group(function () {
        Route::patch('/karyawan/customer/{id}/restore', function () {
            return Inertia::render('Karyawan/Customer');
        });
    });

    // Langganan (full CRUD)
    Route::middleware('permission:karyawan-langganan.list')->group(function () {
        Route::get('/karyawan/langganan-customer', function () {
            return Inertia::render('Karyawan/LanggananCustomer');
        });
    });
    Route::middleware('permission:karyawan-langganan.create')->group(function () {
        Route::post('/karyawan/langganan-customer', function () {
            return Inertia::render('Karyawan/LanggananCustomer');
        });
    });
    Route::middleware('permission:karyawan-langganan.edit')->group(function () {
        Route::put('/karyawan/langganan-customer/{id}', function () {
            return Inertia::render('Karyawan/LanggananCustomer');
        });
    });
    Route::middleware('permission:karyawan-langganan.delete')->group(function () {
        Route::delete('/karyawan/langganan-customer/{id}', function () {
            return Inertia::render('Karyawan/LanggananCustomer');
        });
    });
    Route::middleware('permission:karyawan-langganan.restore')->group(function () {
        Route::patch('/karyawan/langganan-customer/{id}/restore', function () {
            return Inertia::render('Karyawan/LanggananCustomer');
        });
    });

    // Tagihan (full CRUD)
    Route::middleware('permission:karyawan-tagihan.list')->group(function () {
        Route::get('/karyawan/tagihan', function () {
            return Inertia::render('Karyawan/Tagihan');
        });
    });
    Route::middleware('permission:karyawan-tagihan.create')->group(function () {
        Route::post('/karyawan/tagihan', function () {
            return Inertia::render('Karyawan/Tagihan');
        });
    });
    Route::middleware('permission:karyawan-tagihan.edit')->group(function () {
        Route::put('/karyawan/tagihan/{id}', function () {
            return Inertia::render('Karyawan/Tagihan');
        });
    });
    Route::middleware('permission:karyawan-tagihan.delete')->group(function () {
        Route::delete('/karyawan/tagihan/{id}', function () {
            return Inertia::render('Karyawan/Tagihan');
        });
    });
    Route::middleware('permission:karyawan-tagihan.restore')->group(function () {
        Route::patch('/karyawan/tagihan/{id}/restore', function () {
            return Inertia::render('Karyawan/Tagihan');
        });
    });

    // Insentif (full CRUD)
    Route::middleware('permission:karyawan-insentif.list')->group(function () {
        Route::get('/karyawan/insentif-saya', function () {
            return Inertia::render('Karyawan/InsentifSaya');
        });
    });
    Route::middleware('permission:karyawan-insentif.create')->group(function () {
        Route::post('/karyawan/insentif-saya', function () {
            return Inertia::render('Karyawan/InsentifSaya');
        });
    });
    Route::middleware('permission:karyawan-insentif.edit')->group(function () {
        Route::put('/karyawan/insentif-saya/{id}', function () {
            return Inertia::render('Karyawan/InsentifSaya');
        });
    });
    Route::middleware('permission:karyawan-insentif.delete')->group(function () {
        Route::delete('/karyawan/insentif-saya/{id}', function () {
            return Inertia::render('Karyawan/InsentifSaya');
        });
    });
    Route::middleware('permission:karyawan-insentif.restore')->group(function () {
        Route::patch('/karyawan/insentif-saya/{id}/restore', function () {
            return Inertia::render('Karyawan/InsentifSaya');
        });
    });

    // Riwayat Pembayaran (full CRUD)
    Route::middleware('permission:karyawan-riwayat-pembayaran.list')->group(function () {
        Route::get('/karyawan/riwayat-pembayaran', function () {
            return Inertia::render('Karyawan/RiwayatPembayaran');
        });
    });
    Route::middleware('permission:karyawan-riwayat-pembayaran.create')->group(function () {
        Route::post('/karyawan/riwayat-pembayaran', function () {
            return Inertia::render('Karyawan/RiwayatPembayaran');
        });
    });
    Route::middleware('permission:karyawan-riwayat-pembayaran.edit')->group(function () {
        Route::put('/karyawan/riwayat-pembayaran/{id}', function () {
            return Inertia::render('Karyawan/RiwayatPembayaran');
        });
    });
    Route::middleware('permission:karyawan-riwayat-pembayaran.delete')->group(function () {
        Route::delete('/karyawan/riwayat-pembayaran/{id}', function () {
            return Inertia::render('Karyawan/RiwayatPembayaran');
        });
    });
    Route::middleware('permission:karyawan-riwayat-pembayaran.restore')->group(function () {
        Route::patch('/karyawan/riwayat-pembayaran/{id}/restore', function () {
            return Inertia::render('Karyawan/RiwayatPembayaran');
        });
    });
});
