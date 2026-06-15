// E2E test: Bulk Verifikasi Midtrans di view Karyawan + Operator Perusahaan
// Verifikasi end-to-end:
//   1. Tombol "Verifikasi Midtrans (N)" visible di bulk action toolbar saat ada row midtrans+pending dipilih
//   2. Count di tombol = jumlah row midtrans+pending yang dipilih (skip non-midtrans/paid otomatis)
//   3. Klik tombol → trigger POST /api/riwayat-pembayaran/bulk-verify-midtrans dengan { ids: [...] }
//   4. Response JSON punya { status: 'ok', summary: { ok, failed, skipped, ... }, results: [...] }
//   5. Backend call Midtrans API real-time per payment (200 kalau ada di Midtrans, 502 kalau test order fake)
//   6. Toast sukses tampil dengan summary
//   7. Selected rows ter-unset + fetchData() refresh table
//
// Test ini MELENGKAPI `verify-midtrans-karyawan-perusahaan.cjs` (per-row sinkron)
// dengan fokus ke BULK action: checklist multi-row → klik 1 tombol → sinkron semua.

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const PlaywrightHelper = require('../../support/PlaywrightHelper.cjs');

const BASE = 'http://erp-rt-rw-net.test';
const SCREENSHOT_DIR = path.join(__dirname, 'screenshots-bulk-verify');
if (!fs.existsSync(SCREENSHOT_DIR)) fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });

function phpExec(expression) {
    // Tulis expression ke storage/app, require via tinker --execute (psysh-friendly)
    const fname = `_php_${Date.now()}_${Math.random().toString(36).slice(2, 8)}.php`;
    const fsPath = path.join('C:\\laragon\\www\\erp-rt-rw-net\\storage\\app', fname);
    const relPath = `storage/app/${fname}`;
    fs.writeFileSync(fsPath, `<?php\n${expression}\n`);
    try {
        const result = execSync(`php artisan tinker --no-interaction --execute='require "${relPath}";'`, {
            encoding: 'utf8',
            cwd: 'C:\\laragon\\www\\erp-rt-rw-net',
            shell: 'C:\\Program Files\\Git\\bin\\bash.exe',
        });
        return result.trim();
    } finally {
        try { fs.unlinkSync(fsPath); } catch (e) { /* ignore */ }
    }
}

function flushCache() {
    try {
        execSync('rm -rf /c/laragon/www/erp-rt-rw-net/storage/framework/cache/data/*', { stdio: 'pipe', shell: 'C:\\Program Files\\Git\\bin\\bash.exe' });
        execSync('cd /c/laragon/www/erp-rt-rw-net && php artisan cache:clear', { stdio: 'pipe', shell: 'C:\\Program Files\\Git\\bin\\bash.exe' });
    } catch (e) { /* ignore */ }
}

