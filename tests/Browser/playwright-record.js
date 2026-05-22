import { chromium } from 'playwright';
import path from 'path';
import fs from 'fs';
import { spawn } from 'child_process';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function recordVideo() {
    const outputDir = 'tests/Browser/videos/playwright';

    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    console.log('=== Playwright Video Recorder ===');
    console.log('');

    const browser = await chromium.launch({
        headless: false
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        recordVideo: {
            dir: outputDir,
            size: { width: 1920, height: 1080 }
        }
    });

    const page = await context.newPage();

    console.log('Navigating to: http://erp-rt-rw-net.test/operator-perusahaan/daftar-paket');

    try {
        await page.goto('http://erp-rt-rw-net.test/operator-perusahaan/daftar-paket', {
            timeout: 30000,
            waitUntil: 'networkidle'
        });

        console.log('Recording for 5 seconds...');
        await page.waitForTimeout(5000);

        const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
        const screenshotPath = path.join(outputDir, `screenshot_${timestamp}.png`);

        console.log('Taking screenshot...');
        await page.screenshot({
            path: screenshotPath,
            fullPage: true
        });

        console.log('Closing browser...');
        await context.close();
        await browser.close();

        const videoFiles = fs.readdirSync(outputDir).filter(f => f.endsWith('.webm'));

        if (videoFiles.length > 0) {
            const webmFile = path.join(outputDir, videoFiles[0]);
            const mp4File = path.join(outputDir, `video_${timestamp}.mp4`);

            console.log('');
            console.log(`Video file: ${webmFile}`);

            if (fs.existsSync('c:\\laragon\\www\\erp-rt-rw-net\\ffmpeg.exe')) {
                console.log('Converting to MP4...');
                const ffmpeg = spawn('c:\\laragon\\www\\erp-rt-rw-net\\ffmpeg.exe', [
                    '-y',
                    '-i', webmFile,
                    '-c:v', 'libx264',
                    '-pix_fmt', 'yuv420p',
                    '-crf', '23',
                    mp4File
                ]);

                ffmpeg.on('close', (code) => {
                    if (code === 0 && fs.existsSync(mp4File)) {
                        console.log(`MP4 saved: ${mp4File}`);

                        const stats = fs.statSync(mp4File);
                        console.log(`Size: ${(stats.size / 1024).toFixed(1)} KB`);
                    }

                    try { fs.unlinkSync(webmFile); } catch (e) {}
                    console.log('');
                    console.log('Done!');
                });

                ffmpeg.stderr.on('data', () => {});
            } else {
                console.log('FFmpeg not found. Keeping WebM format.');
                console.log(`WebM saved: ${webmFile}`);
                console.log('');
                console.log('Done!');
            }
        } else {
            console.log('');
            console.log('No video file created');
            console.log('Done!');
        }
    } catch (error) {
        console.error('Error:', error.message);
        await browser.close();
    }
}

recordVideo();