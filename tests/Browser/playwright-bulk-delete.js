import { chromium } from 'playwright';
import path from 'path';
import fs from 'fs';
import { spawn } from 'child_process';
import { fileURLToPath } from 'url';
import { randomUUID } from 'crypto';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function waitForAndClick(page, selector, timeout = 5000) {
    try {
        await page.waitForSelector(selector, { timeout: timeout, state: 'visible' });
        await page.click(selector);
        return true;
    } catch (e) {
        console.log(`[WARN] Could not click: ${selector}`);
        return false;
    }
}

async function recordVideo() {
    const outputDir = 'tests/Browser/videos/playwright';

    if (!fs.existsSync(outputDir)) {
        fs.mkdirSync(outputDir, { recursive: true });
    }

    console.log('=== Playwright - Bulk Delete Test ===');

    const browser = await chromium.launch({ headless: false });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 },
        recordVideo: { dir: outputDir, size: { width: 1920, height: 1080 } }
    });

    const page = await context.newPage();

    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
    const uniqueCode = randomUUID().split('-')[0].toUpperCase();

    let consoleMessages = [];
    page.on('console', msg => consoleMessages.push(`[${msg.type()}] ${msg.text()}`));

    try {
        console.log('Step 1: Go to login-perusahaan');
        await page.goto('http://erp-rt-rw-net.test/login-perusahaan', { timeout: 15000 });
        await page.screenshot({ path: path.join(outputDir, `01-login_${timestamp}.png`), fullPage: true });

        console.log('Step 2: Click Cari Perusahaan');
        await waitForAndClick(page, 'button:has-text("Cari Perusahaan")');

        console.log('Step 3: Select PT Net Sejahtera Abadi');
        await waitForAndClick(page, 'text=PT Net Sejahtera Abadi');

        console.log('Step 4: Fill email');
        await page.type('input[type="email"]', 'admin@netsejahtera.com');

        console.log('Step 5: Fill password');
        await page.type('input[type="password"]', 'password123');
        await page.screenshot({ path: path.join(outputDir, `02-before-login_${timestamp}.png`), fullPage: true });

        console.log('Step 6: Click submit');
        await waitForAndClick(page, 'button[type="submit"]');
        await page.waitForTimeout(3000);
        await page.screenshot({ path: path.join(outputDir, `03-after-login_${timestamp}.png`), fullPage: true });

        console.log('Step 7: Go to daftar-paket');
        await page.goto('http://erp-rt-rw-net.test/operator-perusahaan/daftar-paket', { timeout: 15000 });
        await page.waitForTimeout(2000);
        await page.screenshot({ path: path.join(outputDir, `04-daftar-paket_${timestamp}.png`), fullPage: true });

        console.log('Step 8: Click Tambah Paket button');
        await waitForAndClick(page, 'button:has-text("Tambah Paket")');
        await page.waitForTimeout(1000);
        await page.screenshot({ path: path.join(outputDir, `05-modal_${timestamp}.png`), fullPage: true });

        console.log('Step 9: Fill form paket 1');
        await page.type('input[type="text"] >> nth=0', 'Paket Test ' + uniqueCode);
        await page.type('input[type="text"] >> nth=1', 'PKT-' + uniqueCode);
        await page.type('input[type="number"]', '100000');
        await page.type('textarea', 'Deskripsi ' + uniqueCode);
        await page.screenshot({ path: path.join(outputDir, `06-form-filled_${timestamp}.png`), fullPage: true });

        console.log('Step 10: Click Simpan');
        await page.click('button[type="submit"]');

        await page.waitForTimeout(3000);
        await page.screenshot({ path: path.join(outputDir, `07-after-submit_${timestamp}.png`), fullPage: true });

        console.log('Console logs:', consoleMessages.slice(-5));

        const isModalClosed = await page.locator('button:has-text("Tambah Paket")').isVisible().catch(() => false);
        if (isModalClosed) {
            console.log('Step 11: Modal closed - Success!');
            await waitForAndClick(page, 'button:has-text("Tambah Paket")');
        } else {
            console.log('[WARN] Modal still open -可能有validation error');
        }

        console.log('Step 12: Fill form paket 2');
        await page.type('input[type="text"] >> nth=0', 'Paket Test 2 ' + uniqueCode);
        await page.type('input[type="text"] >> nth=1', 'PKT2-' + uniqueCode);
        await page.type('input[type="number"]', '150000');
        await page.type('textarea', 'Deskripsi 2 ' + uniqueCode);

        console.log('Step 13: Click Simpan paket 2');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(3000);
        await page.screenshot({ path: path.join(outputDir, `08-after-create-2_${timestamp}.png`), fullPage: true });

        console.log('Step 14: Select checkboxes');
        await waitForAndClick(page, 'tbody input[type="checkbox"] >> nth=0');
        await waitForAndClick(page, 'tbody input[type="checkbox"] >> nth=1');
        await page.waitForTimeout(1000);
        await page.screenshot({ path: path.join(outputDir, `09-selected_${timestamp}.png`), fullPage: true });

        console.log('Step 15: Click Hapus');
        await waitForAndClick(page, 'button:has-text("Hapus")');
        await page.waitForTimeout(2000);
        await page.screenshot({ path: path.join(outputDir, `10-after-delete_${timestamp}.png`), fullPage: true });

    } catch (e) {
        console.log('[ERROR]', e.message);
        await page.screenshot({ path: path.join(outputDir, `error_${timestamp}.png`), fullPage: true });
        console.log('Console logs:', consoleMessages);
    }

    console.log('Step 16: Close browser');
    await context.close();
    await browser.close();

    const videoFiles = fs.readdirSync(outputDir).filter(f => f.endsWith('.webm'));

    if (videoFiles.length > 0) {
        const webmFile = path.join(outputDir, videoFiles[0]);
        const mp4File = path.join(outputDir, `bulk-delete-test_${timestamp}.mp4`);

        console.log(`Video: ${webmFile}`);

        if (fs.existsSync('c:\\laragon\\www\\erp-rt-rw-net\\ffmpeg.exe')) {
            const ffmpeg = spawn('c:\\laragon\\www\\erp-rt-rw-net\\ffmpeg.exe', [
                '-y', '-i', webmFile, '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-crf', '23', mp4File
            ]);

            ffmpeg.on('close', (code) => {
                if (code === 0 && fs.existsSync(mp4File)) {
                    console.log(`MP4 saved: ${mp4File} (${(fs.statSync(mp4File).size / 1024).toFixed(1)} KB)`);
                }
                try { fs.unlinkSync(webmFile); } catch (e) {}
                console.log('Done!');
            });
        } else {
            console.log('WebM saved:', webmFile);
            console.log('Done!');
        }
    } else {
        console.log('No video created');
        console.log('Done!');
    }
}

recordVideo().catch(console.error);