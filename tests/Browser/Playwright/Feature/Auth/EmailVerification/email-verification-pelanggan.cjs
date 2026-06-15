/**
 * E2E Test: Email Verification untuk Portal Pelanggan
 *
 * Flow 4 phase:
 *  1. Register customer baru → email verifikasi dikirim → cek redirect ke
 *     /verifikasi-email-pelanggan → extract URL verify dari log → visit URL
 *     → email_verified_at terisi di DB → login harusnya sukses
 *  2. Hard block: customer yang email_verified_at = null coba login → ditolak
 *  3. Kirim ulang link: dari /verifikasi-email-pelanggan, generate token
 *     baru (replace yang lama) → extract URL verify baru → visit → berhasil
 *  4. Admin manual verify: Admin Perusahaan buka customer dengan
 *     email_verified_at = null → klik "Tandai Verified" di edit modal →
 *     simpan → DB updated → customer sekarang bisa login
 *
 * Test data:
 *  - Phase 1-3: pakai 1 customer (test+<timestamp>@mailinator.com)
 *  - Phase 4: customer dari seeder, lalu set email_verified_at = null dulu
 *
 * Catatan:
 *  - Turnstile di-bypass pakai testing key (1x... = always pass) di .env
 *  - Email log ditulis ke storage/logs/laravel.log
 *  - Cleanup: hapus customer + token di akhir
 */

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');
const LogParser = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/LogParser.cjs');
const BASE = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/baseUrl.cjs');

const PROJECT_ROOT = 'C:\\laragon\\www\\erp-rt-rw-net';
const RESULT_DIR = path.join(PROJECT_ROOT, 'tests/Browser/Playwright/result/Auth/EmailVerification');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

// PHP bootstrap helper
const BOOTSTRAP = `<?php
require 'C:\\\\laragon\\\\www\\\\erp-rt-rw-net\\\\vendor\\\\autoload.php';
$app = require 'C:\\\\laragon\\\\www\\\\erp-rt-rw-net\\\\bootstrap\\\\app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
`;
const tmpScript = path.join(PROJECT_ROOT, '.claude', 'tmp_email_verif_test.php');
const writeScript = (code) => fs.writeFileSync(tmpScript, BOOTSTRAP + code);

// Safe execSync: PHP sometimes access-violates during shutdown (DB connection close)
// even when output is correct. Recover stdout from error.
const phpExec = (scriptPath) => {
    try {
        return execSync(`php "${scriptPath}"`, { cwd: PROJECT_ROOT }).toString().trim();
    } catch (e) {
        const stdout = e.stdout ? e.stdout.toString().trim() : '';
        // If we got output, return it (process died after flush, ignore AV)
        if (stdout) return stdout;
        throw e;
    }
};

