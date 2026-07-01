/**
 * E2E Test: Reproduce Turnstile "stuck" issue after logout + re-login.
 *
 * User flow (reported):
 *   1. Login berhasil → /operator-perusahaan/dashboard
 *   2. Click logout (full nav via Inertia) → /login-perusahaan
 *   3. Try login lagi → tombol stuck "Tunggu verifikasi captcha..."
 *
 * Verifies that form['cf-turnstile-response'] is empty even though widget
 * shows "Success!" badge (test key auto-solved).
 */

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE = 'http://erp-rt-rw-net.test';
const RESULT = path.join(__dirname, 'DayXLoginPerusahaanTurnstileRepro');
if (!fs.existsSync(RESULT)) fs.mkdirSync(RESULT, { recursive: true });

const CRED = { email: 'admin@netsejahtera.com', password: 'password123' };
const COMPANY_NAME = 'Net Sejahtera';

async function snap(page, name) {
    await page.screenshot({ path: path.join(RESULT, name + '.png'), fullPage: false });
    console.log('  snap:', name);
}

(async () => {
    console.log('▶ Test: Turnstile stuck after logout + re-login');
    const browser = await chromium.launch({ headless: false, slowMo: 400 });
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await ctx.newPage();
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error' || msg.type() === 'warning') {
            const txt = msg.text().slice(0, 200);
            console.log(`  [${msg.type()}]`, txt);
            consoleErrors.push(txt);
        }
    });
    page.on('pageerror', err => console.log('  [page-error]', err.message.slice(0, 200)));

    try {
        // 1. Login pertama
        console.log('1. First login (initial)');
        await page.goto(BASE + '/login-perusahaan', { waitUntil: 'load' });
        await page.waitForTimeout(3000);
        await snap(page, '01-initial-login');

        // Input email + password
        await page.fill('input[type="email"]', CRED.email);
        await page.fill('input[type="password"]', CRED.password);
        await page.waitForTimeout(500);

        // Tunggu Turnstile widget (test key auto-solve instant)
        console.log('2. Wait for Turnstile widget to render + auto-solve');
        await page.waitForSelector('.cf-turnstile', { timeout: 10000 });
        await page.waitForTimeout(5000); // test key auto-solve but UI update async
        await snap(page, '02-turnstile-solved');

        // Check form data
        const initialFormState = await page.evaluate(() => {
            // useForm not directly accessible — check button state instead
            const btn = document.querySelector('form button[type="submit"]');
            return { btnText: btn?.textContent.trim(), btnDisabled: btn?.disabled };
        });
        console.log('  Initial btn state:', JSON.stringify(initialFormState));

        // Click submit (pakai company picker)
        const hasCompany = await page.evaluate(() => !!Array.from(document.querySelectorAll('button[type="button"]')).find(x => x.textContent.includes('Cari perusahaan')));
        if (hasCompany) {
            await page.evaluate(() => Array.from(document.querySelectorAll('button[type="button"]')).find(x => x.textContent.includes('Cari perusahaan'))?.click());
            await page.waitForTimeout(2500);
            await page.fill('input[placeholder*="Cari perusahaan"]', COMPANY_NAME);
            await page.waitForTimeout(3500);
            await page.evaluate(() => document.querySelector('[data-testid^="company-item-"]')?.click());
            await page.waitForTimeout(500);
        }

        await page.click('form button[type="submit"]');
        await page.waitForTimeout(5000);
        const onDashboard = page.url().includes('/operator-perusahaan/dashboard');
        console.log('  After login URL:', page.url(), '| onDashboard:', onDashboard);
        await snap(page, '03-dashboard');
        if (!onDashboard) { throw new Error('Login 1 failed'); }

        // 2. Logout (via full nav click)
        console.log('3. Logout via Inertia nav (click)');
        // Buka profile dropdown (click user menu trigger) — selector data-testid
        const profileBtn = page.locator('[data-testid="btn-profile-dropdown"]');
        if (await profileBtn.count() > 0) await profileBtn.click();
        await page.waitForTimeout(500);
        await snap(page, '04a-profile-open');
        // Click logout button
        const logoutLink = page.locator('[data-testid="btn-logout"]');
        if (await logoutLink.count() === 0) {
            console.log('  ✗ data-testid="btn-logout" not found, falling back to direct POST');
            const csrf = await page.evaluate(() => document.querySelector('meta[name="csrf-token"]')?.content || '');
            await page.evaluate((token) => fetch('/logout-perusahaan', { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' } }), csrf);
        } else {
            await logoutLink.click();
        }
        await page.waitForTimeout(3000);
        await snap(page, '04-after-logout');
        console.log('  After logout URL:', page.url());

        // 3. Try login lagi (full nav, sama persis kayak user)
        console.log('4. Second login attempt (NAV via Masuk dropdown → Perusahaan link)');
        // Klik tombol "Masuk" di navbar (dropdown trigger)
        await page.click('[data-testid="btn-masuk-dropdown"]');
        await page.waitForTimeout(500);
        await snap(page, '04b-masuk-dropdown-open');
        // Klik link "Perusahaan" di dropdown
        await page.click('[data-testid="link-login-perusahaan"]');
        await page.waitForTimeout(3000);
        await snap(page, '05a-second-login-page-loaded');

        // Check current URL
        const urlAfterNav = page.url();
        console.log('  After Masuk → Perusahaan nav, URL:', urlAfterNav);

        // Wait for Turnstile widget to render
        await page.waitForSelector('[data-testid="cf-turnstile-widget"]', { timeout: 10000 });
        await page.waitForTimeout(5000); // test key auto-solve but UI update async
        await snap(page, '05-second-login-page');

        // Check widget state
        const widgetState = await page.evaluate(() => {
            const widget = document.querySelector('[data-testid="cf-turnstile-widget"]');
            if (!widget) return { found: false };
            const iframe = widget.querySelector('iframe');
            const text = widget.textContent?.trim().slice(0, 100);
            return { found: true, hasIframe: !!iframe, text, hasCallback: !!widget.getAttribute('data-callback') };
        });
        console.log('  Widget state:', JSON.stringify(widgetState));

        // Check button + form state
        const btnState = await page.evaluate(() => {
            const btn = document.querySelector('[data-testid="btn-login-submit"]') || document.querySelector('form button[type="submit"]');
            return {
                text: btn?.textContent?.trim(),
                disabled: btn?.disabled,
            };
        });
        console.log('  Button state:', JSON.stringify(btnState));

        // Fill email + password
        await page.locator('input[type="email"]').first().fill(CRED.email);
        await page.locator('input[type="password"]').first().fill(CRED.password);
        await page.waitForTimeout(500);

        // Wait for Turnstile solve (test key auto-solves)
        await page.waitForTimeout(3000);

        const btnState2 = await page.evaluate(() => {
            const btn = document.querySelector('[data-testid="btn-login-submit"]') || document.querySelector('form button[type="submit"]');
            return { text: btn?.textContent?.trim(), disabled: btn?.disabled };
        });
        console.log('  After fill + wait, Button state:', JSON.stringify(btnState2));

        await snap(page, '06-second-login-filled');

        if (btnState2.text?.includes('Tunggu')) {
            console.log('✗ BUG REPRODUCED: Button stuck at "Tunggu verifikasi captcha..."');
            console.log('  Console errors captured:', consoleErrors.length);
            for (const err of consoleErrors) console.log('   -', err);
        } else {
            console.log('✓ Button enabled — bug not reproduced');
        }

    } catch (e) {
        console.log('✗ EXCEPTION:', e.message);
        await snap(page, '99-exception');
    } finally {
        await browser.close();
    }
})();
