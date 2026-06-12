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
 * Supports 4 cycles: daily, weekly, monthly, yearly. Per-cycle logic:
 * - Daily: 1 invoice per langganan for exact usage_date
 * - Weekly: 1 invoice per langganan for week starting at usage_start_date (Senin)
 * - Monthly: 1 invoice per langganan for year-month
 * - Yearly: 1 invoice per langganan for year
 *
 * Due-date default (3-layer priority):
 *   1. CLI option --due-days=N (explicit override)
 *   2. CompanyConfig key `invoice.default_due_days` (per-company, admin sets)
 *   3. Hardcoded 30 days (fallback)
 *
 * Invoice number format (hybrid per design decision #2):
 *   INV-{CYCLE}-{PERIOD}-{TIMESTAMP_MS}-{6_LETTERS}-{6_DIGITS}
 *   Example: INV-M-202610-1717838400123-XKHQNZ-123456
 */
class InvoiceGeneratorService
{
    public const DEFAULT_DUE_DAYS = 30;
    public const CONFIG_KEY_DUE_DAYS = 'invoice.default_due_days';

    public const CYCLE_DAILY = 'daily';
    public const CYCLE_WEEKLY = 'weekly';
    public const CYCLE_MONTHLY = 'monthly';
    public const CYCLE_YEARLY = 'yearly';

    public const ALL_CYCLES = [
        self::CYCLE_DAILY,
        self::CYCLE_WEEKLY,
        self::CYCLE_MONTHLY,
        self::CYCLE_YEARLY,
    ];

    /**
     * Generate invoices for a given company. Dispatches to per-cycle method.
     *
     * @param  string  $companyId
     * @param  string  $cycle  daily|weekly|monthly|yearly
     * @param  array  $period  cycle-specific: {usage_date} (D/W), {year, month} (M), {year} (Y)
     * @param  int|null  $dueDaysOverride
     * @return array{generated:int, skipped_existing:int, skipped_cycle:int, due_date:string}
     */
    public function generate(string $companyId, string $cycle, array $period, ?int $dueDaysOverride = null): array
    {
        return match ($cycle) {
            self::CYCLE_DAILY => $this->generateDaily($companyId, $period['usage_date'], $dueDaysOverride),
            self::CYCLE_WEEKLY => $this->generateWeekly($companyId, $period['usage_date'], $dueDaysOverride),
            self::CYCLE_MONTHLY => $this->generateMonthly($companyId, $period['year'], $period['month'], $dueDaysOverride),
            self::CYCLE_YEARLY => $this->generateYearly($companyId, $period['year'], $dueDaysOverride),
            default => throw new \InvalidArgumentException("Unsupported cycle: {$cycle}"),
        };
    }

    /**
     * Daily: 1 invoice per active langganan for exact usage_date.
     */
    public function generateDaily(string $companyId, string $usageDate, ?int $dueDaysOverride = null): array
    {
        $startDate = Carbon::parse($usageDate)->startOfDay();
        $endDate = $startDate->copy()->endOfDay();
        $dueDate = $endDate->copy()->addDays($this->resolveDueDays($companyId, $dueDaysOverride));

        $langganans = $this->getActiveLanggananByCycle($companyId, self::CYCLE_DAILY);

        if ($langganans->isEmpty()) {
            return $this->emptyResult($dueDate);
        }

        $now = now();
        $inserts = [];
        $skippedExisting = 0;
        $count = 0;

        foreach ($langganans as $langganan) {
            // Idempotency: exact date match
            $exists = CustInternetInvc::where('cust_internet_id', $langganan->id)
                ->where('cycle', self::CYCLE_DAILY)
                ->whereDate('usage_start_date', $startDate)
                ->exists();
            if ($exists) {
                $skippedExisting++;
                continue;
            }

            $grandTotal = $langganan->internetPackage->price ?? 0;

            $inserts[] = $this->buildInvoiceRow(
                $langganan->id, self::CYCLE_DAILY,
                $startDate, $endDate, $dueDate,
                $startDate->format('Ymd'),
                $grandTotal, $count + 1, $now,
                'Tagihan harian ' . $startDate->translatedFormat('d F Y')
            );
            $count++;
        }

        $this->bulkInsert($inserts);

        return [
            'generated' => $count,
            'skipped_existing' => $skippedExisting,
            'skipped_cycle' => 0,
            'due_date' => $dueDate->format('Y-m-d'),
        ];
    }

    /**
     * Weekly: 1 invoice per active langganan for week starting at usage_start_date (Senin).
     * If usage_date is not Senin, snap to that week's Monday.
     */
    public function generateWeekly(string $companyId, string $usageDate, ?int $dueDaysOverride = null): array
    {
        $startDate = Carbon::parse($usageDate)->startOfWeek(Carbon::MONDAY);
        $endDate = $startDate->copy()->endOfWeek(Carbon::SUNDAY);
        $dueDate = $endDate->copy()->addDays($this->resolveDueDays($companyId, $dueDaysOverride));

        $langganans = $this->getActiveLanggananByCycle($companyId, self::CYCLE_WEEKLY);

        if ($langganans->isEmpty()) {
            return $this->emptyResult($dueDate);
        }

        $now = now();
        $inserts = [];
        $skippedExisting = 0;
        $count = 0;

        foreach ($langganans as $langganan) {
            // Idempotency: cek apakah ada invoice dalam 7-hari window minggu ini
            $exists = CustInternetInvc::where('cust_internet_id', $langganan->id)
                ->where('cycle', self::CYCLE_WEEKLY)
                ->whereBetween('usage_start_date', [$startDate, $endDate])
                ->exists();
            if ($exists) {
                $skippedExisting++;
                continue;
            }

            $grandTotal = $langganan->internetPackage->price ?? 0;

            $inserts[] = $this->buildInvoiceRow(
                $langganan->id, self::CYCLE_WEEKLY,
                $startDate, $endDate, $dueDate,
                $startDate->format('Ymd'),
                $grandTotal, $count + 1, $now,
                'Tagihan mingguan ' . $startDate->translatedFormat('d F') . ' - ' . $endDate->translatedFormat('d F Y')
            );
            $count++;
        }

        $this->bulkInsert($inserts);

        return [
            'generated' => $count,
            'skipped_existing' => $skippedExisting,
            'skipped_cycle' => 0,
            'due_date' => $dueDate->format('Y-m-d'),
        ];
    }

    /**
     * Monthly: 1 invoice per active langganan for year-month.
     */
    public function generateMonthly(string $companyId, int $year, int $month, ?int $dueDaysOverride = null): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $dueDate = $endDate->copy()->addDays($this->resolveDueDays($companyId, $dueDaysOverride));

        $langganans = $this->getActiveLanggananByCycle($companyId, self::CYCLE_MONTHLY);

        if ($langganans->isEmpty()) {
            return $this->emptyResult($dueDate);
        }

        $now = now();
        $inserts = [];
        $skippedExisting = 0;
        $count = 0;

        foreach ($langganans as $langganan) {
            $exists = CustInternetInvc::where('cust_internet_id', $langganan->id)
                ->where('cycle', self::CYCLE_MONTHLY)
                ->whereMonth('usage_start_date', $month)
                ->whereYear('usage_start_date', $year)
                ->exists();
            if ($exists) {
                $skippedExisting++;
                continue;
            }

            $grandTotal = $langganan->internetPackage->price ?? 0;

            $inserts[] = $this->buildInvoiceRow(
                $langganan->id, self::CYCLE_MONTHLY,
                $startDate, $endDate, $dueDate,
                $year . str_pad($month, 2, '0', STR_PAD_LEFT),
                $grandTotal, $count + 1, $now,
                'Tagihan periode ' . $startDate->translatedFormat('F Y')
            );
            $count++;
        }

        $this->bulkInsert($inserts);

        return [
            'generated' => $count,
            'skipped_existing' => $skippedExisting,
            'skipped_cycle' => 0,
            'due_date' => $dueDate->format('Y-m-d'),
        ];
    }

    /**
     * Yearly: 1 invoice per active langganan for year.
     */
    public function generateYearly(string $companyId, int $year, ?int $dueDaysOverride = null): array
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear();
        $endDate = $startDate->copy()->endOfYear();
        $dueDate = $endDate->copy()->addDays($this->resolveDueDays($companyId, $dueDaysOverride));

        $langganans = $this->getActiveLanggananByCycle($companyId, self::CYCLE_YEARLY);

        if ($langganans->isEmpty()) {
            return $this->emptyResult($dueDate);
        }

        $now = now();
        $inserts = [];
        $skippedExisting = 0;
        $count = 0;

        foreach ($langganans as $langganan) {
            $exists = CustInternetInvc::where('cust_internet_id', $langganan->id)
                ->where('cycle', self::CYCLE_YEARLY)
                ->whereYear('usage_start_date', $year)
                ->exists();
            if ($exists) {
                $skippedExisting++;
                continue;
            }

            $grandTotal = $langganan->internetPackage->price ?? 0;

            $inserts[] = $this->buildInvoiceRow(
                $langganan->id, self::CYCLE_YEARLY,
                $startDate, $endDate, $dueDate,
                (string) $year,
                $grandTotal, $count + 1, $now,
                'Tagihan tahunan ' . $year
            );
            $count++;
        }

        $this->bulkInsert($inserts);

        return [
            'generated' => $count,
            'skipped_existing' => $skippedExisting,
            'skipped_cycle' => 0,
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
     * Build invoice row array (shared by all 4 cycle methods).
     */
    protected function buildInvoiceRow(
        string $langgananId,
        string $cycle,
        Carbon $startDate,
        Carbon $endDate,
        Carbon $dueDate,
        string $period,
        float $amount,
        int $counter,
        Carbon $now,
        string $description
    ): array {
        $cycleLetter = match ($cycle) {
            self::CYCLE_DAILY => 'D',
            self::CYCLE_WEEKLY => 'W',
            self::CYCLE_MONTHLY => 'M',
            self::CYCLE_YEARLY => 'Y',
        };

        // Hybrid format: INV-{C}-{PERIOD}-{TS_MS}-{6_LETTERS}-{6_DIGITS}
        // Letters: 6 random uppercase A-Z (per design decision #2)
        $tsMs = (int) (microtime(true) * 1000);
        $letters = '';
        for ($i = 0; $i < 6; $i++) {
            $letters .= chr(random_int(65, 90)); // A-Z
        }
        $digits = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $invoiceNumber = "INV-{$cycleLetter}-{$period}-{$tsMs}-{$letters}-{$digits}";

        return [
            'id' => Str::uuid7(),
            'cust_internet_id' => $langgananId,
            'cycle' => $cycle,
            'invoice_number' => $invoiceNumber,
            'usage_start_date' => $startDate->format('Y-m-d'),
            'usage_end_date' => $endDate->format('Y-m-d'),
            'invoice_due_date' => $dueDate->format('Y-m-d'),
            'amount' => $amount,
            'total_amount' => $amount,
            'grand_total' => $amount,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'payment_status' => 'unpaid',
            'status' => 'unpaid',
            'description' => $description,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * Bulk insert with chunking (500 per batch to avoid huge queries).
     */
    protected function bulkInsert(array $inserts): void
    {
        if (empty($inserts)) {
            return;
        }
        foreach (array_chunk($inserts, 500) as $chunk) {
            CustInternetInvc::insert($chunk);
        }
    }

    /**
     * Query active langganan filtered by cycle (via package.billing_cycle).
     */
    protected function getActiveLanggananByCycle(string $companyId, string $cycle): Collection
    {
        return CustInternet::with(['customer', 'internetPackage'])
            ->whereHas('customer', fn($q) => $q->where('company_id', $companyId))
            ->where('internet_status', 'active')
            ->whereHas('internetPackage', fn($q) => $q->where('billing_cycle', $cycle))
            ->get();
    }

    protected function emptyResult(Carbon $dueDate): array
    {
        return [
            'generated' => 0,
            'skipped_existing' => 0,
            'skipped_cycle' => 0,
            'due_date' => $dueDate->format('Y-m-d'),
        ];
    }
}
