<?php

namespace App\Http\Controllers\Customer;

use App\Enums\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Models\CustInternetInvc;
use App\Models\CustInternetPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap;
use Midtrans\Transaction;

/**
 * Midtrans Snap Payment Gateway integration untuk customer portal.
 *
 * 3 actions:
 *  - createSnapToken: POST /customer/pembayaran-tambah/create-snap-token
 *  - checkStatus:     GET  /customer/pembayaran-tambah/{id}/status
 *  - handleWebhook:   POST /webhooks/midtrans (public, CSRF-exempt)
 *
 * Dokumentasi Midtrans Snap:
 *  - https://docs.midtrans.com/en/snap/overview
 *  - https://docs.midtrans.com/en/after-payment/http-notification
 */
class MidtransPaymentController extends Controller
{
    /**
     * Apply Midtrans config dari config/midtrans.php ke static class SDK.
     * Dipanggil setiap kali akan panggil SDK (mencegah state leak).
     */
    private function applyMidtransConfig(): void
    {
        MidtransConfig::$serverKey = config('midtrans.server_key');
        MidtransConfig::$clientKey = config('midtrans.client_key');
        MidtransConfig::$isProduction = config('midtrans.is_production');
        MidtransConfig::$isSanitized = config('midtrans.sanitize');
        MidtransConfig::$is3ds = config('midtrans.is_3ds');
        MidtransConfig::$overrideNotifUrl = config('midtrans.notification_url');
    }

