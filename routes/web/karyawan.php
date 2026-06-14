<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login-karyawan', [\App\Http\Controllers\Auth\EmployeeSessionController::class, 'create'])
    ->name('employee.login');

Route::post('/login-karyawan', [\App\Http\Controllers\Auth\EmployeeSessionController::class, 'store']);

// Lupa Password karyawan
Route::get('/lupa-password-karyawan', [ForgotPasswordController::class, 'create'])->defaults('portal', 'karyawan')->name('forgot-password.karyawan.form');
Route::post('/lupa-password-karyawan', [ForgotPasswordController::class, 'store'])->defaults('portal', 'karyawan')->middleware('throttle:30,1')->name('forgot-password.karyawan.send');
Route::post('/lupa-password-karyawan/reset', [ForgotPasswordController::class, 'update'])->defaults('portal', 'karyawan')->middleware('throttle:30,1')->name('forgot-password.karyawan.reset');

Route::middleware('auth:employee')->group(function () {
    Route::get('/karyawan/dashboard', function () {
        $employee = auth()->user();
        $companyId = $employee->company_id;
        return Inertia::render('Karyawan/Dashboard', [
            'stats' => [
                'customer_ditagih' => \App\Models\CustInternet::whereHas('customer', fn($q) => $q->where('company_id', $companyId))->where('internet_status', 'active')->count(),
                'tagihan_bulan_ini' => \App\Models\CustInternetInvc::whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))->whereMonth('created_at', now()->month)->count(),
                // Insentif yang sudah disetujui admin (review_status='approved') bulan ini
                'insentif_bulan_ini' => (float) \App\Models\EmpIncentiveLog::where('submitted_by_id', $employee->id)
                    ->where('submitted_by_type', 'employee')
                    ->where('review_status', 'approved')
                    ->whereMonth('date', now()->month)
                    ->whereYear('date', now()->year)
                    ->sum('amount'),
                'pembayaran_collection' => \App\Models\CustInternetPayment::whereHas('custInternetInvc.custInternet.customer', fn($q) => $q->where('company_id', $companyId))->count(),
            ],
        ]);
    })->name('employee.dashboard');

    Route::middleware('permission:profil-saya.list')->group(function () {
        Route::get('/karyawan/profil-saya', function () {
            $employee = auth()->user();
            $employee->load('company');
            return Inertia::render('Karyawan/ProfilSaya', [
                'employee' => [
                    'id' => $employee->id,
                    'code' => $employee->code,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'phone_country_code' => $employee->phone_country_code,
                    'phone_number' => $employee->phone_number,
                    'no_nik' => $employee->no_nik,
                    'no_kk' => $employee->no_kk,
                    'photo_ktp' => $employee->photo_ktp,
                    'photo_ktp_url' => $employee->photo_ktp ? route('file.proxy', ['path' => $employee->photo_ktp, 'disk' => 'minio']) : null,
                    'photo_kk' => $employee->photo_kk,
                    'photo_kk_url' => $employee->photo_kk ? route('file.proxy', ['path' => $employee->photo_kk, 'disk' => 'minio']) : null,
                    'photo_profile' => $employee->photo_profile,
                    'photo_profile_url' => $employee->photo_profile ? route('file.proxy', ['path' => $employee->photo_profile, 'disk' => 'minio']) : null,
                    'company' => $employee->company ? [
                        'id' => $employee->company->id,
                        'name' => $employee->company->name,
                    ] : null,
                ],
            ]);
        });
        Route::put('/karyawan/profil-saya', function (\Illuminate\Http\Request $request) {
            $employee = auth()->user();
            $uploadService = new \App\Services\FileUploadService();

            $validated = $request->validate([
                'no_nik' => ['nullable', 'string', 'max:50'],
                'no_kk' => ['nullable', 'string', 'max:50'],
                'photo_ktp' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
                'photo_kk' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
                'photo_profile' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ], [
                'photo_ktp.max' => 'Ukuran foto KTP maksimal 2MB.',
                'photo_kk.max' => 'Ukuran foto KK maksimal 2MB.',
                'photo_profile.max' => 'Ukuran foto profil maksimal 2MB.',
            ]);

            $data = [
                'no_nik' => $validated['no_nik'] ?? null,
                'no_kk' => $validated['no_kk'] ?? null,
            ];

            if ($request->hasFile('photo_ktp')) {
                if ($employee->photo_ktp) $uploadService->deleteFile($employee->photo_ktp);
                $data['photo_ktp'] = $uploadService->processDocument($request->file('photo_ktp'), 'employees');
            }
            if ($request->hasFile('photo_kk')) {
                if ($employee->photo_kk) $uploadService->deleteFile($employee->photo_kk);
                $data['photo_kk'] = $uploadService->processDocument($request->file('photo_kk'), 'employees');
            }
            if ($request->hasFile('photo_profile')) {
                if ($employee->photo_profile) $uploadService->deleteFile($employee->photo_profile);
                $data['photo_profile'] = $uploadService->processImage($request->file('photo_profile'), 'employees');
            }

            $employee->update($data);

            return back()->with('success', 'Profil berhasil diperbarui.');
        });
    });

    $perusahaanNs = 'App\Http\Controllers\OperatorPerusahaan';
    $apiNs = 'App\Http\Controllers\Api';

    // API search untuk SearchableSelectAjax component (karyawan scope)
    Route::get('/karyawan/api/search/customers', [$apiNs.'\SearchController', 'customers']);
    Route::get('/karyawan/api/search/packages', [$apiNs.'\SearchController', 'packages']);
    Route::get('/karyawan/api/search/langganans', [$apiNs.'\SearchController', 'langganans']);
    Route::get('/karyawan/api/search/invoices', [$apiNs.'\SearchController', 'invoices']);
    Route::get('/karyawan/api/search/incentives', [$apiNs.'\SearchController', 'incentives']);
    Route::get('/karyawan/api/search/employees', [$apiNs.'\SearchController', 'employees']);

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
    Route::middleware('permission:karyawan-langganan.export')
        ->get('/karyawan/langganan-customer/export', [$perusahaanNs.'\LanggananController', 'export']);
    Route::middleware('permission:karyawan-langganan.import')
        ->get('/karyawan/langganan-customer/template', [$perusahaanNs.'\LanggananController', 'template']);
    Route::middleware('permission:karyawan-langganan.import')
        ->post('/karyawan/langganan-customer/import', [$perusahaanNs.'\LanggananController', 'import']);

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
    Route::middleware('permission:karyawan-tagihan.delete')
        ->post('/karyawan/tagihan/bulk-delete', [$perusahaanNs.'\TagihanController', 'bulkDelete']);
    Route::middleware('permission:karyawan-tagihan.restore')
        ->post('/karyawan/tagihan/bulk-restore', [$perusahaanNs.'\TagihanController', 'bulkRestore']);
    Route::middleware('permission:karyawan-tagihan.edit')
        ->post('/karyawan/tagihan/bulk-status', [$perusahaanNs.'\TagihanController', 'bulkToggleStatus']);
    Route::middleware('permission:karyawan-tagihan.generate')
        ->post('/karyawan/tagihan/generate', [$perusahaanNs.'\TagihanController', 'generate']);
    Route::middleware('permission:karyawan-tagihan.export')
        ->get('/karyawan/tagihan/export', [$perusahaanNs.'\TagihanController', 'export']);
    Route::middleware('permission:karyawan-tagihan.import')
        ->get('/karyawan/tagihan/template', [$perusahaanNs.'\TagihanController', 'downloadTemplate']);
    Route::middleware('permission:karyawan-tagihan.import')
        ->post('/karyawan/tagihan/import', [$perusahaanNs.'\TagihanController', 'import']);
    Route::middleware('permission:karyawan-tagihan.export')
        ->get('/karyawan/tagihan/{id}/export-pdf', [$perusahaanNs.'\TagihanController', 'exportPdf']);
    Route::middleware('permission:karyawan-tagihan.export')
        ->get('/karyawan/tagihan/{id}/export-word', [$perusahaanNs.'\TagihanController', 'exportWord']);
    Route::middleware('permission:karyawan-tagihan.detail')
        ->get('/karyawan/api/tagihan/{id}/payments', [$perusahaanNs.'\TagihanController', 'paymentsAjax']);

    // Insentif Saya (log/submission klaim insentif - scope karyawan sendiri)
    Route::middleware('permission:riwayat-insentif.list')
        ->get('/karyawan/insentif-saya', [$perusahaanNs.'\RiwayatInsentifController', 'index'])->defaults('view', 'Karyawan/InsentifSaya');
    Route::middleware('permission:riwayat-insentif.create')
        ->post('/karyawan/insentif-saya', [$perusahaanNs.'\RiwayatInsentifController', 'store']);
    Route::middleware('permission:riwayat-insentif.edit')
        ->put('/karyawan/insentif-saya/{empIncentiveLog}', [$perusahaanNs.'\RiwayatInsentifController', 'update']);
    Route::middleware('permission:riwayat-insentif.delete')
        ->delete('/karyawan/insentif-saya/{empIncentiveLog}', [$perusahaanNs.'\RiwayatInsentifController', 'destroy']);
    Route::middleware('permission:riwayat-insentif.restore')
        ->patch('/karyawan/insentif-saya/{id}/restore', [$perusahaanNs.'\RiwayatInsentifController', 'restore']);

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
    Route::middleware('permission:karyawan-riwayat-pembayaran.delete')
        ->post('/karyawan/riwayat-pembayaran/bulk-delete', [$perusahaanNs.'\PembayaranController', 'bulkDelete']);
    Route::middleware('permission:karyawan-riwayat-pembayaran.restore')
        ->post('/karyawan/riwayat-pembayaran/bulk-restore', [$perusahaanNs.'\PembayaranController', 'bulkRestore']);
    Route::middleware('permission:karyawan-riwayat-pembayaran.persetujuan')
        ->post('/karyawan/riwayat-pembayaran/bulk-review', [$perusahaanNs.'\PembayaranController', 'bulkReview']);
    Route::middleware('permission:karyawan-riwayat-pembayaran.persetujuan')
        ->post('/karyawan/riwayat-pembayaran/{id}/review', [$perusahaanNs.'\PembayaranController', 'review']);
    // AJAX: Verifikasi manual status Midtrans (fallback saat webhook lambat/gagal)
    Route::middleware('permission:karyawan-riwayat-pembayaran.persetujuan')
        ->post('/karyawan/api/riwayat-pembayaran/{id}/verify-midtrans', [$perusahaanNs.'\PembayaranController', 'verifyMidtrans']);
    Route::middleware('permission:karyawan-riwayat-pembayaran.export')
        ->get('/karyawan/riwayat-pembayaran/export', [$perusahaanNs.'\PembayaranController', 'export']);
    Route::middleware('permission:karyawan-riwayat-pembayaran.import')
        ->get('/karyawan/riwayat-pembayaran/template', [$perusahaanNs.'\PembayaranController', 'downloadTemplate']);
    Route::middleware('permission:karyawan-riwayat-pembayaran.import')
        ->post('/karyawan/riwayat-pembayaran/import', [$perusahaanNs.'\PembayaranController', 'import']);
    Route::middleware('permission:karyawan-riwayat-pembayaran.export')
        ->get('/karyawan/riwayat-pembayaran/{id}/pdf', [$perusahaanNs.'\PembayaranController', 'downloadPdf']);
    Route::middleware('permission:karyawan-riwayat-pembayaran.export')
        ->get('/karyawan/riwayat-pembayaran/{id}/word', [$perusahaanNs.'\PembayaranController', 'downloadWord']);
});
