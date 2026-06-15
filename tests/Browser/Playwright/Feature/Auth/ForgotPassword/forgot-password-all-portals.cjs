/**
 * E2E Test: Lupa Password untuk 4 Portal
 *
 * Flow per phase:
 *  1. Truncate log
 *  2. Goto forgot-password form
 *  3. Submit email (+ company for multi-tenant)
 *  4. Read log → extract reset URL
 *  5. Visit reset URL → form password baru muncul
 *  6. Submit new password
 *  7. Verify redirect ke login
 *  8. Login dengan new password → success
 *  9. Reset password kembali ke password123 (cleanup)
 *
 * 4 phases:
 *  - Phase 1: operator-saas (superadmin@demo.test, single tenant)
 *  - Phase 2: perusahaan (admin@netsejahtera.com + company "PT Net Sejahtera Abadi")
 *  - Phase 3: karyawan (ahmad@netsejahtera.com + company "PT Net Sejahtera Abadi")
 *  - Phase 4: pelanggan (sugeng@gmail.com + company "PT Net Sejahtera Abadi")
 */

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');
const PlaywrightHelper = require('../../../support/PlaywrightHelper.cjs');
const LogParser = require('../../../support/LogParser.cjs');


const BASE = require('../../../support/baseUrl.cjs');
const PROJECT_ROOT = path.resolve(__dirname, '..', '..', '..', '..');
const RESULT_DIR = path.join(PROJECT_ROOT, 'tests/Browser/Playwright/Feature/result/Auth/ForgotPassword');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

// PHP bootstrap helper
const PROJ_WIN = PROJECT_ROOT.replace(/\\/g, '\\\\');
const BOOTSTRAP = `<?php
require '${PROJ_WIN}\\\\vendor\\\\autoload.php';
$app = require '${PROJ_WIN}\\\\bootstrap\\\\app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
`;
const tmpScript = path.join(PROJECT_ROOT, '.claude', 'tmp_forgot_pw_test.php');
const writeScript = (code) => fs.writeFileSync(tmpScript, BOOTSTRAP + code);

