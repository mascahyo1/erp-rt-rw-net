<?php

namespace App\Support\Payment;

use App\Models\CustInternetPayment;

interface PaymentGatewayInterface
{
    /**
     * Buat transaksi pembayaran di gateway.
     * Return array dengan key: gateway_ref, redirect_url, raw_response.
     */
    public function createTransaction(CustInternetPayment $payment): array;

    /**
     * Cek status transaksi di gateway berdasarkan gateway_ref.
     */
    public function checkStatus(string $gatewayRef): array;

    /**
     * Handle callback/notification dari gateway.
     * Return array dengan key: status, amount_paid, gateway_ref, raw.
     */
    public function handleCallback(array $payload): array;

    /**
     * Nama gateway (midtrans, xendit, stripe, dsb.)
     */
    public function name(): string;
}
