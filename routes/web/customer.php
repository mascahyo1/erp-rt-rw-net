<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/customer/login-register', function () {
    return Inertia::render('Customer/LoginRegister');
});

Route::middleware('auth:customer')->group(function () {
    Route::get('/customer/dashboard', function () {
        return Inertia::render('Customer/Dashboard');
    })->name('customer.dashboard');

    Route::get('/customer/profil-saya', function () {
        return Inertia::render('Customer/ProfilSaya');
    });

    Route::get('/customer/paket-saya', function () {
        return Inertia::render('Customer/PaketSaya');
    });

    Route::get('/customer/paket-saya/tambah', function () {
        return Inertia::render('Customer/PaketTambah');
    });

    Route::get('/customer/paket-saya/detail', function () {
        return Inertia::render('Customer/PaketDetail');
    });

    Route::get('/customer/tagihan-saya', function () {
        return Inertia::render('Customer/TagihanSaya');
    });

    Route::get('/customer/tagihan-saya/detail', function () {
        return Inertia::render('Customer/TagihanDetail');
    });

    Route::get('/customer/riwayat-pembayaran', function () {
        return Inertia::render('Customer/RiwayatPembayaran');
    });

    Route::get('/customer/riwayat-pembayaran/tambah', function () {
        return Inertia::render('Customer/PembayaranTambah');
    });

    Route::get('/customer/riwayat-pembayaran/detail', function () {
        return Inertia::render('Customer/PembayaranDetail');
    });
});
