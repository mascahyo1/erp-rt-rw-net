<?php

namespace Tests\Browser\Support;

use Facebook\WebDriver\Remote\RemoteWebDriver;

class VideoRecorder
{
    private RemoteWebDriver $driver;
    private string $outputDir;
    private string $testName;
    private array $frames = [];
    private bool $isRecording = false;
    private string $sessionId;
    private float $startTime;
    private string $ffmpegPath;
    private int $frameCounter = 0;
    private int $captureIntervalMs = 200;

    public function __construct(RemoteWebDriver $driver, string $outputDir, string $testName)
    {
        $this->driver = $driver;
        $this->outputDir = $outputDir;
        $this->testName = $this->sanitizeFileName($testName);
        $this->sessionId = $driver->getSessionID();
        $this->ffmpegPath = $this->detectFfmpeg();
        $this->ensureOutputDir();
    }

    private function detectFfmpeg(): string
    {
        $paths = [
            'c:\laragon\www\erp-rt-rw-net\ffmpeg.exe',
            dirname(__DIR__, 4) . '\ffmpeg.exe',
            'C:\ffmpeg\bin\ffmpeg.exe',
            'C:\Program Files\ffmpeg\bin\ffmpeg.exe',
            'ffmpeg',
        ];

        foreach ($paths as $path) {
            if ($path === 'ffmpeg' || file_exists($path)) {
                $cmd = $path === 'ffmpeg' ? "ffmpeg -version" : "\"$path\" -version";
                $result = shell_exec($cmd . " 2>&1");
                if ($result && strpos($result, 'ffmpeg version') !== false) {
                    return $path === 'ffmpeg' ? $path : realpath($path) ?: $path;
                }
            }
        }
        return '';
    }

    public function isFfmpegAvailable(): bool
    {
        return !empty($this->ffmpegPath) && file_exists($this->ffmpegPath);
    }

    public function start(): void
    {
        $this->isRecording = true;
        $this->startTime = microtime(true);
        $this->frames = [];
        $this->frameCounter = 0;

        $ffmpegStatus = $this->isFfmpegAvailable() ? 'FFmpeg OK' : 'No FFmpeg';
        fwrite(STDERR, "[VideoRecorder] START {$this->testName} | {$ffmpegStatus}\n");

        $this->captureFrame('start');
    }

    public function captureFrame(string $label = ''): string
    {
        if (!$this->isRecording) {
            return '';
        }

        try {
            $this->frameCounter++;
            $timestamp = sprintf('%06.3f', microtime(true) - $this->startTime);
            $prefix = $label ? "{$label}_" : "frame_";
            $frameName = "{$this->testName}_{$prefix}{$this->frameCounter}_{$timestamp}s.png";
            $framePath = $this->outputDir . '\\' . $frameName;

            $screenshot = $this->driver->takeScreenshot();
            file_put_contents($framePath, $screenshot);

            $this->frames[] = $framePath;

            fwrite(STDERR, "[VideoRecorder] Frame {$this->frameCounter}: {$label}\n");

            return $framePath;
        } catch (\Exception $e) {
            fwrite(STDERR, "[VideoRecorder] Capture error: " . $e->getMessage() . "\n");
            return '';
        }
    }

    public function stop(): string
    {
        $this->isRecording = false;

        if (empty($this->frames)) {
            fwrite(STDERR, "[VideoRecorder] No frames captured\n");
            return '';
        }

        $this->captureFrame('end');

        fwrite(STDERR, "[VideoRecorder] Stopped. Total frames: " . count($this->frames) . "\n");

        if ($this->isFfmpegAvailable()) {
            $mp4Path = $this->generateMp4();
            if ($mp4Path) {
                $this->cleanupFrames();
                return $mp4Path;
            }
        }

        return $this->generateGif();
    }

    private function generateMp4(): string
    {
        $mp4Name = $this->testName . '_' . date('Ymd_His') . '.mp4';
        $mp4Path = $this->outputDir . '\\' . $mp4Name;
        $tempDir = $this->outputDir . '\\temp_' . uniqid();

        if (!mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
            fwrite(STDERR, "[VideoRecorder] Failed to create temp dir\n");
            return '';
        }

        fwrite(STDERR, "[VideoRecorder] Creating " . count($this->frames) . " frames in temp dir...\n");

        foreach ($this->frames as $index => $framePath) {
            if (file_exists($framePath)) {
                $destPath = $tempDir . '\\frame_' . str_pad($index + 1, 6, '0', STR_PAD_LEFT) . '.png';
                copy($framePath, $destPath);
            }
        }

        $inputPattern = $tempDir . '\\frame_%06d.png';

        $command = sprintf(
            '"%s" -y -framerate 10 -i "%s" -vf "scale=trunc(iw/2)*2:trunc(ih/2)*2" -c:v libx264 -pix_fmt yuv420p -crf 23 -preset ultrafast "%s" 2>&1',
            $this->ffmpegPath,
            $inputPattern,
            $mp4Path
        );

        fwrite(STDERR, "[VideoRecorder] Running FFmpeg...\n");
        $output = shell_exec($command);

        $this->cleanupDir($tempDir);

        if (file_exists($mp4Path) && filesize($mp4Path) > 1000) {
            fwrite(STDERR, "[VideoRecorder] MP4 created: " . filesize($mp4Path) . " bytes\n");
            return $mp4Path;
        }

        fwrite(STDERR, "[VideoRecorder] FFmpeg failed, output: " . substr($output ?: 'no output', 0, 300) . "\n");
        return '';
    }

    private function generateGif(): string
    {
        $gifName = $this->testName . '_' . date('Ymd_His') . '.gif';
        $gifPath = $this->outputDir . '\\' . $gifName;

        if ($this->isFfmpegAvailable() && !empty($this->frames)) {
            $tempDir = $this->outputDir . '\\temp_' . uniqid();
            mkdir($tempDir, 0755, true);

            foreach ($this->frames as $index => $framePath) {
                if (file_exists($framePath)) {
                    $destPath = $tempDir . '\\frame_' . str_pad($index + 1, 6, '0', STR_PAD_LEFT) . '.png';
                    copy($framePath, $destPath);
                }
            }

            $command = sprintf(
                '"%s" -y -framerate 5 -i "%s\\frame_%%06d.png" -vf "scale=960:-1:flags=lanczos,split[s0][s1];[s0]palettegen[p];[s1][p]paletteuse" "%s" 2>&1',
                $this->ffmpegPath,
                $tempDir,
                $gifPath
            );

            shell_exec($command);
            $this->cleanupDir($tempDir);
        }

        if (file_exists($gifPath) && filesize($gifPath) > 1000) {
            return $gifPath;
        }

        return '';
    }

    private function cleanupFrames(): void
    {
        foreach ($this->frames as $frame) {
            if (file_exists($frame)) {
                @unlink($frame);
            }
        }
        $this->frames = [];
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