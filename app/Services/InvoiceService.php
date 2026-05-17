<?php

namespace App\Services;

use App\Models\CustInternet;
use App\Models\CustInternetInvc;
use Illuminate\Support\Str;

class InvoiceService
{
    /**
     * Generate invoice untuk satu langganan.
     * Dipanggil tiap bulan (via scheduler) atau manual.
     */
    public function generateFor(CustInternet $langganan, ?\DateTime $period = null): CustInternetInvc
    {
        $period ??= new \DateTime('first day of this month');

        $billingAmount = $langganan->billing_amount > 0
            ? $langganan->billing_amount
            : ($langganan->internetPackage?->price ?? 0);

        $discount = 0;
        $tax = 0;
        $grandTotal = $billingAmount - $discount + $tax;

        $invoice = CustInternetInvc::create([
            'id' => Str::uuid(),
            'cust_internet_id' => $langganan->id,
            'invoice_number' => $this->generateNumber(),
            'amount' => $billingAmount,
            'total_amount' => $billingAmount,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'grand_total' => $grandTotal,
            'due_date' => (clone $period)->modify('+15 days'),
            'invoice_due_date' => (clone $period)->modify('+15 days'),
            'payment_status' => 'unpaid',
            'status' => 'unpaid',
            'description' => 'Tagihan periode ' . $period->format('M Y'),
        ]);

        return $invoice;
    }

    /**
     * Generate invoice untuk semua langganan aktif.
     */
    public function generateMonthly(): int
    {
        $count = 0;
        $period = new \DateTime('first day of this month');

        CustInternet::where('internet_status', 'active')
            ->whereDoesntHave('invoices', function ($q) use ($period) {
                $q->whereMonth('created_at', $period->format('m'))
                  ->whereYear('created_at', $period->format('Y'));
            })
            ->chunk(100, function ($langganans) use ($period, &$count) {
                foreach ($langganans as $langganan) {
                    $this->generateFor($langganan, $period);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Generate nomor invoice unik.
     */
    protected function generateNumber(): string
    {
        return 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

    /**
     * Tandai invoice sebagai lunas setelah pembayaran diverifikasi.
     */
    public function markAsPaid(CustInternetInvc $invoice): void
    {
        $invoice->update([
            'payment_status' => 'paid',
            'status' => 'paid',
            'paid_at' => now(),
            'status_description' => 'Pembayaran diterima',
        ]);

        // Update langganan billing status
        $invoice->custInternet?->update(['billing_status' => 'paid']);
    }
}
