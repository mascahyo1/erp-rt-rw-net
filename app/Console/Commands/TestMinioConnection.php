<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestMinioConnection extends Command
{
    protected $signature = 'minio:test';

    protected $description = 'Test MinIO object storage connection and operations';

    public function handle(): int
    {
        $this->info('=== MinIO Connection Test ===');
        $this->newLine();

        // 1. Show config
        $this->info('1. Configuration:');
        $this->line('  Endpoint: ' . config('filesystems.disks.minio.endpoint'));
        $this->line('  Bucket:   ' . config('filesystems.disks.minio.bucket'));
        $this->line('  Region:   ' . config('filesystems.disks.minio.region'));
        $this->line('  Key:      ' . (config('filesystems.disks.minio.key') ? '✓ set' : '✗ EMPTY'));
        $this->line('  Secret:   ' . (config('filesystems.disks.minio.secret') ? '✓ set' : '✗ EMPTY'));
        $this->newLine();

        // 2. Test listing (basic connectivity)
        $this->info('2. Testing connectivity (listing root)...');
        try {
            $files = Storage::disk('minio')->files('');
            $this->info('  ✓ Connected! Found ' . count($files) . ' file(s) in root.');
        } catch (\Throwable $e) {
            $this->error('  ✗ Connection failed: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->newLine();

        // 3. Test write
        $this->info('3. Testing write...');
        $testFile = '.minio-test/' . date('Y-m-d_H-i-s') . '.txt';
        $testContent = 'MinIO connection test at ' . now()->toDateTimeString();
        try {
            Storage::disk('minio')->put($testFile, $testContent);
            $this->info('  ✓ Write succeeded: ' . $testFile);
        } catch (\Throwable $e) {
            $this->error('  ✗ Write failed: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->newLine();

        // 4. Test exists
        $this->info('4. Testing exists...');
        try {
            $exists = Storage::disk('minio')->exists($testFile);
            $this->info(' url: ' . Storage::disk('minio')->temporaryUrl($testFile, now()->addMinutes(5)));die;
            $this->info('  ✓ File exists: ' . ($exists ? 'yes' : 'no'));
        } catch (\Throwable $e) {
            $this->error('  ✗ Exists check failed: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->newLine();

        // 5. Test read
        $this->info('5. Testing read...');
        try {
            $content = Storage::disk('minio')->get($testFile);
            $this->info('  ✓ Read succeeded, content: "' . $content . '"');
        } catch (\Throwable $e) {
            $this->error('  ✗ Read failed: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->newLine();

        // 6. Cleanup
        $this->info('6. Cleaning up test file...');
        try {
            Storage::disk('minio')->delete($testFile);
            $this->info('  ✓ Test file deleted');
        } catch (\Throwable $e) {
            $this->warn('  ⚠ Could not delete test file: ' . $e->getMessage());
        }
        $this->newLine();

        $this->info('=== All tests passed! MinIO is working correctly. ===');
        return self::SUCCESS;
    }
}
