/**
 * E2E Test: Field email_verified_at manual override di web Karyawan
 *
 * Flow:
 *  1. Login Karyawan (Siti @ Net Sejahtera)
 *  2. Buka halaman Customer
 *  3. Cari customer → badge "Verified" muncul
 *  4. Set null di DB → refresh → badge "Belum"
 *  5. Buka Edit modal → field "Email Verified" tampil tombol "Tandai Verified"
 *  6. Klik + Update → DB updated ke VERIFIED
 *  7. Bulk action: checklist + "Tandai Verified" → success
 *
 * Permission: karyawan-customer.verify-email
 */

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const BASE = require('../../support/baseUrl.cjs');

// Path dinamis berdasarkan lokasi file test.
// __dirname = tests/Browser/Playwright/Feature/Karyawan/
// Naik 5 level = project root.
const PROJECT_ROOT = path.resolve(__dirname, '..', '..', '..', '..', '..');
const RESULT_DIR = path.join(PROJECT_ROOT, 'tests/Browser/Playwright/result/Karyawan/email-verified-at-karyawan');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

// PHP bootstrap — path pakai PROJECT_ROOT (dinamis dari __dirname)
const BOOTSTRAP = `<?php
require '${PROJECT_ROOT.replace(/\\/g, '\\\\')}\\\\vendor\\\\autoload.php';
$app = require '${PROJECT_ROOT.replace(/\\/g, '\\\\')}\\\\bootstrap\\\\app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
`;
const tmpScript = path.join(PROJECT_ROOT, '.claude', 'tmp_email_verif_karyawan_test.php');
const writeScript = (code) => fs.writeFileSync(tmpScript, BOOTSTRAP + code);

// Safe execSync wrapper
const phpExec = (scriptPath) => {
    try {
        return execSync(`php "${scriptPath}"`, { cwd: PROJECT_ROOT }).toString().trim();
    } catch (e) {
        const stdout = e.stdout ? e.stdout.toString().trim() : '';
        if (stdout) return stdout;
        throw e;
    }
};

