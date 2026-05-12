<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/operator-perusahaan/dashboard', function () {
    return Inertia::render('OperatorPerusahaan/Dashboard');
});

Route::get('/operator-perusahaan/perusahaan-saya', function () {
    return Inertia::render('OperatorPerusahaan/PerusahaanSaya');
});

Route::get('/operator-perusahaan/daftar-paket', function () {
    return Inertia::render('OperatorPerusahaan/DaftarPaket');
});

Route::get('/operator-perusahaan/customer', function () {
    return Inertia::render('OperatorPerusahaan/Customer');
});

Route::get('/operator-perusahaan/langganan-customer', function () {
    return Inertia::render('OperatorPerusahaan/LanggananCustomer');
});

Route::get('/operator-perusahaan/tagihan', function () {
    return Inertia::render('OperatorPerusahaan/Tagihan');
});

Route::get('/operator-perusahaan/riwayat-pembayaran', function () {
    return Inertia::render('OperatorPerusahaan/RiwayatPembayaran');
});

Route::get('/operator-perusahaan/insentif', function () {
    return Inertia::render('OperatorPerusahaan/Insentif');
});

Route::get('/operator-perusahaan/riwayat-insentif', function () {
    return Inertia::render('OperatorPerusahaan/RiwayatInsentif');
});

Route::get('/operator-perusahaan/admin-perusahaan', function () {
    return Inertia::render('OperatorPerusahaan/AdminPerusahaan');
});

Route::get('/operator-perusahaan/role-perusahaan', function () {
    return Inertia::render('OperatorPerusahaan/RolePerusahaan');
});

Route::get('/operator-perusahaan/admin-role-perusahaan', function () {
    return Inertia::render('OperatorPerusahaan/AdminRolePerusahaan');
});

Route::get('/operator-perusahaan/karyawan', function () {
    return Inertia::render('OperatorPerusahaan/Karyawan');
});

Route::get('/operator-perusahaan/konfigurasi-perusahaan', function () {
    return Inertia::render('OperatorPerusahaan/KonfigurasiPerusahaan');
});

Route::get('/operator-perusahaan/profil-saya', function () {
    return Inertia::render('OperatorPerusahaan/ProfilSaya');
});

Route::get('/operator-perusahaan/role-web-karyawan', function () {
    return Inertia::render('OperatorPerusahaan/RoleWebKaryawan');
});

Route::get('/operator-perusahaan/admin-role-web-karyawan', function () {
    return Inertia::render('OperatorPerusahaan/AdminRoleWebKaryawan');
});
