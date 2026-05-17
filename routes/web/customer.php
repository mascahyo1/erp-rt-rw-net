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
        ]);
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
