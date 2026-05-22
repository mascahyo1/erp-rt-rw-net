<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\AfterClass;
use Tests\Browser\Support\VideoRecorder;

abstract class DuskTestCase extends BaseTestCase
{
    protected ?VideoRecorder $videoRecorder = null;
    protected static bool $enableVideoRecording = false;
    protected static string $videoOutputDir = 'tests/Browser/videos';
    protected static bool $enableVideoByDefault = false;

    #[AfterClass]
    public static function cleanupVideoRecording(): void
    {
        static::$enableVideoRecording = false;
    }

    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions());
        
        $args = [
            '--window-size=1920,1080',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--disable-software-rasterizer',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--disable-extensions',
            '--disable-infobars',
            '--disable-notifications',
            '--disable-popup-blocking',
            '--disable-translate',
            '--metrics-recording-only',
            '--mute-audio',
            '--ignore-certificate-errors',
            '--disable-setuid-sandbox',
            '--disable-accelerated-2d-canvas',
            '--disable-gpu-sandbox',
            '--remote-debugging-port=9223',
        ];

        if (! $this->hasHeadlessDisabled()) {
            $args[] = '--headless=new';
        }

        $options->addArguments($args);
        $options->addExtensions(config('dusk.chrome_extensions', []));

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? config('dusk.driver_url', 'http://localhost:9515'),
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    protected function startVideoRecording(string $testName): void
    {
        try {
            $this->videoRecorder = new VideoRecorder(
                $this->driver(),
                static::$videoOutputDir,
                $testName
            );
            $this->videoRecorder->start();
            fwrite(STDERR, "[VideoRecorder] Started recording: {$testName}\n");
        } catch (\Exception $e) {
            fwrite(STDERR, "[VideoRecorder] Failed to start: " . $e->getMessage() . "\n");
            $this->videoRecorder = null;
        }
    }

    protected function captureVideoFrame(string $label = 'frame'): void
    {
        if ($this->videoRecorder) {
            $this->videoRecorder->captureFrame($label);
        }
    }

    protected function stopVideoRecording(): string
    {
        if ($this->videoRecorder) {
            $path = $this->videoRecorder->stop();
            $this->videoRecorder = null;
            return $path;
        }
        return '';
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (static::$enableVideoRecording) {
            $testName = $this->getName() ?: 'test';
            $this->startVideoRecording($testName);
        }
    }

    protected function tearDown(): void
    {
        if ($this->videoRecorder && $this->videoRecorder->isRecording()) {
            $this->videoRecorder->captureFrame('test-end');
        }
        $this->stopVideoRecording();
        parent::tearDown();
    }

    public static function enableVideoRecording(bool $enable = true, string $outputDir = 'tests/Browser/videos'): void
    {
        static::$enableVideoRecording = $enable;
        static::$videoOutputDir = $outputDir;
        
        if ($enable && !is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        fwrite(STDERR, "[VideoRecorder] Recording " . ($enable ? 'ENABLED' : 'DISABLED') . " -> Output: {$outputDir}\n");
    }
}