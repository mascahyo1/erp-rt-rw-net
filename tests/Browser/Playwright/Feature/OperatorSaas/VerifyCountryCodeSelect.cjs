// DeepVerify CountryCodeSelect — flag + name + searchable dropdown component
// Source data: github.com/datasets/country-codes
// Flags: github.com/lipis/flag-icons
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorSaas', 'CountryCodeSelect');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

const BASE = 'http://erp-rt-rw-net.test';

let pass = 0;
let fail = 0;
const failures = [];

function check(name, condition, detail = '') {
    if (condition) {
        pass++;
        console.log(`    ✓ ${name}${detail ? ' — ' + detail : ''}`);
    } else {
        fail++;
        failures.push(name);
        console.log(`    ✗ ${name}${detail ? ' — ' + detail : ''}`);
    }
}

async function shot(page, name) {
    await page.screenshot({ path: path.join(RESULT_DIR, name), fullPage: false });
    console.log(`      → ${name}`);
}

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 350 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();
    const consoleErrors = [];
    page.on('pageerror', e => consoleErrors.push('pageerror: ' + e.message));
    page.on('console', m => { if (m.type() === 'error') consoleErrors.push('console.error: ' + m.text()); });

    // Login as Operator SaaS
    console.log('[1] Login as Operator SaaS');
    await page.goto(`${BASE}/login-operator-saas`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.fill('input[type="email"]', 'admin@demo.test');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);

    // Goto profil-saya
    console.log('[2] Goto Operator SaaS profil-saya');
    await page.goto(`${BASE}/operator-saas/profil-saya`, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await shot(page, '01-profil-saya-default.png');

    // ─────────────────────────────────────────────────────────────────
    // Verify CountryCodeSelect rendering
    // ─────────────────────────────────────────────────────────────────
    console.log('[3] Verify CountryCodeSelect is rendered');
    const initState = await page.evaluate(() => {
        const telpSection = Array.from(document.querySelectorAll('label')).find(l => l.textContent.trim() === 'Telepon');
        if (!telpSection) return { found: false };
        const container = telpSection.parentElement;
        // Component root is div.w-36, inside has div.relative > button
        const ccs = container.querySelector('div.w-36 div.relative button') || container.querySelector('div.w-36 button');
        if (!ccs) return { found: false };
        return {
            found: true,
            triggerText: ccs.textContent.trim(),
            hasFlagIcon: !!ccs.querySelector('span.fi'),
            flagClasses: ccs.querySelector('span.fi')?.className || null,
        };
    });
    check('CountryCodeSelect component rendered', initState.found);
    check('Trigger shows flag icon (lipis/flag-icons)', initState.hasFlagIcon);
    check('Flag classes include "fi fi-XX"', initState.flagClasses?.includes('fi fi-'),
        `flagClasses: ${initState.flagClasses}`);
    check('Default value shows "Indonesia" (name from country-codes data)',
        initState.triggerText?.includes('Indonesia'),
        `triggerText: "${initState.triggerText}"`);
    check('Default value shows +62 dial code', initState.triggerText?.includes('+62'));
    await shot(page, '02-ccs-default.png');

    // ─────────────────────────────────────────────────────────────────
    // Open dropdown + verify all options
    // ─────────────────────────────────────────────────────────────────
    console.log('[4] Open dropdown + verify options');
    const trigger = page.locator('div.w-36 button').first();
    await trigger.click();
    await page.waitForTimeout(500);
    await shot(page, '03-dropdown-open.png');

    // Verify search input visible + has 250 countries
    const dropdownState = await page.evaluate(() => {
        const searchInput = document.querySelector('input[placeholder*="Pilih"]');
        const items = document.querySelectorAll('[data-highlighted]');
        const visibleItems = Array.from(document.querySelectorAll('button[type="button"]')).filter(b => b.textContent.includes('+'));
        return {
            hasSearchInput: !!searchInput,
            itemCount: visibleItems.length,
            hasFirstItem: visibleItems[0]?.textContent.includes('+'),
            exampleItem: visibleItems[0]?.textContent.trim().slice(0, 80),
        };
    });
    check('Dropdown search input visible', dropdownState.hasSearchInput);
    check('Dropdown shows all countries (>=150)', dropdownState.itemCount >= 150, `count: ${dropdownState.itemCount}`);
    check('Each option shows dial code +', dropdownState.hasFirstItem);
    await shot(page, '04-dropdown-options.png');

    // ─────────────────────────────────────────────────────────────────
    // Search by country name
    // ─────────────────────────────────────────────────────────────────
    console.log('[5] Search "japan"');
    const searchInput = page.locator('input[placeholder*="Pilih"]');
    await searchInput.fill('japan');
    await page.waitForTimeout(500);
    const japanResults = await page.evaluate(() => {
        // Scope to inside dropdown panel only (fixed z-[60] container)
        const dropdown = document.querySelector('.fixed.z-\\[60\\]');
        if (!dropdown) return [];
        const items = Array.from(dropdown.querySelectorAll('button')).filter(b => b.querySelector('span.fi'));
        return items.map(i => i.textContent.trim().slice(0, 60));
    });
    check('Search "japan" filters to Japan only', japanResults.length === 1 && japanResults[0].includes('Japan'),
        `results: ${JSON.stringify(japanResults)}`);
    await shot(page, '05-search-japan.png');

    // ─────────────────────────────────────────────────────────────────
    // Search by dial code
    // ─────────────────────────────────────────────────────────────────
    console.log('[6] Search by dial code "44"');
    await searchInput.fill('44');
    await page.waitForTimeout(500);
    const dial44Results = await page.evaluate(() => {
        const dropdown = document.querySelector('.fixed.z-\\[60\\]');
        if (!dropdown) return [];
        const items = Array.from(dropdown.querySelectorAll('button')).filter(b => b.querySelector('span.fi') && (b.textContent.includes('+44') || b.textContent.includes('+449') || b.textContent.includes('+441')));
        return items.map(i => i.textContent.trim().slice(0, 60));
    });
    check('Search "44" finds UK (+44)', dial44Results.length > 0 && dial44Results[0].includes('United Kingdom'));
    await shot(page, '06-search-dial-44.png');

    // ─────────────────────────────────────────────────────────────────
    // Search "indonesia" + select (scope to dropdown)
    // ─────────────────────────────────────────────────────────────────
    console.log('[7] Search "indonesia" + select');
    await searchInput.fill('indonesia');
    await page.waitForTimeout(500);
    // Click using JS to avoid backdrop interception
    await page.evaluate(() => {
        const dropdown = document.querySelector('.fixed.z-\\[60\\]');
        if (!dropdown) return;
        const buttons = Array.from(dropdown.querySelectorAll('button')).filter(b => b.textContent.includes('Indonesia') && b.querySelector('span.fi'));
        if (buttons.length > 0) buttons[0].click();
    });
    await page.waitForTimeout(500);
    const afterSelect = await page.evaluate(() => {
        const telpSection = Array.from(document.querySelectorAll('label')).find(l => l.textContent.trim() === 'Telepon');
        const container = telpSection.parentElement;
        const ccs = container.querySelector('div.w-36 button') || container.querySelector('div.w-36 div button');
        return ccs?.textContent.trim();
    });
    check('After select Indonesia, trigger shows "Indonesia +62"', afterSelect?.includes('Indonesia') && afterSelect?.includes('+62'));
    await shot(page, '07-after-select-indonesia.png');

    // ─────────────────────────────────────────────────────────────────
    // Save roundtrip with new value
    // ─────────────────────────────────────────────────────────────────
    console.log('[8] Change to Japan + save roundtrip');
    await trigger.click();
    await page.waitForTimeout(500);
    await searchInput.fill('japan');
    await page.waitForTimeout(500);
    // Click via JS to avoid backdrop
    await page.evaluate(() => {
        const dropdown = document.querySelector('.fixed.z-\\[60\\]');
        if (!dropdown) return;
        const buttons = Array.from(dropdown.querySelectorAll('button')).filter(b => b.textContent.includes('Japan') && b.querySelector('span.fi'));
        if (buttons.length > 0) buttons[0].click();
    });
    await page.waitForTimeout(800);
    // Check dropdown is closed
    const dropdownStillOpen = await page.evaluate(() => !!document.querySelector('.fixed.z-\\[60\\]'));
    if (dropdownStillOpen) {
        console.log('  ! Dropdown still open, closing via ESC');
        await page.keyboard.press('Escape');
        await page.waitForTimeout(500);
    }

    // Find the no_telp input
    const noTelp = page.locator('input[placeholder*="812"]');
    await noTelp.fill('8012345678');
    await page.waitForTimeout(500);
    // Verify pre-submit
    const preSubmit = await page.evaluate(() => {
        const telpSection = Array.from(document.querySelectorAll('label')).find(l => l.textContent.trim() === 'Telepon');
        const container = telpSection?.parentElement;
        return {
            phone: container?.querySelector('input[placeholder*="812"]')?.value,
            countryTrigger: container?.querySelector('div.w-36 button')?.textContent.trim(),
        };
    });
    console.log('  → pre-submit state:', JSON.stringify(preSubmit));
    check('Pre-submit: phone = 8012345678', preSubmit.phone === '8012345678', `actual: ${preSubmit.phone}`);
    check('Pre-submit: country = Japan', preSubmit.countryTrigger?.includes('Japan') ?? false, `actual: ${preSubmit.countryTrigger}`);

    // Submit
    await page.locator('button[type="submit"]', { hasText: 'Simpan' }).click();
    await page.waitForTimeout(4000);
    // Check for validation errors
    const validationErrors = await page.evaluate(() => {
        const errEls = Array.from(document.querySelectorAll('.text-red-500'));
        return errEls.map(e => e.textContent.trim()).filter(t => t);
    });
    console.log('  → validation errors after save:', JSON.stringify(validationErrors));
    await shot(page, '08-after-save.png');

    // Reload + verify
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    const afterReload = await page.evaluate(() => {
        const telpSection = Array.from(document.querySelectorAll('label')).find(l => l.textContent.trim() === 'Telepon');
        const container = telpSection?.parentElement;
        const ccs = container?.querySelector('div.w-36 button') || container?.querySelector('div.w-36 div button');
        const phoneInput = container?.querySelector('input[placeholder*="812"]');
        return {
            country: ccs?.textContent.trim(),
            phone: phoneInput?.value,
        };
    });
    check('After save+reload: country = Japan', afterReload?.country?.includes('Japan') ?? false,
        `actual: ${afterReload?.country}`);
    check('After save+reload: phone = 8012345678', afterReload?.phone === '8012345678',
        `actual: ${afterReload?.phone}`);
    await shot(page, '09-after-reload-japan.png');

    // ─────────────────────────────────────────────────────────────────
    // Cross-portal consistency: Check other profil-saya pages
    // ─────────────────────────────────────────────────────────────────
    console.log('[10] Cross-portal: Karyawan profil-saya uses CountryCodeSelect');
    // Logout SaaS first
    await page.goto(`${BASE}/operator-saas/dashboard`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1000);
    // Login as Karyawan
    await page.goto(`${BASE}/login-karyawan`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.fill('input[type="email"]', 'ahmad@netsejahtera.com');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);

    await page.goto(`${BASE}/karyawan/profil-saya`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    const karyState = await page.evaluate(() => {
        // Karyawan profil-saya may be in view mode (not edit) — CountryCodeSelect only in edit form
        const telpLabel = Array.from(document.querySelectorAll('label')).find(l => l.textContent.trim() === 'Telepon');
        if (!telpLabel) return { found: false, reason: 'no Telepon label' };
        const container = telpLabel.parentElement;
        const ccs = container.querySelector('div.w-36 button') || container.querySelector('div.w-36 div button');
        if (ccs) return { hasCcs: true, text: ccs.textContent.trim() };
        // Check if edit mode is hidden (form not shown)
        const form = document.querySelector('form');
        return { hasCcs: false, reason: 'no CountryCodeSelect in view; form present=' + !!form, html: container.outerHTML.slice(0, 200) };
    });
    check('Karyawan profil-saya juga pakai CountryCodeSelect', karyState?.hasCcs,
        `actual: ${JSON.stringify(karyState)}`);
    await shot(page, '10-karyawan-ccs.png');

    // ─────────────────────────────────────────────────────────────────
    // Cleanup: reset phone back
    // ─────────────────────────────────────────────────────────────────
    console.log('[11] Cleanup: reset phone back to defaults');
    await page.goto(`${BASE}/operator-saas/profil-saya`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    await trigger.click();
    await page.waitForTimeout(500);
    await searchInput.fill('indonesia');
    await page.waitForTimeout(500);
    await page.evaluate(() => {
        const dropdown = document.querySelector('.fixed.z-\\[60\\]');
        if (!dropdown) return;
        const buttons = Array.from(dropdown.querySelectorAll('button')).filter(b => b.textContent.includes('Indonesia') && b.querySelector('span.fi'));
        if (buttons.length > 0) buttons[0].click();
    });
    await page.waitForTimeout(500);
    await page.locator('input[placeholder*="812"]').fill('82222222222');
    await page.waitForTimeout(300);
    await page.locator('button[type="submit"]', { hasText: 'Simpan' }).click();
    await page.waitForTimeout(2500);

    check('No JS errors selama test', consoleErrors.length === 0, consoleErrors.join('; '));

    console.log(`\n${'═'.repeat(60)}`);
    console.log(`DEEP VERIFY SUMMARY (CountryCodeSelect)`);
    console.log(`${'═'.repeat(60)}`);
    console.log(`Pass: ${pass}`);
    console.log(`Fail: ${fail}`);
    if (failures.length > 0) {
        console.log('\nFailures:');
        failures.forEach(f => console.log(`  - ${f}`));
    }
    console.log(`${'═'.repeat(60)}\n`);

    await page.waitForTimeout(2000);
    await browser.close();
    process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
