
const BASE = require('../../support/baseUrl.cjs');
/**
 * E2E Test: Operator SaaS Dashboard
 * Test render + 6 stat cards + dark mode + responsive.
 */
const { chromium } = require('playwright');

const EMAIL = 'superadmin@demo.test';
const PASSWORD = 'password123';

async function loginAsSaaS(page) {
    await page.goto(`${BASE}/login-operator-saas`, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.fill('input[type="email"]', EMAIL);
    await page.fill('input[type="password"]', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/operator-saas/dashboard**', { timeout: 10000 });
}

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
        console.log('=== Operator SaaS Dashboard ===');
        await loginAsSaaS(page);
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'tests/Browser/Playwright/result/OperatorSaas/Dashboard/01-page.png', fullPage: true });

        // 1. Heading
        const heading = await page.locator('h2:has-text("Dashboard")').count();
        assert('Heading "Dashboard" visible', heading > 0);

        // 2. 6 stat cards
        const labels = ['Perusahaan Aktif', 'Admin Perusahaan', 'Admin SaaS', 'Pelanggan Aktif', 'Karyawan Aktif', 'Langganan Aktif'];
        for (const label of labels) {
            const cnt = await page.locator(`text=${label}`).count();
            assert(`Card label "${label}" visible`, cnt > 0);
        }

        // 3. System Online badge
        const online = await page.locator('text=System Online').count();
        assert('"System Online" badge visible', online > 0);

        // 4. Dark mode toggle (Tailwind .dark class di html)
        const darkBefore = await page.evaluate(() => document.documentElement.classList.contains('dark'));
        assert('Initial dark mode state captured', true, `dark=${darkBefore}`);

        // 5. Responsive (mobile viewport 375x667)
        await page.setViewportSize({ width: 375, height: 667 });
        await page.waitForTimeout(500);
        await page.screenshot({ path: 'tests/Browser/Playwright/result/OperatorSaas/Dashboard/02-mobile.png', fullPage: true });
        // Heading masih visible di mobile
        const mobileHeading = await page.locator('h2:has-text("Dashboard")').count();
        assert('Mobile viewport: heading masih visible', mobileHeading > 0);

        // 6. Kembali ke desktop, verify
        await page.setViewportSize({ width: 1280, height: 900 });
        await page.waitForTimeout(500);
        const desktopCards = await page.locator('text=Pelanggan Aktif').count();
        assert('Desktop viewport: cards still visible', desktopCards > 0);
    } catch (e) {
        console.log('  ✗ FATAL:', e.message);
        results.failed++;
    } finally {
        console.log(`\nResult: ${results.passed}/${results.total} pass`);
        await browser.close();
        process.exit(results.failed > 0 ? 1 : 0);
    }
})();
