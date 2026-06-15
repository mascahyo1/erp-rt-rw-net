
const BASE = require('../../support/baseUrl.cjs');
/**
 * Verifikasi fix bug tombol 382x382 di /login-operator-saas.
 * Skenario: login dengan email valid + password SALAH.
 * - Button height harus normal (~40-50px), BUKAN 382px
 * - Error message harus muncul (server-side)
 */
const { chromium } = require('playwright');


(async () => {
    const browser = await chromium.launch({ headless: false, slowMo: 200 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    const results = { total: 0, passed: 0, failed: 0 };
    const assert = (name, cond, info) => {
        results.total++;
        cond ? results.passed++ : results.failed++;
        console.log(`  ${cond ? '✓' : '✗'} ${name}${info ? ' — ' + info : ''}`);
    };

    try {
        console.log('=== Verify login button fix ===');
        await page.goto(`${BASE}/login-operator-saas`, { waitUntil: 'domcontentloaded' });
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(500);

        // Isi form dengan email valid + password salah
        await page.fill('input[type="email"]', 'superadmin@demo.test');
        await page.fill('input[type="password"]', 'passwordSALAH');

        // BEFORE click: cek dimensi button
        const btnSel = 'form button[type="submit"]';
        const dimBefore = await page.locator(btnSel).boundingBox();
        console.log(`  [before-click] button: ${dimBefore.width}x${dimBefore.height}`);
        assert('Button width normal (w-full)', dimBefore.width > 100 && dimBefore.width < 600, `w=${dimBefore.width}`);
        assert('Button height NORMAL (NOT 382px)', dimBefore.height < 100, `h=${dimBefore.height}`);

        // Click submit
        await page.click(btnSel);
        await page.waitForTimeout(3000);

        // AFTER click + error response: cek lagi dimensi button
        const dimAfter = await page.locator(btnSel).boundingBox();
        console.log(`  [after-click]  button: ${dimAfter.width}x${dimAfter.height}`);
        assert('Button width normal setelah submit', dimAfter.width > 100 && dimAfter.width < 600, `w=${dimAfter.width}`);
        assert('Button height TETAP normal setelah error (NOT 382px)', dimAfter.height < 100, `h=${dimAfter.height}`);

        // Cek error message muncul
        const errorLocators = [
            'text=credentials',
            'text=kredensial',
            'text=tidak valid',
            '.text-red-500',
            'p.text-red-500',
            'p[class*="text-red"]',
        ];
        let errorVisible = 0;
        let errorText = '';
        for (const sel of errorLocators) {
            const count = await page.locator(sel).count();
            if (count > 0) {
                errorVisible += count;
                errorText += `[${sel}=${count}] `;
                // ambil text dari element pertama
                try {
                    const txt = await page.locator(sel).first().innerText();
                    errorText += `"${txt.slice(0, 80)}" `;
                } catch (e) { /* ignore */ }
            }
        }
        assert('Error message tampil setelah password salah', errorVisible > 0, `found=${errorVisible} | ${errorText}`);

        // Screenshot untuk visual verify
        await page.screenshot({ path: 'tests/Browser/Playwright/result/Auth/LoginButtonFix/01-error-state.png', fullPage: true });

        // Mobile viewport test (382px wide - skenario asli user)
        await page.setViewportSize({ width: 382, height: 800 });
        await page.waitForTimeout(500);
        await page.screenshot({ path: 'tests/Browser/Playwright/result/Auth/LoginButtonFix/02-mobile-error.png', fullPage: true });
        const dimMobile = await page.locator(btnSel).boundingBox();
        console.log(`  [mobile]       button: ${dimMobile.width}x${dimMobile.height}`);
        assert('Mobile 382px: button width sesuai viewport', dimMobile.width <= 382, `w=${dimMobile.width}`);
        assert('Mobile 382px: button height TETAP normal', dimMobile.height < 100, `h=${dimMobile.height}`);
    } catch (e) {
        console.log('  ✗ FATAL:', e.message);
        results.failed++;
    } finally {
        console.log(`\nResult: ${results.passed}/${results.total} pass`);
        await browser.close();
        process.exit(results.failed > 0 ? 1 : 0);
    }
})();