// Phase config
const PHASES = [
    {
        name: 'operator-saas',
        portal: 'operator-saas',
        url: '/lupa-password-operator-saas',
        resetUrl: '/lupa-password-operator-saas/reset',
        loginUrl: '/login-operator-saas',
        email: 'superadmin@demo.test',
        companyName: null,
        model: 'AdminSaas',
        newPassword: 'PasswordBaruSaaS1!',
    },
    {
        name: 'perusahaan',
        portal: 'perusahaan',
        url: '/lupa-password-perusahaan',
        resetUrl: '/lupa-password-perusahaan/reset',
        loginUrl: '/login-perusahaan',
        email: 'admin@netsejahtera.com',
        companyName: 'PT Net Sejahtera Abadi',
        model: 'AdminCompany',
        newPassword: 'PasswordBaruComp1!',
    },
    {
        name: 'karyawan',
        portal: 'karyawan',
        url: '/lupa-password-karyawan',
        resetUrl: '/lupa-password-karyawan/reset',
        loginUrl: '/login-karyawan',
        email: 'ahmad@netsejahtera.com',
        companyName: 'PT Net Sejahtera Abadi',
        model: 'Employee',
        newPassword: 'PasswordBaruKry1!',
    },
    {
        name: 'pelanggan',
        portal: 'pelanggan',
        url: '/lupa-password-pelanggan',
        resetUrl: '/lupa-password-pelanggan/reset',
        loginUrl: '/login-pelanggan',
        email: 'test+1781247641870@example.com',
        companyName: 'PT Net Sejahtera Abadi',
        model: 'Customer',
        newPassword: 'PasswordBaruCust1!',
    },
];

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
    const helper = new PlaywrightHelper(BASE);

    try {
        for (const phase of PHASES) {
            console.log(`\n=== Phase: ${phase.name} (${phase.email}) ===`);
            // Throttle 30/menit = max 1 request per 2 detik per route. Tunggu 2.5s
            // untuk pastikan window sudah clear dari request phase sebelumnya.
            await page.waitForTimeout(2500);

            // ===== 1. Truncate log =====
            logParser.truncate();
            console.log('  [1] Truncated log');

            // ===== 2. Goto forgot-password form =====
            await page.goto(BASE + phase.url, { waitUntil: 'domcontentloaded' });
            // await page.waitForLoadState('networkidle');  // Disabled: Reverb websocket blocks networkidle
            await page.waitForTimeout(1000);
            await page.screenshot({ path: path.join(RESULT_DIR, `${phase.name}-01-form.png`), fullPage: true });
            assert(`[${phase.name}] Form "Lupa Password" renders`, (await page.locator('h1:has-text("Lupa Password")').count()) > 0);

            // ===== 3. Submit email (+ company) =====
            let companyId = null;
            if (phase.companyName) {
                // Click search input + type company
                const triggerBtn = page.locator('button:has-text("Cari perusahaan")').first();
                if (await triggerBtn.count() > 0) await triggerBtn.click();
                await page.waitForTimeout(500);
                await page.fill('input[placeholder*="Cari perusahaan"]', phase.companyName);
                await page.waitForTimeout(1500);
                // Click first company item
                const companyItem = page.locator('[data-testid^="company-item-"]').first();
                await companyItem.click();
                await page.waitForTimeout(500);
                // Get company_id from DB to verify
                writeScript(`
                    $c = \\App\\Models\\Company::where('name','${phase.companyName.replace(/'/g, "\\'")}')->first();
                    echo $c ? $c->id : 'NOT_FOUND';
                `);
                companyId = execSync(`php "${tmpScript}"`, { cwd: PROJECT_ROOT }).toString().trim();
                assert(`[${phase.name}] Company "${phase.companyName}" selected`, companyId !== 'NOT_FOUND', `id: ${companyId}`);
            }

            await page.fill('input[type="email"]', phase.email);
            await page.waitForTimeout(300);
            await page.screenshot({ path: path.join(RESULT_DIR, `${phase.name}-02-filled.png`), fullPage: true });

            await page.click('button:has-text("Kirim Link Reset")');
            await page.waitForTimeout(2000);
            await page.screenshot({ path: path.join(RESULT_DIR, `${phase.name}-03-submitted.png`), fullPage: true });

            // ===== 4. Read log → extract reset URL =====
            const resetUrl = logParser.findResetUrl(phase.portal);
            assert(`[${phase.name}] Reset URL found in log`, resetUrl !== null, `URL: ${resetUrl ? resetUrl.substring(0, 100) : 'null'}`);

            if (!resetUrl) {
                console.log('  [SKIP] Cannot continue without reset URL');
                continue;
            }

            // ===== 5. Visit reset URL =====
            await page.goto(resetUrl, { waitUntil: 'domcontentloaded' });
            // await page.waitForLoadState('networkidle');  // Disabled: Reverb websocket blocks networkidle
            await page.waitForTimeout(1500);
            await page.screenshot({ path: path.join(RESULT_DIR, `${phase.name}-04-reset-form.png`), fullPage: true });
            assert(`[${phase.name}] Reset form (password baru) renders`, (await page.locator('h1:has-text("Buat Password Baru")').count()) > 0);

            // ===== 6. Submit new password =====
            await page.fill('input[type="password"]', phase.newPassword);
            const confirmInput = page.locator('input[type="password"]').nth(1);
            await confirmInput.fill(phase.newPassword);
            await page.waitForTimeout(300);
            await page.screenshot({ path: path.join(RESULT_DIR, `${phase.name}-05-new-password.png`), fullPage: true });

            await page.click('button:has-text("Reset Password")');
            await page.waitForTimeout(3000);
            await page.screenshot({ path: path.join(RESULT_DIR, `${phase.name}-06-after-reset.png`), fullPage: true });

            // ===== 7. Verify password changed in DB (definitive check — Inertia XHR submit
            //        tidak navigate URL bar, jadi kita cek dari DB langsung) =====
            const passwordCheckQuery = phase.companyName
                ? `\\App\\Models\\${phase.model}::where('email','${phase.email}')->where('company_id','${companyId}')->first()`
                : `\\App\\Models\\${phase.model}::where('email','${phase.email}')->first()`;
            writeScript(`
                $u = ${passwordCheckQuery};
                echo $u ? (\\Hash::check('${phase.newPassword}', $u->password) ? 'MATCH' : 'NOMATCH') : 'NO_USER';
            `);
            const pwCheck = execSync(`php "${tmpScript}"`, { cwd: PROJECT_ROOT }).toString().trim();
            assert(`[${phase.name}] Password successfully changed in DB`, pwCheck === 'MATCH', `check: ${pwCheck}`);

            // ===== 8. Login with new password =====
            if (phase.companyName) {
                const triggerBtn = page.locator('button:has-text("Cari perusahaan")').first();
                if (await triggerBtn.count() > 0) await triggerBtn.click();
                await page.waitForTimeout(500);
                await page.fill('input[placeholder*="Cari perusahaan"]', phase.companyName);
                await page.waitForTimeout(1500);
                const companyItem = page.locator('[data-testid^="company-item-"]').first();
                await companyItem.click();
                await page.waitForTimeout(500);
            }
            await page.fill('input[type="email"]', phase.email);
            await page.fill('input[type="password"]', phase.newPassword);
            await page.waitForTimeout(500);
            await page.click('form button[type="submit"]');
            await page.waitForTimeout(4000);
            await page.screenshot({ path: path.join(RESULT_DIR, `${phase.name}-07-after-login.png`), fullPage: true });
            const loggedIn = !page.url().includes(phase.loginUrl);
            assert(`[${phase.name}] Login with new password succeeds`, loggedIn, `URL: ${page.url()}`);

            // ===== 9. Cleanup: reset password to password123 =====
            writeScript(`
                $cls = 'App\\\\Models\\\\${phase.model}';
                $q = $cls::query()->where('email', '${phase.email}');
                ${phase.companyName ? `$q->where('company_id', '${companyId}');` : ''}
                $u = $q->first();
                if ($u) {
                    $u->password = bcrypt('password123');
                    $u->save();
                }
                echo $u ? 'OK' : 'NOT_FOUND';
            `);
            const cleanupResult = execSync(`php "${tmpScript}"`, { cwd: PROJECT_ROOT }).toString().trim();
            assert(`[${phase.name}] Cleanup: password reset back to password123`, cleanupResult === 'OK', `result: ${cleanupResult}`);

            // Cleanup tokens
            writeScript(`
                \\DB::table('password_reset_tokens')
                    ->where('email', '${phase.email}')
                    ${phase.companyName ? `->where('company_id', '${companyId}')` : `->where('company_id', '')`}
                    ->delete();
                echo 'cleaned';
            `);
            execSync(`php "${tmpScript}"`, { cwd: PROJECT_ROOT });
        }
    } finally {
        try { fs.unlinkSync(tmpScript); } catch (e) {}
        await browser.close();
    }

    console.log('\n' + '='.repeat(50));
    console.log(`Lupa Password — 4 Portal Test: ${results.passed}/${results.total} pass`);
    if (results.failed > 0) {
        console.log('Failed tests:');
        results.tests.filter(t => !t.pass).forEach(t => console.log(`  ✗ ${t.name}${t.info ? ' — ' + t.info : ''}`));
    }
    console.log('='.repeat(50));
    return results.failed === 0;
}

testAll().then(ok => process.exit(ok ? 0 : 1)).catch(err => { console.error(err); process.exit(1); });
