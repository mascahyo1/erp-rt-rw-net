/**
 * E2E: Bulk action Admin Role Web Karyawan (tambah sekaligus + ubah sekaligus)
 * Local: http://erp-rt-rw-net.test — user jamal (admin perusahaan JMP)
 * Selector: data-testid. Log FE (console/pageerror) + BE (semua response >=400 & AJAX).
 * Screenshot tiap langkah + video mp4.
 */

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE = 'http://erp-rt-rw-net.test';
const OUT = path.join(__dirname, '..', 'result', 'BulkActionAdminRoleWebKaryawan');
const SC = path.join(OUT, 'screenshots');
const VID = path.join(OUT, 'videos');
for (const d of [OUT, SC, VID]) if (!fs.existsSync(d)) fs.mkdirSync(d, { recursive: true });
const LOG = path.join(OUT, 'log.txt');
fs.writeFileSync(LOG, '');

const log = (s) => { const line = `[${new Date().toISOString()}] ${s}`; console.log(line); fs.appendFileSync(LOG, line + '\n'); };
const snap = async (p, n) => { const f = path.join(SC, n + '.png'); await p.screenshot({ path: f, fullPage: false }); log('  [snap] ' + f); };

(async () => {
    log('=== START: bulk action admin-role-web-karyawan ===');
    const browser = await chromium.launch({ headless: false, slowMo: 200 });
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, recordVideo: { dir: VID, size: { width: 1440, height: 900 } } });
    const page = await ctx.newPage();

    const feErrors = [];
    const beErrors = [];
    page.on('console', m => { if (m.type() === 'error') { feErrors.push(m.text()); log('  [FE-console-error] ' + m.text().slice(0, 200)); } });
    page.on('pageerror', e => { feErrors.push(e.message); log('  [FE-pageerror] ' + e.message.slice(0, 300)); });
    page.on('response', r => {
        const u = r.url();
        if (u.includes('admin-role-web-karyawan')) {
            log(`  [BE ${r.status()}] ${r.request().method()} ${u.replace(BASE, '')}`);
            if (r.status() >= 400) beErrors.push(`HTTP ${r.status()} ${u}`);
        }
    });

    try {
        // ── 1. LOGIN (portal operator perusahaan) ──
        log('\n--- STEP 1: Login operator perusahaan (jamal / JMP) ---');
        await page.goto(BASE + '/login-perusahaan', { waitUntil: 'load' });
        await page.waitForTimeout(2500);
        await snap(page, '01-login-page');

        await page.fill('input[type="email"]', 'jamalulinsan1997@gmail.com');
        await page.fill('input[type="password"]', 'P@ssw0rd!2026');
        await page.waitForTimeout(1000);

        // Company picker
        log('  Buka company picker...');
        await page.evaluate(() => Array.from(document.querySelectorAll('button[type="button"]')).find(x => x.textContent.includes('Cari perusahaan'))?.click());
        await page.waitForTimeout(2000);
        await page.fill('input[placeholder*="Cari perusahaan"]', 'JMP');
        await page.waitForTimeout(2500);
        await snap(page, '02-company-picker');
        const companyItem = page.locator('[data-testid^="company-item-"]').first();
        if (await companyItem.count() === 0) throw new Error('Company item tidak ditemukan');
        await companyItem.click();
        log('  Company dipilih: JMP');
        await page.waitForTimeout(800);

        await page.waitForTimeout(3000); // turnstile test key auto-solve
        await page.click('form button[type="submit"]');
        await page.waitForTimeout(6000);
        log('  URL after login: ' + page.url());
        await snap(page, '03-after-login');
        if (!page.url().includes('/operator-perusahaan/dashboard')) throw new Error('Login gagal — URL: ' + page.url());

        // ── 2. BUKA HALAMAN ──
        log('\n--- STEP 2: Buka /operator-perusahaan/admin-role-web-karyawan ---');
        await page.goto(BASE + '/operator-perusahaan/admin-role-web-karyawan', { waitUntil: 'load' });
        await page.waitForTimeout(3000);
        await snap(page, '03-halaman');

        const rowCount = await page.locator('tbody tr').count();
        log('  Rows in table: ' + rowCount);
        if (rowCount === 0) throw new Error('Tabel kosong — cek data seed');

        // ── 3. TAMBAH SEKALIGUS (bulk assign) ──
        log('\n--- STEP 3: Buka modal Tambah Sekaligus ---');
        await page.click('[data-testid="btn-open-bulk-assign"]');
        await page.waitForTimeout(1500);
        await snap(page, '04-modal-bulk-assign');

        // multi-select: buka dropdown, pilih 3 karyawan
        log('  Buka dropdown multi-select karyawan...');
        await page.click('[data-testid="multiselect-bulk-karyawan"]');
        await page.waitForTimeout(1500);
        await snap(page, '05-multiselect-open');

        const dropdownOptions = page.locator('div.absolute.z-50 button[type="button"]:visible');
        const totalOpts = await dropdownOptions.count();
        log('  Options visible: ' + totalOpts);
        for (let i = 0; i < Math.min(3, totalOpts); i++) {
            const txt = await dropdownOptions.nth(i).innerText();
            await dropdownOptions.nth(i).click();
            log('  Pilih karyawan: ' + txt.replace(/\s+/g, ' ').slice(0, 40));
            await page.waitForTimeout(400);
        }
        await page.click('[data-testid="modal-bulk-assign"] h3');
        await page.waitForTimeout(800);
        await snap(page, '06-multiselect-selected');

        // role select (single AJAX)
        log('  Pilih role...');
        await page.click('[data-testid="select-bulk-assign-role"]');
        await page.waitForTimeout(1500);
        await snap(page, '07-role-dropdown');
        const roleOptions = page.locator('div.absolute.z-50 button[type="button"]:visible');
        const roleCount = await roleOptions.count();
        if (roleCount === 0) throw new Error('Role options kosong');
        const roleTxt = await roleOptions.first().innerText();
        await roleOptions.first().click();
        log('  Role dipilih: ' + roleTxt.replace(/\s+/g, ' ').slice(0, 40));
        await page.waitForTimeout(800);
        await snap(page, '08-bulk-assign-filled');

        // submit
        log('  Submit bulk assign...');
        await page.click('[data-testid="btn-submit-bulk-assign"]');
        await page.waitForTimeout(4000);
        await snap(page, '09-after-bulk-assign');

        // ── 4. UBAH SEKALIGUS (bulk update role) ──
        log('\n--- STEP 4: Ubah Sekaligus (pilih 2 mapping) ---');
        const checkboxes = page.locator('tbody input[type="checkbox"]');
        const cbCount = await checkboxes.count();
        log('  Checkboxes: ' + cbCount);
        for (let i = 0; i < Math.min(2, cbCount); i++) {
            await checkboxes.nth(i).check();
            log('  Check row ' + i);
        }
        await page.waitForTimeout(800);
        await snap(page, '10-rows-selected');

        await page.click('[data-testid="btn-bulk-update-role"]');
        await page.waitForTimeout(1500);
        await snap(page, '11-modal-bulk-update');

        await page.click('[data-testid="select-bulk-update-role"]');
        await page.waitForTimeout(1500);
        const updRoleOptions = page.locator('div.absolute.z-50 button[type="button"]:visible');
        const updRoleCount = await updRoleOptions.count();
        if (updRoleCount === 0) throw new Error('Role options kosong (update)');
        await updRoleOptions.first().click();
        log('  Role baru dipilih (update)');
        await page.waitForTimeout(800);
        await snap(page, '12-bulk-update-filled');

        await page.click('[data-testid="btn-submit-bulk-update-role"]');
        await page.waitForTimeout(4000);
        await snap(page, '13-after-bulk-update');

        // ── 5. SUMMARY ──
        log('\n=== SUMMARY ===');
        log('FE errors: ' + feErrors.length);
        feErrors.slice(0, 5).forEach(e => log('  FE: ' + e.slice(0, 150)));
        log('BE errors (>=400): ' + beErrors.length);
        beErrors.slice(0, 10).forEach(e => log('  BE: ' + e));
        log('PASS criteria: bulk assign + bulk update without 4xx/5xx');
        log('RESULT: ' + (beErrors.length === 0 ? 'PASS' : 'CHECK BE ERRORS'));
    } catch (e) {
        log('✗ EXCEPTION: ' + e.message);
        await snap(page, '99-exception');
    } finally {
        const vid = page.video();
        if (vid) { const vp = await vid.path(); log('  Video: ' + vp); }
        await browser.close();
        process.exit(0);
    }
})();
