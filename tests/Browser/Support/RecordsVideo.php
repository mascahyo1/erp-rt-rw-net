<?php

namespace Tests\Browser\Support;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Tests\DuskTestCase;

trait RecordsVideo
{
    protected string $testVideoName = '';
    
    protected function startVideo(string $name = ''): void
    {
        $this->testVideoName = $name ?: $this->getTestName();
        $this->startVideoRecording($this->testVideoName);
    }

    protected function recordFrame(string $label = ''): void
    {
        $this->captureVideoFrame($label ?: $this->getCurrentStep());
    }

    protected function finishVideo(): string
    {
        $path = $this->stopVideoRecording();
        if ($path) {
            fwrite(STDERR, "[VIDEO] Saved: {$path}\n");
        }
        return $path;
    }

    protected function videoSnapshot(string $label): void
    {
        $this->captureVideoFrame($label);
    }

    private function getTestName(): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '_', static::class) . '_' . uniqid();
    }

    private function getCurrentStep(): string
    {
        return 'step_' . time();
    }

    protected function withVideoRecording(string $testName, callable $callback): mixed
    {
        $this->startVideo($testName);
        
        try {
            $result = $callback($this);
        } catch (\Exception $e) {
            $this->captureVideoFrame('error_' . uniqid());
            $this->finishVideo();
            throw $e;
        }
        
        $this->finishVideo();
        return $result;
    }
}