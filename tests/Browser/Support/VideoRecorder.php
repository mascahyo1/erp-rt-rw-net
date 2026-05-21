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
    private int $frameRate = 10;
    private float $startTime;

    public function __construct(RemoteWebDriver $driver, string $outputDir, string $testName)
    {
        $this->driver = $driver;
        $this->outputDir = $outputDir;
        $this->testName = $this->sanitizeFileName($testName);
        $this->sessionId = $driver->getSessionID();
    }

    public function start(): self
    {
        $this->isRecording = true;
        $this->startTime = microtime(true);
        $this->frames = [];
        
        $this->ensureOutputDir();
        
        fwrite(STDERR, "[VideoRecorder] Started recording for: {$this->testName}\n");
        
        return $this;
    }

    public function captureFrame(string $label = 'frame'): self
    {
        if (!$this->isRecording) {
            return $this;
        }

        try {
            $timestamp = sprintf('%06f', microtime(true) - $this->startTime);
            $frameName = $this->testName . '_' . $label . '_' . str_replace('.', '', $timestamp) . '.png';
            $framePath = $this->outputDir . '/' . $frameName;
            
            $screenshot = $this->driver->takeScreenshot();
            file_put_contents($framePath, $screenshot);
            
            $this->frames[] = [
                'path' => $framePath,
                'label' => $label,
                'timestamp' => $timestamp,
            ];
            
            fwrite(STDERR, "[VideoRecorder] Captured frame: {$label}\n");
        } catch (\Exception $e) {
            fwrite(STDERR, "[VideoRecorder] Frame capture failed: " . $e->getMessage() . "\n");
        }

        return $this;
    }

    public function stop(): string
    {
        $this->isRecording = false;
        
        if (empty($this->frames)) {
            fwrite(STDERR, "[VideoRecorder] No frames captured\n");
            return '';
        }

        $videoPath = $this->generateHtmlVideo();
        
        fwrite(STDERR, "[VideoRecorder] HTML video saved: {$videoPath}\n");
        
        return $videoPath;
    }

    private function generateHtmlVideo(): string
    {
        $htmlName = $this->testName . '_' . date('Ymd_His') . '.html';
        $htmlPath = $this->outputDir . '/' . $htmlName;
        
        $totalDuration = count($this->frames) > 0 
            ? round((float)end($this->frames)['timestamp'] * 1000) 
            : 1000;
        
        $framesJson = json_encode(array_map(function($f) {
            return [
                'src' => basename($f['path']),
                'label' => $f['label'],
                'timestamp' => round((float)$f['timestamp'] * 1000),
            ];
        }, $this->frames));

        $framesCount = count($this->frames);
        $testNameEsc = htmlspecialchars($this->testName, ENT_QUOTES, 'UTF-8');
        
        $frameItems = '';
        foreach ($this->frames as $index => $frame) {
            $frameTime = round((float)$frame['timestamp'], 1);
            $labelEsc = htmlspecialchars($frame['label'], ENT_QUOTES, 'UTF-8');
            $frameItems .= '<div class="frame-item" data-index="' . $index . '" onclick="goToFrame(' . $index . ')">';
            $frameItems .= '<img src="' . htmlspecialchars(basename($frame['path']), ENT_QUOTES, 'UTF-8') . '" alt="' . $labelEsc . '">';
            $frameItems .= '<div class="info"><div class="label">' . $labelEsc . '</div><div class="time">' . $frameTime . 's</div></div></div>';
        }

        $html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recording: ' . $testNameEsc . '</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: "Segoe UI", system-ui, sans-serif; background: #1a1a2e; color: #eee; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; }
        .header h1 { font-size: 1.5rem; margin-bottom: 5px; }
        .header .meta { font-size: 0.85rem; opacity: 0.9; }
        .controls { display: flex; gap: 10px; padding: 15px; background: #16213e; flex-wrap: wrap; align-items: center; }
        .controls button { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s; }
        .btn-play { background: #e94560; color: white; }
        .btn-play:hover { background: #d63850; }
        .btn-pause { background: #0f3460; color: white; }
        .timeline { flex: 1; min-width: 200px; height: 8px; background: #0f3460; border-radius: 4px; cursor: pointer; position: relative; }
        .timeline-progress { height: 100%; background: linear-gradient(90deg, #667eea, #764ba2); border-radius: 4px; width: 0; transition: width 0.1s; }
        .time-display { font-size: 0.85rem; padding: 0 15px; color: #aaa; min-width: 120px; }
        .frame-info { font-size: 0.8rem; color: #888; padding: 5px 15px; }
        .viewer { display: flex; justify-content: center; align-items: center; padding: 20px; min-height: calc(100vh - 200px); }
        .viewer img { max-width: 100%; max-height: 70vh; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        .frame-list { max-height: 200px; overflow-y: auto; padding: 15px; background: #16213e; }
        .frame-list h3 { margin-bottom: 10px; font-size: 0.9rem; color: #aaa; }
        .frame-item { display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 6px; cursor: pointer; margin-bottom: 5px; transition: background 0.2s; }
        .frame-item:hover { background: #0f3460; }
        .frame-item.active { background: #667eea; }
        .frame-item img { width: 60px; height: 40px; object-fit: cover; border-radius: 4px; }
        .frame-item .info { flex: 1; }
        .frame-item .label { font-size: 0.85rem; font-weight: 500; }
        .frame-item .time { font-size: 0.75rem; color: #888; }
        .status { position: fixed; bottom: 20px; right: 20px; padding: 10px 15px; background: rgba(0,0,0,0.8); border-radius: 8px; font-size: 0.85rem; }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        .recording { display: inline-block; width: 10px; height: 10px; background: #e94560; border-radius: 50%; animation: blink 1s infinite; margin-right: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📹 Test Recording: ' . $testNameEsc . '</h1>
        <div class="meta">
            <span class="recording"></span>Total ' . $totalDuration . 'ms | ' . $framesCount . ' frames | ' . $this->frameRate . ' fps
        </div>
    </div>
    
    <div class="controls">
        <button class="btn-play" id="playBtn">▶ Play</button>
        <button class="btn-pause" id="pauseBtn" style="display:none">⏸ Pause</button>
        <div class="timeline" id="timeline">
            <div class="timeline-progress" id="progress"></div>
        </div>
        <span class="time-display" id="timeDisplay">0.0s / ' . round($totalDuration/1000, 1) . 's</span>
    </div>
    
    <div class="viewer">
        <img id="currentFrame" src="" alt="Frame">
    </div>
    
    <div class="frame-info">
        <span id="currentLabel">Frame: start</span>
        <span id="frameCount">0 / ' . $framesCount . '</span>
    </div>
    
    <div class="frame-list">
        <h3>📋 Frame List (' . $framesCount . ' frames)</h3>
        <div id="frameListContainer">' . $frameItems . '</div>
    </div>
    
    <div class="status">🟢 Recording playback ready</div>
    
    <script>
        const frames = ' . $framesJson . ';
        let currentFrame = 0;
        let isPlaying = false;
        let playInterval = null;
        const totalDuration = ' . $totalDuration . ';
        
        const playBtn = document.getElementById("playBtn");
        const pauseBtn = document.getElementById("pauseBtn");
        const progress = document.getElementById("progress");
        const timeDisplay = document.getElementById("timeDisplay");
        const currentLabel = document.getElementById("currentLabel");
        const frameCount = document.getElementById("frameCount");
        const currentFrameImg = document.getElementById("currentFrame");
        
        function goToFrame(index) {
            if (index < 0 || index >= frames.length) return;
            currentFrame = index;
            updateDisplay();
            updateFrameList();
        }
        
        function updateDisplay() {
            const frame = frames[currentFrame];
            currentFrameImg.src = frame.src;
            currentLabel.textContent = "Frame: " + frame.label;
            frameCount.textContent = (currentFrame + 1) + " / " + frames.length;
            
            const pct = (frame.timestamp / totalDuration) * 100;
            progress.style.width = pct + "%";
            
            const currentTime = (frame.timestamp / 1000).toFixed(1);
            const totalTime = (totalDuration / 1000).toFixed(1);
            timeDisplay.textContent = currentTime + "s / " + totalTime + "s";
        }
        
        function updateFrameList() {
            document.querySelectorAll(".frame-item").forEach((el, i) => {
                el.classList.toggle("active", i === currentFrame);
            });
            const container = document.querySelector(".frame-list");
            const activeEl = document.querySelector(".frame-item.active");
            if (activeEl) activeEl.scrollIntoView({ behavior: "smooth", block: "nearest" });
        }
        
        function play() {
            isPlaying = true;
            playBtn.style.display = "none";
            pauseBtn.style.display = "inline-block";
            
            const frameDelay = 1000 / ' . $this->frameRate . ';
            playInterval = setInterval(() => {
                if (currentFrame < frames.length - 1) {
                    currentFrame++;
                    updateDisplay();
                    updateFrameList();
                } else {
                    pause();
                }
            }, frameDelay);
        }
        
        function pause() {
            isPlaying = false;
            playBtn.style.display = "inline-block";
            pauseBtn.style.display = "none";
            clearInterval(playInterval);
        }
        
        playBtn.addEventListener("click", play);
        pauseBtn.addEventListener("click", pause);
        
        document.getElementById("timeline").addEventListener("click", function(e) {
            const rect = this.getBoundingClientRect();
            const pct = (e.clientX - rect.left) / rect.width;
            const targetTime = pct * totalDuration;
            
            let closest = 0;
            for (let i = 0; i < frames.length; i++) {
                if (frames[i].timestamp <= targetTime) closest = i;
            }
            goToFrame(closest);
        });
        
        document.addEventListener("keydown", function(e) {
            if (e.code === "Space") { e.preventDefault(); isPlaying ? pause() : play(); }
            if (e.code === "ArrowLeft") goToFrame(currentFrame - 1);
            if (e.code === "ArrowRight") goToFrame(currentFrame + 1);
        });
        
        if (frames.length > 0) goToFrame(0);
    </script>
</body>
</html>';

        file_put_contents($htmlPath, $html);
        
        return $htmlPath;
    }

    private function sanitizeFileName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
    }

    private function ensureOutputDir(): void
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    private function cleanupFrames(): void
    {
        foreach ($this->frames as $frame) {
            if (file_exists($frame['path'])) {
                @unlink($frame['path']);
            }
        }
        $this->frames = [];
    }

    public function getFrameCount(): int
    {
        return count($this->frames);
    }
}