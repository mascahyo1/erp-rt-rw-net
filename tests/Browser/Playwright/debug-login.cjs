const { chromium } = require('playwright');

async function test() {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();

    console.log('1. Buka login page...');
    await page.goto('http://erp-rt-rw-net.test/login-perusahaan');
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/result/debug-login.png' });
    console.log('   Screenshot: debug-login.png');

    console.log('2. Fill credentials...');
    await page.fill('input[type="email"]', 'test@playwright.dev');
    await page.fill('input[type="password"]', 'password123');
    await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/result/debug-filled.png' });
    console.log('   Screenshot: debug-filled.png');

    console.log('3. Submit login...');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);
    await page.screenshot({ path: 'C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/result/debug-after.png' });
    console.log('   Screenshot: debug-after.png');
    console.log('   URL:', page.url());

    const body = await page.textContent('body');
    if (body.includes('dashboard')) {
        console.log('   LOGIN BERHASIL!');
    } else if (body.includes('credentials')) {
        console.log('   LOGIN GAGAL: credentials mismatch');
    } else if (body.includes('403')) {
        console.log('   ACCESS DENIED (403)');
    } else {
        console.log('   STATUS TIDAK JELAS');
        console.log('   Body preview:', body.substring(0, 300));
    }

    await browser.close();
}

test().catch(e => {
    console.error('Error:', e.message);
    process.exit(1);
});