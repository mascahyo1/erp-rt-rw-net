<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduler DISABLED per user decision (2026-06-08). Manual trigger only via:
//   - UI: tombol "Generate" di Tagihan page (Operator Perusahaan)
//   - CLI: php artisan app:invoice-generate [--month=YYYY-MM] [--due-days=N] [--company=<uuid>]
//
// Untuk re-enable nanti (uncomment + sesuaikan cycle):
// Schedule::command('app:invoice-generate')->monthlyOn(1, '00:00')->withoutOverlapping()->onOneServer()->runInBackground();
