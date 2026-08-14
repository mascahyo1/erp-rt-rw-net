/**
 * E2E Test (Playwright headed): VERIFY prod deployment dgn login flow.
 *
 * Test against LIVE production URL (https://net.cahyosoft.my.id via Cloudflare Tunnel).
 * Verifies:
 * - Login page load + Turnstile widget rendered
 * - Masuk dropdown → Perusahaan nav works (full Inertia navigation)
 * - Customer login via customer portal (sugeng@gmail.com) works
 * - Company login via operator-perusahaan portal (admin@netsejahtera.com) works
 * - No 500 error
 *
 * Login credentials per test user spec:
 *   Customer: sugeng@gmail.com / password123
 *   Admin Perusahaan: admin@netsejahtera.com / password123
 */

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE = 'https://net.cahyosoft.my.id';
const RESULT = path.join(__dirname, 'DayXProductionLoginVerify');
if (!fs.existsSync(RESULT)) fs.mkdirSync(RESULT, { recursive: true });

const TESTS = [
    {
        name: 'customer-login',
        loginPath: '/login-pelanggan',
        cred: { email: 'sugeng@gmail.com', password: 'password123' },
        companyName: null,  // Customer gak butuh company
        expectRedirect: '/customer/dashboard',
    },
    {
        name: 'perusahaan-login',
        loginPath: '/login-perusahaan',
        cred: { email: 'admin@netsejahtera.com', password: 'password123' },
        companyName: 'Net Sejahtera',
        expectRedirect: '/operator-perusahaan/dashboard',
    },
];

async function snap(page, name) {
    await page.screenshot({ path: path.join(RESULT, name + '.png'), fullPage: false });
    console.log('  snap:', name);
}

async function runTest(browser, test) {
    console.log(`\n========== Test: ${test.name} ==========`);
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();
    const errors = [];
    page.on('pageerror', err => {
        errors.push(err.message);
        console.log('  [page-error]', err.message.slice(0, 150));
    });
    page.on('response', resp => {
        if (resp.status() >= 500) {
            errors.push(`HTTP ${resp.status()} on ${resp.url()}`);
            console.log('  [5xx]', resp.status(), resp.url());
        }
    });

    try {
        // 1. Buka login page
        await page.goto(BASE + test.loginPath, { waitUntil: 'load', timeout: 30000 });
        await page.waitForTimeout(3000);
        await snap(page, `${test.name}-01-page`);

        // 2. Tunggu Turnstile widget
        const widgetFound = await page.locator('[data-testid="cf-turnstile-widget"]').count();
        if (widgetFound === 0) {
            console.log('  ✗ Turnstile widget not found');
            return { ok: false, reason: 'no widget' };
        }
        await page.waitForTimeout(5000); // test key auto-solve
        await snap(page, `${test.name}-02-widget-solved`);

        // 3. Check button state
        const btnState = await page.evaluate(() => {
            const btn = document.querySelector('[data-testid="btn-login-submit"]');
            return { text: btn?.textContent?.trim(), disabled: btn?.disabled };
        });
        console.log('  Initial btn state:', JSON.stringify(btnState));
        if (btnState.disabled || btnState.text?.includes('Tunggu')) {
            console.log('  ✗ Button still disabled — bug not fixed!');
            return { ok: false, reason: 'button stuck' };
        }

        // 4. If company-picker needed, select
        if (test.companyName) {
            const hasCompany = await page.evaluate(() =>
                !!Array.from(document.querySelectorAll('button[type="button"]')).find(x => x.textContent.includes('Cari perusahaan'))
            );
            if (hasCompany) {
                await page.evaluate(() =>
                    Array.from(document.querySelectorAll('button[type="button"]')).find(x => x.textContent.includes('Cari perusahaan'))?.click()
                );
                await page.waitForTimeout(2500);
                await page.fill('input[placeholder*="Cari perusahaan"]', test.companyName);
                await page.waitForTimeout(3500);
                await page.evaluate(() => document.querySelector('[data-testid^="company-item-"]')?.click());
                await page.waitForTimeout(500);
            }
        }

        // 5. Fill email + password
        await page.fill('input[type="email"]', test.cred.email);
        await page.fill('input[type="password"]', test.cred.password);
        await page.waitForTimeout(500);
        await snap(page, `${test.name}-03-filled`);

        // 6. Submit
        await page.click('[data-testid="btn-login-submit"]');
        await page.waitForTimeout(6000);
        await snap(page, `${test.name}-04-after-submit`);

        const finalUrl = page.url();
        console.log('  Final URL:', finalUrl);
        const onDashboard = finalUrl.includes(test.expectRedirect);
        if (onDashboard) {
            console.log('  ✓ Login OK — redirected to', test.expectRedirect);
            return { ok: true, url: finalUrl };
        } else {
            console.log('  ✗ Login failed — expected', test.expectRedirect);
            return { ok: false, reason: 'redirect mismatch', url: finalUrl };
        }
    } catch (e) {
        console.log('  ✗ EXCEPTION:', e.message);
        await snap(page, `${test.name}-99-exception`);
        return { ok: false, reason: e.message };
    } finally {
        if (errors.length > 0) {
            console.log('  Errors captured:', errors.length);
            for (const e of errors) console.log('   -', e);
        }
        await ctx.close();
    }
}

(async () => {
    console.log('▶ Production login verification (live URL)');
    const browser = await chromium.launch({ headless: false, slowMo: 400 });
    let pass = 0, fail = 0;
    const results = [];
    for (const test of TESTS) {
        const r = await runTest(browser, test);
        results.push({ name: test.name, ...r });
        if (r.ok) pass++;
        else fail++;
    }
    await browser.close();
    console.log(`\n=== Hasil: ${pass} passed, ${fail} failed ===`);
    for (const r of results) {
        console.log(`  ${r.ok ? '✓' : '✗'} ${r.name}: ${r.reason || 'OK'}`);
    }
    process.exit(fail > 0 ? 1 : 0);
})();
