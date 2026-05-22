<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Playwright\Playwright;

class RecordVideo
{
    private string $outputDir;
    private string $videoPath;

    public function __construct(string $outputDir = 'tests/Browser/videos/playwright')
    {
        $this->outputDir = $outputDir;

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    public function record(string $url, string $testName, int $durationSeconds = 5): string
    {
        echo "Starting Playwright browser...\n";

        $context = Playwright::chromium([
            'headless' => false,
            'args' => ['--window-size=1920,1080'],
            'context' => [
                'viewport' => ['width' => 1920, 'height' => 1080],
                'recordVideo' => [
                    'dir' => $this->outputDir,
                    'size' => ['width' => 1920, 'height' => 1080]
                ]
            ]
        ]);

        $page = $context->newPage();

        echo "Navigating to: $url\n";
        $page->goto($url, ['timeout' => 30000, 'waitUntil' => 'networkidle']);

        echo "Recording for {$durationSeconds} seconds...\n";
        $page->waitForTimeout($durationSeconds * 1000);

        echo "Taking screenshot...\n";
        $screenshotPath = $this->outputDir . '/' . $testName . '_screenshot_' . date('Ymd_His') . '.png';
        $page->screenshot(['path' => $screenshotPath, 'fullPage' => true]);

        echo "Closing browser...\n";
        $context->close();

        $videoFiles = glob($this->outputDir . '/*.webm');
        if (!empty($videoFiles)) {
            $webmFile = $videoFiles[0];
            $this->videoPath = $this->outputDir . '/' . $testName . '_' . date('Ymd_His') . '.mp4';

            echo "Converting $webmFile to MP4...\n";
            $this->convertToMp4($webmFile, $this->videoPath);

            unlink($webmFile);

            echo "Video saved to: {$this->videoPath}\n";
            return $this->videoPath;
        }

        return '';
    }

    private function convertToMp4(string $webmFile, string $mp4File): void
    {
        $ffmpegPath = 'c:\laragon\www\erp-rt-rw-net\ffmpeg.exe';

        if (!file_exists($ffmpegPath)) {
            echo "FFmpeg not found, keeping webm format\n";
            $this->videoPath = $webmFile;
            return;
        }

        $command = sprintf(
            '"%s" -y -i "%s" -c:v libx264 -pix_fmt yuv420p -crf 23 -preset fast "%s" 2>&1',
            $ffmpegPath,
            $webmFile,
            $mp4File
        );

        $output = shell_exec($command);

        if (!file_exists($mp4File) || filesize($mp4File) < 1000) {
            echo "FFmpeg conversion failed, keeping webm\n";
            $this->videoPath = $webmFile;
        }
    }

    public function recordWithActions(string $url, string $testName, callable $actions, int $durationSeconds = 10): string
    {
        echo "Starting Playwright browser with actions...\n";

        $context = Playwright::chromium([
            'headless' => false,
            'args' => ['--window-size=1920,1080'],
            'context' => [
                'viewport' => ['width' => 1920, 'height' => 1080],
                'recordVideo' => [
                    'dir' => $this->outputDir,
                    'size' => ['width' => 1920, 'height' => 1080]
                ]
            ]
        ]);

        $page = $context->newPage();

        echo "Navigating to: $url\n";
        $page->goto($url, ['timeout' => 30000, 'waitUntil' => 'networkidle']);

        echo "Executing custom actions...\n";
        $actions($page, $this);

        echo "Recording for additional {$durationSeconds} seconds...\n";
        $page->waitForTimeout($durationSeconds * 1000);

        echo "Taking screenshot...\n";
        $screenshotPath = $this->outputDir . '/' . $testName . '_screenshot_' . date('Ymd_His') . '.png';
        $page->screenshot(['path' => $screenshotPath, 'fullPage' => true]);

        echo "Closing browser...\n";
        $context->close();

        $videoFiles = glob($this->outputDir . '/*.webm');
        if (!empty($videoFiles)) {
            $webmFile = $videoFiles[0];
            $this->videoPath = $this->outputDir . '/' . $testName . '_' . date('Ymd_His') . '.mp4';

            echo "Converting $webmFile to MP4...\n";
            $this->convertToMp4($webmFile, $this->videoPath);

            unlink($webmFile);

            echo "Video saved to: {$this->videoPath}\n";
            return $this->videoPath;
        }

        return '';
    }

    public function captureFrame($page, string $label): void
    {
        $screenshotPath = $this->outputDir . '/' . $label . '_' . date('Ymd_His') . '.png';
        $page->screenshot(['path' => $screenshotPath, 'fullPage' => true]);
        echo "Captured: $label\n";
    }
}

if (php_sapi_name() === 'cli' && realpath($argv[0]) === __FILE__) {
    echo "=== Playwright Video Recorder ===\n\n";

    $recorder = new RecordVideo('tests/Browser/videos/playwright');

    $url = $argv[1] ?? 'http://localhost/operator-perusahaan/daftar-paket';
    $testName = $argv[2] ?? 'DaftarPaketTest';
    $duration = isset($argv[3]) ? (int)$argv[3] : 5;

    echo "URL: $url\n";
    echo "Test Name: $testName\n";
    echo "Duration: {$duration}s\n\n";

    $videoPath = $recorder->record($url, $testName, $duration);

    if ($videoPath) {
        echo "\n✅ Video recording successful!\n";
        echo "Output: $videoPath\n";
    } else {
        echo "\n❌ Video recording failed\n";
    }
}