async function testAll() {
    const browser = await chromium.launch({ headless: false, slowMo: 200 });
    const ctx = await browser.newContext({ viewport: { width: 1366, height: 900 } });
    const page = await ctx.newPage();
    page.on('pageerror', e => console.log('PAGEERROR:', e.message));
    page.on('console', msg => { if (msg.type() === 'error') console.log('CONSOLE-ERR:', msg.text()); });

    const results = { total: 0, passed: 0, failed: 0, tests: [] };
    const assert = (name, cond, info) => {
        results.total++;
        cond ? results.passed++ : results.failed++;
        results.tests.push({ name, pass: cond, info });
        console.log(`  ${cond ? '✓' : '✗'} ${name}${info ? ' — ' + info : ''}`);
    };

    const KARYAWAN_EMAIL = 'siti@netsejahtera.com';
    const KARYAWAN_PASSWORD = 'password123';
    const COMPANY_NAME = 'PT Net Sejahtera Abadi';

    let testCustomerId = null;
    let testCustomer2Id = null;

    try {
        console.log('=== Email Verified Field - Karyawan ===\n');

        // ============================================
        // 0. Setup
        // ============================================
        writeScript(`
            $c = \\App\\Models\\Company::where('name', '${COMPANY_NAME}')->first();
            echo $c ? $c->id : 'NOT_FOUND';
        `);
        const companyId = phpExec(tmpScript);
        assert('Setup: company_id ditemukan', companyId !== 'NOT_FOUND', `id: ${companyId}`);

        const timestamp = Date.now();
        const email1 = `test+kary${timestamp}@mailinator.com`;
        const email2 = `test+kary2${timestamp}@mailinator.com`;
        const phone1 = `08${String(timestamp + 1).slice(-10)}`;
        const phone2 = `08${String(timestamp + 2).slice(-10)}`;

        writeScript(`
            $c1 = \\App\\Models\\Customer::create([
                'name' => 'Test Kary Verif 1', 'email' => '${email1}',
                'phone_country_code' => '+62', 'phone_number' => '${phone1}',
                'company_id' => '${companyId}', 'password' => bcrypt('Password123!'),
                'is_active' => true, 'email_verified_at' => now(),
            ]);
            $c2 = \\App\\Models\\Customer::create([
                'name' => 'Test Kary Verif 2', 'email' => '${email2}',
                'phone_country_code' => '+62', 'phone_number' => '${phone2}',
                'company_id' => '${companyId}', 'password' => bcrypt('Password123!'),
                'is_active' => true, 'email_verified_at' => now(),
            ]);
            echo $c1->id . '|' . $c2->id;
        `);
        const created = phpExec(tmpScript).split('|');
        testCustomerId = created[0];
        testCustomer2Id = created[1];
        assert('Setup: 2 customer test dibuat', testCustomerId && testCustomer2Id, `ids: ${created.join(', ')}`);

        // ============================================
        // 1. Login Karyawan
        // ============================================
        console.log('\n[1] Login Karyawan');
        await page.waitForTimeout(1000);
        try {
            await page.goto(BASE + '/login-karyawan', { waitUntil: 'domcontentloaded', timeout: 15000 });
        } catch (e) {
            await page.evaluate(() => { window.location.href = '/login-karyawan'; });
            await page.waitForTimeout(2000);
        }
        await page.waitForTimeout(1500);
        await page.screenshot({ path: path.join(RESULT_DIR, '01-login.png'), fullPage: true });

        // Pilih perusahaan (Karyawan login juga butuh company)
        const triggerBtn = page.locator('button:has-text("Cari perusahaan")').first();
        if (await triggerBtn.count() > 0) {
            await triggerBtn.click();
            await page.waitForTimeout(800);
        }
        await page.evaluate(async (companyName) => {
            const input = document.querySelector('input[placeholder*="Cari perusahaan"]');
            if (!input) return false;
            const nativeSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
            nativeSetter.call(input, companyName);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            await new Promise(r => setTimeout(r, 2500));
            const item = document.querySelector('[data-testid^="company-item-"]');
            if (item) { item.click(); return true; }
            return false;
        }, COMPANY_NAME);
        await page.waitForTimeout(500);

        // Isi email + password + turnstile
        await page.fill('input[type="email"]', KARYAWAN_EMAIL);
        await page.fill('input[type="password"]', KARYAWAN_PASSWORD);
        await page.evaluate(() => {
            if (typeof window.onLoginTurnstileSuccess === 'function') {
                window.onLoginTurnstileSuccess('XXXX.DUMMY.TOKEN.XXXX');
            } else if (typeof window.onTurnstileSuccess === 'function') {
                window.onTurnstileSuccess('XXXX.DUMMY.TOKEN.XXXX');
            }
        });
        await page.waitForTimeout(500);
        await page.click('button[type="submit"]');
        try {
            await page.waitForURL(/\/karyawan\/dashboard/, { timeout: 8000 });
        } catch (e) {}
        await page.waitForTimeout(1500);
        await page.screenshot({ path: path.join(RESULT_DIR, '02-dashboard.png'), fullPage: true });
        assert('Login Karyawan SUKSES', page.url().includes('/karyawan/dashboard'), `URL: ${page.url()}`);

        // ============================================
        // 2. Buka halaman Customer
        // ============================================
        console.log('\n[2] Buka halaman Customer');
        await page.waitForTimeout(1000);
        try {
            await page.goto(BASE + '/karyawan/customer', { waitUntil: 'domcontentloaded', timeout: 15000 });
        } catch (e) {
            await page.evaluate(() => { window.location.href = '/karyawan/customer'; });
            await page.waitForTimeout(3000);
        }
        await page.waitForTimeout(2000);
        await page.screenshot({ path: path.join(RESULT_DIR, '03-customer-list.png'), fullPage: true });
        assert('Halaman customer terbuka', page.url().includes('/karyawan/customer'), `URL: ${page.url()}`);

        // ============================================
        // 3. Cari customer → badge "Verified" muncul
        // ============================================
        console.log('\n[3] Cari customer + badge Verified');
        await page.locator('input[placeholder*="Cari nama"]').first().fill(email1);
        await page.locator('button:has-text("Filter")').first().click();
        await page.waitForTimeout(2000);
        const verifiedCount = await page.locator('span:has-text("Verified")').count();
        assert('Badge "Verified" muncul untuk customer verified', verifiedCount >= 1, `count: ${verifiedCount}`);
        await page.screenshot({ path: path.join(RESULT_DIR, '04-verified-badge.png'), fullPage: true });

        // ============================================
        // 4. Set null di DB + refresh
        // ============================================
        console.log('\n[4] Set null di DB + refresh');
        writeScript(`
            $c = \\App\\Models\\Customer::find('${testCustomerId}');
            $c->email_verified_at = null;
            $c->save();
            echo $c->email_verified_at ? 'STILL_VERIFIED' : 'NULL';
        `);
        const setNull = phpExec(tmpScript);
        assert('Set email_verified_at = null di DB', setNull === 'NULL', `result: ${setNull}`);

        try {
            await page.goto(BASE + '/karyawan/customer?refresh=1', { waitUntil: 'domcontentloaded', timeout: 15000 });
        } catch (e) {
            await page.evaluate(() => { window.location.href = '/karyawan/customer?refresh=1'; });
            await page.waitForTimeout(3000);
        }
        await page.waitForTimeout(2000);
        await page.locator('input[placeholder*="Cari nama"]').first().fill(email1);
        await page.locator('button:has-text("Filter")').first().click();
        await page.waitForTimeout(2000);
        const belumCount = await page.locator('span:has-text("Belum")').count();
        assert('Badge "Belum" muncul setelah set null', belumCount >= 1, `count: ${belumCount}`);
        await page.screenshot({ path: path.join(RESULT_DIR, '05-belum-badge.png'), fullPage: true });

        // ============================================
        // 5. Buka Edit modal → tombol Tandai Verified
        // ============================================
        console.log('\n[5] Buka Edit modal');
        await page.locator('button[title="Edit"]').first().click();
        await page.waitForTimeout(1500);
        await page.screenshot({ path: path.join(RESULT_DIR, '06-edit-modal.png'), fullPage: true });

        const tandaiBtn = page.locator('.modal-scroll button:has-text("Tandai Verified")');
        const tandaiCount = await tandaiBtn.count();
        assert('Tombol "Tandai Verified" muncul di edit modal', tandaiCount > 0, `count: ${tandaiCount}`);

        // ============================================
        // 6. Klik Tandai Verified + Update
        // ============================================
        console.log('\n[6] Klik Tandai Verified + Update');
        if (tandaiCount > 0) {
            await tandaiBtn.first().click();
            await page.waitForTimeout(500);
            await page.screenshot({ path: path.join(RESULT_DIR, '07-tandai-clicked.png'), fullPage: true });
            // Update button - pakai selector yang visible + type=submit
            await page.locator('button[type="submit"]:has-text("Update"):visible').first().click();
            await page.waitForTimeout(3000);
            await page.screenshot({ path: path.join(RESULT_DIR, '08-after-update.png'), fullPage: true });

            writeScript(`
                $c = \\App\\Models\\Customer::find('${testCustomerId}');
                echo $c->email_verified_at ? 'VERIFIED' : 'NULL';
            `);
            const afterUpdate = phpExec(tmpScript);
            assert('Setelah klik Tandai Verified + Update, DB = VERIFIED', afterUpdate === 'VERIFIED', `result: ${afterUpdate}`);
        }

        // ============================================
        // 7. Refresh table → badge "Verified" muncul
        // ============================================
        console.log('\n[7] Refresh table + badge Verified');
        try {
            await page.goto(BASE + '/karyawan/customer?refresh=2', { waitUntil: 'domcontentloaded', timeout: 15000 });
        } catch (e) {
            await page.evaluate(() => { window.location.href = '/karyawan/customer?refresh=2'; });
            await page.waitForTimeout(3000);
        }
        await page.waitForTimeout(2000);
        await page.locator('input[placeholder*="Cari nama"]').first().fill(email1);
        await page.locator('button:has-text("Filter")').first().click();
        await page.waitForTimeout(2000);
        const verifiedAfterCount = await page.locator('span:has-text("Verified")').count();
        assert('Badge "Verified" muncul setelah update', verifiedAfterCount >= 1, `count: ${verifiedAfterCount}`);
        await page.screenshot({ path: path.join(RESULT_DIR, '09-table-verified.png'), fullPage: true });

        // ============================================
        // 8. Test bulk action
        // ============================================
        console.log('\n[8] Bulk action: Tandai Verified');
        writeScript(`
            \\App\\Models\\Customer::whereIn('id', ['${testCustomerId}', '${testCustomer2Id}'])
                ->update(['email_verified_at' => null]);
            echo 'OK';
        `);
        phpExec(tmpScript);
        try {
            await page.goto(BASE + '/karyawan/customer?refresh=3', { waitUntil: 'domcontentloaded', timeout: 15000 });
        } catch (e) {
            await page.evaluate(() => { window.location.href = '/karyawan/customer?refresh=3'; });
            await page.waitForTimeout(3000);
        }
        await page.waitForTimeout(2000);
        await page.locator('input[placeholder*="Cari nama"]').first().fill('');
        await page.waitForTimeout(500);
        await page.locator('input[placeholder*="Cari nama"]').first().fill('test+kary');
        await page.waitForTimeout(2000);
        await page.screenshot({ path: path.join(RESULT_DIR, '10-bulk-pre.png'), fullPage: true });

        // Header checkbox
        const headerCheckbox = page.locator('thead input[type="checkbox"]').first();
        if (await headerCheckbox.count() > 0) {
            await headerCheckbox.click();
            await page.waitForTimeout(500);
        }
        await page.screenshot({ path: path.join(RESULT_DIR, '11-bulk-selected.png'), fullPage: true });

        const bulkTandaiBtn = page.locator('button:has-text("Tandai Verified")');
        const bulkTandaiCount = await bulkTandaiBtn.count();
        assert('Tombol bulk "Tandai Verified" muncul', bulkTandaiCount > 0, `count: ${bulkTandaiCount}`);
        if (bulkTandaiCount > 0) {
            await bulkTandaiBtn.first().click();
            await page.waitForTimeout(3000);
            await page.screenshot({ path: path.join(RESULT_DIR, '12-bulk-after.png'), fullPage: true });

            writeScript(`
                $count = \\App\\Models\\Customer::whereIn('id', ['${testCustomerId}', '${testCustomer2Id}'])
                    ->whereNotNull('email_verified_at')
                    ->count();
                echo $count;
            `);
            const verifiedCount2 = phpExec(tmpScript);
            assert('Bulk verify: 2 customer verified', verifiedCount2 === '2', `count: ${verifiedCount2}`);
        }

        console.log('\n==================================================');
        console.log(`Email Verified Field - Karyawan: ${results.passed}/${results.total} pass`);
        if (results.failed > 0) {
            console.log('Failed tests:');
            results.tests.filter(t => !t.pass).forEach(t => console.log(`  ✗ ${t.name} — ${t.info}`));
        }
        console.log('==================================================');

    } catch (e) {
        console.log('FATAL:', e.message);
        await page.screenshot({ path: path.join(RESULT_DIR, 'fatal.png'), fullPage: true });
    } finally {
        if (testCustomerId || testCustomer2Id) {
            writeScript(`
                $ids = array_filter(['${testCustomerId}', '${testCustomer2Id}']);
                \\App\\Models\\Customer::whereIn('id', $ids)->forceDelete();
                echo 'cleaned';
            `);
            try { phpExec(tmpScript); } catch (e) {}
        }
        try { fs.unlinkSync(tmpScript); } catch (e) {}
        await browser.close();
    }
    return results;
}

testAll().then(r => {
    process.exit(r.failed > 0 ? 1 : 0);
}).catch(e => {
    console.error('UNCAUGHT:', e);
    process.exit(1);
});
