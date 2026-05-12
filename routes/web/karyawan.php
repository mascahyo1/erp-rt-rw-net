<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login-karyawan', function () {
    return Inertia::render('Karyawan/Login');
});

Route::get('/karyawan/dashboard', function () {
    return Inertia::render('Karyawan/Dashboard');
});

Route::get('/karyawan/profil-saya', function () {
    return Inertia::render('Karyawan/ProfilSaya');
});

Route::get('/karyawan/customer', function () {
    return Inertia::render('Karyawan/Customer');
});

Route::get('/karyawan/langganan-customer', function () {
    return Inertia::render('Karyawan/LanggananCustomer');
});

Route::get('/karyawan/tagihan', function () {
    return Inertia::render('Karyawan/Tagihan');
});

Route::get('/karyawan/insentif-saya', function () {
    return Inertia::render('Karyawan/InsentifSaya');
});

Route::get('/karyawan/riwayat-pembayaran', function () {
    return Inertia::render('Karyawan/RiwayatPembayaran');
});
