<?php

namespace App\Services;

use App\Models\CompanyConfig;
use App\Models\CustInternet;
use App\Models\CustInternetInvc;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Service for bulk-generating invoice (tagihan) records.
 *
 * Reused by:
 * - TagihanController::generate() (manual button from UI)
 * - App\Console\Commands\GenerateInvoicesCommand (artisan CLI + scheduler)
 *
 * MVP scope: only `monthly` billing_cycle. Non-monthly packages are skipped
 * with a warning (extension deferred until data exists).
 *
 * Due-date default (3-layer priority):
 *   1. CLI option --due-days=N (explicit override)
 *   2. CompanyConfig key `invoice.default_due_days` (per-company, admin sets)
 *   3. Hardcoded 30 days (fallback)
 */
class InvoiceGeneratorService
{
    public const DEFAULT_DUE_DAYS = 30;
    public const CONFIG_KEY_DUE_DAYS = 'invoice.default_due_days';
    public const SUPPORTED_CYCLE = 'monthly';

    /**
     * Generate invoices for a given company + period.
     *
     * @param  string  $companyId
     * @param  int  $year  e.g. 2026
     * @param  int  $month  1-12
     * @param  int|null  $dueDaysOverride  explicit --due-days=N value, or null to use config
     * @return array{generated:int, skipped_existing:int, skipped_cycle:int, due_date:string}
     */
    public function generate(string $companyId, int $year, int $month, ?int $dueDaysOverride = null): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        // Due date = end of period + N days (e.g. Aug 31 + 14 days = Sep 14)
        $dueDate = $endDate->copy()->addDays($this->resolveDueDays($companyId, $dueDaysOverride));

        // For monthly cycle: every active langganan gets invoiced (idempotent
        // via exists check). billing_cycle_start/end is metadata for when
        // the langganan started, NOT a filter for which months to invoice.
        $langganans = $this->getActiveMonthlyLangganan($companyId);

        if ($langganans->isEmpty()) {
            return [
                'generated' => 0,
                'skipped_existing' => 0,
                'skipped_cycle' => 0,
                'due_date' => $dueDate->format('Y-m-d'),
            ];
        }

        $now = now();
        $inserts = [];
        $skippedExisting = 0;
        $skippedCycle = 0;
        $count = 0;

        foreach ($langganans as $langganan) {
            // MVP: skip non-monthly packages
            $package = $langganan->internetPackage;
            if (!$package || $package->billing_cycle !== self::SUPPORTED_CYCLE) {
                $skippedCycle++;
                continue;
            }

            // Idempotency: skip if invoice already exists for this period
            $exists = CustInternetInvc::where('cust_internet_id', $langganan->id)
                ->whereMonth('usage_start_date', $month)
                ->whereYear('usage_start_date', $year)
                ->exists();
            if ($exists) {
                $skippedExisting++;
                continue;
            }

            $grandTotal = $langganan->billing_amount;

            $inserts[] = [
                'id' => Str::uuid7(),
                'cust_internet_id' => $langganan->id,
                'invoice_number' => 'INV-' . $year . str_pad($month, 2, '0', STR_PAD_LEFT)
                    . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT),
                'usage_start_date' => $startDate->format('Y-m-d'),
                'usage_end_date' => $endDate->format('Y-m-d'),
                'invoice_due_date' => $dueDate->format('Y-m-d'),
                'due_date' => $dueDate->format('Y-m-d'),
                'amount' => $grandTotal,
                'total_amount' => $grandTotal,
                'grand_total' => $grandTotal,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'payment_status' => 'unpaid',
                'status' => 'unpaid',
                'description' => 'Tagihan periode ' . $startDate->translatedFormat('F Y'),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $count++;
        }

        if (!empty($inserts)) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                CustInternetInvc::insert($chunk);
            }
        }

        return [
            'generated' => $count,
            'skipped_existing' => $skippedExisting,
            'skipped_cycle' => $skippedCycle,
            'due_date' => $dueDate->format('Y-m-d'),
        ];
    }

    /**
     * Resolve due_days from 3-layer priority.
     */
    public function resolveDueDays(string $companyId, ?int $override = null): int
    {
        if ($override !== null && $override > 0) {
            return $override;
        }
        $config = CompanyConfig::where('company_id', $companyId)
            ->where('key', self::CONFIG_KEY_DUE_DAYS)
            ->value('value');
        if ($config !== null && is_numeric($config) && (int) $config > 0) {
            return (int) $config;
        }
        return self::DEFAULT_DUE_DAYS;
    }

    /**
     * Query active langganan (no date filter — monthly recurring billing
     * invoices all active langganan every month, idempotent via exists check).
     */
    protected function getActiveMonthlyLangganan(string $companyId): Collection
    {
        return CustInternet::with(['customer', 'internetPackage'])
            ->whereHas('customer', fn($q) => $q->where('company_id', $companyId))
            ->where('internet_status', 'active')
            ->get();
    }
}
