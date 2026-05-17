<?php

namespace App\Support\Payment;

use App\Models\CustInternetPayment;

class MidtransGateway implements PaymentGatewayInterface
{
    public function __construct()
    {
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$clientKey = config('services.midtrans.client_key');
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production', false);
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
    }

    public function name(): string
    {
        return 'midtrans';
    }

    public function createTransaction(CustInternetPayment $payment): array
    {
        $invoice = $payment->custInternetInvc;
        $customer = $invoice?->custInternet?->customer;

        $orderId = $payment->id;
        $grossAmount = (int) $payment->amount_paid;

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => $customer ? [
                'first_name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone_number,
            ] : [],
            'item_details' => [[
                'id' => $invoice?->invoice_number ?? $orderId,
                'price' => $grossAmount,
                'quantity' => 1,
                'name' => 'Pembayaran Tagihan Internet',
            ]],
        ];

        $snapToken = \Midtrans\Snap::getSnapToken($params);

        return [
            'gateway_ref' => $orderId,
            'snap_token' => $snapToken,
            'redirect_url' => null, // Snap JS handles this on frontend
            'raw_response' => ['snap_token' => $snapToken],
        ];
    }

    public function checkStatus(string $gatewayRef): array
    {
        $response = \Midtrans\Transaction::status($gatewayRef);

        $statusMap = [
            'capture' => 'paid',
            'settlement' => 'paid',
            'pending' => 'pending',
            'deny' => 'rejected',
            'cancel' => 'cancelled',
            'expire' => 'expired',
            'failure' => 'rejected',
        ];

        return [
            'status' => $statusMap[$response->transaction_status ?? ''] ?? 'pending',
            'amount_paid' => $response->gross_amount ?? 0,
            'gateway_ref' => $gatewayRef,
            'raw' => (array) $response,
        ];
    }

    public function handleCallback(array $payload): array
    {
        $transaction = $payload['transaction_status'] ?? '';
        $orderId = $payload['order_id'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? 0;

        $statusMap = [
            'capture' => 'paid',
            'settlement' => 'paid',
            'pending' => 'pending',
            'deny' => 'rejected',
            'cancel' => 'cancelled',
            'expire' => 'expired',
            'failure' => 'rejected',
        ];

        return [
            'status' => $statusMap[$transaction] ?? 'pending',
            'amount_paid' => (int) $grossAmount,
            'gateway_ref' => $orderId,
            'raw' => $payload,
        ];
    }
}