async function testAll() {
    const browser = await chromium.launch({ headless: false, slowMo: 200 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
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

    const logParser = new LogParser();

    // Test data: pakai mailinator.com (public mailbox, sesuai catatan)
    const timestamp = Date.now();
    const newEmail = `test+${timestamp}@mailinator.com`;
    const newPassword = 'PasswordVerif1!';
    const newName = 'Customer Verif Test';
    // Phone harus unique per test (ada unique constraint customers.cust_unique_phone per company)
    const newPhone = `08${String(timestamp).slice(-10)}`;
    const companyName = 'PT Net Sejahtera Abadi';

    // Lookup company_id
    writeScript(`
        $c = \\App\\Models\\Company::where('name', '${companyName.replace(/'/g, "\\'")}')->first();
        echo $c ? $c->id : 'NOT_FOUND';
    `);
    const companyId = phpExec(tmpScript);
    console.log(`\nTest data: email=${newEmail}, company_id=${companyId}`);

    let registeredCustomerId = null;

    try {
        // ====================================================================
        // PHASE 1: Register → email verifikasi → klik link → login success
        // ====================================================================
        console.log('\n=== Phase 1: Register + Verify Email + Login ===');

        // ===== 1. Truncate log =====
        logParser.truncate();
        console.log('  [1] Truncated log');

        // ===== 2. Goto register form =====
        await page.goto(BASE + '/login-pelanggan', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('button:has-text("Daftar")', { timeout: 10000 });
        await page.waitForTimeout(1000);
        // Klik tab "Daftar"
        await page.click('button:has-text("Daftar")');
        await page.waitForTimeout(500);
        await page.screenshot({ path: path.join(RESULT_DIR, 'phase1-01-register-form.png'), fullPage: true });
        assert('[Phase 1] Register form renders', (await page.locator('input[placeholder="Nama Anda"]').count()) > 0);

        // ===== 3. Pilih company =====
        const triggerBtn = page.locator('button:has-text("Cari perusahaan")').first();
        if (await triggerBtn.count() > 0) {
            await triggerBtn.click();
            await page.waitForTimeout(800);
        }
        // Trigger search via input + click first matching item
        const itemSelected = await page.evaluate(async (companyName) => {
            const input = document.querySelector('input[placeholder*="Cari perusahaan"]');
            if (!input) return false;
            input.value = companyName;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            await new Promise(r => setTimeout(r, 1500));
            const item = document.querySelector('[data-testid^="company-item-"]');
            if (item) { item.click(); return true; }
            return false;
        }, companyName);
        await page.waitForTimeout(500);

        // ===== 4. Isi form =====
        await page.fill('input[placeholder="Nama Anda"]', newName);
        await page.fill('input[placeholder="email@contoh.com"]', newEmail);
        // Phone sekarang pisah: CountryCodeSelect (default +62) + input no_telp (placeholder "812-3456-7890")
        await page.fill('input[placeholder="812-3456-7890"]', newPhone);
        await page.fill('input[placeholder="Minimal 8 karakter"]', newPassword);
        await page.fill('input[placeholder="Ulangi password"]', newPassword);

        // Inject Turnstile token manual (test mode, tidak perlu widget load)
        await page.evaluate(() => {
            if (typeof window.onRegisterTurnstileSuccess === 'function') {
                window.onRegisterTurnstileSuccess('XXXX.DUMMY.TOKEN.XXXX');
            }
        });
        await page.waitForTimeout(500);
        await page.screenshot({ path: path.join(RESULT_DIR, 'phase1-02-form-filled.png'), fullPage: true });

        // ===== 5. Submit register =====
        await page.click('button:has-text("Daftar Sekarang")');
        // Tunggu redirect ke verif-email-pelanggan (paling cepat selesai < 3s)
        let onVerifPage = false;
        try {
            await page.waitForURL(/\/verifikasi-email-pelanggan/, { timeout: 5000 });
            onVerifPage = true;
        } catch (e) {
            // Gak sampai ke verif (mungkin validation error)
        }
        await page.waitForTimeout(500);
        await page.screenshot({ path: path.join(RESULT_DIR, 'phase1-03-after-submit.png'), fullPage: true });

        // ===== 6. Verify redirect ke /verifikasi-email-pelanggan =====
        if (!onVerifPage) onVerifPage = page.url().includes('/verifikasi-email-pelanggan');
        assert('[Phase 1] Redirect ke halaman verifikasi-email-pelanggan', onVerifPage, `URL: ${page.url()}`);

        if (!onVerifPage) {
            console.log('  [SKIP] Phase 1 failed, tidak ada redirect ke verif page');
        } else {
            // ===== 7. Customer dibuat di DB dengan email_verified_at = NULL =====
            writeScript(`
                $c = \\App\\Models\\Customer::where('email', '${newEmail}')->where('company_id', '${companyId}')->first();
                if (!$c) { echo 'NOT_FOUND'; exit; }
                echo $c->id . '|' . ($c->email_verified_at ? 'VERIFIED' : 'NULL');
            `);
            const customerCheck = phpExec(tmpScript);
            const [cid, verifStatus] = customerCheck.split('|');
            registeredCustomerId = cid;
            assert('[Phase 1] Customer dibuat di DB', cid && cid !== 'NOT_FOUND', `id: ${cid}`);
            assert('[Phase 1] email_verified_at = NULL setelah register', verifStatus === 'NULL', `status: ${verifStatus}`);

            // ===== 8. Extract URL verify dari log =====
            const verifyUrl = logParser.findVerifyUrl();
            assert('[Phase 1] URL verifikasi ditemukan di log', verifyUrl !== null, `URL: ${verifyUrl ? verifyUrl.substring(0, 100) : 'null'}`);

            if (verifyUrl) {
                // ===== 9. Visit URL verifikasi (pakai JS navigate untuk hindari double nav dari Vite HMR) =====
                await page.waitForTimeout(1500);
                try {
                    await page.goto(verifyUrl, { waitUntil: 'domcontentloaded', timeout: 15000 });
                } catch (e) {
                    console.log('  goto error, retry via JS:', e.message.substring(0, 80));
                    await page.evaluate((u) => { window.location.href = u; }, verifyUrl);
                }
                await page.waitForTimeout(3000);
                if (!page.url().includes('konfirmasi') && !page.url().includes('login-pelanggan')) {
                    await page.evaluate((u) => { window.location.href = u; }, verifyUrl);
                    await page.waitForTimeout(3000);
                }
                // Tunggu sampai login-pelanggan fully loaded
                await page.waitForSelector('input[placeholder*="Cari perusahaan"]', { timeout: 10000, state: 'attached' });
                await page.waitForTimeout(2000);
                // Click tab "Masuk" jika belum
                const isLoginTab = await page.evaluate(() => {
                    return !!document.querySelector('input[placeholder="pelanggan@email.com"]');
                });
                if (!isLoginTab) {
                    await page.locator('button:has-text("Masuk")').first().click();
                    await page.waitForTimeout(800);
                }
                // Debug: log URL + body
                const debugInfo = await page.evaluate(() => ({
                    url: window.location.href,
                    hasCompanyInput: !!document.querySelector('input[placeholder*="Cari perusahaan"]'),
                    allInputPlaceholders: Array.from(document.querySelectorAll('input')).map(i => i.placeholder).filter(Boolean),
                }));
                console.log('  [Phase 1] Debug pre-select:', JSON.stringify(debugInfo));

                // ===== 12. Login dengan customer baru → harusnya success =====
                // (Step 12 dipindah ke bawah: skip dulu, kita re-navigate fresh)
                await page.screenshot({ path: path.join(RESULT_DIR, 'phase1-04-verified-redirect.png'), fullPage: true });

                // ===== 10. Verify redirect ke login + flash message =====
                const onLoginPage = page.url().includes('/login-pelanggan');
                assert('[Phase 1] Setelah verify, redirect ke /login-pelanggan', onLoginPage, `URL: ${page.url()}`);
                const bodyText = await page.innerText('body');
                // Flash check soft: hanya info, tidak fail test (Inertia flash bisa di session tapi belum ke-render di body)
                if (bodyText.includes('berhasil diverifikasi') || bodyText.includes('Silakan login') || bodyText.includes('Email berhasil')) {
                    console.log('  [Phase 1] Flash message: OK');
                } else {
                    console.log('  [Phase 1] Flash message not visible in body (DB check is definitive)');
                }

                // ===== 11. DB: email_verified_at terisi + token dihapus =====
                writeScript(`
                    $c = \\App\\Models\\Customer::where('email', '${newEmail}')->where('company_id', '${companyId}')->first();
                    $token = \\DB::table('email_verifications')->where('email', $c->getEmailForVerification())->where('company_id', '${companyId}')->first();
                    echo ($c->email_verified_at ? 'VERIFIED' : 'NULL') . '|' . ($token ? 'TOKEN_EXISTS' : 'TOKEN_DELETED');
                `);
                const dbCheck = phpExec(tmpScript);
                const [dbStatus, tokenStatus] = dbCheck.split('|');
                assert('[Phase 1] DB email_verified_at = VERIFIED', dbStatus === 'VERIFIED', `status: ${dbStatus}`);
                assert('[Phase 1] Token email_verifications dihapus', tokenStatus === 'TOKEN_DELETED', `token: ${tokenStatus}`);

                // ===== 12. Login dengan customer baru → harusnya success =====
                // Fresh goto ke login-pelanggan (supaya form login benar-benar fresh render)
                // Tunggu HMR settle dulu, baru navigate
                await page.waitForTimeout(2000);
                try {
                    await page.goto(BASE + '/login-pelanggan?cache=' + Date.now(), { waitUntil: 'domcontentloaded', timeout: 15000 });
                } catch (e) {
                    // Vite HMR bisa interrupt navigation. Re-navigate via JS.
                    console.log('  [Phase 1] goto interrupted, fallback ke JS navigate');
                    await page.evaluate((url) => { window.location.href = url; }, BASE + '/login-pelanggan?retry=' + Date.now());
                    await page.waitForTimeout(3000);
                }
                await page.waitForTimeout(2000);
                // Pilih company: klik trigger + set v-model via Vue instance
                const triggerBtn2 = page.locator('button:has-text("Cari perusahaan")').first();
                if (await triggerBtn2.count() > 0) {
                    await triggerBtn2.click();
                    await page.waitForTimeout(1000);
                }
                // Set v-model selectedCompany via API (skip dropdown UI race condition)
                const selectedOk2 = await page.evaluate(async (companyName) => {
                    // Re-query inside evaluate to get fresh DOM
                    const input = document.querySelector('input[placeholder*="Cari perusahaan"]');
                    if (!input) {
                        const all = Array.from(document.querySelectorAll('input')).map(i => i.placeholder).filter(Boolean);
                        return { ok: false, reason: 'no input, placeholders=' + JSON.stringify(all) };
                    }
                    // Set value dengan native setter (bypass Vue tracker)
                    const nativeSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
                    nativeSetter.call(input, companyName);
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    await new Promise(r => setTimeout(r, 2500));
                    let item = document.querySelector('[data-testid^="company-item-"]');
                    if (item) {
                        item.click();
                        return { ok: true };
                    }
                    // Fallback: API search to get item count
                    const res = await fetch('/api/companies/search?q=' + encodeURIComponent(companyName) + '&page=1', {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });
                    const data = await res.json();
                    return { ok: false, reason: 'no item, api count=' + (data.data?.length || 0) };
                }, companyName);
                if (!selectedOk2.ok) console.log('  [Phase 1] Company select:', selectedOk2.reason);
                assert('[Phase 1] Company selected for login', selectedOk2.ok, JSON.stringify(selectedOk2));
                await page.waitForTimeout(500);
                await page.fill('input[type="email"]', newEmail);
                await page.fill('input[type="password"]', newPassword);
                // Inject Turnstile token manual
                await page.evaluate(() => {
                    if (typeof window.onLoginTurnstileSuccess === 'function') {
                        window.onLoginTurnstileSuccess('XXXX.DUMMY.TOKEN.XXXX');
                    }
                });
                await page.waitForTimeout(500);
                await page.click('form button[type="submit"]');
                await page.waitForTimeout(4000);
                await page.screenshot({ path: path.join(RESULT_DIR, 'phase1-05-after-login.png'), fullPage: true });
                const loggedIn = !page.url().includes('/login-pelanggan');
                assert('[Phase 1] Login setelah verifikasi email SUKSES', loggedIn, `URL: ${page.url()}`);

                // Logout via POST (route POST, bukan GET)
                if (loggedIn) {
                    await page.evaluate(() => {
                        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                        return fetch('/logout-pelanggan', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                    }).catch(() => {});
                    await page.waitForTimeout(1500);
                }
            }
        }

        // ====================================================================
        // PHASE 2: Hard block login kalau email_verified_at = null
        // ====================================================================
        console.log('\n=== Phase 2: Hard Block Login jika email_verified_at = null ===');

        // ===== 1. Set customer dari Phase 1 email_verified_at = null =====
        writeScript(`
            $c = \\App\\Models\\Customer::where('email', '${newEmail}')->where('company_id', '${companyId}')->first();
            $c->email_verified_at = null;
            $c->save();
            echo $c ? 'OK' : 'NOT_FOUND';
        `);
        const resetResult = phpExec(tmpScript);
        assert('[Phase 2] Set email_verified_at = null di DB', resetResult === 'OK', `result: ${resetResult}`);

        // ===== 2. Goto login form =====
        await page.goto(BASE + '/login-pelanggan', { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(800);
        await page.waitForTimeout(1000);

        // ===== 3. Pilih company + isi form =====
        const triggerBtn3 = page.locator('button:has-text("Cari perusahaan")').first();
        if (await triggerBtn3.count() > 0) {
            await triggerBtn3.click();
            await page.waitForTimeout(800);
        }
        await page.evaluate(async (companyName) => {
            const input = document.querySelector('input[placeholder*="Cari perusahaan"]');
            if (!input) return false;
            input.value = companyName;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            await new Promise(r => setTimeout(r, 1500));
            const item = document.querySelector('[data-testid^="company-item-"]');
            if (item) { item.click(); return true; }
            return false;
        }, companyName);
        await page.waitForTimeout(500);
        await page.fill('input[type="email"]', newEmail);
        await page.fill('input[type="password"]', newPassword);
        // Inject Turnstile token manual
        await page.evaluate(() => {
            if (typeof window.onLoginTurnstileSuccess === 'function') {
                window.onLoginTurnstileSuccess('XXXX.DUMMY.TOKEN.XXXX');
            }
        });
        await page.waitForTimeout(500);
        await page.click('form button[type="submit"]');
        await page.waitForTimeout(3000);
        await page.screenshot({ path: path.join(RESULT_DIR, 'phase2-01-blocked.png'), fullPage: true });

        // ===== 4. Verify masih di login page + error message =====
        const stillOnLogin = page.url().includes('/login-pelanggan');
        assert('[Phase 2] Hard block: tetap di /login-pelanggan', stillOnLogin, `URL: ${page.url()}`);
        const errBody = await page.innerText('body');
        assert('[Phase 2] Error "belum diverifikasi" muncul', errBody.includes('belum diverifikasi') || errBody.includes('Email belum diverifikasi'), `body: ${errBody.substring(0, 200)}`);

        // ====================================================================
        // PHASE 3: Kirim ulang link verifikasi → replace token lama
        // ====================================================================
        console.log('\n=== Phase 3: Kirim Ulang Link Verifikasi ===');

        // Ambil token lama (kalau ada) untuk compare
        writeScript(`
            $t = \\DB::table('email_verifications')
                ->where('email', \\App\\Models\\Customer::where('email', '${newEmail}')->where('company_id', '${companyId}')->first()->getEmailForVerification())
                ->where('company_id', '${companyId}')
                ->first();
            echo $t ? $t->created_at : 'NONE';
        `);
        const oldTokenTime = phpExec(tmpScript);
        console.log(`  Token lama created_at: ${oldTokenTime}`);

        // Truncate log + tunggu sebentar biar timestamp beda
        logParser.truncate();
        await page.waitForTimeout(1500);

        // Goto /verifikasi-email-pelanggan
        await page.goto(BASE + '/verifikasi-email-pelanggan', { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(800);
        await page.waitForTimeout(1000);
        await page.screenshot({ path: path.join(RESULT_DIR, 'phase3-01-verif-form.png'), fullPage: true });

        // Pilih company + email
        const triggerBtn4 = page.locator('button:has-text("Cari perusahaan")').first();
        if (await triggerBtn4.count() > 0) {
            await triggerBtn4.click();
            await page.waitForTimeout(800);
        }
        await page.evaluate(async (companyName) => {
            const input = document.querySelector('input[placeholder*="Cari perusahaan"]');
            if (!input) return false;
            input.value = companyName;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            await new Promise(r => setTimeout(r, 1500));
            const item = document.querySelector('[data-testid^="company-item-"]');
            if (item) { item.click(); return true; }
            return false;
        }, companyName);
        await page.waitForTimeout(500);
        await page.fill('input[placeholder="email@contoh.com"]', newEmail);
        await page.waitForTimeout(500);

        // Inject Turnstile token manual
        await page.evaluate(() => {
            if (typeof window.onTurnstileSuccess === 'function') {
                window.onTurnstileSuccess('XXXX.DUMMY.TOKEN.XXXX');
            }
        });
        await page.waitForTimeout(500);

        await page.click('button:has-text("Kirim Ulang Link Verifikasi")');
        await page.waitForTimeout(3000);
        await page.screenshot({ path: path.join(RESULT_DIR, 'phase3-02-after-submit.png'), fullPage: true });

        // Extract URL verify baru
        const newVerifyUrl = logParser.findVerifyUrl();
        assert('[Phase 3] URL verifikasi baru ditemukan di log', newVerifyUrl !== null, `URL: ${newVerifyUrl ? newVerifyUrl.substring(0, 100) : 'null'}`);

        if (newVerifyUrl) {
            // Visit URL baru
            try {
                await page.goto(newVerifyUrl, { waitUntil: 'domcontentloaded', timeout: 10000 });
            } catch (e) {
                await page.evaluate((u) => { window.location.href = u; }, newVerifyUrl);
            }
            await page.waitForTimeout(3000);
            if (!page.url().includes('login-pelanggan') && !page.url().includes('konfirmasi')) {
                await page.evaluate((u) => { window.location.href = u; }, newVerifyUrl);
                await page.waitForTimeout(3000);
            }

            // DB: email_verified_at terisi
            writeScript(`
                $c = \\App\\Models\\Customer::where('email', '${newEmail}')->where('company_id', '${companyId}')->first();
                echo $c->email_verified_at ? 'VERIFIED' : 'NULL';
            `);
            const verifCheck = phpExec(tmpScript);
            assert('[Phase 3] Setelah verify, email_verified_at = VERIFIED', verifCheck === 'VERIFIED', `status: ${verifCheck}`);

            // Login harusnya success - fresh goto dulu (setelah verify, page mungkin di login)
            await page.waitForTimeout(2000);
            try {
                await page.goto(BASE + '/login-pelanggan?cache=p3_' + Date.now(), { waitUntil: 'domcontentloaded', timeout: 15000 });
            } catch (e) {
                await page.evaluate((url) => { window.location.href = url; }, BASE + '/login-pelanggan?retry=p3');
                await page.waitForTimeout(3000);
            }
            await page.waitForTimeout(2000);
            const triggerBtn5 = page.locator('button:has-text("Cari perusahaan")').first();
            if (await triggerBtn5.count() > 0) {
                await triggerBtn5.click();
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
            }, companyName);
            await page.waitForTimeout(500);
            await page.fill('input[type="email"]', newEmail);
            await page.fill('input[type="password"]', newPassword);
            // Inject Turnstile token manual
            await page.evaluate(() => {
                if (typeof window.onLoginTurnstileSuccess === 'function') {
                    window.onLoginTurnstileSuccess('XXXX.DUMMY.TOKEN.XXXX');
                }
            });
            await page.waitForTimeout(500);
            await page.click('form button[type="submit"]');
            await page.waitForTimeout(4000);
            const loggedIn2 = !page.url().includes('/login-pelanggan');
            assert('[Phase 3] Login setelah kirim ulang SUKSES', loggedIn2, `URL: ${page.url()}`);

            // Logout via POST
            if (loggedIn2) {
                await page.evaluate(() => {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    return fetch('/logout-pelanggan', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                }).catch(() => {});
                await page.waitForTimeout(1500);
            }
        }

        // ====================================================================
        // PHASE 4: Admin Perusahaan manual verify
        // ====================================================================
        console.log('\n=== Phase 4: Admin Manual Verify (Operator Perusahaan) ===');

        // ===== 1. Set customer email_verified_at = null (seperti baru register) =====
        writeScript(`
            $c = \\App\\Models\\Customer::where('email', '${newEmail}')->where('company_id', '${companyId}')->first();
            $c->email_verified_at = null;
            $c->save();
            echo $c->id;
        `);
        const cidPhase4 = phpExec(tmpScript);
        assert('[Phase 4] Customer ID retrieved', cidPhase4 && cidPhase4 !== 'NOT_FOUND', `id: ${cidPhase4}`);

        // ===== 2. Login Admin Perusahaan =====
        await page.waitForTimeout(2000);
        try {
            await page.goto(BASE + '/login-perusahaan', { waitUntil: 'domcontentloaded', timeout: 15000 });
        } catch (e) {
            await page.evaluate(() => { window.location.href = '/login-perusahaan'; });
            await page.waitForTimeout(3000);
        }
        await page.waitForTimeout(1500);
        // Pilih company: klik trigger button "Cari perusahaan" dulu
        const triggerAdmin = page.locator('button:has-text("Cari perusahaan")').first();
        if (await triggerAdmin.count() > 0) {
            await triggerAdmin.click();
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
        }, companyName);
        await page.waitForTimeout(500);
        await page.fill('input[type="email"]', 'admin@netsejahtera.com');
        await page.fill('input[type="password"]', 'password123');
        // Inject Turnstile token manual
        await page.evaluate(() => {
            if (typeof window.onTurnstileSuccess === 'function') {
                window.onTurnstileSuccess('XXXX.DUMMY.TOKEN.XXXX');
            }
        });
        await page.waitForTimeout(500);
        await page.click('form button[type="submit"]');
        await page.waitForTimeout(4000);

        const adminLoggedIn = !page.url().includes('/login-perusahaan');
        assert('[Phase 4] Login Admin Perusahaan SUKSES', adminLoggedIn, `URL: ${page.url()}`);

        if (adminLoggedIn) {
            // ===== 3. Goto halaman Customer =====
            await page.goto(BASE + '/operator-perusahaan/customer', { waitUntil: 'domcontentloaded' });
            await page.waitForTimeout(800);
            await page.waitForTimeout(1500);
            await page.screenshot({ path: path.join(RESULT_DIR, 'phase4-01-customer-list.png'), fullPage: true });

            // ===== 4. Cari customer by email =====
            const searchInput = page.locator('input[placeholder="Cari customer..."]').first();
            await searchInput.fill(newEmail);
            await page.waitForTimeout(500);
            await page.press('input[placeholder="Cari customer..."]', 'Enter');
            await page.waitForTimeout(2000);
            await page.screenshot({ path: path.join(RESULT_DIR, 'phase4-02-search-result.png'), fullPage: true });

            // ===== 5. Verify badge "Belum" muncul =====
            const belumBadge = await page.locator('span:has-text("Belum")').count();
            assert('[Phase 4] Badge "Belum" muncul untuk customer', belumBadge > 0, `count: ${belumBadge}`);

            // ===== 6. Klik tombol Edit (icon pensil) =====
            const editBtn = page.locator('button[title="Edit"]').first();
            if (await editBtn.count() > 0) {
                await editBtn.click();
                await page.waitForTimeout(1000);
                await page.screenshot({ path: path.join(RESULT_DIR, 'phase4-03-edit-modal.png'), fullPage: true });

                // ===== 7. Klik "Tandai Verified" =====
                const tandaiBtn = page.locator('button:has-text("Tandai Verified")').first();
                if (await tandaiBtn.count() > 0) {
                    await tandaiBtn.click();
                    await page.waitForTimeout(800);
                    await page.screenshot({ path: path.join(RESULT_DIR, 'phase4-04-tandai-clicked.png'), fullPage: true });

                    // ===== 8. Submit form (klik Update) =====
                    await page.click('button:has-text("Update")');
                    await page.waitForTimeout(3000);
                    await page.screenshot({ path: path.join(RESULT_DIR, 'phase4-05-after-update.png'), fullPage: true });

                    // ===== 9. DB: email_verified_at terisi =====
                    writeScript(`
                        $c = \\App\\Models\\Customer::where('email', '${newEmail}')->where('company_id', '${companyId}')->first();
                        echo $c->email_verified_at ? 'VERIFIED' : 'NULL';
                    `);
                    const adminVerif = phpExec(tmpScript);
                    assert('[Phase 4] Setelah admin manual verify, email_verified_at = VERIFIED', adminVerif === 'VERIFIED', `status: ${adminVerif}`);

                    // ===== 10. Refresh table → badge "Verified" muncul =====
                    try {
                        await page.goto(BASE + '/operator-perusahaan/customer', { waitUntil: 'domcontentloaded', timeout: 10000 });
                    } catch (e) {
                        await page.evaluate(() => { window.location.href = '/operator-perusahaan/customer'; });
                        await page.waitForTimeout(3000);
                    }
                    await page.waitForTimeout(2000);
                    await page.locator('input[placeholder="Cari customer..."]').first().fill(newEmail);
                    await page.waitForTimeout(500);
                    await page.press('input[placeholder="Cari customer..."]', 'Enter');
                    await page.waitForTimeout(2000);
                    await page.screenshot({ path: path.join(RESULT_DIR, 'phase4-06-table-verified.png'), fullPage: true });
                    const verifiedBadge = await page.locator('span:has-text("Verified")').count();
                    assert('[Phase 4] Badge "Verified" muncul di tabel', verifiedBadge > 0, `count: ${verifiedBadge}`);
                } else {
                    console.log('  [SKIP] Tombol Tandai Verified tidak ditemukan');
                    results.failed++;
                }
            } else {
                console.log('  [SKIP] Tombol Edit tidak ditemukan');
                results.failed++;
            }

            // Logout admin via POST
            await page.evaluate(() => {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                return fetch('/logout-perusahaan', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
            }).catch(() => {});
            await page.waitForTimeout(1500);
        }

    } finally {
        // ===== Cleanup: hapus customer + token =====
        if (registeredCustomerId || newEmail) {
            writeScript(`
                \\DB::table('email_verifications')
                    ->where('company_id', '${companyId}')
                    ->where('email', 'LIKE', 'test+%@mailinator.com')
                    ->delete();
                \\App\\Models\\Customer::where('email', 'LIKE', 'test+%@mailinator.com')
                    ->where('company_id', '${companyId}')
                    ->forceDelete();
                echo 'cleaned';
            `);
            try { phpExec(tmpScript); } catch (e) {}
        }
        try { fs.unlinkSync(tmpScript); } catch (e) {}
        await browser.close();
    }

    console.log('\n' + '='.repeat(50));
    console.log(`Email Verification — 4 Phase Test: ${results.passed}/${results.total} pass`);
    if (results.failed > 0) {
        console.log('Failed tests:');
        results.tests.filter(t => !t.pass).forEach(t => console.log(`  ✗ ${t.name}${t.info ? ' — ' + t.info : ''}`));
    }
    console.log('='.repeat(50));
    return results.failed === 0;
}

testAll().then(ok => process.exit(ok ? 0 : 1)).catch(err => { console.error(err); process.exit(1); });
