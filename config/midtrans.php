<?php

/**
 * Midtrans Payment Gateway Configuration
 *
 * Dokumentasi:
 * - Sandbox: https://docs.midtrans.com/en/snap/overview
 * - Server Key & Client Key: https://dashboard.sandbox.midtrans.com/settings/access-keys
 *
 * Untuk production, ganti:
 *   MIDTRANS_IS_PRODUCTION=true
 *   MIDTRANS_SERVER_KEY=<production key dari Midtrans Dashboard>
 *   MIDTRANS_CLIENT_KEY=<production key dari Midtrans Dashboard>
 */

return [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),

    // Sandbox (false) atau production (true). Sandbox pakai https://app.sandbox.midtrans.com
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),

    // URL webhook yang akan dipanggil Midtrans saat status payment berubah.
    // Override MIDTRANS_NOTIFICATION_URL di .env kalau perlu.
    'notification_url' => env('MIDTRANS_NOTIFICATION_URL', env('APP_URL') . '/webhooks/midtrans'),

    // Sanitize params sebelum dikirim ke Midtrans (recommended true)
    'sanitize' => (bool) env('MIDTRANS_SANITIZE', true),

    // Enable 3D Secure untuk credit card
    'is_3ds' => true,

    // Snap base URL (computed)
    'snap_base_url' => env('MIDTRANS_IS_PRODUCTION', false)
        ? 'https://app.midtrans.com/snap/v1'
        : 'https://app.sandbox.midtrans.com/snap/v1',
];
