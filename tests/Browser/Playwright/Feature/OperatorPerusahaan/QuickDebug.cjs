// Quick single-context debug
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
(async () => {
    const browser = await chromium.launch({ slowMo: 350, headless: false, args: ['--no-sandbox'] });
    const ctx = await browser.newContext({ viewport: { width: 1366, height: 850 } });
    const page = await ctx.newPage();

    await page.goto(BASE + '/login-perusahaan');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    const companyBtn = page.locator('button:has(.fa-building)').first();
    if (await companyBtn.count() > 0) {
        await companyBtn.click();
        await page.waitForTimeout(2000);
        await page.locator('input[placeholder*="Cari perusahaan"]').first().fill('Digital Media');
        await page.waitForTimeout(2000);
        await page.locator('text=CV Digital Media Nusantara').first().click();
        await page.waitForTimeout(2000);
    }
    await page.fill('input[type="email"]', 'admin@digitalmedia.id');
    await page.fill('input[type="password"]', 'password123');
    await page.waitForTimeout(500);
    await page.click('button[type="submit"]');
    await page.waitForTimeout(8000);
    console.log('after login:', page.url());

    await page.goto(BASE + '/operator-perusahaan/admin-role-perusahaan');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    await page.screenshot({ path: path.join(__dirname, '..', 'result', '_dbg2-list.png') });

    // Check page content
    const bodyText = await page.textContent('body');
    console.log('PAGE TEXT (first 500 chars):', bodyText.substring(0, 500));
    console.log('Contains Tambah:', bodyText.includes('Tambah'));
    console.log('Contains Import:', bodyText.includes('Import'));
    console.log('Contains Export:', bodyText.includes('Export'));
    console.log('Contains Template:', bodyText.includes('Template'));

    const tambahBtns = await page.$$('button:has-text("Tambah")');
    const importBtns = await page.$$('button:has-text("Import")');
    const exportBtns = await page.$$('button:has-text("Export")');
    console.log('Tombol Tambah count:', tambahBtns.length);
    console.log('Tombol Import count:', importBtns.length);
    console.log('Tombol Export count:', exportBtns.length);

    await browser.close();
})();
