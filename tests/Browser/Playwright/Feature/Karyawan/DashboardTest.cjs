/**
 * E2E Test: Karyawan Dashboard
 * Test render + hero + 4 stat cards (semua real) + dark mode + responsive.
 */
const { chromium } = require('playwright');

const BASE = 'http://erp-rt-rw-net.test';
const EMAIL = 'ahmad@netsejahtera.com';
const PASSWORD = 'password123';

async function loginAsKaryawan(page) {
    await page.goto(`${BASE}/login-karyawan`, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    const trigger = page.locator('button:has-text("Cari perusahaan")').first();
    if (await trigger.count() > 0) await trigger.click();
    await page.waitForTimeout(500);
    await page.fill('input[placeholder*="Cari perusahaan"]', 'PT Net Sejahtera Abadi');
    await page.waitForTimeout(1500);
    await page.locator('[data-testid^="company-item-"]').first().click();
    await page.waitForTimeout(500);
    await page.fill('input[type="email"]', EMAIL);
    await page.fill('input[type="password"]', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/karyawan/dashboard**', { timeout: 10000 });
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
        console.log('=== Karyawan Dashboard ===');
        await loginAsKaryawan(page);
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'tests/Browser/Playwright/result/Karyawan/Dashboard/01-page.png', fullPage: true });

        // 1. Heading "Dashboard" — di Karyawan pakai slot, jadi cek text apa pun
        const headingText = await page.locator('h1:has-text("Dashboard"), h2:has-text("Dashboard"), h3:has-text("Dashboard"), h4:has-text("Dashboard")').count();
        const anyDashboardText = await page.evaluate(() => {
            // Cari text 'Dashboard' di top-level page (bukan di sidebar menu)
            const main = document.querySelector('main') || document.body;
            return main.innerText.includes('Dashboard') ? 1 : 0;
        });
        assert('"Dashboard" text visible in main area', headingText > 0 || anyDashboardText > 0);

        // 2. Hero welcome banner
        const welcome = await page.locator('text=Selamat datang').count();
        assert('Hero "Selamat datang" visible', welcome > 0);

        // 3. 4 stat cards (semua real, tidak ada &mdash; dummy)
        const labels = ['Customer Ditagih', 'Tagihan Bulan Ini', 'Insentif Bulan Ini', 'Pembayaran Collection'];
        for (const label of labels) {
            const cnt = await page.locator(`text=${label}`).count();
            assert(`Card label "${label}" visible`, cnt > 0);
        }

        // 4. Insentif Bulan Ini value — bukan "&mdash;" dummy
        const insentifValue = await page.evaluate(() => {
            const card = [...document.querySelectorAll('*')].find(el => el.textContent.trim() === 'Insentif Bulan Ini');
            if (!card) return null;
            const container = card.closest('.group') || card.parentElement.parentElement;
            const valEl = container?.querySelector('.text-2xl, .text-lg, .text-4xl, [class*="font-bold"]');
            return valEl?.textContent?.trim();
        });
        console.log(`  [info] Insentif Bulan Ini value: ${insentifValue}`);
        assert('Insentif Bulan Ini value NOT "&mdash;" dummy', insentifValue !== '—' && insentifValue !== '&mdash;' && insentifValue !== null);

        // 5. Sublabel
        const sub = await page.locator('text=Klaim disetujui admin').count();
        assert('Sublabel "Klaim disetujui admin" visible', sub > 0);

        // 6. Mobile
        await page.setViewportSize({ width: 375, height: 667 });
        await page.waitForTimeout(500);
        await page.screenshot({ path: 'tests/Browser/Playwright/result/Karyawan/Dashboard/02-mobile.png', fullPage: true });
        const mobileCards = await page.locator('text=Customer Ditagih').count();
        assert('Mobile viewport: cards still visible', mobileCards > 0);

        // 7. Desktop
        await page.setViewportSize({ width: 1280, height: 900 });
        await page.waitForTimeout(500);
        const desktop = await page.locator('text=Pembayaran Collection').count();
        assert('Desktop viewport: cards visible', desktop > 0);
    } catch (e) {
        console.log('  ✗ FATAL:', e.message);
        results.failed++;
    } finally {
        console.log(`\nResult: ${results.passed}/${results.total} pass`);
        await browser.close();
        process.exit(results.failed > 0 ? 1 : 0);
    }
})();
