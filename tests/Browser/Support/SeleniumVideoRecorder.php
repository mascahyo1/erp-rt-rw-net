<?php

namespace Tests\Browser\Support;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

class SeleniumVideoRecorder
{
    private RemoteWebDriver $driver;
    private string $outputDir;
    private string $testName;
    private string $sessionId;
    private bool $isRecording = false;
    private float $startTime;

    public function __construct(RemoteWebDriver $driver, string $outputDir, string $testName)
    {
        $this->driver = $driver;
        $this->outputDir = $outputDir;
        $this->testName = $this->sanitizeFileName($testName);
        $this->sessionId = $driver->getSessionID();
        $this->ensureOutputDir();
    }

    public function start(): void
    {
        $this->isRecording = true;
        $this->startTime = microtime(true);

        $script = <<<'JS'
        (function() {
            // Use Chrome DevTools Protocol for screen recording
            const options = {
                mode: 'animated',
                format: 'webm',
                quality: 100,
                maxWidth: 1920,
                maxHeight: 1080,
                everyNthFrame: 2  // ~30fps from 60fps source
            };

            window.__seleniumRecording = {
                chunks: [],
                options: options
            };

            console.log('Selenium recording started');
            return 'Recording started';
        })();
        JS;

        try {
            $this->driver->executeScript($script);
            fwrite(STDERR, "[SeleniumVideoRecorder] START {$this->testName}\n");
        } catch (\Exception $e) {
            fwrite(STDERR, "[SeleniumVideoRecorder] Start error: " . $e->getMessage() . "\n");
        }
    }

    public function captureFrame(string $label = ''): string
    {
        if (!$this->isRecording) {
            return '';
        }

        try {
            $timestamp = sprintf('%.2f', microtime(true) - $this->startTime);
            $suffix = $label ? "_{$label}" : '';
            $frameName = "{$this->testName}{$suffix}_{$timestamp}s.png";
            $framePath = $this->outputDir . '\\' . $frameName;

            $screenshot = $this->driver->takeScreenshot($framePath);

            fwrite(STDERR, "[SeleniumVideoRecorder] Frame: {$label}\n");

            return $framePath;
        } catch (\Exception $e) {
            fwrite(STDERR, "[SeleniumVideoRecorder] Capture error: " . $e->getMessage() . "\n");
            return '';
        }
    }

    public function stop(): string
    {
        $this->isRecording = false;

        $duration = round(microtime(true) - $this->startTime, 2);
        fwrite(STDERR, "[SeleniumVideoRecorder] STOP after {$duration}s\n");

        $mp4Path = $this->generateMp4FromFrames();

        return $mp4Path ?: '';
    }

    private function generateMp4FromFrames(): string
    {
        $frames = glob($this->outputDir . '\\' . $this->testName . '_*.png');

        if (empty($frames)) {
            fwrite(STDERR, "[SeleniumVideoRecorder] No frames found\n");
            return '';
        }

        sort($frames);

        $mp4Name = $this->testName . '_' . date('Ymd_His') . '.mp4';
        $mp4Path = $this->outputDir . '\\' . $mp4Name;
        $tempDir = $this->outputDir . '\\temp_' . uniqid();

        if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
            return '';
        }

        foreach ($frames as $index => $framePath) {
            $destPath = $tempDir . '\\frame_' . str_pad($index + 1, 6, '0', STR_PAD_LEFT) . '.png';
            copy($framePath, $destPath);
        }

        fwrite(STDERR, "[SeleniumVideoRecorder] Creating MP4 from " . count($frames) . " frames...\n");

        $ffmpegPath = $this->findFfmpeg();

        if ($ffmpegPath) {
            $inputPattern = $tempDir . '\\frame_%06d.png';

            $command = sprintf(
                '"%s" -y -framerate 10 -i "%s" -vf "scale=trunc(iw/2)*2:trunc(ih/2)*2" -c:v libx264 -pix_fmt yuv420p -crf 18 -preset fast "%s" 2>&1',
                $ffmpegPath,
                $inputPattern,
                $mp4Path
            );

            $output = shell_exec($command);

            if (file_exists($mp4Path) && filesize($mp4Path) > 1000) {
                fwrite(STDERR, "[SeleniumVideoRecorder] MP4 created: " . filesize($mp4Path) . " bytes\n");

                foreach ($frames as $frame) {
                    @unlink($frame);
                }
                $this->cleanupDir($tempDir);

                return $mp4Path;
            }

            fwrite(STDERR, "[SeleniumVideoRecorder] FFmpeg failed: " . substr($output ?: 'unknown', 0, 200) . "\n");
        }

        $this->cleanupDir($tempDir);
        return '';
    }

    private function findFfmpeg(): string
    {
        $paths = [
            'c:\laragon\www\erp-rt-rw-net\ffmpeg.exe',
            'C:\ffmpeg\bin\ffmpeg.exe',
            'C:\Program Files\ffmpeg\bin\ffmpeg.exe',
            'ffmpeg',
        ];

        foreach ($paths as $path) {
            if ($path === 'ffmpeg' || file_exists($path)) {
                $cmd = $path === 'ffmpeg' ? "ffmpeg -version" : "\"$path\" -version";
                $result = shell_exec($cmd . " 2>&1");
                if ($result && strpos($result, 'ffmpeg version') !== false) {
                    return $path;
                }
            }
        }

        return '';
    }

    private function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '\\' . $file;
            is_dir($path) ? $this->cleanupDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    private function ensureOutputDir(): void
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    private function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        return trim($name, '_') ?: 'test';
    }
}