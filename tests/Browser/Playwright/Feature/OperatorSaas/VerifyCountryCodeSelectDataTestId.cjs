
const BASE = require('../../support/baseUrl.cjs');
// Test new data-testid selectors for CountryCodeSelect (Katalon-friendly)
const { chromium } = require('playwright');


let pass = 0;
let fail = 0;
function check(name, ok, detail = '') {
    ok ? (pass++, console.log(`    ✓ ${name}${detail ? ' — ' + detail : ''}`))
        : (fail++, console.log(`    ✗ ${name}${detail ? ' — ' + detail : ''}`));
}

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 300 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();

    // Login
    console.log('[1] Login as superadmin@demo.test');
    await page.goto(`${BASE}/login-operator-saas`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.fill('input[type="email"]', 'superadmin@demo.test');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);

    // Goto profil-saya
    await page.goto(`${BASE}/operator-saas/profil-saya`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);

    // Verify new data-testid selectors
    console.log('\n[2] Verify stable data-testid selectors');
    const selectors = await page.evaluate(() => {
        return {
            trigger: !!document.querySelector('[data-testid="countrycode-trigger"]'),
            dropdownExistsInDOM: !!document.querySelector('[data-testid="countrycode-dropdown"]'),
            // Before click, dropdown is not in DOM (v-if)
        };
    });
    check('Trigger button punya data-testid="countrycode-trigger"', selectors.trigger);

    // Click trigger
    console.log('\n[3] Click trigger → dropdown opens');
    await page.click('[data-testid="countrycode-trigger"]');
    await page.waitForTimeout(500);

    const afterOpen = await page.evaluate(() => {
        const dropdown = document.querySelector('[data-testid="countrycode-dropdown"]');
        const panel = document.querySelector('[data-testid="countrycode-dropdown-panel"]');
        const search = document.querySelector('[data-testid="countrycode-search"]');
        return {
            dropdownFound: !!dropdown,
            panelFound: !!panel,
            searchFound: !!search,
            searchType: search?.getAttribute('type'),
            searchName: search?.getAttribute('name'),
            // Verify search is INSIDE dropdown (not outside)
            searchInsideDropdown: dropdown?.contains(search) ?? false,
        };
    });
    check('Dropdown rendered with data-testid="countrycode-dropdown"', afterOpen.dropdownFound);
    check('Dropdown panel with data-testid="countrycode-dropdown-panel"', afterOpen.panelFound);
    check('Search input with data-testid="countrycode-search" found', afterOpen.searchFound);
    check('Search input type=text (not readonly)', afterOpen.searchType === 'text');
    check('Search input name="countrycode-search"', afterOpen.searchName === 'countrycode-search');
    check('Search is INSIDE dropdown (correct nesting)', afterOpen.searchInsideDropdown);

    // Try Katalon-style xpath
    console.log('\n[4] Test Katalon-style selectors');
    const searchInput = page.locator('[data-testid="countrycode-search"]');
    await searchInput.fill('germany');
    await page.waitForTimeout(500);
    const germanyResult = await page.evaluate(() => {
        const dropdown = document.querySelector('[data-testid="countrycode-dropdown"]');
        const items = Array.from(dropdown?.querySelectorAll('button[data-highlighted]') || []);
        return { count: items.length, first: items[0]?.textContent.trim().slice(0, 60) };
    });
    check('Katalon can type "germany" → filter to 1 result', germanyResult.count === 1 && germanyResult.first?.includes('Germany'),
        `count: ${germanyResult.count}, first: ${germanyResult.first}`);

    // Print katalon-friendly xpath cheatsheet
    console.log('\n[5] Katalon xpath cheatsheet (NEW):');
    console.log('  - Trigger:  //*[@data-testid="countrycode-trigger"]');
    console.log('  - Search:   //*[@data-testid="countrycode-search"]');
    console.log('  - Dropdown: //*[@data-testid="countrycode-dropdown"]');
    console.log('  - Option:   //*[@data-testid="countrycode-dropdown"]//button[@data-highlighted]');

    console.log(`\n${'═'.repeat(50)}`);
    console.log(`SUMMARY: ${pass} pass, ${fail} fail`);
    console.log(`${'═'.repeat(50)}\n`);

    await browser.close();
    process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
