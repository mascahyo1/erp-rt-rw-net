/**
 * Verifikasi VISUAL + happy path Turnstile di 1 portal (operator-saas).
 *
 * Test ini pakai headed browser untuk verify:
 *   1. Widget Turnstile rendering di halaman login (cf-turnstile div ada di DOM)
 *   2. Script Turnstile loaded dari CDN Cloudflare
 *   3. User solve captcha → token auto-populate ke hidden input
 *   4. Submit form dengan kredensial valid + solved captcha → redirect ke dashboard
 *
 * TIDAK throttle 5/menit (cukup 1 attempt, di bawah limit).
 */
const { chromium } = require('playwright');
const { execSync } = require('child_process');

const BASE = 'http://erp-rt-rw-net.test';

function flushCache() {
    try {
        execSync('cd /c/laragon/www/erp-rt-rw-net && php artisan cache:clear', { stdio: 'pipe' });
        execSync('sleep 1');
    } catch (e) { /* ignore */ }
}

(async () => {
    const browser = await chromium.launch({ headless: false, slowMo: 300 });
    const results = { total: 0, passed: 0, failed: 0 };
    const assert = (name, cond, info) => {
        results.total++;
        cond ? results.passed++ : results.failed++;
        console.log(`  ${cond ? '✓' : '✗'} ${name}${info ? ' — ' + info : ''}`);
    };

    try {
        flushCache();
        const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
        const page = await ctx.newPage();

        console.log('\n=== 1. Goto login page ===');
        await page.goto(`${BASE}/login-operator-saas`, { waitUntil: 'domcontentloaded' });

        // Capture console log untuk debug
        const consoleMsgs = [];
        page.on('console', msg => consoleMsgs.push(`[${msg.type()}] ${msg.text()}`));
        page.on('pageerror', err => consoleMsgs.push(`[PAGEERROR] ${err.message}`));

        await page.waitForTimeout(5000); // kasih waktu script load + widget render

        // 1. Verify Turnstile script loaded
        const turnstileScript = await page.$('script#cf-turnstile-script');
        assert('Turnstile script tag ada di DOM', !!turnstileScript);

        // 2. Verify Turnstile widget div dirender
        const widgetDiv = await page.$('.cf-turnstile');
        assert('Widget div (.cf-turnstile) ada di DOM', !!widgetDiv);

        // 3. Verify data-sitekey attribute ter-set
        const sitekey = await widgetDiv?.getAttribute('data-sitekey');
        assert('Widget punya data-sitekey (1x... testing key)', sitekey === '1x00000000000000000000AA', `sitekey=${sitekey}`);

        // 4. Wait sampai widget auto-solve (testing key instant) — iframe muncul dalam widget div
        let iframe = null;
        try {
            await page.waitForSelector('.cf-turnstile iframe', { timeout: 30000, state: 'attached' });
            iframe = await page.$('.cf-turnstile iframe');
        } catch (e) {
            console.log('  ⚠ Console messages:');
            consoleMsgs.slice(-15).forEach(m => console.log('    ' + m));
        }
        assert('Widget Turnstile loaded iframe (auto-solved oleh testing key)', !!iframe);

        // 5. Verify hidden input ter-populate token
        await page.waitForFunction(
            () => document.querySelector('input[name="cf-turnstile-response"]')?.value?.length > 10,
            { timeout: 5000 }
        ).catch(() => null);
        const token = await page.inputValue('input[name="cf-turnstile-response"]');
        assert('Hidden input ter-populate token (>10 char)', token.length > 10, `token.length=${token.length}`);

        // 6. Screenshot
        await page.screenshot({ path: 'tests/Browser/Playwright/result/Auth/Turnstile/login-with-captcha.png', fullPage: true });
        console.log('  📸 Screenshot saved: login-with-captcha.png');

        // 7. Fill kredensial valid + submit
        console.log('\n=== 2. Submit form dengan kredensial valid + solved captcha ===');
        await page.fill('input[type="email"]', 'superadmin@demo.test');
        await page.fill('input[type="password"]', 'password123');
        await page.click('button[type="submit"]');

        // 8. Wait redirect
        try {
            await page.waitForURL(/\/operator-saas/, { timeout: 10000 });
            assert('Submit happy path → redirect ke /operator-saas/*', true, `url=${page.url()}`);
        } catch (e) {
            assert('Submit happy path → redirect ke /operator-saas/*', false, `timeout, url=${page.url()}`);
        }

        await ctx.close();

    } catch (e) {
        console.log('  ✗ FATAL:', e.message, e.stack);
        results.failed++;
    } finally {
        console.log(`\nResult: ${results.passed}/${results.total} pass`);
        await browser.close();
        process.exit(results.failed > 0 ? 1 : 0);
    }
})();
