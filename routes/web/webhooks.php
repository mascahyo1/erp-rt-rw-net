<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Webhook Routes
|--------------------------------------------------------------------------
|
| Routes di file ini adalah PUBLIC (no auth) dan CSRF-exempt.
| Dipanggil oleh service eksternal (Midtrans) untuk update status payment.
|
| Security:
|  - Midtrans SDK otomatis validate signature_key dari request body.
|  - Tetap pasang VerifyCsrfToken $except untuk double-safety.
|  - Log semua incoming webhook untuk audit trail.
|
*/

// Midtrans payment gateway webhook
Route::post('/webhooks/midtrans', [\App\Http\Controllers\Customer\MidtransPaymentController::class, 'handleWebhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])
    ->name('webhooks.midtrans');
