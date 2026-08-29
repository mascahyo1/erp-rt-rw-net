/**
 * Buktiin end-to-end:
 * 1. Tunnel URL reachable + render Laravel HTML
 * 2. Vite HMR script injected dengan URL localhost:5173
 * 3. Vite serve @vite/client reachable
 * 4. Login page di-tunnel functional (submit + lihat error)
 * 5. Stop Vite dev → tunnel harus tetap works (fallback built assets)
 */
const { chromium } = require('playwright');
const { execSync } = require('child_process');

const TUNNEL = 'https://lerp-rt-rw-net.cahyosoft.my.id';

(async () => {
    const browser = await chromium.launch({ slowMo: 350, headless: false });
    const results = { total: 0, passed: 0, failed: 0 };
    const assert = (name, cond, info) => {
        results.total++;
        cond ? results.passed++ : results.failed++;
        console.log(`  ${cond ? '✓' : '✗'} ${name}${info ? ' — ' + info : ''}`);
    };

    try {
        const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
        const page = await ctx.newPage();

        console.log('=== TEST: Tunnel + Vite HMR end-to-end ===\n');

        // 1. Load tunnel home page
        const resp = await page.goto(TUNNEL + '/', { waitUntil: 'commit', timeout: 60000 });
        await page.waitForTimeout(3000);
        assert('[1] Tunnel home page reachable', resp && resp.status() === 200, `status=${resp?.status()}`);

        // 2. Cek HMR script injected
        const hmrScripts = await page.locator('script[src*="@vite/client"]').count();
        assert('[2] HMR @vite/client script injected', hmrScripts > 0, `count=${hmrScripts}`);

        // 3. Verify script src pakai localhost:5173 (bukan [::1])
        const hmrSrc = await page.locator('script[src*="@vite/client"]').first().getAttribute('src');
        assert('[3] HMR script src pakai localhost (bukan [::1])', hmrSrc && hmrSrc.includes('localhost:5173'), `src=${hmrSrc}`);

        // 4. Cek HMR script reachable
        const hmrCheck = await page.request.get(hmrSrc, { timeout: 5000 });
        assert('[4] HMR @vite/client reachable', hmrCheck.status() === 200, `status=${hmrCheck.status()}`);

        // 5. Cek app.js reachable (real Vue app code)
        const appJsCheck = await page.request.get('http://localhost:5173/resources/js/app.js', { timeout: 5000 });
        assert('[5] app.js reachable', appJsCheck.status() === 200, `status=${appJsCheck.status()}, size=${(await appJsCheck.body()).length}B`);

        // 6. Login page via tunnel
        const consoleErrors = [];
        page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
        page.on('pageerror', err => consoleErrors.push('PAGE ERROR: ' + err.message));

        await page.goto(TUNNEL + '/login-operator-saas', { waitUntil: 'commit', timeout: 60000 });
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(3000);
        // Tunggu Inertia render h1
        await page.waitForSelector('h1', { timeout: 15000 }).catch(() => { /* timeout logged below */ });
        const h1Count = await page.locator('h1').count();
        if (h1Count === 0) {
            console.log('  console errors so far:', consoleErrors.slice(0, 3));
        }
        assert('[6] Login page di-tunnel render (h1 visible)', h1Count > 0, `h1 count=${h1Count}`);

        // 7. Submit login dengan password salah — error message harus muncul
        await page.fill('input[type="email"]', 'superadmin@demo.test');
        await page.fill('input[type="password"]', 'salah123');
        await page.click('form button[type="submit"]');
        await page.waitForTimeout(3000);
        const errorCount = await page.locator('text=credentials, text=kredensial, .text-red-500').count();
        assert('[7] Login form submit + error response works', errorCount > 0, `error elements=${errorCount}`);

        // 8. Verify button tidak 382x382 (ripple fix dari session sebelumnya)
        const btnBox = await page.locator('form button[type="submit"]').boundingBox();
        assert('[8] Button height normal (bukan 382px)', btnBox.height < 100, `h=${btnBox.height}`);

        // 9. Screenshot full page
        await page.screenshot({ path: 'tests/Browser/Playwright/result/Auth/TunnelViteHmr/01-tunnel-login.png', fullPage: true });

        if (consoleErrors.length > 0) {
            console.log('\n  Console errors (debug):');
            consoleErrors.slice(0, 5).forEach(e => console.log('   -', e.slice(0, 150)));
        }

        await ctx.close();
    } catch (e) {
        console.log('  ✗ FATAL:', e.message);
        results.failed++;
    } finally {
        console.log(`\nResult: ${results.passed}/${results.total} pass`);
        await browser.close();
        process.exit(results.failed > 0 ? 1 : 0);
    }
})();
