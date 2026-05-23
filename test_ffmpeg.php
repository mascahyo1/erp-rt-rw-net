<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ffmpegPath = 'c:\laragon\www\erp-rt-rw-net\ffmpeg.exe';
$testImage = 'tests/Browser/screenshots/operator-perusahaan/daftar-paket/simple-test/01-page-loaded.png';
$outputMp4 = 'tests/Browser/videos/test_manual.mp4';
$tempDir = 'tests/Browser/videos/temp_test_manual';

if (!file_exists($testImage)) {
    echo "ERROR: Test image not found: $testImage\n";
    exit(1);
}

if (!file_exists($ffmpegPath)) {
    echo "ERROR: FFmpeg not found: $ffmpegPath\n";
    exit(1);
}

echo "Testing FFmpeg...\n";
$version = shell_exec("\"$ffmpegPath\" -version 2>&1");
echo "FFmpeg version: " . substr($version, 0, 100) . "...\n";

echo "Copying test image to temp frames...\n";
mkdir($tempDir, 0755, true);
for ($i = 0; $i < 10; $i++) {
    copy($testImage, "$tempDir/frame_" . sprintf('%06d', $i) . ".png");
}
echo "Created 10 frames in: $tempDir\n";

echo "Running FFmpeg to create MP4...\n";
$command = sprintf(
    '"%s" -y -framerate 10 -i "%s\\frame_%%06d.png" -vf "scale=trunc(iw/2)*2:trunc(ih/2)*2" -c:v libx264 -pix_fmt yuv420p -crf 23 -preset medium "%s" 2>&1',
    $ffmpegPath,
    $tempDir,
    $outputMp4
);
echo "Command: $command\n";

$output = shell_exec($command);
echo "FFmpeg output:\n$output\n";

if (file_exists($outputMp4)) {
    $size = filesize($outputMp4);
    echo "SUCCESS! MP4 created: $outputMp4 (size: $size bytes)\n";
} else {
    echo "ERROR: MP4 not created!\n";
}

echo "Cleanup...\n";
$files = array_diff(scandir($tempDir), ['.', '..']);
foreach ($files as $file) {
    @unlink("$tempDir/$file");
}
@rmdir($tempDir);
echo "Done.\n";