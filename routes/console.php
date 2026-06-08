<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-generate tagihan tiap awal bulan jam 00:00 (semua company aktif)
// Idempotent: skip langganan yang sudah ada invoice di periode yang sama
Schedule::command('app:invoice-generate')
    ->monthlyOn(1, '00:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();
