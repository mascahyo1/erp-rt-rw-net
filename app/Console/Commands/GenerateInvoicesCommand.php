<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\InvoiceGeneratorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Bulk-generate tagihan (invoices) for all active langganan.
 *
 * Usage:
 *   php artisan app:invoice-generate --month=2026-06
 *   php artisan app:invoice-generate --month=2026-06 --due-days=14
 *   php artisan app:invoice-generate --month=2026-06 --company=72705ef8-ab2a-...
 *   php artisan app:invoice-generate --month=2026-06 --all-companies
 *
 * Scheduler:
 *   - Default: jalan tiap tgl 1 jam 00:00 (auto-generate bulan sebelumnya)
 *   - Per-company: command bisa di-scope ke 1 company
 *
 * Idempotent: skip langganan yang sudah punya invoice di periode yang sama.
 */
class GenerateInvoicesCommand extends Command
{
    protected $signature = 'app:invoice-generate
        {--month= : Target period YYYY-MM (default: bulan ini)}
        {--due-days= : Override due_days (skip CompanyConfig)}
        {--company= : Process only 1 company by UUID}
        {--all-companies : Process all active companies (default if --company not set)}';

    protected $description = 'Bulk-generate tagihan (invoices) untuk semua langganan aktif. Idempotent — aman dijalan ulang.';

    public function handle(InvoiceGeneratorService $service): int
    {
        $monthOpt = $this->option('month');
        if ($monthOpt) {
            try {
                $period = Carbon::createFromFormat('Y-m', $monthOpt)->startOfMonth();
            } catch (\Throwable $e) {
                $this->error("Format --month harus YYYY-MM. Contoh: --month=2026-06");
                return self::FAILURE;
            }
        } else {
            $period = Carbon::now()->startOfMonth();
        }
        $year = (int) $period->format('Y');
        $month = (int) $period->format('n');

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

        $this->info("Generate invoices: " . $period->translatedFormat('F Y'));
        $this->info("Target companies: " . $companies->count() . " (due_days override: " . ($dueDays ?? 'from config') . ")");
        $this->newLine();

        $totalGenerated = 0;
        $totalSkippedExisting = 0;
        $totalSkippedCycle = 0;
        $totalErrors = 0;

        foreach ($companies as $company) {
            try {
                $result = $service->generate($company->id, $year, $month, $dueDays);

                $this->line(sprintf(
                    "  [%s] generated=%d skipped_existing=%d skipped_cycle=%d due_date=%s",
                    $company->name,
                    $result['generated'],
                    $result['skipped_existing'],
                    $result['skipped_cycle'],
                    $result['due_date']
                ));

                $totalGenerated += $result['generated'];
                $totalSkippedExisting += $result['skipped_existing'];
                $totalSkippedCycle += $result['skipped_cycle'];
            } catch (\Throwable $e) {
                $this->error("  [{$company->name}] ERROR: " . $e->getMessage());
                $totalErrors++;
            }
        }

        $this->newLine();
        $this->info("=== SUMMARY ===");
        $this->info("Generated       : {$totalGenerated}");
        $this->info("Skipped (exists): {$totalSkippedExisting}");
        $this->info("Skipped (cycle) : {$totalSkippedCycle}");
        $this->info("Errors          : {$totalErrors}");

        return $totalErrors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
