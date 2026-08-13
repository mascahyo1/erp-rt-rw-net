/**
 * E2E: 1) Landing page JMPGroup redesign  2) Form tambah customer — 3 lapis error
 * Local: http://erp-rt-rw-net.test
 * Log FE (console/pageerror) + BE (response >=400). Screenshot tiap langkah. Video mp4.
 */
const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE = 'http://erp-rt-rw-net.test';
const OUT = path.join(__dirname, '..', 'result', 'LandingAndCustomerFormError');
const SC = path.join(OUT, 'screenshots');
const VID = path.join(OUT, 'videos');
for (const d of [OUT, SC, VID]) if (!fs.existsSync(d)) fs.mkdirSync(d, { recursive: true });
const LOG = path.join(OUT, 'log.txt');
fs.writeFileSync(LOG, '');
const log = (s) => { const line = `[${new Date().toISOString()}] ${s}`; console.log(line); fs.appendFileSync(LOG, line + '\n'); };
const snap = async (p, n) => { const f = path.join(SC, n + '.png'); await p.screenshot({ path: f, fullPage: false }); log('  [snap] ' + f); };

(async () => {
    log('=== START: landing JMPGroup + form error 3 lapis ===');
    const browser = await chromium.launch({ headless: false, slowMo: 200 });
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, recordVideo: { dir: VID, size: { width: 1440, height: 900 } } });
    const page = await ctx.newPage();
    const feErrors = [];
    const beErrors = [];
    page.on('console', m => { if (m.type() === 'error') { feErrors.push(m.text()); log('  [FE-error] ' + m.text().slice(0, 180)); } });
    page.on('pageerror', e => { feErrors.push(e.message); log('  [FE-pageerror] ' + e.message.slice(0, 250)); });
    page.on('response', r => { if (r.status() >= 400 && !r.url().includes('cloudflare')) { beErrors.push(`HTTP ${r.status()} ${r.url()}`); log(`  [BE ${r.status()}] ${r.request().method()} ${r.url().replace(BASE, '')}`); } });

    const results = [];
    const check = (name, pass, detail) => { results.push({ name, pass, detail }); log(`  ${pass ? '✓' : '✗'} ${name}${detail ? ' — ' + detail : ''}`); };

    try {
        // ════════ PART 1: LANDING PAGE ════════
        log('\n════ PART 1: Landing page JMPGroup ════');
        await page.goto(BASE + '/', { waitUntil: 'load' });
        await page.waitForTimeout(3000);
        await snap(page, '01-landing-top');

        const hasJmp = await page.locator('[data-testid="logo-jmpgroup"]').count();
        check('Logo JMPGroup di navbar', hasJmp === 1);

        const heroText = await page.evaluate(() => document.querySelector('h1')?.innerText || '');
        log('  Hero: ' + heroText.replace(/\s+/g, ' '));
        check('Hero menampilkan JMPGroup/ISP', !heroText.includes('RT/RW'));

        const bodyText = await page.evaluate(() => document.body.innerText);
        const rtRwCount = (bodyText.match(/RT\/RW/gi) || []).length;
        log('  Jumlah "RT/RW" tersisa di halaman: ' + rtRwCount);
        check('Wording "RT/RW Net" sudah takeout', rtRwCount === 0, rtRwCount + ' sisa');

        const footerText = await page.evaluate(() => document.querySelector('footer')?.innerText || '');
        check('Footer menampilkan JMPGroup', footerText.includes('JMPGroup'));

        const title = await page.title();
        log('  Page title: ' + title);
        check('Title browser JMPGroup', title.toLowerCase().includes('jmpgroup'));

        await snap(page, '02-landing-hero');
        await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
        await page.waitForTimeout(1500);
        await snap(page, '03-landing-footer');

        // Portal login links tetap berfungsi
        log('  Cek 4 link portal login...');
        const portals = [
            ['/login-operator-saas', 'Operator SaaS'],
            ['/login-perusahaan', 'Perusahaan'],
            ['/login-pelanggan', 'Pelanggan'],
            ['/login-karyawan', 'Karyawan'],
        ];
        for (const [url, name] of portals) {
            await page.goto(BASE + url, { waitUntil: 'load' });
            await page.waitForTimeout(2500);
            const ok = page.url().includes(url) && !(await page.evaluate(() => document.body.innerText)).includes('404');
            check(`Link login ${name} berfungsi`, ok, page.url());
        }
        await page.goto(BASE + '/', { waitUntil: 'load' });
        await page.waitForTimeout(2000);

        // ════════ PART 2: FORM ERROR 3 LAPIS ════════
        log('\n════ PART 2: Form tambah customer — 3 lapis error ════');
        await page.goto(BASE + '/login-karyawan', { waitUntil: 'load' });
        await page.waitForTimeout(2500);
        await snap(page, '04-login-karyawan');

        await page.fill('input[type="email"]', 'karyawan1@jmp.test');
        await page.fill('input[type="password"]', 'password');
        await page.waitForTimeout(1000);

        // company picker kalau ada
        const hasPicker = await page.evaluate(() => !!Array.from(document.querySelectorAll('button[type="button"]')).find(x => x.textContent.includes('Cari perusahaan')));
        if (hasPicker) {
            await page.evaluate(() => Array.from(document.querySelectorAll('button[type="button"]')).find(x => x.textContent.includes('Cari perusahaan'))?.click());
            await page.waitForTimeout(2000);
            await page.fill('input[placeholder*="Cari perusahaan"]', 'JMP');
            await page.waitForTimeout(2500);
            const item = page.locator('[data-testid^="company-item-"]').first();
            if (await item.count() > 0) await item.click();
            await page.waitForTimeout(800);
        }
        await page.waitForTimeout(3000);
        await page.click('form button[type="submit"]');
        await page.waitForTimeout(6000);
        log('  URL setelah login: ' + page.url());
        if (!page.url().includes('/karyawan/')) throw new Error('Login karyawan gagal: ' + page.url());
        await snap(page, '05-karyawan-dashboard');

        await page.goto(BASE + '/karyawan/customer', { waitUntil: 'load' });
        await page.waitForTimeout(3000);
        await snap(page, '06-customer-page');

        log('  Buka modal Tambah Customer...');
        await page.click('[data-testid="btn-open-create"]');
        await page.waitForTimeout(1500);
        await snap(page, '07-modal-tambah');

        log('  Submit form kosong (harus validasi gagal)...');
        await page.click('[data-testid="btn-submit-create-customer"]');
        await page.waitForTimeout(4000);
        await snap(page, '08-after-submit-error');

        // LAPIS 1: per-field error
        const errNama = await page.locator('[data-testid="error-nama"]').count();
        const errEmail = await page.locator('[data-testid="error-email"]').count();
        const namaBorderRed = await page.evaluate(() => document.querySelector('[data-testid="input-create-nama"]')?.className.includes('border-red-400'));
        check('Lapis 1a: teks error di bawah input nama', errNama === 1);
        check('Lapis 1b: teks error di bawah input email', errEmail === 1);
        check('Lapis 1c: border merah pada input nama', namaBorderRed === true);

        // LAPIS 2: alert ringkasan di atas form
        const summary = await page.locator('[data-testid="create-error-summary"]');
        const summaryVisible = await summary.count();
        check('Lapis 2: alert ringkasan di atas form', summaryVisible === 1);
        if (summaryVisible === 1) log('  Summary text: ' + (await summary.innerText()).replace(/\s+/g, ' ').slice(0, 200));

        // LAPIS 3: toastr
        const toastText = await page.evaluate(() => document.body.innerText);
        const hasToast = toastText.includes('isian perlu diperbaiki') || toastText.includes('Gagal menyimpan');
        check('Lapis 3: toastr error informatif', hasToast);

        // ════════ SUMMARY ════════
        log('\n=== SUMMARY ===');
        const failed = results.filter(r => !r.pass);
        log('Total check: ' + results.length + ' | FAIL: ' + failed.length);
        failed.forEach(f => log('  FAIL: ' + f.name + ' — ' + f.detail));
        log('FE errors: ' + feErrors.length);
        feErrors.slice(0, 5).forEach(e => log('  FE: ' + e.slice(0, 150)));
        log('BE errors: ' + beErrors.length);
        beErrors.slice(0, 5).forEach(e => log('  BE: ' + e));
        log('RESULT: ' + (failed.length === 0 && beErrors.length === 0 ? 'PASS' : 'FAIL'));
    } catch (e) {
        log('✗ EXCEPTION: ' + e.message);
        await snap(page, '99-exception');
    } finally {
        const vid = page.video();
        if (vid) log('  Video: ' + await vid.path());
        await browser.close();
        process.exit(0);
    }
})();
