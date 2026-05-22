<?php

namespace Tests\Browser\Support;

use Facebook\WebDriver\Remote\RemoteWebDriver;

class BrowserVideoRecorder
{
    private RemoteWebDriver $driver;
    private string $outputDir;
    private string $testName;
    private string $recordingId;
    private bool $isRecording = false;
    private float $startTime;

    public function __construct(RemoteWebDriver $driver, string $outputDir, string $testName)
    {
        $this->driver = $driver;
        $this->outputDir = $outputDir;
        $this->testName = $this->sanitizeFileName($testName);
        $this->recordingId = uniqid('rec_');
        $this->ensureOutputDir();
    }

    public function start(): void
    {
        $this->isRecording = true;
        $this->startTime = microtime(true);

        $script = <<<JS
        (function() {
            if (window.__videoRecorder) {
                window.__videoRecorder.stop();
            }

            // Create video element
            const video = document.createElement('video');
            video.id = 'recording-video';
            video.style.cssText = 'position:fixed;top:0;left:0;width:1px;height:1px;opacity:0;';
            video.muted = true;
            video.playsInline = true;

            // Get display media
            navigator.mediaDevices.getDisplayMedia({
                video: {
                    displaySurface: 'browser',
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
                    frameRate: { ideal: 60 }
                },
                audio: false,
                selfBrowserSurface: 'include',
                systemAudio: 'include',
                surfaceSwitching: 'include',
                monitorTypeSurfaces: 'include'
            }).then(stream => {
                video.srcObject = stream;
                video.play();

                // Create MediaRecorder
                const options = { mimeType: 'video/webm;codecs=vp9' };
                if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                    options.mimeType = 'video/webm;codecs=vp8';
                }
                if (!MediaRecorder.isTypeSupported(options.mimeType)) {
                    options.mimeType = 'video/webm';
                }

                window.__mediaRecorder = new MediaRecorder(stream, options);
                window.__videoChunks = [];

                window.__mediaRecorder.ondataavailable = (e) => {
                    if (e.data.size > 0) {
                        window.__videoChunks.push(e.data);
                    }
                };

                window.__mediaRecorder.start(100); // collect data every 100ms
                console.log('Recording started with mimeType:', options.mimeType);
            }).catch(err => {
                console.error('getDisplayMedia error:', err);
                window.__recordingError = err.message;
            });

            document.body.appendChild(video);
        })();
        JS;

        try {
            $this->driver->executeScript($script);
            fwrite(STDERR, "[BrowserVideoRecorder] START {$this->testName} | ID: {$this->recordingId}\n");
        } catch (\Exception $e) {
            fwrite(STDERR, "[BrowserVideoRecorder] Start error: " . $e->getMessage() . "\n");
        }
    }

    public function stop(): string
    {
        $this->isRecording = false;

        $duration = round(microtime(true) - $this->startTime, 2);
        fwrite(STDERR, "[BrowserVideoRecorder] STOP after {$duration}s\n");

        $script = <<<JS
        (function() {
            return new Promise((resolve) => {
                if (!window.__mediaRecorder) {
                    resolve(null);
                    return;
                }

                window.__mediaRecorder.onstop = () => {
                    const blob = new Blob(window.__videoChunks, { type: window.__mediaRecorder.mimeType || 'video/webm' });
                    window.__videoBlob = blob;
                    window.__videoUrl = URL.createObjectURL(blob);

                    // Download the video
                    const a = document.createElement('a');
                    a.href = window.__videoUrl;
                    a.download = window.__videoFileName || 'recording.webm';
                    a.click();

                    // Cleanup
                    if (window.__videoRecorder) {
                        const stream = window.__videoRecorder.srcObject;
                        if (stream) {
                            stream.getTracks().forEach(track => track.stop());
                        }
                        window.__videoRecorder.remove();
                    }
                    const recVideo = document.getElementById('recording-video');
                    if (recVideo) recVideo.remove();

                    resolve({
                        url: window.__videoUrl,
                        blobSize: blob.size,
                        mimeType: window.__mediaRecorder.mimeType || 'video/webm'
                    });
                };

                if (window.__mediaRecorder.state === 'recording') {
                    window.__mediaRecorder.stop();
                } else {
                    resolve(null);
                }
            });
        })();
        JS;

        try {
            $result = $this->driver->executeAsyncScript($script);

            if ($result && isset($result['url'])) {
                fwrite(STDERR, "[BrowserVideoRecorder] Video saved: {$result['blobSize']} bytes\n");
                return $this->outputDir . '\\' . $this->testName . '_' . date('Ymd_His') . '.webm';
            }
        } catch (\Exception $e) {
            fwrite(STDERR, "[BrowserVideoRecorder] Stop error: " . $e->getMessage() . "\n");
        }

        return '';
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