<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth:customer')->group(function () {
    Route::get('/customer/dashboard', function () {
        $customer = auth()->user();

        return Inertia::render('Customer/Dashboard', [
            'stats' => [
                'paket_aktif' => \App\Models\CustInternet::where('customer_id', $customer->id)->where('internet_status', 'active')->count(),
                'tagihan_bulan_ini' => \App\Models\CustInternetInvc::whereHas('custInternet', fn($q) => $q->where('customer_id', $customer->id))->whereMonth('created_at', now()->month)->count(),
                'riwayat_pembayaran' => \App\Models\CustInternetPayment::whereHas('custInternetInvc.custInternet', fn($q) => $q->where('customer_id', $customer->id))->count(),
            ],
        ]);
    })->name('customer.dashboard');

    Route::get('/customer/profil-saya', function () {
        $customer = auth()->user()->load('company');
        return Inertia::render('Customer/ProfilSaya', [
            'customer' => $customer,
            'customer_extra' => [
                'code' => $customer->code,
                'no_nik' => $customer->no_nik,
                'no_kk' => $customer->no_kk,
                'photo_ktp' => $customer->photo_ktp,
                'photo_ktp_url' => $customer->photo_ktp ? route('file.proxy', ['path' => $customer->photo_ktp, 'disk' => 'minio']) : null,
                'photo_kk' => $customer->photo_kk,
                'photo_kk_url' => $customer->photo_kk ? route('file.proxy', ['path' => $customer->photo_kk, 'disk' => 'minio']) : null,
                'photo_profile' => $customer->photo_profile,
                'photo_profile_url' => $customer->photo_profile ? route('file.proxy', ['path' => $customer->photo_profile, 'disk' => 'minio']) : null,
            ],
        ]);
    });

    Route::put('/customer/profil-saya', function (\Illuminate\Http\Request $request) {
        $customer = auth()->user();
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
            if ($customer->photo_ktp) $uploadService->deleteFile($customer->photo_ktp);
            $data['photo_ktp'] = $uploadService->processDocument($request->file('photo_ktp'), 'customers');
        }
        if ($request->hasFile('photo_kk')) {
            if ($customer->photo_kk) $uploadService->deleteFile($customer->photo_kk);
            $data['photo_kk'] = $uploadService->processDocument($request->file('photo_kk'), 'customers');
        }
        if ($request->hasFile('photo_profile')) {
            if ($customer->photo_profile) $uploadService->deleteFile($customer->photo_profile);
            $data['photo_profile'] = $uploadService->processImage($request->file('photo_profile'), 'customers');
        }

        $customer->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    });

    Route::get('/customer/paket-saya', function () {
        $customer = auth()->user();
        $pakets = \App\Models\CustInternet::with(['internetPackage', 'customer'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'nama_paket' => $p->internetPackage?->name ?? 'Paket',
                'kecepatan' => $p->internetPackage?->speed ?? 0,
                'harga' => $p->billing_amount ?? 0,
                'fup' => $p->internetPackage?->fup ?? null,
                'status' => $p->internet_status === 'active' ? 'Aktif' : 'Nonaktif',
                'tgl_mulai' => $p->created_at?->format('Y-m-d'),
                'tgl_akhir' => $p->billing_cycle_end?->format('Y-m-d'),
                'account_number' => $p->account_number,
            ]);

        return Inertia::render('Customer/PaketSaya', [
            'pakets' => $pakets,
        ]);
    });

    // Daftar paket internet (katalog paket yang ditawarkan, terlepas dari langganan customer)
    Route::get('/customer/daftar-paket', function () {
        $customer = auth()->user();
        $companyId = $customer->company_id;

        $pakets = \App\Models\InternetPackage::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('price', 'asc')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'kode' => $p->code,
                'nama' => $p->name,
                'deskripsi' => $p->description,
                'harga' => (float) $p->price,
                'kecepatan_down_kbps' => (float) $p->speed_down_kbps,
                'kecepatan_up_kbps' => (float) $p->speed_up_kbps,
                'kuota_gb' => $p->quota_gb,
                'unlimited' => $p->is_unlimited,
                'billing_cycle' => $p->billing_cycle,
                'max_devices' => $p->max_devices,
                'fup_quota_down' => $p->fup_quota_down,
                'fup_quota_up' => $p->fup_quota_up,
                'fup_speed_down_kbps' => $p->fup_speed_down_kbps,
                'fup_speed_up_kbps' => $p->fup_speed_up_kbps,
            ]);

        return Inertia::render('Customer/DaftarPaket', [
            'pakets' => $pakets,
        ]);
    });

    // Detail paket (katalog)
    Route::get('/customer/daftar-paket/detail', function () {
        $id = request()->query('id');
        $p = \App\Models\InternetPackage::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->findOrFail($id);

        return Inertia::render('Customer/DaftarPaketDetail', [
            'paket' => [
                'id' => $p->id,
                'kode' => $p->code,
                'nama' => $p->name,
                'deskripsi' => $p->description,
                'harga' => (float) $p->price,
                'kecepatan_down_kbps' => (float) $p->speed_down_kbps,
                'kecepatan_up_kbps' => (float) $p->speed_up_kbps,
                'kuota_gb' => $p->quota_gb,
                'unlimited' => $p->is_unlimited,
                'billing_cycle' => $p->billing_cycle,
                'max_devices' => $p->max_devices,
                'fup_quota_down' => $p->fup_quota_down,
                'fup_quota_up' => $p->fup_quota_up,
                'fup_speed_down_kbps' => $p->fup_speed_down_kbps,
                'fup_speed_up_kbps' => $p->fup_speed_up_kbps,
            ],
        ]);
    });

    // Form tambah langganan (customer buat CustInternet baru)
    Route::get('/customer/paket-tambah', function () {
        $customer = auth()->user();
        $companyId = $customer->company_id;

        $pakets = \App\Models\InternetPackage::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('price', 'asc')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'nama' => $p->name,
                'harga' => (float) $p->price,
                'kecepatan_down_kbps' => (float) $p->speed_down_kbps,
                'kecepatan_up_kbps' => (float) $p->speed_up_kbps,
                'kuota_gb' => $p->quota_gb,
                'max_devices' => $p->max_devices,
                'billing_cycle' => $p->billing_cycle,
            ]);

        $preselected = null;
        $idPaket = request()->query('id_paket');
        if ($idPaket) {
            $p = \App\Models\InternetPackage::where('company_id', $companyId)
                ->where('is_active', true)
                ->find($idPaket);
            if ($p) {
                $preselected = ['id' => $p->id, 'nama' => $p->name, 'harga' => (float) $p->price];
            }
        }

        return Inertia::render('Customer/PaketTambah', [
            'pakets' => $pakets,
            'preselected' => $preselected,
        ]);
    });

    Route::post('/customer/paket-tambah', function (\Illuminate\Http\Request $request) {
        $customer = auth()->user();

        $validated = $request->validate([
            'internet_package_id' => ['required', 'uuid', 'exists:internet_packages,id'],
            'customer_address' => ['required', 'string', 'max:500'],
            'company_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'internet_package_id.required' => 'Pilih paket terlebih dahulu.',
            'internet_package_id.exists' => 'Paket tidak valid.',
            'customer_address.required' => 'Alamat instalasi wajib diisi.',
        ]);

        // Validasi paket milik company customer
        $paket = \App\Models\InternetPackage::where('company_id', $customer->company_id)
            ->where('is_active', true)
            ->findOrFail($validated['internet_package_id']);

        $accountNumber = 'CI-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));

        \App\Models\CustInternet::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $paket->id,
            'account_number' => $accountNumber,
            'customer_address' => $validated['customer_address'],
            'internet_status' => 'inactive', // menunggu aktivasi admin (enum: active/inactive/suspended/terminated)
            'company_notes' => $validated['company_notes'] ?? null,
            'created_by' => $customer->id,
            'updated_by' => $customer->id,
        ]);

        return back()->with('success', 'Pengajuan langganan berhasil. Mohon tunggu konfirmasi admin.');
    });

    // Form tambah pembayaran
    Route::get('/customer/pembayaran-tambah', function () {
        $customer = auth()->user();

        $tagihans = \App\Models\CustInternetInvc::with(['custInternet.internetPackage'])
            ->whereHas('custInternet', fn($q) => $q->where('customer_id', $customer->id))
            ->where('payment_status', '!=', 'paid')
            ->latest()
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'kode' => $t->invoice_number,
                'nominal' => $t->grand_total ?? $t->total_amount ?? 0,
                'paket' => $t->custInternet?->internetPackage?->name ?? '—',
                'tgl_jatuh_tempo' => $t->due_date?->format('Y-m-d'),
            ]);

        return Inertia::render('Customer/PembayaranTambah', [
            'tagihans' => $tagihans,
        ]);
    });

    Route::post('/customer/pembayaran-tambah', function (\Illuminate\Http\Request $request) {
        $customer = auth()->user();

        $validated = $request->validate([
            'cust_internet_invc_id' => ['required', 'uuid', 'exists:cust_internet_invcs,id'],
            'amount_paid' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:tunai,transfer_bank,e_wallet,qris'],
            'status_description' => ['nullable', 'string', 'max:500'],
        ], [
            'cust_internet_invc_id.required' => 'Pilih tagihan terlebih dahulu.',
            'amount_paid.required' => 'Nominal pembayaran wajib diisi.',
            'amount_paid.min' => 'Nominal harus lebih dari 0.',
            'payment_method.required' => 'Pilih metode pembayaran.',
        ]);

        $tagihan = \App\Models\CustInternetInvc::whereHas('custInternet', fn($q) => $q->where('customer_id', $customer->id))
            ->findOrFail($validated['cust_internet_invc_id']);

        \App\Models\CustInternetPayment::create([
            'cust_internet_invc_id' => $tagihan->id,
            'amount_paid' => $validated['amount_paid'],
            'payment_date' => now(),
            'payment_method' => $validated['payment_method'],
            'provider' => 'customer-portal',
            'status' => 'pending', // menunggu verifikasi admin
            'status_description' => $validated['status_description'] ?? null,
            'created_by' => $customer->id,
            'updated_by' => $customer->id,
        ]);

        return back()->with('success', 'Pembayaran berhasil dicatat. Mohon tunggu verifikasi admin.');
    });

    Route::get('/customer/paket-saya/detail', function () {
        $id = request()->query('id');
        $p = \App\Models\CustInternet::with(['internetPackage', 'customer'])
            ->where('customer_id', auth()->id())
            ->findOrFail($id);

        return Inertia::render('Customer/PaketDetail', [
            'paket' => [
                'id' => $p->id,
                'nama_paket' => $p->internetPackage?->name ?? 'Paket',
                'kecepatan' => $p->internetPackage?->speed ?? 0,
                'harga' => $p->billing_amount ?? 0,
                'fup' => $p->internetPackage?->fup ?? null,
                'status' => $p->internet_status === 'active' ? 'Aktif' : 'Nonaktif',
                'tgl_mulai' => $p->created_at?->format('Y-m-d'),
                'tgl_akhir' => $p->billing_cycle_end?->format('Y-m-d'),
                'account_number' => $p->account_number,
                'internet_status' => $p->internet_status,
                'billing_cycle_start' => $p->billing_cycle_start?->format('Y-m-d'),
            ],
        ]);
    });

    Route::get('/customer/tagihan-saya', function () {
        $customer = auth()->user();
        $tagihans = \App\Models\CustInternetInvc::with(['custInternet.internetPackage'])
            ->whereHas('custInternet', fn($q) => $q->where('customer_id', $customer->id))
            ->latest()
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'kode' => $t->invoice_number,
                'invoice_number' => $t->invoice_number,
                'nominal' => $t->grand_total ?? $t->total_amount ?? 0,
                'total_amount' => $t->total_amount,
                'grand_total' => $t->grand_total,
                'status' => $t->payment_status === 'paid' ? 'Lunas' : ($t->payment_status === 'overdue' ? 'Kadaluarsa' : 'Belum Bayar'),
                'payment_status' => $t->payment_status,
                'tgl_jatuh_tempo' => $t->due_date?->format('Y-m-d'),
                'due_date' => $t->due_date?->format('Y-m-d'),
                'tgl_bayar' => $t->paid_at?->format('Y-m-d'),
                'paid_at' => $t->paid_at?->format('Y-m-d'),
            ]);

        return Inertia::render('Customer/TagihanSaya', [
            'tagihans' => $tagihans,
        ]);
    });

    Route::get('/customer/tagihan-saya/detail', function () {
        $id = request()->query('id');
        $t = \App\Models\CustInternetInvc::with(['custInternet.internetPackage', 'custInternet.customer'])
            ->whereHas('custInternet', fn($q) => $q->where('customer_id', auth()->id()))
            ->findOrFail($id);

        return Inertia::render('Customer/TagihanDetail', [
            'tagihan' => [
                'id' => $t->id,
                'kode' => $t->invoice_number,
                'invoice_number' => $t->invoice_number,
                'nominal' => $t->grand_total ?? $t->total_amount ?? 0,
                'total_amount' => $t->total_amount,
                'grand_total' => $t->grand_total,
                'paket' => $t->custInternet?->internetPackage?->name ?? '—',
                'status' => $t->payment_status === 'paid' ? 'Lunas' : ($t->payment_status === 'overdue' ? 'Kadaluarsa' : 'Belum Bayar'),
                'payment_status' => $t->payment_status,
                'tgl_jatuh_tempo' => $t->due_date?->format('Y-m-d'),
                'due_date' => $t->due_date?->format('Y-m-d'),
                'tgl_bayar' => $t->paid_at?->format('Y-m-d'),
                'paid_at' => $t->paid_at?->format('Y-m-d'),
                'metode' => $t->payment_method ?? '—',
                'payment_method' => $t->payment_method,
            ],
        ]);
    });

    Route::get('/customer/riwayat-pembayaran', function () {
        $customer = auth()->user();
        $pembayarans = \App\Models\CustInternetPayment::with(['custInternetInvc.custInternet.customer'])
            ->whereHas('custInternetInvc.custInternet', fn($q) => $q->where('customer_id', $customer->id))
            ->latest()
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'kode' => $p->custInternetInvc?->invoice_number ?? '—',
                'invoice_number' => $p->custInternetInvc?->invoice_number,
                'jumlah' => $p->amount_paid ?? 0,
                'amount_paid' => $p->amount_paid,
                'metode' => $p->payment_method ?? '—',
                'payment_method' => $p->payment_method,
                'tgl_bayar' => $p->created_at?->format('Y-m-d'),
                'created_at' => $p->created_at?->format('Y-m-d'),
                'status' => $p->status,
                'status_description' => $p->status_description,
            ]);

        return Inertia::render('Customer/RiwayatPembayaran', [
            'pembayarans' => $pembayarans,
        ]);
    });

    Route::get('/customer/riwayat-pembayaran/detail', function () {
        $id = request()->query('id');
        $p = \App\Models\CustInternetPayment::with(['custInternetInvc.custInternet.customer'])
            ->whereHas('custInternetInvc.custInternet', fn($q) => $q->where('customer_id', auth()->id()))
            ->findOrFail($id);

        return Inertia::render('Customer/PembayaranDetail', [
            'pembayaran' => [
                'id' => $p->id,
                'kode' => $p->custInternetInvc?->invoice_number ?? '—',
                'invoice_number' => $p->custInternetInvc?->invoice_number,
                'jumlah' => $p->amount_paid ?? 0,
                'amount_paid' => $p->amount_paid,
                'metode' => $p->payment_method ?? '—',
                'payment_method' => $p->payment_method,
                'tgl_bayar' => $p->created_at?->format('Y-m-d'),
                'created_at' => $p->created_at?->format('Y-m-d'),
                'keterangan' => $p->status_description,
                'status' => $p->status,
            ],
        ]);
    });
});
