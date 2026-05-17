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
            $company = auth()->user()->company;
            return Inertia::render('OperatorPerusahaan/PerusahaanSaya', [
                'company' => $company?->toArray(),
            ]);
        });
    });

    Route::middleware('permission:paket.list')->group(function () {
        Route::get('/operator-perusahaan/daftar-paket', function () {
            $packages = \App\Models\InternetPackage::where('is_active', true)->latest()->get();
            return Inertia::render('OperatorPerusahaan/DaftarPaket', [
                'packages' => $packages,
            ]);
        });
    });

    // Langganan (full CRUD)
    Route::middleware('permission:langganan.list')->group(function () {
        Route::get('/operator-perusahaan/langganan-customer', [\App\Http\Controllers\OperatorPerusahaan\LanggananController::class, 'index']);
    });
    Route::middleware('permission:langganan.create')->group(function () {
        Route::post('/operator-perusahaan/langganan-customer', [\App\Http\Controllers\OperatorPerusahaan\LanggananController::class, 'store']);
    });
    Route::middleware('permission:langganan.edit')->group(function () {
        Route::put('/operator-perusahaan/langganan-customer/{custInternet}', [\App\Http\Controllers\OperatorPerusahaan\LanggananController::class, 'update']);
        Route::post('/operator-perusahaan/langganan-customer/bulk-status', [\App\Http\Controllers\OperatorPerusahaan\LanggananController::class, 'bulkToggleStatus']);
    });
    Route::middleware('permission:langganan.delete')->group(function () {
        Route::delete('/operator-perusahaan/langganan-customer/{custInternet}', [\App\Http\Controllers\OperatorPerusahaan\LanggananController::class, 'destroy']);
        Route::post('/operator-perusahaan/langganan-customer/bulk-delete', [\App\Http\Controllers\OperatorPerusahaan\LanggananController::class, 'bulkDelete']);
    });
    Route::middleware('permission:langganan.restore')->group(function () {
        Route::patch('/operator-perusahaan/langganan-customer/{id}/restore', [\App\Http\Controllers\OperatorPerusahaan\LanggananController::class, 'restore']);
    });

    // Tagihan (full CRUD)
    Route::middleware('permission:tagihan.list')->group(function () {
        Route::get('/operator-perusahaan/tagihan', [\App\Http\Controllers\OperatorPerusahaan\TagihanController::class, 'index']);
    });
    Route::middleware('permission:tagihan.create')->group(function () {
        Route::post('/operator-perusahaan/tagihan', [\App\Http\Controllers\OperatorPerusahaan\TagihanController::class, 'store']);
    });
    Route::middleware('permission:tagihan.edit')->group(function () {
        Route::put('/operator-perusahaan/tagihan/{custInternetInvc}', [\App\Http\Controllers\OperatorPerusahaan\TagihanController::class, 'update']);
        Route::post('/operator-perusahaan/tagihan/bulk-status', [\App\Http\Controllers\OperatorPerusahaan\TagihanController::class, 'bulkToggleStatus']);
    });
    Route::middleware('permission:tagihan.delete')->group(function () {
        Route::delete('/operator-perusahaan/tagihan/{custInternetInvc}', [\App\Http\Controllers\OperatorPerusahaan\TagihanController::class, 'destroy']);
        Route::post('/operator-perusahaan/tagihan/bulk-delete', [\App\Http\Controllers\OperatorPerusahaan\TagihanController::class, 'bulkDelete']);
    });
    Route::middleware('permission:tagihan.restore')->group(function () {
        Route::patch('/operator-perusahaan/tagihan/{id}/restore', [\App\Http\Controllers\OperatorPerusahaan\TagihanController::class, 'restore']);
    });

    // Riwayat Pembayaran (full CRUD + persetujuan)
    Route::middleware('permission:riwayat-pembayaran.list')->group(function () {
        Route::get('/operator-perusahaan/riwayat-pembayaran', [\App\Http\Controllers\OperatorPerusahaan\PembayaranController::class, 'index']);
    });
    Route::middleware('permission:riwayat-pembayaran.create')->group(function () {
        Route::post('/operator-perusahaan/riwayat-pembayaran', [\App\Http\Controllers\OperatorPerusahaan\PembayaranController::class, 'store']);
    });
    Route::middleware('permission:riwayat-pembayaran.edit')->group(function () {
        Route::put('/operator-perusahaan/riwayat-pembayaran/{custInternetPayment}', [\App\Http\Controllers\OperatorPerusahaan\PembayaranController::class, 'update']);
    });
    Route::middleware('permission:riwayat-pembayaran.delete')->group(function () {
        Route::delete('/operator-perusahaan/riwayat-pembayaran/{custInternetPayment}', [\App\Http\Controllers\OperatorPerusahaan\PembayaranController::class, 'destroy']);
        Route::post('/operator-perusahaan/riwayat-pembayaran/bulk-delete', [\App\Http\Controllers\OperatorPerusahaan\PembayaranController::class, 'bulkDelete']);
    });
    Route::middleware('permission:riwayat-pembayaran.restore')->group(function () {
        Route::patch('/operator-perusahaan/riwayat-pembayaran/{id}/restore', [\App\Http\Controllers\OperatorPerusahaan\PembayaranController::class, 'restore']);
    });
    Route::middleware('permission:riwayat-pembayaran.persetujuan')->group(function () {
        Route::post('/operator-perusahaan/riwayat-pembayaran/{id}/approve', [\App\Http\Controllers\OperatorPerusahaan\PembayaranController::class, 'approve']);
    });

    // Insentif (full CRUD)
    Route::middleware('permission:insentif.list')->group(function () {
        Route::get('/operator-perusahaan/insentif', [\App\Http\Controllers\OperatorPerusahaan\InsentifController::class, 'index']);
    });
    Route::middleware('permission:insentif.create')->group(function () {
        Route::post('/operator-perusahaan/insentif', [\App\Http\Controllers\OperatorPerusahaan\InsentifController::class, 'store']);
    });
    Route::middleware('permission:insentif.edit')->group(function () {
        Route::put('/operator-perusahaan/insentif/{empIncentive}', [\App\Http\Controllers\OperatorPerusahaan\InsentifController::class, 'update']);
        Route::post('/operator-perusahaan/insentif/bulk-status', [\App\Http\Controllers\OperatorPerusahaan\InsentifController::class, 'bulkToggleStatus']);
    });
    Route::middleware('permission:insentif.delete')->group(function () {
        Route::delete('/operator-perusahaan/insentif/{empIncentive}', [\App\Http\Controllers\OperatorPerusahaan\InsentifController::class, 'destroy']);
        Route::post('/operator-perusahaan/insentif/bulk-delete', [\App\Http\Controllers\OperatorPerusahaan\InsentifController::class, 'bulkDelete']);
    });
    Route::middleware('permission:insentif.restore')->group(function () {
        Route::patch('/operator-perusahaan/insentif/{id}/restore', [\App\Http\Controllers\OperatorPerusahaan\InsentifController::class, 'restore']);
    });

    // Riwayat Insentif (full CRUD + persetujuan)
    Route::middleware('permission:riwayat-insentif.list')->group(function () {
        Route::get('/operator-perusahaan/riwayat-insentif', [\App\Http\Controllers\OperatorPerusahaan\RiwayatInsentifController::class, 'index']);
    });
    Route::middleware('permission:riwayat-insentif.create')->group(function () {
        Route::post('/operator-perusahaan/riwayat-insentif', [\App\Http\Controllers\OperatorPerusahaan\RiwayatInsentifController::class, 'store']);
    });
    Route::middleware('permission:riwayat-insentif.edit')->group(function () {
        Route::put('/operator-perusahaan/riwayat-insentif/{empIncentiveLog}', [\App\Http\Controllers\OperatorPerusahaan\RiwayatInsentifController::class, 'update']);
    });
    Route::middleware('permission:riwayat-insentif.delete')->group(function () {
        Route::delete('/operator-perusahaan/riwayat-insentif/{empIncentiveLog}', [\App\Http\Controllers\OperatorPerusahaan\RiwayatInsentifController::class, 'destroy']);
        Route::post('/operator-perusahaan/riwayat-insentif/bulk-delete', [\App\Http\Controllers\OperatorPerusahaan\RiwayatInsentifController::class, 'bulkDelete']);
    });
    Route::middleware('permission:riwayat-insentif.restore')->group(function () {
        Route::patch('/operator-perusahaan/riwayat-insentif/{id}/restore', [\App\Http\Controllers\OperatorPerusahaan\RiwayatInsentifController::class, 'restore']);
    });
    Route::middleware('permission:riwayat-insentif.persetujuan')->group(function () {
        Route::post('/operator-perusahaan/riwayat-insentif/{id}/approve', [\App\Http\Controllers\OperatorPerusahaan\RiwayatInsentifController::class, 'approve']);
    });

    Route::middleware('permission:role-perusahaan-op.list')->group(function () {
        Route::get('/operator-perusahaan/role-perusahaan', function () {
            $roles = \App\Models\Role::where('scope', 'admin_perusahaan')->get();
            return Inertia::render('OperatorPerusahaan/RolePerusahaan', ['roles' => $roles]);
        });
    });

    Route::middleware('permission:admin-role-perusahaan-op.list')->group(function () {
        Route::get('/operator-perusahaan/admin-role-perusahaan', function () {
            $assignments = \App\Models\ModelHasRole::with(['role', 'model'])
                ->where('model_type', 'App\Models\AdminCompany')
                ->get();
            return Inertia::render('OperatorPerusahaan/AdminRolePerusahaan', ['assignments' => $assignments]);
        });
    });

    Route::middleware('permission:konfigurasi-perusahaan.list')->group(function () {
        Route::get('/operator-perusahaan/konfigurasi-perusahaan', function () {
            $configs = \App\Models\SaasConfig::all();
            return Inertia::render('OperatorPerusahaan/KonfigurasiPerusahaan', ['configs' => $configs]);
        });
    });

    Route::get('/operator-perusahaan/profil-saya', function () {
        return Inertia::render('OperatorPerusahaan/ProfilSaya', [
            'admin' => auth()->user()->load('company'),
        ]);
    });

    Route::middleware('permission:role-web-karyawan.list')->group(function () {
        Route::get('/operator-perusahaan/role-web-karyawan', function () {
            $roles = \App\Models\Role::where('scope', 'karyawan_perusahaan')->get();
            return Inertia::render('OperatorPerusahaan/RoleWebKaryawan', ['roles' => $roles]);
        });
    });

    Route::middleware('permission:admin-role-web-karyawan.list')->group(function () {
        Route::get('/operator-perusahaan/admin-role-web-karyawan', function () {
            $assignments = \App\Models\ModelHasRole::with(['role', 'model'])
                ->where('model_type', 'App\Models\Employee')
                ->get();
            return Inertia::render('OperatorPerusahaan/AdminRoleWebKaryawan', ['assignments' => $assignments]);
        });
    });
});
