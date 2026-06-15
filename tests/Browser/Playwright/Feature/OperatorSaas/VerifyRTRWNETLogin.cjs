// Verify: ProductionSeeder user admin@rtrwnet.id can login + access profil-saya
// + CountryCodeSelect works.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorSaas', 'RTRWNETLoginVerify');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });


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
    const browser = await chromium.launch({ headless: false, slowMo: 400 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();
    const consoleErrors = [];
    page.on('pageerror', e => consoleErrors.push('pageerror: ' + e.message));
    page.on('console', m => { if (m.type() === 'error') consoleErrors.push('console.error: ' + m.text()); });

    // Test 1: Login with PASSWORD123 (user's actual input — should FAIL)
    console.log('\n[1] Login with admin@rtrwnet.id / password123 (user input)');
    await page.goto(`${BASE}/login-operator-saas`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.fill('input[type="email"]', 'admin@rtrwnet.id');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);
    const urlAfterBadLogin = page.url();
    const isStillOnLogin = urlAfterBadLogin.includes('/login');
    const errorOnLogin = await page.evaluate(() => {
        const err = document.querySelector('.text-red-500, .text-red-700, [role="alert"]');
        return err ? err.textContent.trim() : null;
    });
    check('Login with password123 GAGAL (expected — bukan password produksi)', isStillOnLogin,
        `URL: ${urlAfterBadLogin}, error: ${errorOnLogin}`);
    await shot(page, '01-login-wrong-password.png');

    // Test 2: Login with correct prod password
    console.log('\n[2] Login with admin@rtrwnet.id / P@ssw0rd!2026 (correct prod)');
    await page.goto(`${BASE}/login-operator-saas`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.fill('input[type="email"]', 'admin@rtrwnet.id');
    await page.fill('input[type="password"]', 'P@ssw0rd!2026');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);
    const urlAfterGoodLogin = page.url();
    const isOnDashboard = urlAfterGoodLogin.includes('/operator-saas/dashboard') || !urlAfterGoodLogin.includes('/login');
    check('Login dengan P@ssw0rd!2026 BERHASIL (redirect ke dashboard)', isOnDashboard,
        `URL: ${urlAfterGoodLogin}`);
    await shot(page, '02-login-success.png');

    // Test 3: Goto profil-saya
    console.log('\n[3] Goto /operator-saas/profil-saya');
    await page.goto(`${BASE}/operator-saas/profil-saya`, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await shot(page, '03-profil-saya.png');

    // Test 4: Verify country code field shows real flag (not gray)
    const flagState = await page.evaluate(() => {
        const telpLabel = Array.from(document.querySelectorAll('label')).find(l => l.textContent.trim() === 'Telepon');
        if (!telpLabel) return { found: false };
        const container = telpLabel.parentElement;
        const flagEl = container.querySelector('div.w-36 span.fi');
        if (!flagEl) return { found: false, reason: 'no flag element' };
        const computed = window.getComputedStyle(flagEl);
        return {
            found: true,
            className: flagEl.className,
            backgroundImage: computed.backgroundImage.slice(0, 100),
            inlineStyle: flagEl.getAttribute('style'),
        };
    });
    check('CountryCodeSelect rendered (Operator SaaS profil-saya)', flagState.found);
    check('Flag CSS loaded (bukan gray placeholder)',
        flagState.backgroundImage?.includes('.svg') && !flagState.backgroundImage?.includes('fill=%22%23eee'),
        `bg: ${flagState.backgroundImage?.slice(0, 80)}, inlineStyle: ${flagState.inlineStyle}`);

    // Test 5: Open dropdown + verify search works
    console.log('\n[5] Test search functionality');
    const trigger = page.locator('div.w-36 button').first();
    await trigger.click();
    await page.waitForTimeout(500);
    await shot(page, '04-dropdown-open.png');

    const searchInput = page.locator('input[placeholder*="Pilih"]');
    await searchInput.fill('singapore');
    await page.waitForTimeout(500);
    const searchResult = await page.evaluate(() => {
        const dropdown = document.querySelector('[data-testid="countrycode-dropdown"]');
        if (!dropdown) return { count: 0 };
        const items = Array.from(dropdown.querySelectorAll('button')).filter(b => b.querySelector('span.fi'));
        return { count: items.length, first: items[0]?.textContent.trim().slice(0, 60) };
    });
    check('Search "singapore" works', searchResult.count === 1 && searchResult.first?.includes('Singapore'),
        `count: ${searchResult.count}, first: ${searchResult.first}`);
    await shot(page, '05-search-singapore.png');

    check('No JS errors selama test', consoleErrors.length === 0, consoleErrors.join('; '));

    console.log(`\n${'═'.repeat(60)}`);
    console.log(`SUMMARY (admin@rtrwnet.id / P@ssw0rd!2026 Login Test)`);
    console.log(`${'═'.repeat(60)}`);
    console.log(`Pass: ${pass}`);
    console.log(`Fail: ${fail}`);
    if (failures.length > 0) {
        console.log('\nFailures:');
        failures.forEach(f => console.log(`  - ${f}`));
    }
    console.log(`${'═'.repeat(60)}\n`);

    await page.waitForTimeout(3000);
    await browser.close();
    process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
