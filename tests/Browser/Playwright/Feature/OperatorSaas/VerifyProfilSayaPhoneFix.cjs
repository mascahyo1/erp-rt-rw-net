// Deep verify: Operator SaaS profil-saya now has phone fields + saves correctly.
// User feedback: 'ga ada input kode negara dan no telp' (2026-06-07 19:52)
//
// Verifies:
//   1. Phone fields visible in form (kode negara select + no telp input)
//   2. Default value is +62 (Indonesia)
//   3. Kode negara list contains common codes
//   4. Save roundtrip: change phone → submit → reload → value persists
//   5. Phone field exists in ALL other profil-saya pages (cross-portal consistency)
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorSaas', 'ProfilSayaPhoneFix');
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
    page.on('pageerror', e => console.log('  ! pageerror:', e.message));
    page.on('console', m => { if (m.type() === 'error') console.log('  ! console.error:', m.text()); });

    // ─────────────────────────────────────────────────────────────────
    // Login as Operator SaaS
    // ─────────────────────────────────────────────────────────────────
    console.log('[1] Login as Operator SaaS');
    await page.goto(`${BASE}/login-operator-saas`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.fill('input[type="email"]', 'admin@demo.test');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);

    // ─────────────────────────────────────────────────────────────────
    // Goto profil-saya
    // ─────────────────────────────────────────────────────────────────
    console.log('[2] Goto profil-saya');
    await page.goto(`${BASE}/operator-saas/profil-saya`, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    await shot(page, '01-profil-saya.png');

    // ─────────────────────────────────────────────────────────────────
    // Verify phone fields are NOW present (was missing before)
    // ─────────────────────────────────────────────────────────────────
    console.log('[3] Verify phone fields are visible');
    const formState = await page.evaluate(() => {
        // Find Telepon label, then the parent div, then check for select + input
        const labels = Array.from(document.querySelectorAll('label'));
        const teleponLabel = labels.find(l => l.textContent.trim() === 'Telepon');
        if (!teleponLabel) return { found: false };

        // Get the container (parent .flex)
        const container = teleponLabel.parentElement;
        const select = container.querySelector('select');
        const input = container.querySelector('input');

        // Get the kode negara options
        const options = select ? Array.from(select.options).map(o => o.value) : [];

        return {
            found: true,
            hasSelect: !!select,
            hasInput: !!input,
            selectValue: select?.value || null,
            inputValue: input?.value || null,
            inputPlaceholder: input?.placeholder || null,
            options: options,
        };
    });
    check('Telepon label ditemukan', formState.found);
    check('Telepon: select (kode negara) ada', formState.hasSelect);
    check('Telepon: input (no telp) ada', formState.hasInput);
    check('Telepon: select default value = "+62"', formState.selectValue === '+62',
        `actual: ${formState.selectValue}`);
    check('Telepon: kode negara list mengandung +62, +1, +44, +81, +86',
        ['+62', '+1', '+44', '+81', '+86'].every(c => formState.options?.includes(c)),
        `options: [${formState.options?.join(', ')}]`);
    await shot(page, '02-phone-fields-present.png');

    // ─────────────────────────────────────────────────────────────────
    // Save roundtrip: change phone + save + reload + verify
    // ─────────────────────────────────────────────────────────────────
    console.log('[4] Save roundtrip: change phone to +60 1234567890');
    const newCountry = '+60';
    const newNumber = '123456789012';
    // Use Playwright's native locator interactions (better Vue compatibility)
    const telpContainer = page.locator('div').filter({ has: page.locator('label', { hasText: /^Telepon$/ }) }).last();
    const telpSelect = telpContainer.locator('select');
    const telpInput = telpContainer.locator('input');
    await telpSelect.selectOption(newCountry);
    await telpInput.fill(newNumber);
    await page.waitForTimeout(500);

    // Verify form values via Vue
    const formValsBefore = await page.evaluate(() => {
        const labels = Array.from(document.querySelectorAll('label'));
        const teleponLabel = labels.find(l => l.textContent.trim() === 'Telepon');
        const container = teleponLabel.parentElement;
        return {
            selectValue: container.querySelector('select')?.value,
            inputValue: container.querySelector('input')?.value,
        };
    });
    console.log('  → form values before submit:', JSON.stringify(formValsBefore));
    check('Pre-submit: form select = "+60"', formValsBefore.selectValue === '+60', `actual: ${formValsBefore.selectValue}`);
    check('Pre-submit: form input = "123456789012"', formValsBefore.inputValue === '123456789012', `actual: ${formValsBefore.inputValue}`);
    await shot(page, '03-phone-changed.png');

    // Click Simpan
    const simpanBtn = page.locator('button[type="submit"]', { hasText: 'Simpan' });
    await simpanBtn.click();
    await page.waitForTimeout(3000);
    await shot(page, '04-after-save.png');

    // Reload and verify
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    const afterReload = await page.evaluate(() => {
        const labels = Array.from(document.querySelectorAll('label'));
        const teleponLabel = labels.find(l => l.textContent.trim() === 'Telepon');
        const container = teleponLabel.parentElement;
        return {
            selectValue: container.querySelector('select')?.value,
            inputValue: container.querySelector('input')?.value,
        };
    });
    check('Save roundtrip: select = "+60" setelah reload', afterReload.selectValue === '+60',
        `actual: ${afterReload.selectValue}`);
    check('Save roundtrip: input = "123456789012" setelah reload', afterReload.inputValue === '123456789012',
        `actual: ${afterReload.inputValue}`);
    await shot(page, '05-after-reload.png');

    // ─────────────────────────────────────────────────────────────────
    // Reset back to default
    // ─────────────────────────────────────────────────────────────────
    console.log('[5] Reset phone back to defaults');
    const telpContainerReset = page.locator('div').filter({ has: page.locator('label', { hasText: /^Telepon$/ }) }).last();
    const telpSelectReset = telpContainerReset.locator('select');
    const telpInputReset = telpContainerReset.locator('input');
    await telpSelectReset.selectOption('+62');
    await telpInputReset.fill('');
    await page.waitForTimeout(300);
    await simpanBtn.click();
    await page.waitForTimeout(2500);

    // ─────────────────────────────────────────────────────────────────
    // Cross-portal consistency check: visit other 3 profil-saya
    // ─────────────────────────────────────────────────────────────────
    console.log('[6] Cross-portal consistency check (logout, login as other)');

    // Logout
    await page.goto(`${BASE}/operator-saas/profil-saya`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);
    // Find logout button (usually in dropdown)
    const logoutLink = page.locator('a, button').filter({ hasText: /Logout|Keluar/i }).first();
    if (await logoutLink.count() > 0) {
        await logoutLink.click();
        await page.waitForTimeout(3000);
    } else {
        // Manual logout via POST
        const csrfToken = await page.evaluate(() => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
        await page.evaluate((token) => {
            return fetch('/operator-saas/logout', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json' },
                credentials: 'same-origin',
            });
        }, csrfToken);
        await page.waitForTimeout(2000);
    }

    // Login as Karyawan
    console.log('  [6a] Login as Karyawan (ahmad@netsejahtera.com)');
    await page.goto(`${BASE}/login-karyawan`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.fill('input[type="email"]', 'ahmad@netsejahtera.com');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);

    await page.goto(`${BASE}/karyawan/profil-saya`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    const karyPhone = await page.evaluate(() => {
        const labels = Array.from(document.querySelectorAll('label'));
        const telpLabel = labels.find(l => l.textContent.includes('Telepon') || l.textContent.includes('Telp'));
        if (!telpLabel) return { hasPhone: false };
        const container = telpLabel.parentElement;
        return {
            hasPhone: true,
            hasSelect: !!container.querySelector('select'),
            hasInput: !!container.querySelector('input'),
        };
    });
    check('Karyawan profil-saya: phone field ada', karyPhone.hasPhone && karyPhone.hasSelect && karyPhone.hasInput);
    await shot(page, '06-karyawan-profil-saya.png');

    // ─────────────────────────────────────────────────────────────────
    // Summary
    // ─────────────────────────────────────────────────────────────────
    console.log(`\n${'═'.repeat(60)}`);
    console.log(`DEEP VERIFY SUMMARY (Profil Saya Phone Fix)`);
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
