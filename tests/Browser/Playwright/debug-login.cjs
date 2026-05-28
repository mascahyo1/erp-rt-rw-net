const { chromium } = require('playwright');

async function test() {
    const browser = await chromium.launch({ headless: false });
    const context = await browser.newContext({ viewport: { width: 1280, height: 720 } });
    const page = await context.newPage();

    console.log('1. Buka login page...');
    await page.goto('http://erp-rt-rw-net.test/login-perusahaan');
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/result/debug-login-01.png' });
    console.log('   Screenshot: debug-login-01.png');

    console.log('2. Click company button...');
    const companyBtn = page.locator('button:has(.fa-building)').first();
    await companyBtn.click();
    await page.waitForTimeout(1000);
    await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/result/debug-login-02-company-modal.png' });
    console.log('   Screenshot: debug-login-02.png');

    console.log('3. Select company...');
    const firstCompany = page.locator('button:has-text("CV Digital Media Nusantara")').first();
    await firstCompany.click();
    await page.waitForTimeout(800);
    await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/result/debug-login-03-after-company.png' });
    console.log('   Screenshot: debug-login-03.png');

    console.log('4. Fill credentials...');
    await page.fill('input[type="email"]', 'rbac.full@rtrwnet.id');
    await page.fill('input[type="password"]', 'password');
    await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/result/debug-login-04-form-filled.png' });
    console.log('   Screenshot: debug-login-04.png');

    console.log('5. Submit login...');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(10000);
    await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/result/debug-login-05-after-submit.png' });
    console.log('   Screenshot: debug-login-05.png');
    console.log('   URL:', page.url());

    if (page.url().includes('operator-perusahaan')) {
        console.log('   LOGIN BERHASIL!');
    } else if (page.url().includes('login')) {
        console.log('   STILL ON LOGIN PAGE');
        const body = await page.textContent('body');
        console.log('   Body preview:', body.substring(0, 500));
    } else {
        console.log('   URL UNKNOWN');
    }

    await browser.close();
}

test().catch(e => {
    console.error('Error:', e.message);
    process.exit(1);
});