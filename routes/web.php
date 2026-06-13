<?php

use App\Http\Controllers\FileProxyController;
use Illuminate\Support\Facades\Route;

// File proxy — serve MinIO/S3 files via Laravel (hindari SignatureDoesNotMatch MinIO)
Route::get('/file-proxy/{path?}', [FileProxyController::class, 'show'])
    ->where('path', '.*')
    ->middleware('auth:admin-company,admin-saas,employee,customer')
    ->name('file.proxy');

require __DIR__.'/web/landing-page.php';
require __DIR__.'/web/operator-saas.php';
require __DIR__.'/web/operator-perusahaan.php';
require __DIR__.'/web/karyawan.php';
require __DIR__.'/web/customer.php';
require __DIR__.'/web/webhooks.php';
require __DIR__.'/web/error-test.php';
