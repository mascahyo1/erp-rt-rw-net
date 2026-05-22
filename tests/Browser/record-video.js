const puppeteer = require('puppeteer');
const path = require('path');
const fs = require('fs');

async function recordBrowserVideo(url, outputPath, duration = 5000, fps = 30) {
    const browser = await puppeteer.launch({
        headless: false,
        args: [
            '--window-size=1920,1080',
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu'
        ]
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    console.log(`Navigating to: ${url}`);
    await page.goto(url, { waitUntil: 'networkidle0' });

    const outputDir = path.dirname(outputPath);
    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    const videoFile = path.join(outputDir, `temp_${Date.now()}.webm`);
    const client = await page.createCDPSession();
    await client.send('Page.startScreencast', {
        mode: 'animated',
        format: 'webm',
        quality: 100,
        maxWidth: 1920,
        maxHeight: 1080,
        everyNthFrame: Math.round(60 / fps)
    });

    let frameCount = 0;
    const frames = [];

    client.on('Page.screencastFrame', async (frame) => {
        const framePath = path.join(outputDir, `frame_${String(frameCount).padStart(6, '0')}.png`);
        fs.writeFileSync(framePath, Buffer.from(frame.data, 'base64'));
        frames.push(framePath);
        frameCount++;
        process.stdout.write(`\rRecording: ${frameCount} frames captured...`);
    });

    console.log(`\nRecording for ${duration}ms at ${fps} fps...`);
    await new Promise(resolve => setTimeout(resolve, duration));

    await client.send('Page.stopScreencast');
    await browser.close();

    console.log(`\nTotal frames captured: ${frameCount}`);

    if (frames.length > 0) {
        await createVideoFromFrames(frames, outputPath, fps);
        frames.forEach(f => fs.unlinkSync(f));
        console.log(`Video saved to: ${outputPath}`);
    }
}

async function createVideoFromFrames(frames, outputPath, fps) {
    const { spawn } = require('child_process');
    const ffmpegPath = 'c:\\laragon\\www\\erp-rt-rw-net\\ffmpeg.exe';

    const tempDir = path.join(path.dirname(outputPath), 'temp_' + Date.now());
    fs.mkdirSync(tempDir, { recursive: true });

    frames.forEach((src, i) => {
        const dest = path.join(tempDir, `frame_${String(i + 1).padStart(6, '0')}.png`);
        fs.copyFileSync(src, dest);
    });

    return new Promise((resolve, reject) => {
        const ffmpeg = spawn(ffmpegPath, [
            '-y',
            '-framerate', String(fps),
            '-i', path.join(tempDir, 'frame_%06d.png'),
            '-vf', 'scale=trunc(iw/2)*2:trunc(ih/2)*2',
            '-c:v', 'libx264',
            '-pix_fmt', 'yuv420p',
            '-crf', '18',
            '-preset', 'medium',
            outputPath
        ]);

        ffmpeg.on('close', (code) => {
            try {
                fs.rmSync(tempDir, { recursive: true, force: true });
            } catch (e) {}
            resolve();
        });

        ffmpeg.on('error', reject);
    });
}

const url = process.argv[2] || 'http://localhost/operator-perusahaan/daftar-paket';
const output = process.argv[3] || 'tests/Browser/videos/test_video.mp4';
const duration = parseInt(process.argv[4]) || 5000;
const fps = parseInt(process.argv[5]) || 30;

console.log('=== Browser Video Recorder (Puppeteer) ===');
console.log(`URL: ${url}`);
console.log(`Output: ${output}`);
console.log(`Duration: ${duration}ms`);
console.log(`FPS: ${fps}`);
console.log('');

recordBrowserVideo(url, output, duration, fps)
    .then(() => console.log('\nDone!'))
    .catch(err => console.error('\nError:', err));