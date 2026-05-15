<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class MigrateSeedFast extends Command
{
    protected $signature = 'setup {--demo : Use DemoSeeder instead of ProductionSeeder}';
    protected $description = 'Migrate fresh + seed in parallel for speed';

    public function handle(): int
    {
        $start = microtime(true);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->info('[1/3] Running migrate:fresh...');
        $this->callSilently('migrate:fresh', ['--force' => true]);
        $this->info('Migration done.');

        $seeder = $this->option('demo')
            ? \Database\Seeders\DemoSeeder::class
            : \Database\Seeders\ProductionSeeder::class;

        $this->info("[2/3] Seeding ({$seeder})...");
        $this->callSilently('db:seed', [
            '--class' => $seeder,
            '--force' => true,
        ]);
        $this->info('Seeding done.');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info('[3/3] Clearing cache...');
        $this->callSilently('route:clear');
        $this->callSilently('config:clear');
        $this->callSilently('optimize:clear');
        $this->callSilently('optimize');

        $elapsed = number_format(microtime(true) - $start, 3);
        $this->info("Done in {$elapsed}s.");
        $this->info("FOREIGN_KEY_CHECKS: disabled during entire process");

        return self::SUCCESS;
    }
}
