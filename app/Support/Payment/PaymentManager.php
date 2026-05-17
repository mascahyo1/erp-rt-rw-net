<?php

namespace App\Support\Payment;

use App\Models\CustInternetPayment;
use InvalidArgumentException;

class PaymentManager
{
    protected array $gateways = [];

    /**
     * Daftarkan payment gateway.
     */
    public function register(string $name, PaymentGatewayInterface $gateway): void
    {
        $this->gateways[$name] = $gateway;
    }

    /**
     * Ambil gateway berdasarkan nama.
     */
    public function driver(?string $name = null): PaymentGatewayInterface
    {
        $name ??= config('services.payment.default', 'midtrans');

        if (! isset($this->gateways[$name])) {
            throw new InvalidArgumentException("Payment gateway [{$name}] tidak terdaftar.");
        }

        return $this->gateways[$name];
    }

    /**
     * Buat transaksi di gateway yang sesuai.
     */
    public function create(CustInternetPayment $payment, ?string $gateway = null): array
    {
        return $this->driver($gateway ?? $payment->provider)->createTransaction($payment);
    }

    /**
     * Cek status pembayaran.
     */
    public function status(CustInternetPayment $payment): array
    {
        return $this->driver($payment->provider)->checkStatus($payment->id);
    }

    /**
     * Proses callback dari gateway.
     */
    public function callback(string $gateway, array $payload): array
    {
        return $this->driver($gateway)->handleCallback($payload);
    }

    /**
     * Daftar gateway yang tersedia.
     */
    public function available(): array
    {
        return array_keys($this->gateways);
    }
}