async function run() {
    const helper = new PlaywrightHelper(BASE);
    let pass = 0, fail = 0;
    const log = (m) => console.log(`[${new Date().toISOString().slice(11, 23)}] ${m}`);
    const assert = (cond, label) => {
        if (cond) { log(`✅ ${label}`); pass++; }
        else { log(`❌ ${label}`); fail++; }
    };

    // Pre-flight: ensure ada minimal 2 payment midtrans+pending di company Net Sejahtera
    log('=== PRE-FLIGHT: ensure ≥2 midtrans+pending payment ===');
    const netSejahteraId = phpExec(`echo \\App\\Models\\Company::where('name', 'LIKE', '%Net Sejahtera%')->value('id');`);
    log(`Net Sejahtera company_id=${netSejahteraId}`);
    const pendingCount = phpExec(`
$cid = '${netSejahteraId}';
echo \\App\\Models\\CustInternetPayment::where('provider', 'midtrans')
    ->where('status', 'pending')
    ->whereNull('deleted_at')
    ->whereHas('custInternetInvc.custInternet.customer', function ($q) use ($cid) {
        $q->where('company_id', $cid);
    })
    ->count();
`);
    log(`Midtrans+pending di Net Sejahtera: ${pendingCount}`);
    assert(parseInt(pendingCount) >= 2, 'Ada ≥2 midtrans+pending payment di Net Sejahtera');

    if (parseInt(pendingCount) < 2) {
        log('⚠️ Skip test — butuh minimal 2 midtrans+pending di Net Sejahtera.');
        await helper.close();
        process.exit(1);
    }

    // Disable dialog confirm supaya auto-accept. Pakai flag supaya tidak double-register.
    let dialogHandlerAttached = false;
    function attachDialogHandler(page) {
        if (dialogHandlerAttached) return;
        page.on('dialog', async (dialog) => {
            log(`  [dialog] ${dialog.type()}: ${dialog.message().substring(0, 80)}`);
            try { await dialog.accept(); } catch (e) { /* already handled */ }
        });
        dialogHandlerAttached = true;
    }

    try {
        // === PHASE 1: Login Admin Net Sejahtera + Bulk Verify ===
        log('\n=== PHASE 1: Login Admin Net Sejahtera + Bulk Verify ===');
        await helper.launch();
        helper.screenshotCount = 0;
        await attachDialogHandler(helper.page);

        await helper.page.goto(`${BASE}/login-perusahaan`, { waitUntil: 'domcontentloaded' });
        await helper.page.waitForTimeout(2500);
        await helper.page.locator('button:has-text("Cari perusahaan")').first().click();
        await helper.page.waitForTimeout(2000);
        await helper.page.locator('input[placeholder="Cari perusahaan..."]').first().fill('Net Sejahtera');
        await helper.page.waitForTimeout(2000);
        await helper.page.locator('button[data-testid^="company-item-"]:has-text("PT Net Sejahtera Abadi")').first().click();
        await helper.page.waitForTimeout(1000);
        await helper.page.fill('input[type="email"]', 'admin@netsejahtera.com');
        await helper.page.fill('input[type="password"]', 'password123');
        await helper.page.click('button[type="submit"]');
        await helper.page.waitForLoadState('domcontentloaded');
        await helper.page.waitForTimeout(3000);
        log(`URL after login: ${helper.page.url()}`);
        assert(helper.page.url().includes('dashboard') || helper.page.url().includes('admin-perusahaan'), 'Login admin Net Sejahtera sukses');

        // Open Riwayat Pembayaran + filter hanya midtrans+pending (biar data fokus)
        await helper.page.goto(`${BASE}/operator-perusahaan/riwayat-pembayaran`, { waitUntil: 'domcontentloaded' });
        await helper.page.waitForTimeout(3000);

        // Filter status=pending
        log('[perusahaan] Apply filter status=pending');
        const statusSelect = helper.page.locator('select').filter({ has: helper.page.locator('option[value="pending"]') }).first();
        await statusSelect.selectOption('pending').catch(() => log('  filter select not found, skip'));
        await helper.page.waitForTimeout(500);
        // Filter provider=external
        const providerSelect = helper.page.locator('select').filter({ has: helper.page.locator('option[value="external"]') }).first();
        await providerSelect.selectOption('external').catch(() => log('  filter select not found, skip'));
        await helper.page.waitForTimeout(500);
        const filterBtn = helper.page.locator('button:has-text("Filter")').first();
        if (await filterBtn.isVisible({ timeout: 500 }).catch(() => false)) {
            await filterBtn.click();
            await helper.page.waitForTimeout(2000);
        }

        // Cari row midtrans+pending, checklist 2 row pertama
        const rows1 = helper.page.locator('tbody tr');
        const count1 = await rows1.count();
        log(`[perusahaan] Rows setelah filter: ${count1}`);

        let checkedCount = 0;
        const targetCheck = 2;
        for (let i = 0; i < count1 && checkedCount < targetCheck; i++) {
            const row = rows1.nth(i);
            const text = (await row.textContent() || '').toLowerCase();
            if (text.includes('midtrans') && (text.includes('pending') || text.includes('menunggu'))) {
                const checkbox = row.locator('input[type="checkbox"]').first();
                if (await checkbox.isVisible({ timeout: 500 }).catch(() => false)) {
                    await checkbox.click();
                    checkedCount++;
                    log(`[perusahaan] Checked row ${i} (total checked: ${checkedCount})`);
                }
            }
        }
        assert(checkedCount === targetCheck, `[perusahaan] ${targetCheck} row midtrans+pending di-checklist`);

        // Toolbar harus muncul + tombol "Verifikasi Midtrans (N)" visible
        await helper.page.waitForTimeout(1000);
        await helper.screenshot('perusahaan-toolbar-shown');

        const bulkBtn = helper.page.locator('button:has-text("Verifikasi Midtrans")').first();
        const bulkVisible = await bulkBtn.isVisible({ timeout: 2000 }).catch(() => false);
        assert(bulkVisible, '[perusahaan] Tombol "Verifikasi Midtrans" VISIBLE di toolbar');

        // Verify counter di tombol = checkedCount
        const btnText = await bulkBtn.textContent().catch(() => '');
        const counterMatch = btnText.match(/\((\d+)\)/);
        const btnCounter = counterMatch ? parseInt(counterMatch[1]) : 0;
        log(`[perusahaan] Tombol counter: ${btnCounter} (expected: ${targetCheck})`);
        assert(btnCounter === targetCheck, `[perusahaan] Counter tombol = ${targetCheck}`);

        // Capture API call
        log('[perusahaan] Clicking Bulk Verify Midtrans');
        const apiRespPromise = helper.page.waitForResponse(
            r => r.url().includes('/bulk-verify-midtrans') && r.request().method() === 'POST',
            { timeout: 30000 }
        ).catch(() => null);

        await bulkBtn.click();

        const apiResp = await apiRespPromise;
        if (apiResp) {
            const body = await apiResp.text().catch(() => '');
            log(`[perusahaan] API response: HTTP ${apiResp.status()} ${body.substring(0, 300)}`);
            assert(apiResp.status() === 200, '[perusahaan] Bulk verify API response HTTP 200');

            // Parse JSON
            let payload;
            try { payload = JSON.parse(body); } catch (e) { payload = {}; }
            assert(payload.status === 'ok', '[perusahaan] Response status=ok');
            assert(payload.summary && typeof payload.summary.ok !== 'undefined', '[perusahaan] Response ada summary.ok');
            assert(payload.summary && typeof payload.summary.verified !== 'undefined', '[perusahaan] Response ada summary.verified');
            assert(Array.isArray(payload.results), '[perusahaan] Response ada results array');
            log(`[perusahaan] Summary: ok=${payload.summary?.ok} failed=${payload.summary?.failed} verified=${payload.summary?.verified} skipped=${payload.summary?.skipped}`);
        } else {
            assert(false, '[perusahaan] Bulk verify API terpanggil');
        }

        // Toast sukses harus muncul
        await helper.page.waitForTimeout(2000);
        const toastText = await helper.page.locator('p').filter({ hasText: /Sinkron selesai/ }).first().textContent({ timeout: 3000 }).catch(() => '');
        log(`[perusahaan] Toast text: "${toastText}"`);
        assert(toastText.toLowerCase().includes('sinkron') || toastText.toLowerCase().includes('berhasil'), '[perusahaan] Toast sukses muncul');

        // Selected rows harus ter-unset (toolbar hilang)
        await helper.page.waitForTimeout(2000);
        await helper.screenshot('perusahaan-after-bulk-verify');
        const toolbarGone = await helper.page.locator('button:has-text("Verifikasi Midtrans")').isVisible({ timeout: 1000 }).catch(() => false);
        assert(!toolbarGone, '[perusahaan] Toolbar hilang setelah bulk verify (selectedIds cleared)');

        // === PHASE 2: Login Karyawan + Bulk Verify ===
        log('\n=== PHASE 2: Login Karyawan + Bulk Verify ===');
        await helper.page.context().clearCookies();
        await helper.page.goto(`${BASE}/login-karyawan`, { waitUntil: 'domcontentloaded' });
        await helper.page.waitForTimeout(3000);
        await helper.page.locator('button:has-text("Cari perusahaan")').first().click();
        await helper.page.waitForTimeout(2000);
        await helper.page.locator('input[placeholder="Cari perusahaan..."]').first().fill('Net Sejahtera');
        await helper.page.waitForTimeout(2500);
        await helper.page.locator('button[data-testid^="company-item-"]:has-text("PT Net Sejahtera Abadi")').first().click();
        await helper.page.waitForTimeout(1000);
        await helper.page.fill('input[type="email"]', 'ahmad@netsejahtera.com');
        await helper.page.fill('input[type="password"]', 'password123');
        await helper.page.click('button[type="submit"]');
        await helper.page.waitForLoadState('domcontentloaded');
        await helper.page.waitForTimeout(3000);
        log(`URL after login: ${helper.page.url()}`);
        assert(helper.page.url().includes('karyawan') && !helper.page.url().includes('login'), 'Login karyawan sukses');
        await attachDialogHandler(helper.page);

        await helper.page.goto(`${BASE}/karyawan/riwayat-pembayaran`, { waitUntil: 'domcontentloaded' });
        await helper.page.waitForTimeout(3000);

        // Filter sama
        const statusSelect2 = helper.page.locator('select').filter({ has: helper.page.locator('option[value="pending"]') }).first();
        await statusSelect2.selectOption('pending').catch(() => log('  filter select not found, skip'));
        await helper.page.waitForTimeout(500);
        const providerSelect2 = helper.page.locator('select').filter({ has: helper.page.locator('option[value="external"]') }).first();
        await providerSelect2.selectOption('external').catch(() => log('  filter select not found, skip'));
        await helper.page.waitForTimeout(500);
        const filterBtn2 = helper.page.locator('button:has-text("Filter")').first();
        if (await filterBtn2.isVisible({ timeout: 500 }).catch(() => false)) {
            await filterBtn2.click();
            await helper.page.waitForTimeout(2000);
        }

        const rows2 = helper.page.locator('tbody tr');
        const count2 = await rows2.count();
        log(`[karyawan] Rows setelah filter: ${count2}`);

        let checkedCount2 = 0;
        for (let i = 0; i < count2 && checkedCount2 < targetCheck; i++) {
            const row = rows2.nth(i);
            const text = (await row.textContent() || '').toLowerCase();
            if (text.includes('midtrans') && (text.includes('pending') || text.includes('menunggu'))) {
                const checkbox = row.locator('input[type="checkbox"]').first();
                if (await checkbox.isVisible({ timeout: 500 }).catch(() => false)) {
                    await checkbox.click();
                    checkedCount2++;
                    log(`[karyawan] Checked row ${i} (total: ${checkedCount2})`);
                }
            }
        }
        assert(checkedCount2 === targetCheck, `[karyawan] ${targetCheck} row midtrans+pending di-checklist`);

        await helper.page.waitForTimeout(1000);
        await helper.screenshot('karyawan-toolbar-shown');

        const bulkBtn2 = helper.page.locator('button:has-text("Verifikasi Midtrans")').first();
        const bulkVisible2 = await bulkBtn2.isVisible({ timeout: 2000 }).catch(() => false);
        assert(bulkVisible2, '[karyawan] Tombol "Verifikasi Midtrans" VISIBLE di toolbar');

        const btnText2 = await bulkBtn2.textContent().catch(() => '');
        const counterMatch2 = btnText2.match(/\((\d+)\)/);
        const btnCounter2 = counterMatch2 ? parseInt(counterMatch2[1]) : 0;
        log(`[karyawan] Tombol counter: ${btnCounter2}`);
        assert(btnCounter2 === targetCheck, `[karyawan] Counter tombol = ${targetCheck}`);

        log('[karyawan] Clicking Bulk Verify Midtrans');
        const apiRespPromise2 = helper.page.waitForResponse(
            r => r.url().includes('/bulk-verify-midtrans') && r.request().method() === 'POST',
            { timeout: 30000 }
        ).catch(() => null);

        await bulkBtn2.click();

        const apiResp2 = await apiRespPromise2;
        if (apiResp2) {
            const body = await apiResp2.text().catch(() => '');
            log(`[karyawan] API response: HTTP ${apiResp2.status()} ${body.substring(0, 300)}`);
            assert(apiResp2.status() === 200, '[karyawan] Bulk verify API response HTTP 200');

            let payload;
            try { payload = JSON.parse(body); } catch (e) { payload = {}; }
            assert(payload.status === 'ok', '[karyawan] Response status=ok');
            assert(payload.summary && typeof payload.summary.ok !== 'undefined', '[karyawan] Response ada summary.ok');
            assert(Array.isArray(payload.results), '[karyawan] Response ada results array');
        } else {
            assert(false, '[karyawan] Bulk verify API terpanggil');
        }

        await helper.page.waitForTimeout(2000);
        await helper.screenshot('karyawan-after-bulk-verify');
        const toolbarGone2 = await helper.page.locator('button:has-text("Verifikasi Midtrans")').isVisible({ timeout: 1000 }).catch(() => false);
        assert(!toolbarGone2, '[karyawan] Toolbar hilang setelah bulk verify');

    } catch (e) {
        log(`❌ FATAL: ${e.message}\n${e.stack}`);
        fail++;
    } finally {
        log(`\n=== RESULT: ${pass} pass, ${fail} fail ===`);
        await helper.close();
        process.exit(fail > 0 ? 1 : 0);
    }
}

run();