    /**
     * Step 1: Customer klik "Bayar Online" → buat Snap token.
     * Backend: create CustInternetPayment (pending) + panggil Midtrans API → return snap_token.
     */
    public function createSnapToken(Request $request): JsonResponse
    {
        $customer = auth()->user();

        $validated = $request->validate([
            'cust_internet_invc_id' => ['required', 'uuid', 'exists:cust_internet_invcs,id'],
            'amount_paid' => ['required', 'numeric', 'min:1'],
        ], [
            'cust_internet_invc_id.required' => 'Pilih tagihan terlebih dahulu.',
            'amount_paid.required' => 'Nominal pembayaran wajib diisi.',
        ]);

        // Validasi tagihan milik customer ini (security)
        $tagihan = CustInternetInvc::whereHas('custInternet', fn($q) => $q->where('customer_id', $customer->id))
            ->where('payment_status', '!=', 'paid')
            ->findOrFail($validated['cust_internet_invc_id']);

        // Generate Midtrans order_id yang unique
        $orderId = 'PAY-' . strtoupper(Str::random(12));

        // Simpan payment record dengan status=pending (untuk di-update webhook nanti)
        $payment = CustInternetPayment::create([
            'cust_internet_invc_id' => $tagihan->id,
            'amount_paid' => $validated['amount_paid'],
            'payment_date' => now(),
            'payment_method' => 'midtrans',
            'provider' => PaymentProvider::MIDTRANS->value,
            'status' => 'pending',
            'midtrans_order_id' => $orderId,
            'midtrans_expires_at' => now()->addHours(24), // VA biasanya expire 24 jam
            'created_by' => $customer->id,
            'updated_by' => $customer->id,
        ]);

        // Apply config & panggil Midtrans Snap
        $this->applyMidtransConfig();

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) round($validated['amount_paid']),
            ],
            'customer_details' => [
                'first_name' => $customer->name,
                'email' => $customer->email,
                'phone' => trim(($customer->phone_country_code ?? '+62') . ($customer->phone_number ?? '')),
            ],
            'item_details' => [[
                'id' => $tagihan->invoice_number,
                'price' => (int) round($validated['amount_paid']),
                'quantity' => 1,
                'name' => 'Tagihan ' . $tagihan->invoice_number,
            ]],
            // Expiry: 24 jam dari sekarang
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'hour',
                'duration' => 24,
            ],
        ];

        try {
            $snapResponse = Snap::createTransaction($params);
        } catch (\Throwable $e) {
            Log::error('Midtrans Snap::createTransaction failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            // Rollback payment supaya customer tidak stuck
            $payment->forceDelete();
            return response()->json([
                'error' => 'Gagal membuat transaksi Midtrans: ' . $e->getMessage(),
            ], 502);
        }

        $payment->update([
            'snap_token' => $snapResponse->token,
            'data' => array_merge($payment->data ?? [], [
                'redirect_url' => $snapResponse->redirect_url,
                'created_at_midtrans' => now()->toIso8601String(),
            ]),
        ]);

        return response()->json([
            'snap_token' => $snapResponse->token,
            'redirect_url' => $snapResponse->redirect_url,
            'midtrans_order_id' => $orderId,
            'payment_id' => $payment->id,
            'client_key' => config('midtrans.client_key'),
        ]);
    }

    /**
     * Step 2: Customer polling status (kalau webhook lambat, atau customer refresh).
     * Cek ke Midtrans API langsung untuk status real-time.
     */
    public function checkStatus(string $paymentId): JsonResponse
    {
        $customer = auth()->user();

        $payment = CustInternetPayment::with('custInternetInvc.custInternet')
            ->whereHas('custInternetInvc.custInternet', fn($q) => $q->where('customer_id', $customer->id))
            ->where('provider', PaymentProvider::MIDTRANS->value)
            ->findOrFail($paymentId);

        // Kalau status sudah final, return langsung (no need call Midtrans)
        if (in_array($payment->status, ['paid', 'expired', 'cancelled', 'rejected'])) {
            return response()->json($this->serializePayment($payment));
        }

        // Status masih pending → cek ke Midtrans real-time
        if ($payment->midtrans_order_id) {
            try {
                $this->applyMidtransConfig();
                $resp = Transaction::status($payment->midtrans_order_id);

                // Map response Midtrans → payment kita (idempotent, sama logic dgn webhook)
                $newStatus = $this->mapMidtransStatus(
                    $resp->transaction_status ?? null,
                    $resp->fraud_status ?? null,
                    $resp->payment_type ?? null
                );

                if ($newStatus !== $payment->status) {
                    DB::transaction(function () use ($payment, $resp, $newStatus) {
                        $payment->update([
                            'status' => $newStatus,
                            'midtrans_payment_type' => $resp->payment_type ?? null,
                            'midtrans_va_number' => $resp->va_numbers[0]->va_number ?? null,
                            'midtrans_fraud_status' => $resp->fraud_status ?? null,
                            'midtrans_settled_at' => in_array($newStatus, ['paid', 'cancelled']) ? now() : null,
                            'data' => array_merge($payment->data ?? [], [
                                'last_status_check' => now()->toIso8601String(),
                                'midtrans_response' => (array) $resp,
                            ]),
                        ]);

                        if ($newStatus === 'paid') {
                            $payment->custInternetInvc->update([
                                'payment_status' => 'paid',
                                'paid_at' => now(),
                            ]);
                        }
                    });
                    $payment->refresh();
                }
            } catch (\Throwable $e) {
                Log::warning('Midtrans Transaction::status failed', [
                    'order_id' => $payment->midtrans_order_id,
                    'error' => $e->getMessage(),
                ]);
                // Fallback: return current state
            }
        }

        return response()->json($this->serializePayment($payment));
    }

    /**
     * Step 3: Webhook dari Midtrans. Public, no auth, CSRF-exempt.
     * URL: POST /webhooks/midtrans
     *
     * Implementasi local signature validation (SHA512 hash check) — lebih cepat
     * dan reliable dari SDK Notification class (yg call API Midtrans untuk verify).
     *
     * Signature format (per Midtrans docs):
     *   SHA512(order_id + status_code + gross_amount + server_key)
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $body = $request->all();

        $orderId = $body['order_id'] ?? null;
        $statusCode = $body['status_code'] ?? null;
        $grossAmount = $body['gross_amount'] ?? null;
        $signatureKey = $body['signature_key'] ?? null;
        $transactionStatus = $body['transaction_status'] ?? null;
        $fraudStatus = $body['fraud_status'] ?? null;
        $paymentType = $body['payment_type'] ?? null;

        // Validate signature
        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            Log::warning('Midtrans webhook missing required fields', $body);
            return response()->json(['error' => 'Missing required fields'], 400);
        }

        $serverKey = config('midtrans.server_key');
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if (!hash_equals($expectedSignature, $signatureKey)) {
            Log::warning('Midtrans webhook signature mismatch', [
                'order_id' => $orderId,
                'expected_prefix' => substr($expectedSignature, 0, 12),
                'received_prefix' => substr($signatureKey, 0, 12),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        if (!$transactionStatus) {
            Log::warning('Midtrans webhook missing transaction_status', $body);
            return response()->json(['error' => 'Missing transaction_status'], 400);
        }

        $payment = CustInternetPayment::where('midtrans_order_id', $orderId)->first();
        if (!$payment) {
            Log::warning('Midtrans webhook: order_id not found', ['order_id' => $orderId]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        $newStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus, $paymentType);

        // Idempotency: kalau status sudah final dan sama, skip update
        $isFinal = in_array($payment->status, ['paid', 'expired', 'cancelled', 'rejected']);
        if ($isFinal && $payment->status === $newStatus) {
            return response()->json(['status' => 'ok', 'noop' => true]);
        }

        DB::transaction(function () use ($payment, $newStatus, $body, $paymentType) {
            $vaNumber = null;
            if ($paymentType === 'bank_transfer' && !empty($body['va_numbers'])) {
                $vaNumber = $body['va_numbers'][0]['va_number'] ?? null;
            }

            $payment->update([
                'status' => $newStatus,
                'midtrans_payment_type' => $paymentType,
                'midtrans_va_number' => $vaNumber,
                'midtrans_fraud_status' => $body['fraud_status'] ?? null,
                'midtrans_settled_at' => $newStatus === 'paid' ? now() : $payment->midtrans_settled_at,
                'payment_date' => $newStatus === 'paid' ? now() : $payment->payment_date,
                'data' => array_merge($payment->data ?? [], [
                    'webhook_received_at' => now()->toIso8601String(),
                    'midtrans_full_response' => $body,
                ]),
            ]);

            // Kalau paid → update invoice
            if ($newStatus === 'paid') {
                $payment->custInternetInvc->update([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                ]);
            }
        });

        Log::info('Midtrans webhook processed', [
            'order_id' => $orderId,
            'new_status' => $newStatus,
            'payment_id' => $payment->id,
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Map status dari Midtrans ke status internal kita.
     * - settlement (bank_transfer/e_wallet/qris) → paid
     * - capture (credit_card) + fraud=accept → paid
     * - capture + fraud=challenge → pending (admin review needed)
     * - capture + fraud=deny → cancelled
     * - pending → pending
     * - deny → cancelled
     * - expire → expired
     * - cancel → cancelled
     */
    private function mapMidtransStatus(?string $txStatus, ?string $fraudStatus, ?string $paymentType): string
    {
        if ($txStatus === 'capture' && $paymentType === 'credit_card') {
            if ($fraudStatus === 'accept') return 'paid';
            if ($fraudStatus === 'challenge') return 'pending';
            return 'cancelled';
        }
        return match ($txStatus) {
            'settlement' => 'paid',
            'pending' => 'pending',
            'deny' => 'cancelled',
            'expire' => 'expired',
            'cancel' => 'cancelled',
            default => 'pending',
        };
    }

    private function serializePayment(CustInternetPayment $p): array
    {
        return [
            'id' => $p->id,
            'status' => $p->status,
            'midtrans_status' => $p->status,
            'midtrans_order_id' => $p->midtrans_order_id,
            'midtrans_payment_type' => $p->midtrans_payment_type,
            'midtrans_va_number' => $p->midtrans_va_number,
            'midtrans_fraud_status' => $p->midtrans_fraud_status,
            'midtrans_settled_at' => $p->midtrans_settled_at?->toIso8601String(),
            'midtrans_expires_at' => $p->midtrans_expires_at?->toIso8601String(),
            'amount_paid' => (float) $p->amount_paid,
            'snap_token' => $p->snap_token,
        ];
    }
}
