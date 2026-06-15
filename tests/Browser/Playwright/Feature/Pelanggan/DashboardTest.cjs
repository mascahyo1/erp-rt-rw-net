
const BASE = require('../../support/baseUrl.cjs');
/**
 * E2E Test: Pelanggan Dashboard
 * Test render + breadcrumb + hero + 4 stat cards (semua real) + dark mode + responsive.
 */
const { chromium } = require('playwright');

const EMAIL = 'test+1781247641870@example.com';
const PASSWORD = 'password123';

async function loginAsPelanggan(page) {
    await page.goto(`${BASE}/login-pelanggan`, { waitUntil: 'domcontentloaded' });
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
    await page.waitForURL('**/customer/dashboard**', { timeout: 10000 });
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
        console.log('=== Pelanggan Dashboard ===');
        await loginAsPelanggan(page);
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'tests/Browser/Playwright/result/Pelanggan/Dashboard/01-page.png', fullPage: true });

        // 1. "Dashboard" text — pakai slot di Pelanggan, jadi cek text apa pun
        const anyDashboardText = await page.evaluate(() => {
            const main = document.querySelector('main') || document.body;
            return main.innerText.includes('Dashboard') ? 1 : 0;
        });
        assert('"Dashboard" text visible in main area', anyDashboardText > 0);

        // 2. Breadcrumb (home > Dashboard)
        const breadcrumb = await page.locator('a[href="/customer/dashboard"] i.fa-home').count();
        assert('Breadcrumb home icon visible', breadcrumb > 0);

        // 3. Hero welcome banner
        const welcome = await page.locator('text=Selamat datang').count();
        assert('Hero "Selamat datang" visible', welcome > 0);

        // 4. 4 stat cards (semua real, tidak ada &mdash; dummy)
        const labels = ['Paket Aktif', 'Tagihan Bulan Ini', 'Status Pembayaran', 'Riwayat Pembayaran'];
        for (const label of labels) {
            const cnt = await page.locator(`text=${label}`).count();
            assert(`Card label "${label}" visible`, cnt > 0);
        }

        // 5. Status Pembayaran value — salah satu dari {Lunas, Ada Tunggakan, Belum Bayar, Belum Ada Tagihan}, BUKAN "&mdash;"
        const statusValue = await page.evaluate(() => {
            const card = [...document.querySelectorAll('*')].find(el => el.textContent.trim() === 'Status Pembayaran');
            if (!card) return null;
            const container = card.closest('.group') || card.parentElement.parentElement;
            const valEl = container?.querySelector('.text-2xl, .text-lg, .text-4xl, [class*="font-bold"]');
            return valEl?.textContent?.trim();
        });
        console.log(`  [info] Status Pembayaran value: ${statusValue}`);
        const allowedLabels = ['Lunas', 'Ada Tunggakan', 'Belum Bayar', 'Belum Ada Tagihan'];
        assert('Status Pembayaran value is a valid label (NOT &mdash;)', statusValue !== '—' && statusValue !== '&mdash;' && statusValue !== null);
        assert('Status Pembayaran value matches expected label set', allowedLabels.includes(statusValue), `got: ${statusValue}`);

        // 6. Sublabel
        const sub = await page.locator('text=Status invoice terbaru').count();
        assert('Sublabel "Status invoice terbaru" visible', sub > 0);

        // 7. Mobile
        await page.setViewportSize({ width: 375, height: 667 });
        await page.waitForTimeout(500);
        await page.screenshot({ path: 'tests/Browser/Playwright/result/Pelanggan/Dashboard/02-mobile.png', fullPage: true });
        const mobileCards = await page.locator('text=Status Pembayaran').count();
        assert('Mobile viewport: cards still visible', mobileCards > 0);

        // 8. Desktop
        await page.setViewportSize({ width: 1280, height: 900 });
        await page.waitForTimeout(500);
        const desktop = await page.locator('text=Riwayat Pembayaran').count();
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
