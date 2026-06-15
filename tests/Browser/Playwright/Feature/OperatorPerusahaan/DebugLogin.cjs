// Debug single login + page
const { chromium } = require('playwright');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
(async () => {
    const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
    const ctx = await browser.newContext({ viewport: { width: 1366, height: 850 } });
    const page = await ctx.newPage();
    page.on('console', msg => console.log('[console]', msg.text()));
    page.on('pageerror', err => console.log('[pageerror]', err.message));

    await page.emulateMedia({ colorScheme: 'dark' });

    await page.goto(BASE + '/login-perusahaan');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(__dirname, '..', 'result', '_dbg-01-login-page.png') });

    const companyBtn = page.locator('button:has(.fa-building)').first();
    console.log('companyBtn count:', await companyBtn.count());
    if (await companyBtn.count() > 0) {
        await companyBtn.click();
        await page.waitForTimeout(1500);
        await page.screenshot({ path: path.join(__dirname, '..', 'result', '_dbg-02-after-company-click.png') });

        const search = page.locator('input[placeholder*="Cari perusahaan"]').first();
        console.log('search count:', await search.count());
        await search.fill('Digital Media');
        await page.waitForTimeout(1500);
        await page.screenshot({ path: path.join(__dirname, '..', 'result', '_dbg-03-search.png') });

        const opt = page.locator('text=CV Digital Media Nusantara').first();
        console.log('opt count:', await opt.count());
        await opt.click();
        await page.waitForTimeout(1500);
    }

    await page.fill('input[type="email"]', 'admin@digitalmedia.id');
    await page.fill('input[type="password"]', 'password123');
    await page.screenshot({ path: path.join(__dirname, '..', 'result', '_dbg-04-filled.png') });

    await page.click('button[type="submit"]');
    await page.waitForTimeout(6000);
    console.log('URL after login:', page.url());
    await page.screenshot({ path: path.join(__dirname, '..', 'result', '_dbg-05-after-submit.png') });

    await browser.close();
})();
