// Focused investigation: Operator SaaS profil-saya
// 1. Are flags gray? (flag-icons CSS loading?)
// 2. Is search working?
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorSaas', 'CCSDebug');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });


async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 400 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();
    const consoleErrors = [];
    const networkErrors = [];
    page.on('pageerror', e => consoleErrors.push('pageerror: ' + e.message));
    page.on('console', m => { if (m.type() === 'error') consoleErrors.push('console.error: ' + m.text()); });
    page.on('requestfailed', req => networkErrors.push(`${req.failure()?.errorText} ${req.url()}`));
    page.on('response', res => {
        if (res.status() >= 400) {
            networkErrors.push(`${res.status()} ${res.url()}`);
        }
    });

    await page.goto(`${BASE}/login-operator-saas`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.fill('input[type="email"]', 'admin@demo.test');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);

    await page.goto(`${BASE}/operator-saas/profil-saya`, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    console.log('=== STEP 1: Check flag rendering ===');
    await page.screenshot({ path: path.join(RESULT_DIR, '01-profil-saya.png'), fullPage: false });

    const flagInfo = await page.evaluate(() => {
        const telpLabel = Array.from(document.querySelectorAll('label')).find(l => l.textContent.trim() === 'Telepon');
        if (!telpLabel) return { error: 'no Telepon label' };
        const container = telpLabel.parentElement;
        const flagEl = container.querySelector('div.w-36 span.fi');
        if (!flagEl) return { error: 'no flag element' };
        const rect = flagEl.getBoundingClientRect();
        const computed = window.getComputedStyle(flagEl);
        return {
            className: flagEl.className,
            innerHTML: flagEl.innerHTML.slice(0, 200),
            outerHTML: flagEl.outerHTML.slice(0, 300),
            backgroundImage: computed.backgroundImage,
            backgroundColor: computed.backgroundColor,
            width: rect.width,
            height: rect.height,
            inlineStyle: flagEl.getAttribute('style'),
        };
    });
    console.log('Flag info:', JSON.stringify(flagInfo, null, 2));

    console.log('\n=== STEP 2: Check flag-icons CSS loaded ===');
    const cssLoaded = await page.evaluate(() => {
        // Check if flag-icons CSS is in the stylesheets
        const sheets = Array.from(document.styleSheets);
        let flagIconsFound = false;
        let totalRules = 0;
        for (const sheet of sheets) {
            try {
                if (sheet.cssRules) {
                    totalRules += sheet.cssRules.length;
                    for (const rule of sheet.cssRules) {
                        if (rule.cssText && rule.cssText.includes('fi-id') || rule.cssText.includes('fi ')) {
                            flagIconsFound = true;
                            break;
                        }
                    }
                }
            } catch (e) { /* CORS */ }
            if (flagIconsFound) break;
        }
        // Check if a specific fi-id has background-image set
        const testEl = document.createElement('span');
        testEl.className = 'fi fi-id';
        document.body.appendChild(testEl);
        const testComputed = window.getComputedStyle(testEl);
        const testInfo = {
            backgroundImage: testComputed.backgroundImage.slice(0, 200),
            backgroundSize: testComputed.backgroundSize,
            width: testComputed.width,
            display: testComputed.display,
        };
        testEl.remove();
        return { flagIconsFound, totalRules, testEl: testInfo };
    });
    console.log('CSS info:', JSON.stringify(cssLoaded, null, 2));

    console.log('\n=== STEP 3: Check search functionality ===');
    // Open the dropdown
    const trigger = page.locator('div.w-36 button').first();
    await trigger.click();
    await page.waitForTimeout(500);
    await page.screenshot({ path: path.join(RESULT_DIR, '02-dropdown-open.png'), fullPage: false });

    // Check if search input is focusable and v-model bound
    const searchSetup = await page.evaluate(() => {
        const searchInput = document.querySelector('input[placeholder*="Pilih"]');
        if (!searchInput) return { error: 'no search input' };
        return {
            placeholder: searchInput.placeholder,
            value: searchInput.value,
            visible: searchInput.offsetParent !== null,
            dataAttrs: Object.fromEntries(Array.from(searchInput.attributes).filter(a => a.name.startsWith('data-')).map(a => [a.name, a.value])),
        };
    });
    console.log('Search input:', JSON.stringify(searchSetup, null, 2));

    // Type "japan" and check results
    const searchInput = page.locator('input[placeholder*="Pilih"]');
    await searchInput.click();
    await page.waitForTimeout(200);
    await searchInput.fill('japan');
    await page.waitForTimeout(500);
    await page.screenshot({ path: path.join(RESULT_DIR, '03-search-japan.png'), fullPage: false });

    const afterSearch = await page.evaluate(() => {
        const dropdown = document.querySelector('[data-testid="countrycode-dropdown"]');
        if (!dropdown) return { error: 'no dropdown' };
        const buttons = Array.from(dropdown.querySelectorAll('button')).filter(b => b.querySelector('span.fi'));
        return {
            searchValue: document.querySelector('input[placeholder*="Pilih"]')?.value,
            itemsCount: buttons.length,
            itemsText: buttons.map(b => b.textContent.trim().slice(0, 50)),
        };
    });
    console.log('After typing "japan":', JSON.stringify(afterSearch, null, 2));

    console.log('\n=== STEP 4: Console / Network errors ===');
    console.log('Console errors:', consoleErrors);
    console.log('Network errors:', networkErrors);

    console.log('\n=== Browser stays open for 10s for visual inspection ===');
    await page.waitForTimeout(10000);

    await browser.close();
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
