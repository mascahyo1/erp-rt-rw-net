<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Landing/Home');
});

Route::get('/tentang-kami', function () {
    return Inertia::render('Landing/TentangKami');
});

Route::get('/hubungi-kami', function () {
    return Inertia::render('Landing/HubungiKami');
});

Route::get('/syarat-dan-ketentuan', function () {
    return Inertia::render('Landing/SyaratKetentuan');
});

Route::get('/kebijakan-privasi', function () {
    return Inertia::render('Landing/KebijakanPrivasi');
});

Route::get('/login-operator-saas', function () {
    return Inertia::render('Landing/LoginOperatorSaaS');
});

Route::get('/login-perusahaan', function () {
    return Inertia::render('Landing/LoginPerusahaan');
});

Route::get('/login-pelanggan', function () {
    return Inertia::render('Landing/LoginPelanggan');
});
