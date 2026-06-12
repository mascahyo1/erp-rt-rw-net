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

    Route::middleware('permission:profil-saya.list')->group(function () {
        Route::get('/karyawan/profil-saya', function () { return Inertia::render('Karyawan/ProfilSaya'); });
    });

    $perusahaanNs = 'App\Http\Controllers\OperatorPerusahaan';

    // Customer — reuse OpPerusahaan controller, render Karyawan view
    Route::middleware('permission:karyawan-customer.list')
        ->get('/karyawan/customer', [$perusahaanNs.'\CustomerController', 'index'])->defaults('view', 'Karyawan/Customer');
    Route::middleware('permission:karyawan-customer.create')
        ->post('/karyawan/customer', [$perusahaanNs.'\CustomerController', 'store']);
    Route::middleware('permission:karyawan-customer.edit')
        ->put('/karyawan/customer/{customer}', [$perusahaanNs.'\CustomerController', 'update']);
    Route::middleware('permission:karyawan-customer.delete')
        ->delete('/karyawan/customer/{customer}', [$perusahaanNs.'\CustomerController', 'destroy']);
    Route::middleware('permission:karyawan-customer.restore')
        ->patch('/karyawan/customer/{id}/restore', [$perusahaanNs.'\CustomerController', 'restore']);
    Route::middleware('permission:karyawan-customer.delete')
        ->post('/karyawan/customer/bulk-delete', [$perusahaanNs.'\CustomerController', 'bulkDelete']);
    Route::middleware('permission:karyawan-customer.edit')
        ->post('/karyawan/customer/bulk-status', [$perusahaanNs.'\CustomerController', 'bulkToggleStatus']);
    Route::middleware('permission:karyawan-customer.export')
        ->get('/karyawan/customer/export', [$perusahaanNs.'\CustomerController', 'export']);
    Route::middleware('permission:karyawan-customer.import')
        ->get('/karyawan/customer/template', [$perusahaanNs.'\CustomerController', 'template']);
    Route::middleware('permission:karyawan-customer.import')
        ->post('/karyawan/customer/import', [$perusahaanNs.'\CustomerController', 'import']);

    // Paket (read-only list+detail for karyawan)
    Route::middleware('permission:karyawan-paket.list')
        ->get('/karyawan/paket', [$perusahaanNs.'\PaketController', 'index'])->defaults('view', 'Karyawan/Paket');

    // Langganan
    Route::middleware('permission:karyawan-langganan.list')
        ->get('/karyawan/langganan-customer', [$perusahaanNs.'\LanggananController', 'index'])->defaults('view', 'Karyawan/LanggananCustomer');
    Route::middleware('permission:karyawan-langganan.create')
        ->post('/karyawan/langganan-customer', [$perusahaanNs.'\LanggananController', 'store']);
    Route::middleware('permission:karyawan-langganan.edit')
        ->put('/karyawan/langganan-customer/{custInternet}', [$perusahaanNs.'\LanggananController', 'update']);
    Route::middleware('permission:karyawan-langganan.delete')
        ->delete('/karyawan/langganan-customer/{custInternet}', [$perusahaanNs.'\LanggananController', 'destroy']);
    Route::middleware('permission:karyawan-langganan.restore')
        ->patch('/karyawan/langganan-customer/{id}/restore', [$perusahaanNs.'\LanggananController', 'restore']);

    // Tagihan
    Route::middleware('permission:karyawan-tagihan.list')
        ->get('/karyawan/tagihan', [$perusahaanNs.'\TagihanController', 'index'])->defaults('view', 'Karyawan/Tagihan');
    Route::middleware('permission:karyawan-tagihan.create')
        ->post('/karyawan/tagihan', [$perusahaanNs.'\TagihanController', 'store']);
    Route::middleware('permission:karyawan-tagihan.edit')
        ->put('/karyawan/tagihan/{custInternetInvc}', [$perusahaanNs.'\TagihanController', 'update']);
    Route::middleware('permission:karyawan-tagihan.delete')
        ->delete('/karyawan/tagihan/{custInternetInvc}', [$perusahaanNs.'\TagihanController', 'destroy']);
    Route::middleware('permission:karyawan-tagihan.restore')
        ->patch('/karyawan/tagihan/{id}/restore', [$perusahaanNs.'\TagihanController', 'restore']);

    // Insentif
    Route::middleware('permission:karyawan-insentif.list')
        ->get('/karyawan/insentif-saya', [$perusahaanNs.'\InsentifController', 'index'])->defaults('view', 'Karyawan/InsentifSaya');
    Route::middleware('permission:karyawan-insentif.create')
        ->post('/karyawan/insentif-saya', [$perusahaanNs.'\InsentifController', 'store']);
    Route::middleware('permission:karyawan-insentif.edit')
        ->put('/karyawan/insentif-saya/{empIncentive}', [$perusahaanNs.'\InsentifController', 'update']);
    Route::middleware('permission:karyawan-insentif.delete')
        ->delete('/karyawan/insentif-saya/{empIncentive}', [$perusahaanNs.'\InsentifController', 'destroy']);
    Route::middleware('permission:karyawan-insentif.restore')
        ->patch('/karyawan/insentif-saya/{id}/restore', [$perusahaanNs.'\InsentifController', 'restore']);

    // Riwayat Pembayaran
    Route::middleware('permission:karyawan-riwayat-pembayaran.list')
        ->get('/karyawan/riwayat-pembayaran', [$perusahaanNs.'\PembayaranController', 'index'])->defaults('view', 'Karyawan/RiwayatPembayaran');
    Route::middleware('permission:karyawan-riwayat-pembayaran.create')
        ->post('/karyawan/riwayat-pembayaran', [$perusahaanNs.'\PembayaranController', 'store']);
    Route::middleware('permission:karyawan-riwayat-pembayaran.edit')
        ->put('/karyawan/riwayat-pembayaran/{custInternetPayment}', [$perusahaanNs.'\PembayaranController', 'update']);
    Route::middleware('permission:karyawan-riwayat-pembayaran.delete')
        ->delete('/karyawan/riwayat-pembayaran/{custInternetPayment}', [$perusahaanNs.'\PembayaranController', 'destroy']);
    Route::middleware('permission:karyawan-riwayat-pembayaran.restore')
        ->patch('/karyawan/riwayat-pembayaran/{id}/restore', [$perusahaanNs.'\PembayaranController', 'restore']);
});
