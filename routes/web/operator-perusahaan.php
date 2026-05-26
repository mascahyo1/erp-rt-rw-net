<?php

use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController;
use App\Http\Controllers\OperatorPerusahaan\AdminRolePerusahaanController;
use App\Http\Controllers\OperatorPerusahaan\AdminRoleWebKaryawanController;
use App\Http\Controllers\OperatorPerusahaan\CustomerController;
use App\Http\Controllers\OperatorPerusahaan\InsentifController;
use App\Http\Controllers\OperatorPerusahaan\KaryawanController;
use App\Http\Controllers\OperatorPerusahaan\KonfigurasiPerusahaanController;
use App\Http\Controllers\OperatorPerusahaan\LanggananController;
use App\Http\Controllers\OperatorPerusahaan\PaketController;
use App\Http\Controllers\OperatorPerusahaan\PembayaranController;
use App\Http\Controllers\OperatorPerusahaan\PerusahaanSayaController;
use App\Http\Controllers\OperatorPerusahaan\ProfilSayaController;
use App\Http\Controllers\OperatorPerusahaan\RiwayatInsentifController;
use App\Http\Controllers\OperatorPerusahaan\RolePerusahaanController;
use App\Http\Controllers\OperatorPerusahaan\RoleWebKaryawanController;
use App\Http\Controllers\OperatorPerusahaan\TagihanController;
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
            Route::get('/customer', [CustomerController::class, 'index'])->name('customer.index');
        });
        Route::middleware('permission:customer.create')->group(function () {
            Route::post('/customer', [CustomerController::class, 'store'])->name('customer.store');
        });
        Route::middleware('permission:customer.edit')->group(function () {
            Route::put('/customer/{customer}', [CustomerController::class, 'update'])->name('customer.update');
            Route::post('/customer/bulk-status', [CustomerController::class, 'bulkToggleStatus'])->name('customer.bulkStatus');
        });
        Route::middleware('permission:customer.delete')->group(function () {
            Route::delete('/customer/{customer}', [CustomerController::class, 'destroy'])->name('customer.destroy');
            Route::post('/customer/bulk-delete', [CustomerController::class, 'bulkDelete'])->name('customer.bulkDelete');
        });
        Route::middleware('permission:customer.restore')->group(function () {
            Route::patch('/customer/{id}/restore', [CustomerController::class, 'restore'])->name('customer.restore');
            Route::post('/customer/bulk-restore', [CustomerController::class, 'bulkRestore'])->name('customer.bulkRestore');
        });
        Route::middleware('permission:customer.export')->group(function () {
            Route::get('/customer/export', [CustomerController::class, 'export'])->name('customer.export');
        });
        Route::middleware('permission:customer.import')->group(function () {
            Route::get('/customer/template', [CustomerController::class, 'template'])->name('customer.template');
            Route::post('/customer/import', [CustomerController::class, 'import'])->name('customer.import');
        });

        // Karyawan
        Route::middleware('permission:karyawan.list')->group(function () {
            Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
        });
        Route::middleware('permission:karyawan.create')->group(function () {
            Route::post('/karyawan', [KaryawanController::class, 'store'])->name('karyawan.store');
        });
        Route::middleware('permission:karyawan.edit')->group(function () {
            Route::put('/karyawan/{employee}', [KaryawanController::class, 'update'])->name('karyawan.update');
            Route::post('/karyawan/bulk-status', [KaryawanController::class, 'bulkToggleStatus'])->name('karyawan.bulkStatus');
        });
        Route::middleware('permission:karyawan.delete')->group(function () {
            Route::delete('/karyawan/{employee}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');
            Route::post('/karyawan/bulk-delete', [KaryawanController::class, 'bulkDelete'])->name('karyawan.bulkDelete');
        });
        Route::middleware('permission:karyawan.restore')->group(function () {
            Route::patch('/karyawan/{id}/restore', [KaryawanController::class, 'restore'])->name('karyawan.restore');
            Route::post('/karyawan/bulk-restore', [KaryawanController::class, 'bulkRestore'])->name('karyawan.bulkRestore');
        });

        // Admin Perusahaan
        Route::middleware('permission:admin-perusahaan.list')->group(function () {
            Route::get('/admin-perusahaan', [AdminPerusahaanController::class, 'index'])->name('admin-perusahaan.index');
        });
        Route::middleware('permission:admin-perusahaan.create')->group(function () {
            Route::post('/admin-perusahaan', [AdminPerusahaanController::class, 'store'])->name('admin-perusahaan.store');
        });
        Route::middleware('permission:admin-perusahaan.edit')->group(function () {
            Route::put('/admin-perusahaan/{adminCompany}', [AdminPerusahaanController::class, 'update'])->name('admin-perusahaan.update');
            Route::post('/admin-perusahaan/bulk-status', [AdminPerusahaanController::class, 'bulkToggleStatus'])->name('admin-perusahaan.bulkStatus');
        });
        Route::middleware('permission:admin-perusahaan.delete')->group(function () {
            Route::delete('/admin-perusahaan/{adminCompany}', [AdminPerusahaanController::class, 'destroy'])->name('admin-perusahaan.destroy');
            Route::post('/admin-perusahaan/bulk-delete', [AdminPerusahaanController::class, 'bulkDelete'])->name('admin-perusahaan.bulkDelete');
        });
        Route::middleware('permission:admin-perusahaan.restore')->group(function () {
            Route::patch('/admin-perusahaan/{id}/restore', [AdminPerusahaanController::class, 'restore'])->name('admin-perusahaan.restore');
            Route::post('/admin-perusahaan/bulk-restore', [AdminPerusahaanController::class, 'bulkRestore'])->name('admin-perusahaan.bulkRestore');
        });

        // Perusahaan Saya
        Route::middleware('auth:admin-company')->group(function () {
            Route::get('/perusahaan-saya', [PerusahaanSayaController::class, 'index'])->name('perusahaan-saya.index');
            Route::put('/perusahaan-saya/{company}', [PerusahaanSayaController::class, 'update'])->name('perusahaan-saya.update')->middleware('permission:perusahaan-saya.edit');
        });

        // Role Perusahaan
        Route::middleware('permission:role-perusahaan-op.list')->group(function () {
            Route::get('/role-perusahaan', [RolePerusahaanController::class, 'index'])->name('role-perusahaan.index');
        });
        Route::middleware('permission:role-perusahaan-op.create')->group(function () {
            Route::post('/role-perusahaan', [RolePerusahaanController::class, 'store'])->name('role-perusahaan.store');
        });
        Route::middleware('permission:role-perusahaan-op.edit')->group(function () {
            Route::put('/role-perusahaan/{role}', [RolePerusahaanController::class, 'update'])->name('role-perusahaan.update');
            Route::post('/role-perusahaan/bulk-status', [RolePerusahaanController::class, 'bulkToggleStatus'])->name('role-perusahaan.bulkStatus');
        });
        Route::middleware('permission:role-perusahaan-op.delete')->group(function () {
            Route::delete('/role-perusahaan/{role}', [RolePerusahaanController::class, 'destroy'])->name('role-perusahaan.destroy');
            Route::post('/role-perusahaan/bulk-delete', [RolePerusahaanController::class, 'bulkDelete'])->name('role-perusahaan.bulkDelete');
        });
        Route::middleware('permission:role-perusahaan-op.restore')->group(function () {
            Route::patch('/role-perusahaan/{id}/restore', [RolePerusahaanController::class, 'restore'])->name('role-perusahaan.restore');
            Route::post('/role-perusahaan/bulk-restore', [RolePerusahaanController::class, 'bulkRestore'])->name('role-perusahaan.bulkRestore');
        });

        // Role Web Karyawan
        Route::middleware('permission:role-web-karyawan.list')->group(function () {
            Route::get('/role-web-karyawan', [RoleWebKaryawanController::class, 'index'])->name('role-web-karyawan.index');
        });
        Route::middleware('permission:role-web-karyawan.create')->group(function () {
            Route::post('/role-web-karyawan', [RoleWebKaryawanController::class, 'store'])->name('role-web-karyawan.store');
        });
        Route::middleware('permission:role-web-karyawan.edit')->group(function () {
            Route::put('/role-web-karyawan/{role}', [RoleWebKaryawanController::class, 'update'])->name('role-web-karyawan.update');
            Route::post('/role-web-karyawan/bulk-status', [RoleWebKaryawanController::class, 'bulkToggleStatus'])->name('role-web-karyawan.bulkStatus');
        });
        Route::middleware('permission:role-web-karyawan.delete')->group(function () {
            Route::delete('/role-web-karyawan/{role}', [RoleWebKaryawanController::class, 'destroy'])->name('role-web-karyawan.destroy');
            Route::post('/role-web-karyawan/bulk-delete', [RoleWebKaryawanController::class, 'bulkDelete'])->name('role-web-karyawan.bulkDelete');
        });
        Route::middleware('permission:role-web-karyawan.restore')->group(function () {
            Route::patch('/role-web-karyawan/{id}/restore', [RoleWebKaryawanController::class, 'restore'])->name('role-web-karyawan.restore');
            Route::post('/role-web-karyawan/bulk-restore', [RoleWebKaryawanController::class, 'bulkRestore'])->name('role-web-karyawan.bulkRestore');
        });

        // Admin Role Perusahaan
        Route::middleware('permission:admin-role-perusahaan-op.list')->group(function () {
            Route::get('/admin-role-perusahaan', [AdminRolePerusahaanController::class, 'index'])->name('admin-role-perusahaan.index');
        });
        Route::middleware('permission:admin-role-perusahaan-op.create')->group(function () {
            Route::post('/admin-role-perusahaan', [AdminRolePerusahaanController::class, 'store'])->name('admin-role-perusahaan.store');
        });
        Route::middleware('permission:admin-role-perusahaan-op.edit')->group(function () {
            Route::put('/admin-role-perusahaan/{modelHasRole}', [AdminRolePerusahaanController::class, 'update'])->name('admin-role-perusahaan.update');
        });
        Route::middleware('permission:admin-role-perusahaan-op.delete')->group(function () {
            Route::delete('/admin-role-perusahaan/{modelHasRole}', [AdminRolePerusahaanController::class, 'destroy'])->name('admin-role-perusahaan.destroy');
            Route::post('/admin-role-perusahaan/bulk-delete', [AdminRolePerusahaanController::class, 'bulkDelete'])->name('admin-role-perusahaan.bulkDelete');
        });

        // Admin Role Web Karyawan
        Route::middleware('permission:admin-role-web-karyawan.list')->group(function () {
            Route::get('/admin-role-web-karyawan', [AdminRoleWebKaryawanController::class, 'index'])->name('admin-role-web-karyawan.index');
        });
        Route::middleware('permission:admin-role-web-karyawan.create')->group(function () {
            Route::post('/admin-role-web-karyawan', [AdminRoleWebKaryawanController::class, 'store'])->name('admin-role-web-karyawan.store');
        });
        Route::middleware('permission:admin-role-web-karyawan.edit')->group(function () {
            Route::put('/admin-role-web-karyawan/{modelHasRole}', [AdminRoleWebKaryawanController::class, 'update'])->name('admin-role-web-karyawan.update');
        });
        Route::middleware('permission:admin-role-web-karyawan.delete')->group(function () {
            Route::delete('/admin-role-web-karyawan/{modelHasRole}', [AdminRoleWebKaryawanController::class, 'destroy'])->name('admin-role-web-karyawan.destroy');
            Route::post('/admin-role-web-karyawan/bulk-delete', [AdminRoleWebKaryawanController::class, 'bulkDelete'])->name('admin-role-web-karyawan.bulkDelete');
        });

        // Konfigurasi Perusahaan
        Route::middleware('permission:konfigurasi-perusahaan.list')->group(function () {
            Route::get('/konfigurasi-perusahaan', [KonfigurasiPerusahaanController::class, 'index'])->name('konfigurasi-perusahaan.index');
        });
        Route::middleware('permission:konfigurasi-perusahaan.create')->group(function () {
            Route::post('/konfigurasi-perusahaan', [KonfigurasiPerusahaanController::class, 'store'])->name('konfigurasi-perusahaan.store');
        });
        Route::middleware('permission:konfigurasi-perusahaan.edit')->group(function () {
            Route::put('/konfigurasi-perusahaan/{saasConfig}', [KonfigurasiPerusahaanController::class, 'update'])->name('konfigurasi-perusahaan.update');
        });
        Route::middleware('permission:konfigurasi-perusahaan.delete')->group(function () {
            Route::delete('/konfigurasi-perusahaan/{saasConfig}', [KonfigurasiPerusahaanController::class, 'destroy'])->name('konfigurasi-perusahaan.destroy');
            Route::post('/konfigurasi-perusahaan/bulk-delete', [KonfigurasiPerusahaanController::class, 'bulkDelete'])->name('konfigurasi-perusahaan.bulkDelete');
        });

        // Profil Saya
        Route::get('/profil-saya', [ProfilSayaController::class, 'index'])->name('profil-saya.index');
        Route::put('/profil-saya', [ProfilSayaController::class, 'update'])->name('profil-saya.update');
    });

    // Paket
    Route::middleware('permission:paket.list')->group(function () {
        Route::get('/operator-perusahaan/daftar-paket', [PaketController::class, 'index'])->name('operator-perusahaan.paket.index');
    });
    Route::middleware('permission:paket.create')->group(function () {
        Route::post('/operator-perusahaan/daftar-paket', [PaketController::class, 'store'])->name('operator-perusahaan.paket.store');
    });
    Route::middleware('permission:paket.edit')->group(function () {
        Route::put('/operator-perusahaan/daftar-paket/{internetPackage}', [PaketController::class, 'update'])->name('operator-perusahaan.paket.update');
        Route::post('/operator-perusahaan/daftar-paket/bulk-status', [PaketController::class, 'bulkToggleStatus'])->name('operator-perusahaan.paket.bulkStatus');
    });
    Route::middleware('permission:paket.delete')->group(function () {
        Route::delete('/operator-perusahaan/daftar-paket/{internetPackage}', [PaketController::class, 'destroy'])->name('operator-perusahaan.paket.destroy');
        Route::post('/operator-perusahaan/daftar-paket/bulk-delete', [PaketController::class, 'bulkDelete'])->name('operator-perusahaan.paket.bulkDelete');
    });
    Route::middleware('permission:paket.restore')->group(function () {
        Route::patch('/operator-perusahaan/daftar-paket/{id}/restore', [PaketController::class, 'restore'])->name('operator-perusahaan.paket.restore');
        Route::post('/operator-perusahaan/daftar-paket/bulk-restore', [PaketController::class, 'bulkRestore'])->name('operator-perusahaan.paket.bulkRestore');
    });

    Route::middleware('permission:paket.export')->group(function () {
        Route::get('/operator-perusahaan/daftar-paket/export', [PaketController::class, 'export'])->name('operator-perusahaan.paket.export');
    });

    Route::middleware('permission:paket.import')->group(function () {
        Route::get('/operator-perusahaan/daftar-paket/template', [PaketController::class, 'template'])->name('operator-perusahaan.paket.template');
        Route::post('/operator-perusahaan/daftar-paket/import', [PaketController::class, 'import'])->name('operator-perusahaan.paket.import');
    });

    // Langganan
    Route::middleware('permission:langganan.list')->group(function () {
        Route::get('/operator-perusahaan/langganan-customer', [LanggananController::class, 'index'])->name('operator-perusahaan.langganan.index');
        Route::get('/operator-perusahaan/langganan-customer/export', [LanggananController::class, 'export'])->name('operator-perusahaan.langganan.export');
        Route::get('/operator-perusahaan/langganan-customer/template', [LanggananController::class, 'template'])->name('operator-perusahaan.langganan.template');
    });
    Route::middleware('permission:langganan.create')->group(function () {
        Route::post('/operator-perusahaan/langganan-customer', [LanggananController::class, 'store'])->name('operator-perusahaan.langganan.store');
        Route::post('/operator-perusahaan/langganan-customer/import', [LanggananController::class, 'import'])->name('operator-perusahaan.langganan.import');
    });
    Route::middleware('permission:langganan.edit')->group(function () {
        Route::put('/operator-perusahaan/langganan-customer/{custInternet}', [LanggananController::class, 'update'])->name('operator-perusahaan.langganan.update');
        Route::post('/operator-perusahaan/langganan-customer/bulk-status', [LanggananController::class, 'bulkToggleStatus'])->name('operator-perusahaan.langganan.bulkStatus');
    });
    Route::middleware('permission:langganan.delete')->group(function () {
        Route::delete('/operator-perusahaan/langganan-customer/{custInternet}', [LanggananController::class, 'destroy'])->name('operator-perusahaan.langganan.destroy');
        Route::post('/operator-perusahaan/langganan-customer/bulk-delete', [LanggananController::class, 'bulkDelete'])->name('operator-perusahaan.langganan.bulkDelete');
    });
    Route::middleware('permission:langganan.restore')->group(function () {
        Route::patch('/operator-perusahaan/langganan-customer/{id}/restore', [LanggananController::class, 'restore'])->name('operator-perusahaan.langganan.restore');
        Route::post('/operator-perusahaan/langganan-customer/bulk-restore', [LanggananController::class, 'bulkRestore'])->name('operator-perusahaan.langganan.bulkRestore');
    });

    // Tagihan
    Route::middleware('permission:tagihan.list')->group(function () {
        Route::get('/operator-perusahaan/tagihan', [TagihanController::class, 'index'])->name('operator-perusahaan.tagihan.index');
    });
    Route::middleware('permission:tagihan.create')->group(function () {
        Route::post('/operator-perusahaan/tagihan', [TagihanController::class, 'store'])->name('operator-perusahaan.tagihan.store');
        Route::post('/operator-perusahaan/tagihan/generate', [TagihanController::class, 'generate'])->name('operator-perusahaan.tagihan.generate');
        Route::get('/operator-perusahaan/tagihan/template', [TagihanController::class, 'downloadTemplate'])->name('operator-perusahaan.tagihan.template');
        Route::post('/operator-perusahaan/tagihan/import', [TagihanController::class, 'import'])->name('operator-perusahaan.tagihan.import');
    });
    Route::middleware('permission:tagihan.edit')->group(function () {
        Route::put('/operator-perusahaan/tagihan/{custInternetInvc}', [TagihanController::class, 'update'])->name('operator-perusahaan.tagihan.update');
        Route::post('/operator-perusahaan/tagihan/bulk-status', [TagihanController::class, 'bulkToggleStatus'])->name('operator-perusahaan.tagihan.bulkStatus');
    });
    Route::middleware('permission:tagihan.delete')->group(function () {
        Route::delete('/operator-perusahaan/tagihan/{custInternetInvc}', [TagihanController::class, 'destroy'])->name('operator-perusahaan.tagihan.destroy');
        Route::post('/operator-perusahaan/tagihan/bulk-delete', [TagihanController::class, 'bulkDelete'])->name('operator-perusahaan.tagihan.bulkDelete');
    });
    Route::middleware('permission:tagihan.restore')->group(function () {
        Route::patch('/operator-perusahaan/tagihan/{id}/restore', [TagihanController::class, 'restore'])->name('operator-perusahaan.tagihan.restore');
        Route::post('/operator-perusahaan/tagihan/bulk-restore', [TagihanController::class, 'bulkRestore'])->name('operator-perusahaan.tagihan.bulkRestore');
    });
    Route::middleware('permission:tagihan.export')->group(function () {
        Route::get('/operator-perusahaan/tagihan/export', [TagihanController::class, 'export'])->name('operator-perusahaan.tagihan.export');
    });

    // Riwayat Pembayaran
    Route::middleware('permission:riwayat-pembayaran.list')->group(function () {
        Route::get('/operator-perusahaan/riwayat-pembayaran', [PembayaranController::class, 'index'])->name('operator-perusahaan.pembayaran.index');
    });
    Route::middleware('permission:riwayat-pembayaran.create')->group(function () {
        Route::post('/operator-perusahaan/riwayat-pembayaran', [PembayaranController::class, 'store'])->name('operator-perusahaan.pembayaran.store');
    });
    Route::middleware('permission:riwayat-pembayaran.edit')->group(function () {
        Route::put('/operator-perusahaan/riwayat-pembayaran/{custInternetPayment}', [PembayaranController::class, 'update'])->name('operator-perusahaan.pembayaran.update');
    });
    Route::middleware('permission:riwayat-pembayaran.delete')->group(function () {
        Route::delete('/operator-perusahaan/riwayat-pembayaran/{custInternetPayment}', [PembayaranController::class, 'destroy'])->name('operator-perusahaan.pembayaran.destroy');
        Route::post('/operator-perusahaan/riwayat-pembayaran/bulk-delete', [PembayaranController::class, 'bulkDelete'])->name('operator-perusahaan.pembayaran.bulkDelete');
    });
    Route::middleware('permission:riwayat-pembayaran.restore')->group(function () {
        Route::patch('/operator-perusahaan/riwayat-pembayaran/{id}/restore', [PembayaranController::class, 'restore'])->name('operator-perusahaan.pembayaran.restore');
        Route::post('/operator-perusahaan/riwayat-pembayaran/bulk-restore', [PembayaranController::class, 'bulkRestore'])->name('operator-perusahaan.pembayaran.bulkRestore');
    });
    Route::middleware('permission:riwayat-pembayaran.persetujuan')->group(function () {
        Route::post('/operator-perusahaan/riwayat-pembayaran/{id}/approve', [PembayaranController::class, 'approve'])->name('operator-perusahaan.pembayaran.approve');
    });

    // Insentif
    Route::middleware('permission:insentif.list')->group(function () {
        Route::get('/operator-perusahaan/insentif', [InsentifController::class, 'index'])->name('operator-perusahaan.insentif.index');
    });
    Route::middleware('permission:insentif.create')->group(function () {
        Route::post('/operator-perusahaan/insentif', [InsentifController::class, 'store'])->name('operator-perusahaan.insentif.store');
    });
    Route::middleware('permission:insentif.edit')->group(function () {
        Route::put('/operator-perusahaan/insentif/{empIncentive}', [InsentifController::class, 'update'])->name('operator-perusahaan.insentif.update');
        Route::post('/operator-perusahaan/insentif/bulk-status', [InsentifController::class, 'bulkToggleStatus'])->name('operator-perusahaan.insentif.bulkStatus');
    });
    Route::middleware('permission:insentif.delete')->group(function () {
        Route::delete('/operator-perusahaan/insentif/{empIncentive}', [InsentifController::class, 'destroy'])->name('operator-perusahaan.insentif.destroy');
        Route::post('/operator-perusahaan/insentif/bulk-delete', [InsentifController::class, 'bulkDelete'])->name('operator-perusahaan.insentif.bulkDelete');
    });
    Route::middleware('permission:insentif.restore')->group(function () {
        Route::patch('/operator-perusahaan/insentif/{id}/restore', [InsentifController::class, 'restore'])->name('operator-perusahaan.insentif.restore');
        Route::post('/operator-perusahaan/insentif/bulk-restore', [InsentifController::class, 'bulkRestore'])->name('operator-perusahaan.insentif.bulkRestore');
    });
    Route::middleware('permission:insentif.export')->group(function () {
        Route::get('/operator-perusahaan/insentif/export', [InsentifController::class, 'export'])->name('operator-perusahaan.insentif.export');
        Route::get('/operator-perusahaan/insentif/template', [InsentifController::class, 'downloadTemplate'])->name('operator-perusahaan.insentif.template');
    });
    Route::middleware('permission:insentif.import')->group(function () {
        Route::post('/operator-perusahaan/insentif/import', [InsentifController::class, 'import'])->name('operator-perusahaan.insentif.import');
    });

    // Riwayat Insentif
    Route::middleware('permission:riwayat-insentif.list')->group(function () {
        Route::get('/operator-perusahaan/riwayat-insentif', [RiwayatInsentifController::class, 'index'])->name('operator-perusahaan.riwayat-insentif.index');
    });
    Route::middleware('permission:riwayat-insentif.create')->group(function () {
        Route::post('/operator-perusahaan/riwayat-insentif', [RiwayatInsentifController::class, 'store'])->name('operator-perusahaan.riwayat-insentif.store');
    });
    Route::middleware('permission:riwayat-insentif.edit')->group(function () {
        Route::put('/operator-perusahaan/riwayat-insentif/{empIncentiveLog}', [RiwayatInsentifController::class, 'update'])->name('operator-perusahaan.riwayat-insentif.update');
    });
    Route::middleware('permission:riwayat-insentif.delete')->group(function () {
        Route::delete('/operator-perusahaan/riwayat-insentif/{empIncentiveLog}', [RiwayatInsentifController::class, 'destroy'])->name('operator-perusahaan.riwayat-insentif.destroy');
        Route::post('/operator-perusahaan/riwayat-insentif/bulk-delete', [RiwayatInsentifController::class, 'bulkDelete'])->name('operator-perusahaan.riwayat-insentif.bulkDelete');
    });
    Route::middleware('permission:riwayat-insentif.restore')->group(function () {
        Route::patch('/operator-perusahaan/riwayat-insentif/{id}/restore', [RiwayatInsentifController::class, 'restore'])->name('operator-perusahaan.riwayat-insentif.restore');
        Route::post('/operator-perusahaan/riwayat-insentif/bulk-restore', [RiwayatInsentifController::class, 'bulkRestore'])->name('operator-perusahaan.riwayat-insentif.bulkRestore');
    });
    Route::middleware('permission:riwayat-insentif.persetujuan')->group(function () {
        Route::post('/operator-perusahaan/riwayat-insentif/{id}/review', [RiwayatInsentifController::class, 'review'])->name('operator-perusahaan.riwayat-insentif.review');
        Route::post('/operator-perusahaan/riwayat-insentif/bulk-review', [RiwayatInsentifController::class, 'bulkReview'])->name('operator-perusahaan.riwayat-insentif.bulkReview');
    });
    Route::middleware('permission:riwayat-insentif.list')->group(function () {
        Route::get('/operator-perusahaan/riwayat-insentif/export', [RiwayatInsentifController::class, 'export'])->name('operator-perusahaan.riwayat-insentif.export');
    });

    // API search untuk SearchableSelectAjax component
    Route::get('/operator-perusahaan/api/search/customers', [SearchController::class, 'customers'])->name('operator-perusahaan.api.search.customers');
    Route::get('/operator-perusahaan/api/search/packages', [SearchController::class, 'packages'])->name('operator-perusahaan.api.search.packages');
    Route::get('/operator-perusahaan/api/search/langganans', [SearchController::class, 'langganans'])->name('operator-perusahaan.api.search.langganans');
    Route::get('/operator-perusahaan/api/search/invoices', [SearchController::class, 'invoices'])->name('operator-perusahaan.api.search.invoices');
    Route::get('/operator-perusahaan/api/search/incentives', [SearchController::class, 'incentives'])->name('operator-perusahaan.api.search.incentives');
    Route::get('/operator-perusahaan/api/search/employees', [SearchController::class, 'employees'])->name('operator-perusahaan.api.search.employees');
});
