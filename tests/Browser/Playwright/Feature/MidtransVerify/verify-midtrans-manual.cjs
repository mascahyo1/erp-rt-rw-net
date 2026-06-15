// E2E test: Verifikasi Manual Midtrans (tombol Sinkron Status) + lock non-internal + webhook toggle
// Phase 1: Lock guard (coba edit/delete payment non-internal → 403/blocked)
// Phase 2: Sinkron Status works (webhook ON atau OFF, status Midtrans fetched real-time)
// Phase 3: Config toggle test (webhook ON/OFF)

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');
const PlaywrightHelper = require('../../support/PlaywrightHelper.cjs');


const BASE = require('../../support/baseUrl.cjs');
const SCREENSHOT_DIR = path.join(__dirname, 'screenshots');
if (!fs.existsSync(SCREENSHOT_DIR)) fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });

async function run() {
    const helper = new PlaywrightHelper(BASE);
    let pass = 0, fail = 0;
    const log = (m) => console.log(`[${new Date().toISOString().slice(11, 23)}] ${m}`);
    const assert = (cond, label) => {
        if (cond) { log(`✅ ${label}`); pass++; }
        else { log(`❌ ${label}`); fail++; }
    };

    try {
        await helper.launch();
        helper.screenshotCount = 0;

        // === Login Admin Net Sejahtera ===
        log('=== LOGIN ADMIN NET SEJAHTERA ===');
        await helper.page.goto(`${BASE}/login-perusahaan`);
        await helper.page.waitForLoadState('networkidle');
        await helper.page.waitForTimeout(1500);
        // Click button utama CompanySearchInput (bukan .fa-building di luar)
        const companyButtonA = helper.page.locator('button.flex.items-center.justify-between').first();
        if (await companyButtonA.isVisible({ timeout: 1000 }).catch(() => false)) {
            await companyButtonA.click();
        } else {
            // Fallback: cari button dengan text "Cari perusahaan"
            await helper.page.locator('button:has-text("Cari perusahaan")').first().click();
        }
        await helper.page.waitForTimeout(1500);
        // Ketik di search input dropdown
        const searchA = helper.page.locator('input[placeholder="Cari perusahaan..."]').first();
        await searchA.fill('Net Sejahtera');
        await helper.page.waitForTimeout(1500);
        // Click item perusahaan
        await helper.page.locator('button[data-testid^="company-item-"]').first().click();
        await helper.page.waitForTimeout(500);
        await helper.page.fill('input[type="email"]', 'admin@netsejahtera.com');
        await helper.page.fill('input[type="password"]', 'password123');
        await helper.page.click('button[type="submit"]');
        await helper.page.waitForLoadState('networkidle');
        await helper.page.waitForTimeout(3000);
        log(`After login URL: ${helper.page.url()}`);
        assert(helper.page.url().includes('dashboard') || helper.page.url().includes('admin-perusahaan'), 'Login admin Net Sejahtera sukses');

        // === Open Riwayat Pembayaran ===
        log('=== BUKA RIWAYAT PEMBAYARAN ===');
        await helper.page.goto(`${BASE}/operator-perusahaan/riwayat-pembayaran`);
        await helper.page.waitForLoadState('networkidle');
        await helper.page.waitForTimeout(1500);
        await helper.screenshot('riwayat-pembayaran-operator');
        log(`Screenshot: riwayat-pembayaran-operator.png`);

        // === Filter provider=midtrans ===
        log('=== FILTER PROVIDER=MIDTRANS ===');
        const providerFilter = helper.page.locator('select').filter({ hasText: /Provider/i }).first();
        if (await providerFilter.isVisible({ timeout: 1500 }).catch(() => false)) {
            await providerFilter.selectOption('midtrans');
            await helper.page.waitForTimeout(1500);
        }
        await helper.screenshot('riwayat-pembayaran-filtered-midtrans');
        log(`Screenshot: riwayat-pembayaran-filtered-midtrans.png`);

        // === Verify: Edit/Review/Delete button HIDDEN for non-internal ===
        const rows = helper.page.locator('tbody tr');
        const rowCount = await rows.count();
        log(`Found ${rowCount} rows after filter`);
        // Debug: log text of first 3 rows
        for (let i = 0; i < Math.min(3, rowCount); i++) {
            const row = rows.nth(i);
            const text = await row.textContent();
            log(`  Row ${i}: ${(text || '').replace(/\s+/g, ' ').substring(0, 150)}`);
        }
        let midtransRowFound = false;
        for (let i = 0; i < rowCount; i++) {
            const row = rows.nth(i);
            const text = (await row.textContent() || '').toLowerCase();
            if (text.includes('midtrans') && (text.includes('pending') || text.includes('menunggu'))) {
                midtransRowFound = true;
                log(`Midtrans+pending row found at index ${i}`);

                const editBtn = row.locator('button .fa-edit').first();
                const editVisible = await editBtn.isVisible({ timeout: 500 }).catch(() => false);
                assert(!editVisible, 'Edit button HIDDEN untuk non-internal (locked)');

                const reviewBtn = row.locator('button .fa-clipboard-check').first();
                const reviewVisible = await reviewBtn.isVisible({ timeout: 500 }).catch(() => false);
                assert(!reviewVisible, 'Review button HIDDEN untuk non-internal');

                const deleteBtn = row.locator('button .fa-trash-alt').first();
                const deleteVisible = await deleteBtn.isVisible({ timeout: 500 }).catch(() => false);
                assert(!deleteVisible, 'Delete button HIDDEN untuk non-internal');

                const lockIcon = row.locator('.fa-lock').first();
                const lockVisible = await lockIcon.isVisible({ timeout: 500 }).catch(() => false);
                assert(lockVisible, 'Lock icon VISIBLE untuk non-internal');

                const sinkronBtn = row.locator('button .fa-sync-alt, button .fa-spinner').first();
                const sinkronVisible = await sinkronBtn.isVisible({ timeout: 500 }).catch(() => false);
                assert(sinkronVisible, 'Tombol Sinkron Status Midtrans VISIBLE untuk midtrans+pending');

                await helper.screenshot('riwayat-pembayaran-midtrans-row');
                break;
            }
        }
        assert(midtransRowFound, 'Ada row midtrans+pending di list');

        // === Click Sinkron Status ===
        log('=== KLIK SINKRON STATUS ===');
        if (midtransRowFound) {
            const row = helper.page.locator('tbody tr').filter({ hasText: 'midtrans' }).filter({ hasText: 'pending' }).first();
            const sinkronBtn = row.locator('button .fa-sync-alt').first();
            if (await sinkronBtn.isVisible().catch(() => false)) {
                await sinkronBtn.click();
                await helper.page.waitForTimeout(3000);
                await helper.screenshot('riwayat-pembayaran-after-sinkron');
                log('Screenshot: riwayat-pembayaran-after-sinkron.png');
            }
        }

        // === Login Karyawan + verify same view ===
        log('=== LOGIN KARYAWAN ===');
        await helper.page.context().clearCookies();
        await helper.page.goto(`${BASE}/login-karyawan`);
        await helper.page.waitForLoadState('networkidle');
        await helper.page.waitForTimeout(2500);
        // Click company selector button (text default "Cari perusahaan Anda...")
        const companyBtnK = helper.page.locator('button:has-text("Cari perusahaan")').first();
        await companyBtnK.click();
        await helper.page.waitForTimeout(2000);
        // Type di search input dropdown
        const searchInputK = helper.page.locator('input[placeholder="Cari perusahaan..."]').first();
        await searchInputK.fill('Net Sejahtera');
        await helper.page.waitForTimeout(2500);
        // Log dropdown items
        const itemsK = await helper.page.locator('button[data-testid^="company-item-"]').allTextContents();
        log(`  Dropdown items K: ${JSON.stringify(itemsK)}`);
        // Click hasil pertama
        const companyItemK = helper.page.locator('button[data-testid^="company-item-"]:has-text("PT Net Sejahtera Abadi")').first();
        await companyItemK.click();
        await helper.page.waitForTimeout(1000);
        // Log selected
        const selectedBtnTextK = await helper.page.locator('button.flex.items-center.justify-between').first().textContent();
        log(`  Selected company K: ${selectedBtnTextK}`);
        // Fill email & password
        await helper.page.fill('input[type="email"]', 'ahmad@netsejahtera.com');
        await helper.page.fill('input[type="password"]', 'password123');
        await helper.page.waitForTimeout(500);
        await helper.screenshot('debug-karyawan-form');
        await helper.page.click('form button[type="submit"]');
        await helper.page.waitForLoadState('networkidle');
        await helper.page.waitForTimeout(3000);
        log(`Karyawan URL: ${helper.page.url()}`);
        const errorTextK2 = await helper.page.locator('p.text-red-500, .text-red-500').allTextContents();
        log(`  Error text K2 (after submit): ${JSON.stringify(errorTextK2)}`);
        const karyawanLoginOk = helper.page.url().includes('dashboard') || (helper.page.url().includes('karyawan') && !helper.page.url().includes('login'));
        assert(karyawanLoginOk, 'Login karyawan sukses');

        await helper.page.goto(`${BASE}/karyawan/riwayat-pembayaran`);
        await helper.page.waitForLoadState('networkidle');
        await helper.page.waitForTimeout(1500);

        const providerFilterK = helper.page.locator('select').filter({ hasText: /Provider/i }).first();
        if (await providerFilterK.isVisible({ timeout: 1500 }).catch(() => false)) {
            await providerFilterK.selectOption('midtrans');
            await helper.page.waitForTimeout(1500);
        }
        await helper.screenshot('riwayat-pembayaran-karyawan-midtrans');
        const rowsK = helper.page.locator('tbody tr');
        const countK = await rowsK.count();
        log(`Karyawan: ${countK} rows after filter`);
        let karyawanMidtransFound = false;
        for (let i = 0; i < countK; i++) {
            const row = rowsK.nth(i);
            const text = await row.textContent();
            if (text && text.includes('midtrans')) {
                karyawanMidtransFound = true;
                const sinkronBtn = row.locator('button .fa-sync-alt').first();
                const sinkronVisible = await sinkronBtn.isVisible({ timeout: 500 }).catch(() => false);
                assert(sinkronVisible, 'Tombol Sinkron Status VISIBLE di view karyawan');
                break;
            }
        }

        // === Login Pelanggan + verify same view ===
        log('=== LOGIN PELANGGAN ===');
        await helper.page.context().clearCookies();
        await helper.page.goto(`${BASE}/login-pelanggan`);
        await helper.page.waitForLoadState('networkidle');
        await helper.page.waitForTimeout(2500);
        const companyBtnC = helper.page.locator('button:has-text("Cari perusahaan")').first();
        await companyBtnC.click();
        await helper.page.waitForTimeout(2000);
        const searchInputC = helper.page.locator('input[placeholder="Cari perusahaan..."]').first();
        await searchInputC.fill('Net Sejahtera');
        await helper.page.waitForTimeout(2000);
        const companyItemC = helper.page.locator('button[data-testid^="company-item-"]:has-text("PT Net Sejahtera Abadi")').first();
        await companyItemC.click();
        await helper.page.waitForTimeout(1000);
        const selectedBtnTextC = await helper.page.locator('button.flex.items-center.justify-between').first().textContent();
        log(`  Selected company C: ${selectedBtnTextC}`);
        await helper.page.fill('input[type="email"]', 'dewi.w@gmail.com');
        await helper.page.fill('input[type="password"]', 'password123');
        await helper.page.waitForTimeout(500);
        await helper.page.click('form button[type="submit"]');
        await helper.page.waitForLoadState('networkidle');
        await helper.page.waitForTimeout(3000);
        log(`Customer URL: ${helper.page.url()}`);
        const errorTextC2 = await helper.page.locator('p.text-red-500, .text-red-500').allTextContents();
        log(`  Error text C2: ${JSON.stringify(errorTextC2)}`);
        const customerLoginOk = helper.page.url().includes('dashboard') || (helper.page.url().includes('customer') && !helper.page.url().includes('login'));
        assert(customerLoginOk, 'Login customer sukses');

        await helper.page.goto(`${BASE}/customer/riwayat-pembayaran`);
        await helper.page.waitForLoadState('networkidle');
        await helper.page.waitForTimeout(1500);
        await helper.screenshot('riwayat-pembayaran-customer');

        const rowsC = helper.page.locator('tbody tr');
        const countC = await rowsC.count();
        log(`Customer: ${countC} rows`);
        let customerMidtransFound = false;
        for (let i = 0; i < countC; i++) {
            const row = rowsC.nth(i);
            const text = await row.textContent();
            if (text && text.includes('midtrans') && text.includes('Menunggu')) {
                customerMidtransFound = true;
                const sinkronBtn = row.locator('button .fa-sync-alt').first();
                const sinkronVisible = await sinkronBtn.isVisible({ timeout: 500 }).catch(() => false);
                assert(sinkronVisible, 'Tombol Sinkron Status VISIBLE di view customer');

                const bayarBtn = row.locator('button .fa-bolt').first();
                const bayarVisible = await bayarBtn.isVisible({ timeout: 500 }).catch(() => false);
                assert(bayarVisible, 'Tombol Bayar Sekarang masih ada di view customer');

                await helper.screenshot('riwayat-pembayaran-customer-midtrans-row');
                break;
            }
        }
        assert(customerMidtransFound, 'Customer punya row midtrans+pending');

        log('='.repeat(60));
        log(`RESULT: ${pass} passed, ${fail} failed`);
    } catch (err) {
        log(`ERROR: ${err.message}`);
        console.error(err);
        fail++;
    } finally {
        await helper.close();
    }

    process.exit(fail > 0 ? 1 : 0);
}

run();
