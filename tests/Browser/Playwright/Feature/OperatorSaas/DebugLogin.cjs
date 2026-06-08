// Reproduce user's exact issue: login with superadmin@demo.test in incognito
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorSaas', 'LoginDebug');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

const BASE = 'http://erp_rt_rw_net.test';

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 400 });
    // Use a fresh context to simulate incognito
    const ctx = await browser.newContext({
        viewport: { width: 1280, height: 800 },
        // Clear all cookies/storage
    });
    const page = await ctx.newPage();
    const consoleErrors = [];
    const requests = [];
    page.on('pageerror', e => consoleErrors.push('pageerror: ' + e.message));
    page.on('console', m => { if (m.type() === 'error') consoleErrors.push('console.error: ' + m.text()); });
    page.on('response', async res => {
        if (res.url().includes('/login') || res.url().includes('/operator-saas')) {
            requests.push(`${res.status()} ${res.request().method()} ${res.url()}`);
        }
    });

    console.log('[1] Navigate to /login-operator-saas');
    await page.goto(`${BASE}/login-operator-saas`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(RESULT_DIR, '01-login-page.png') });

    // Check if there's a session/cookie banner
    const pageInfo = await page.evaluate(() => {
        return {
            url: location.href,
            title: document.title,
            hasEmailInput: !!document.querySelector('input[type="email"]'),
            hasPasswordInput: !!document.querySelector('input[type="password"]'),
            bodyText: document.body.textContent.slice(0, 200),
        };
    });
    console.log('Page info:', JSON.stringify(pageInfo, null, 2));

    console.log('\n[2] Fill form with superadmin@demo.test / password123');
    await page.fill('input[type="email"]', 'superadmin@demo.test');
    await page.fill('input[type="password"]', 'password123');
    await page.screenshot({ path: path.join(RESULT_DIR, '02-form-filled.png') });

    console.log('\n[3] Click submit');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);
    await page.screenshot({ path: path.join(RESULT_DIR, '03-after-submit.png') });

    const result = await page.evaluate(() => {
        return {
            url: location.href,
            bodyText: document.body.textContent.slice(0, 500),
            hasErrorMsg: !!document.querySelector('.text-red-500, .text-red-700, [role="alert"]'),
            errorMsg: document.querySelector('.text-red-500, .text-red-700, [role="alert"]')?.textContent.trim() || null,
        };
    });
    console.log('\nResult:', JSON.stringify(result, null, 2));
    console.log('\nRequests:', requests);

    console.log('\nConsole errors:', consoleErrors);

    console.log('\nBrowser stays open 8s for visual inspection');
    await page.waitForTimeout(8000);

    await browser.close();
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
