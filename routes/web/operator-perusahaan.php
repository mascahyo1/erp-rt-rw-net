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
        // Customer
        Route::middleware('permission:customer.list')->group(function () {
            Route::get('/customer', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'index'])
                ->name('customer.index');
        });
        Route::middleware('permission:customer.create')->group(function () {
            Route::post('/customer', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'store'])
                ->name('customer.store');
        });
        Route::middleware('permission:customer.edit')->group(function () {
            Route::put('/customer/{customer}', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'update'])
                ->name('customer.update');
            Route::post('/customer/bulk-status', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'bulkToggleStatus'])
                ->name('customer.bulkStatus');
        });
        Route::middleware('permission:customer.delete')->group(function () {
            Route::delete('/customer/{customer}', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'destroy'])
                ->name('customer.destroy');
            Route::post('/customer/bulk-delete', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'bulkDelete'])
                ->name('customer.bulkDelete');
        });
        Route::middleware('permission:customer.restore')->group(function () {
            Route::patch('/customer/{id}/restore', [\App\Http\Controllers\OperatorPerusahaan\CustomerController::class, 'restore'])
                ->name('customer.restore');
        });

        // Karyawan
        Route::middleware('permission:karyawan.list')->group(function () {
            Route::get('/karyawan', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'index'])
                ->name('karyawan.index');
        });
        Route::middleware('permission:karyawan.create')->group(function () {
            Route::post('/karyawan', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'store'])
                ->name('karyawan.store');
        });
        Route::middleware('permission:karyawan.edit')->group(function () {
            Route::put('/karyawan/{employee}', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'update'])
                ->name('karyawan.update');
            Route::post('/karyawan/bulk-status', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'bulkToggleStatus'])
                ->name('karyawan.bulkStatus');
        });
        Route::middleware('permission:karyawan.delete')->group(function () {
            Route::delete('/karyawan/{employee}', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'destroy'])
                ->name('karyawan.destroy');
            Route::post('/karyawan/bulk-delete', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'bulkDelete'])
                ->name('karyawan.bulkDelete');
        });
        Route::middleware('permission:karyawan.restore')->group(function () {
            Route::patch('/karyawan/{id}/restore', [\App\Http\Controllers\OperatorPerusahaan\KaryawanController::class, 'restore'])
                ->name('karyawan.restore');
        });

        // Admin Perusahaan
        Route::middleware('permission:admin-perusahaan.list')->group(function () {
            Route::get('/admin-perusahaan', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'index'])
                ->name('admin-perusahaan.index');
        });
        Route::middleware('permission:admin-perusahaan.create')->group(function () {
            Route::post('/admin-perusahaan', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'store'])
                ->name('admin-perusahaan.store');
        });
        Route::middleware('permission:admin-perusahaan.edit')->group(function () {
            Route::put('/admin-perusahaan/{adminCompany}', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'update'])
                ->name('admin-perusahaan.update');
            Route::post('/admin-perusahaan/bulk-status', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'bulkToggleStatus'])
                ->name('admin-perusahaan.bulkStatus');
        });
        Route::middleware('permission:admin-perusahaan.delete')->group(function () {
            Route::delete('/admin-perusahaan/{adminCompany}', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'destroy'])
                ->name('admin-perusahaan.destroy');
            Route::post('/admin-perusahaan/bulk-delete', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'bulkDelete'])
                ->name('admin-perusahaan.bulkDelete');
        });
        Route::middleware('permission:admin-perusahaan.restore')->group(function () {
            Route::patch('/admin-perusahaan/{id}/restore', [\App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController::class, 'restore'])
                ->name('admin-perusahaan.restore');
        });
    });

    Route::middleware('permission:perusahaan-saya.list')->group(function () {
        Route::get('/operator-perusahaan/perusahaan-saya', function () {
            return Inertia::render('OperatorPerusahaan/PerusahaanSaya');
        });
    });

    Route::middleware('permission:paket.list')->group(function () {
        Route::get('/operator-perusahaan/daftar-paket', function () {
            return Inertia::render('OperatorPerusahaan/DaftarPaket');
        });
    });

    Route::middleware('permission:langganan.list')->group(function () {
        Route::get('/operator-perusahaan/langganan-customer', function () {
            return Inertia::render('OperatorPerusahaan/LanggananCustomer');
        });
    });

    Route::middleware('permission:tagihan.list')->group(function () {
        Route::get('/operator-perusahaan/tagihan', function () {
            return Inertia::render('OperatorPerusahaan/Tagihan');
        });
    });

    Route::middleware('permission:riwayat-pembayaran.list')->group(function () {
        Route::get('/operator-perusahaan/riwayat-pembayaran', function () {
            return Inertia::render('OperatorPerusahaan/RiwayatPembayaran');
        });
    });

    Route::middleware('permission:insentif.list')->group(function () {
        Route::get('/operator-perusahaan/insentif', function () {
            return Inertia::render('OperatorPerusahaan/Insentif');
        });
    });

    Route::middleware('permission:riwayat-insentif.list')->group(function () {
        Route::get('/operator-perusahaan/riwayat-insentif', function () {
            return Inertia::render('OperatorPerusahaan/RiwayatInsentif');
        });
    });

    Route::middleware('permission:role-perusahaan-op.list')->group(function () {
        Route::get('/operator-perusahaan/role-perusahaan', function () {
            return Inertia::render('OperatorPerusahaan/RolePerusahaan');
        });
    });

    Route::middleware('permission:admin-role-perusahaan-op.list')->group(function () {
        Route::get('/operator-perusahaan/admin-role-perusahaan', function () {
            return Inertia::render('OperatorPerusahaan/AdminRolePerusahaan');
        });
    });

    Route::middleware('permission:konfigurasi-perusahaan.list')->group(function () {
        Route::get('/operator-perusahaan/konfigurasi-perusahaan', function () {
            return Inertia::render('OperatorPerusahaan/KonfigurasiPerusahaan');
        });
    });

    // Profil Saya always accessible
    Route::get('/operator-perusahaan/profil-saya', function () {
        return Inertia::render('OperatorPerusahaan/ProfilSaya');
    });

    Route::middleware('permission:role-web-karyawan.list')->group(function () {
        Route::get('/operator-perusahaan/role-web-karyawan', function () {
            return Inertia::render('OperatorPerusahaan/RoleWebKaryawan');
        });
    });

    Route::middleware('permission:admin-role-web-karyawan.list')->group(function () {
        Route::get('/operator-perusahaan/admin-role-web-karyawan', function () {
            return Inertia::render('OperatorPerusahaan/AdminRoleWebKaryawan');
        });
    });
});
