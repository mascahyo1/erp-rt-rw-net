// Quick visual test for Perusahaan logo feature
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
    const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
    const ctx = await browser.newContext({ viewport: { width: 1366, height: 850 } });
    const page = await ctx.newPage();

    // Login as operator-saas super admin
    await page.goto('http://erp-rt-rw-net.test/login-operator-saas');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    await page.fill('input[type="email"]', 'superadmin@demo.test');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);

    // Force dark mode
    await page.emulateMedia({ colorScheme: 'dark' });

    // Go to Perusahaan
    await page.goto('http://erp-rt-rw-net.test/operator-saas/perusahaan');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/Feature/result/_view-saas-list.png' });

    // Click Tambah
    const tambahBtn = page.locator('button:has-text("Tambah")').first();
    await tambahBtn.click();
    await page.waitForTimeout(1500);
    await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/Feature/result/_view-saas-create.png' });

    // Close
    const close = page.locator('button:has-text("Batal")').first();
    if (await close.count() > 0) await close.click();
    await page.waitForTimeout(500);

    // Open Edit modal
    const editBtn = page.locator('button[title="Edit"]').first();
    if (await editBtn.count() > 0) {
        await editBtn.click();
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/Feature/result/_view-saas-edit.png' });
        const close2 = page.locator('button:has-text("Batal")').first();
        if (await close2.count() > 0) await close2.click();
        await page.waitForTimeout(500);
    }

    // Open Detail
    const detailBtn = page.locator('button[title="Detail"]').first();
    if (await detailBtn.count() > 0) {
        await detailBtn.click();
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/Feature/result/_view-saas-detail.png' });
    }

    // Also test Perusahaan Saya
    await page.context().clearCookies();
    await page.goto('http://erp-rt-rw-net.test/login-perusahaan');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    const cb = page.locator('button:has(.fa-building)').first();
    if (await cb.count() > 0) {
        await cb.click();
        await page.waitForTimeout(1500);
        await page.locator('input[placeholder*="Cari perusahaan"]').first().fill('Digital Media');
        await page.waitForTimeout(1500);
        await page.locator('text=CV Digital Media Nusantara').first().click();
        await page.waitForTimeout(1500);
    }
    await page.fill('input[type="email"]', 'admin@digitalmedia.id');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(6000);

    await page.goto('http://erp-rt-rw-net.test/operator-perusahaan/perusahaan-saya');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/Feature/result/_view-saya-list.png' });

    // Click Edit
    const editSaya = page.locator('button:has-text("Edit")').first();
    if (await editSaya.count() > 0) {
        await editSaya.click();
        await page.waitForTimeout(1500);
        await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/Feature/result/_view-saya-edit.png' });
    }

    await browser.close();
    console.log('Done. Screenshots saved.');
})();
