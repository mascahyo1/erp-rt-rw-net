<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\InvoiceGeneratorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Bulk-generate tagihan (invoices) untuk semua active langganan.
 *
 * Multi-cycle (4 cycles: D/W/M/Y), idempotent per cycle:
 * - daily:   php artisan app:invoice-generate --cycle=daily --usage-date=YYYY-MM-DD
 * - weekly:  php artisan app:invoice-generate --cycle=weekly --usage-date=YYYY-MM-DD (Senin)
 * - monthly: php artisan app:invoice-generate --cycle=monthly --month=YYYY-MM
 * - yearly:  php artisan app:invoice-generate --cycle=yearly --year=YYYY
 *
 * Scheduler (currently DISABLED, see routes/console.php):
 * - When re-enabled, configure per-cycle scheduler entries.
 *
 * Idempotent: skip langganan yang sudah punya invoice di periode yang sama.
 */
class GenerateInvoicesCommand extends Command
{
    protected $signature = 'app:invoice-generate
        {--cycle= : daily|weekly|monthly|yearly (required)}
        {--usage-date= : Usage date for daily/weekly (YYYY-MM-DD)}
        {--month= : Year-month for monthly (YYYY-MM)}
        {--year= : Year for yearly (YYYY)}
        {--due-days= : Override due_days (skip CompanyConfig)}
        {--company= : Process only 1 company by UUID}
        {--all-companies : Process all active companies (default if --company not set)}';

    protected $description = 'Bulk-generate tagihan (invoices) untuk active langganan. Multi-cycle. Idempotent per cycle.';

    public function handle(InvoiceGeneratorService $service): int
    {
        $cycle = $this->option('cycle');
        if (!$cycle) {
            $this->error('--cycle required (daily|weekly|monthly|yearly)');
            return self::FAILURE;
        }
        if (!in_array($cycle, InvoiceGeneratorService::ALL_CYCLES)) {
            $this->error('--cycle must be one of: ' . implode(', ', InvoiceGeneratorService::ALL_CYCLES));
            return self::FAILURE;
        }

        // Parse cycle-specific period
        $period = $this->resolvePeriod($cycle);
        if ($period === null) {
            return self::FAILURE;
        }

        $dueDays = $this->option('due-days') !== null ? (int) $this->option('due-days') : null;
        if ($dueDays !== null && $dueDays < 0) {
            $this->error('--due-days harus >= 0');
            return self::FAILURE;
        }

        // Resolve target companies
        if ($companyOpt = $this->option('company')) {
            $companies = Company::where('id', $companyOpt)->where('is_active', true)->get();
            if ($companies->isEmpty()) {
                $this->error("Company {$companyOpt} tidak ditemukan atau non-aktif.");
                return self::FAILURE;
            }
        } else {
            $companies = Company::where('is_active', true)->get();
        }

        $periodLabel = $this->formatPeriodLabel($cycle, $period);
        $this->info("Generate invoices: cycle={$cycle} period={$periodLabel}");
        $this->info("Target companies: " . $companies->count() . " (due_days override: " . ($dueDays ?? 'from config') . ")");
        $this->newLine();

        $totalGenerated = 0;
        $totalSkippedExisting = 0;
        $totalErrors = 0;

        foreach ($companies as $company) {
            try {
                $result = $service->generate($company->id, $cycle, $period, $dueDays);

                $this->line(sprintf(
                    "  [%s] generated=%d skipped_existing=%d due_date=%s",
                    $company->name,
                    $result['generated'],
                    $result['skipped_existing'],
                    $result['due_date']
                ));

                $totalGenerated += $result['generated'];
                $totalSkippedExisting += $result['skipped_existing'];
            } catch (\Throwable $e) {
                $this->error("  [{$company->name}] ERROR: " . $e->getMessage());
                $totalErrors++;
            }
        }

        $this->newLine();
        $this->info("=== SUMMARY ===");
        $this->info("Cycle          : {$cycle}");
        $this->info("Period         : {$periodLabel}");
        $this->info("Generated      : {$totalGenerated}");
        $this->info("Skipped (exist): {$totalSkippedExisting}");
        $this->info("Errors         : {$totalErrors}");

        return $totalErrors > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function resolvePeriod(string $cycle): ?array
    {
        switch ($cycle) {
            case InvoiceGeneratorService::CYCLE_DAILY:
            case InvoiceGeneratorService::CYCLE_WEEKLY:
                $date = $this->option('usage-date');
                if (!$date) {
                    $this->error("--usage-date required for cycle={$cycle} (YYYY-MM-DD)");
                    return null;
                }
                try {
                    $parsed = Carbon::parse($date);
                } catch (\Throwable $e) {
                    $this->error("--usage-date format invalid. YYYY-MM-DD expected.");
                    return null;
                }
                return ['usage_date' => $parsed->format('Y-m-d')];

            case InvoiceGeneratorService::CYCLE_MONTHLY:
                $monthOpt = $this->option('month');
                if (!$monthOpt) {
                    $this->error('--month required for cycle=monthly (YYYY-MM)');
                    return null;
                }
                try {
                    $parsed = Carbon::createFromFormat('Y-m', $monthOpt);
                } catch (\Throwable $e) {
                    $this->error('--month format invalid. YYYY-MM expected.');
                    return null;
                }
                return ['year' => (int) $parsed->format('Y'), 'month' => (int) $parsed->format('n')];

            case InvoiceGeneratorService::CYCLE_YEARLY:
                $yearOpt = $this->option('year');
                if (!$yearOpt) {
                    $this->error('--year required for cycle=yearly (YYYY)');
                    return null;
                }
                if (!is_numeric($yearOpt) || (int) $yearOpt < 2020) {
                    $this->error('--year must be numeric >= 2020');
                    return null;
                }
                return ['year' => (int) $yearOpt];
        }
        return null;
    }

    protected function formatPeriodLabel(string $cycle, array $period): string
    {
        switch ($cycle) {
            case InvoiceGeneratorService::CYCLE_DAILY:
            case InvoiceGeneratorService::CYCLE_WEEKLY:
                return $period['usage_date'];
            case InvoiceGeneratorService::CYCLE_MONTHLY:
                return $period['year'] . '-' . str_pad($period['month'], 2, '0', STR_PAD_LEFT);
            case InvoiceGeneratorService::CYCLE_YEARLY:
                return (string) $period['year'];
        }
        return '?';
    }
}
