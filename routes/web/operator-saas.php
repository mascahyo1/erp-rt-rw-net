<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/operator-saas/login', function () {
    return Inertia::render('OperatorSaas/Login');
});

Route::get('/operator-saas/dashboard', function () {
    return Inertia::render('OperatorSaas/Dashboard');
});

Route::get('/operator-saas/admin-perusahaan', function () {
    return Inertia::render('OperatorSaas/AdminPerusahaan');
});

Route::get('/operator-saas/perusahaan', function () {
    return Inertia::render('OperatorSaas/Perusahaan');
});

Route::get('/operator-saas/role-perusahaan', function () {
    return Inertia::render('OperatorSaas/RolePerusahaan');
});

Route::get('/operator-saas/pemetaan-admin-perusahaan', function () {
    return Inertia::render('OperatorSaas/RoleAdminPerusahaan');
});

Route::get('/operator-saas/role-admin-perusahaan', function () {
    return Inertia::render('OperatorSaas/RoleAdminPerusahaan');
});

Route::get('/operator-saas/konfigurasi', function () {
    return Inertia::render('OperatorSaas/Konfigurasi');
});

Route::get('/operator-saas/role-saas', function () {
    return Inertia::render('OperatorSaas/RoleSaaS');
});

Route::get('/operator-saas/admin-saas', function () {
    return Inertia::render('OperatorSaas/AdminSaaS');
});

Route::get('/operator-saas/admin-role-saas', function () {
    return Inertia::render('OperatorSaas/AdminRoleSaaS');
});
