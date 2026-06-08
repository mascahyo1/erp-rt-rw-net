// Final verification: demo credentials work + CountryCodeSelect usable
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorSaas', 'DemoCredsFinal');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

const BASE = 'http://erp-rt-rw-net.test';

let pass = 0;
let fail = 0;

function check(name, ok, detail = '') {
    ok ? (pass++, console.log(`    ✓ ${name}${detail ? ' — ' + detail : ''}`))
        : (fail++, console.log(`    ✗ ${name}${detail ? ' — ' + detail : ''}`));
}

async function shot(page, name) {
    await page.screenshot({ path: path.join(RESULT_DIR, name), fullPage: false });
}

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 300 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();

    // Test 1: demo superadmin@demo.test / password123
    console.log('[1] Login as superadmin@demo.test / password123');
    await page.goto(`${BASE}/login-operator-saas`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.fill('input[type="email"]', 'superadmin@demo.test');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);
    const url1 = page.url();
    check('superadmin@demo.test login works', url1.includes('/operator-saas/') && !url1.includes('/login'),
        `URL: ${url1}`);
    await shot(page, '01-superadmin-demo.png');

    // Test 2: Verify CountryCodeSelect on profil-saya
    if (url1.includes('/operator-saas/')) {
        await page.goto(`${BASE}/operator-saas/profil-saya`, { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(2000);

        const phoneFields = await page.evaluate(() => {
            const telpLabel = Array.from(document.querySelectorAll('label')).find(l => l.textContent.trim() === 'Telepon');
            if (!telpLabel) return { hasLabel: false };
            const container = telpLabel.parentElement;
            const flagEl = container.querySelector('div.w-36 span.fi');
            const phoneInput = container.querySelector('input[placeholder*="812"]');
            return {
                hasLabel: true,
                hasFlag: !!flagEl,
                hasInput: !!phoneInput,
                flagBg: window.getComputedStyle(flagEl).backgroundImage.slice(0, 60),
                phoneValue: phoneInput?.value,
            };
        });
        check('Telepon label ada', phoneFields.hasLabel);
        check('Flag icon ada (CountryCodeSelect rendered)', phoneFields.hasFlag);
        check('Phone number input ada', phoneFields.hasInput);
        check('Flag CSS loaded (bukan gray)', !phoneFields.flagBg?.includes('data:image/svg') || phoneFields.flagBg?.includes('.svg'),
            `flagBg: ${phoneFields.flagBg}`);
        check('Phone number ter-populate (database value)', phoneFields.phoneValue?.length > 0,
            `value: ${phoneFields.phoneValue}`);

        // Test 3: Open dropdown, search, select, save
        const trigger = page.locator('div.w-36 button').first();
        await trigger.click();
        await page.waitForTimeout(500);
        await shot(page, '02-dropdown-open.png');

        const searchInput = page.locator('input[placeholder*="Pilih"]');
        await searchInput.fill('malaysia');
        await page.waitForTimeout(500);
        const malaysiaResult = await page.evaluate(() => {
            const dropdown = document.querySelector('[data-testid="countrycode-dropdown"]');
            if (!dropdown) return null;
            const items = Array.from(dropdown.querySelectorAll('button')).filter(b => b.querySelector('span.fi'));
            return { count: items.length, first: items[0]?.textContent.trim().slice(0, 60) };
        });
        check('Search "malaysia" works', malaysiaResult?.count === 1 && malaysiaResult.first?.includes('Malaysia'),
            `count: ${malaysiaResult?.count}, first: ${malaysiaResult?.first}`);
        await shot(page, '03-search-malaysia.png');
    }

    console.log(`\n${'═'.repeat(50)}`);
    console.log(`SUMMARY: ${pass} pass, ${fail} fail`);
    console.log(`${'═'.repeat(50)}\n`);

    await page.waitForTimeout(2000);
    await browser.close();
    process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
