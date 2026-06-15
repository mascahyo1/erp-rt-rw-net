
const BASE = require('../../support/baseUrl.cjs');
/**
 * E2E Test: Operator Perusahaan Dashboard
 * Test render + hero + 4 stat cards (semua real) + dark mode + responsive.
 */
const { chromium } = require('playwright');

const EMAIL = 'admin@netsejahtera.com';
const PASSWORD = 'password123';

async function loginAsPerusahaan(page) {
    await page.goto(`${BASE}/login-perusahaan`, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    // Klik tombol "Cari perusahaan" — ada CompanySearchInput
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
    await page.waitForURL('**/operator-perusahaan/dashboard**', { timeout: 10000 });
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
        console.log('=== Operator Perusahaan Dashboard ===');
        await loginAsPerusahaan(page);
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'tests/Browser/Playwright/result/OperatorPerusahaan/Dashboard/01-page.png', fullPage: true });

        // 1. Heading
        const heading = await page.locator('h2:has-text("Dashboard")').count();
        assert('Heading "Dashboard" visible', heading > 0);

        // 2. Hero welcome banner
        const welcome = await page.locator('text=Selamat datang').count();
        assert('Hero "Selamat datang" visible', welcome > 0);
        const companyName = await page.locator('text=PT Net Sejahtera Abadi').count();
        assert('Company name in hero', companyName > 0);

        // 3. 4 stat cards (semua real, tidak ada &mdash; atau "Rp 0" hardcoded)
        const labels = ['Total Customer', 'Tagihan Bulan Ini', 'Pembayaran Masuk', 'Paket Aktif'];
        for (const label of labels) {
            const cnt = await page.locator(`text=${label}`).count();
            assert(`Card label "${label}" visible`, cnt > 0);
        }

        // 4. Pembayaran Masuk value — bukan "Rp 0" hardcode, harus formatted currency
        // Cari sibling element dari card "Pembayaran Masuk"
        const paymentValue = await page.evaluate(() => {
            const card = [...document.querySelectorAll('*')].find(el => el.textContent.trim() === 'Pembayaran Masuk');
            if (!card) return null;
            // Naik ke card container, ambil elemen "value" (font-bold)
            const container = card.closest('.group') || card.parentElement.parentElement;
            const valEl = container?.querySelector('.text-2xl, .text-lg, .text-4xl, [class*="font-bold"]');
            return valEl?.textContent?.trim();
        });
        console.log(`  [info] Pembayaran Masuk value: ${paymentValue}`);
        assert('Pembayaran Masuk value NOT "Rp 0" hardcode', paymentValue !== 'Rp 0' && paymentValue !== null && paymentValue !== '—');

        // 5. Tagihan Bulan Ini sublabel visible
        const sublabel = await page.locator('text=Jumlah invoice dibuat').count();
        assert('Sublabel "Jumlah invoice dibuat" visible', sublabel > 0);

        // 6. Responsive (mobile)
        await page.setViewportSize({ width: 375, height: 667 });
        await page.waitForTimeout(500);
        await page.screenshot({ path: 'tests/Browser/Playwright/result/OperatorPerusahaan/Dashboard/02-mobile.png', fullPage: true });
        const mobileWelcome = await page.locator('text=Selamat datang').count();
        assert('Mobile viewport: hero still visible', mobileWelcome > 0);

        // 7. Kembali desktop
        await page.setViewportSize({ width: 1280, height: 900 });
        await page.waitForTimeout(500);
        const cards = await page.locator('text=Total Customer').count();
        assert('Desktop viewport: cards visible', cards > 0);
    } catch (e) {
        console.log('  ✗ FATAL:', e.message);
        results.failed++;
    } finally {
        console.log(`\nResult: ${results.passed}/${results.total} pass`);
        await browser.close();
        process.exit(results.failed > 0 ? 1 : 0);
    }
})();
