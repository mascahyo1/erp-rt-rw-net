// Focused test: verify boolean edit modal shows value correctly + save roundtrip.
// Bug fix: previously the boolean value field was completely hidden in edit modal
// because editValueVisible defaulted to false (only kredenial has the masked fallback div).
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorSaas', 'KonfigurasiBooleanEdit');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

async function shot(page, name) {
    await page.screenshot({ path: path.join(RESULT_DIR, name), fullPage: false });
    console.log('  →', name);
}

async function setBooleanSelectValue(page, value) {
    // Trigger Vue's @change handler with the desired value
    await page.evaluate((val) => {
        const selects = Array.from(document.querySelectorAll('select'));
        const boolSelect = selects.find(s => {
            const opts = Array.from(s.options).map(o => o.value);
            return opts.includes('true') && opts.includes('false');
        });
        if (boolSelect) {
            // Use the native value setter so Vue sees the change
            const setter = Object.getOwnPropertyDescriptor(window.HTMLSelectElement.prototype, 'value').set;
            setter.call(boolSelect, val);
            boolSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }, value);
    await page.waitForTimeout(300);
}

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 400 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();
    page.on('pageerror', e => console.log('  ! pageerror:', e.message));
    page.on('console', m => { if (m.type() === 'error') console.log('  ! console.error:', m.text()); });

    console.log('[1] Login as Operator SaaS');
    await page.goto('http://erp-rt-rw-net.test/login-operator-saas', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    await page.fill('input[type="email"]', 'admin@demo.test');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);

    console.log('[2] Goto Konfigurasi + filter Boolean');
    await page.goto('http://erp-rt-rw-net.test/operator-saas/konfigurasi?per_page=10&type=boolean', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    await shot(page, '01-list-boolean.png');

    console.log('[3] Open Edit modal untuk default_auto_compress_file_upload');
    const editBtn = page.locator('tr', { hasText: 'default_auto_compress_file_upload' }).locator('button[title="Edit"]');
    await editBtn.click();
    await page.waitForTimeout(1000);
    await shot(page, '02-edit-modal-open.png');

    // Verify the value field is visible and shows the current value
    const initial = await page.evaluate(() => {
        const sel = Array.from(document.querySelectorAll('select')).find(s => {
            const opts = Array.from(s.options).map(o => o.value);
            return opts.includes('true') && opts.includes('false');
        });
        return sel ? { value: sel.value, visible: sel.offsetParent !== null } : { value: null, visible: false };
    });
    console.log('  → initial boolean state:', JSON.stringify(initial));

    console.log('[4] Change value to false via Vue + Save');
    // Use page.locator().selectOption() which triggers all proper events for Vue
    const boolSelectLocator = page.locator('select').filter({ has: page.locator('option[value="true"]') }).filter({ has: page.locator('option[value="false"]') });
    await boolSelectLocator.selectOption('false');
    await page.waitForTimeout(500);
    await shot(page, '03-set-to-false.png');

    // Verify form.value is now "false" before submitting
    const beforeSubmit = await page.evaluate(() => {
        const sel = Array.from(document.querySelectorAll('select')).find(s => {
            const opts = Array.from(s.options).map(o => o.value);
            return opts.includes('true') && opts.includes('false');
        });
        return sel ? sel.value : null;
    });
    console.log('  → before submit, select value:', beforeSubmit);

    // Click the Update button — use exact text and a more specific selector
    const submitBtn = page.locator('button[type="submit"]', { hasText: 'Update' });
    console.log('  → submit button count:', await submitBtn.count());
    await submitBtn.first().click();
    await page.waitForTimeout(4000);  // wait for Inertia roundtrip + redirect
    await shot(page, '04-after-save.png');

    console.log('[5] Reload + verify value in DB is "false" via edit modal');
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    await shot(page, '05-reload.png');

    const editBtn2 = page.locator('tr', { hasText: 'default_auto_compress_file_upload' }).locator('button[title="Edit"]');
    await editBtn2.click();
    await page.waitForTimeout(1000);
    await shot(page, '06-reopen-edit.png');

    const afterReload = await page.evaluate(() => {
        const sel = Array.from(document.querySelectorAll('select')).find(s => {
            const opts = Array.from(s.options).map(o => o.value);
            return opts.includes('true') && opts.includes('false');
        });
        return sel ? sel.value : null;
    });
    console.log('  → after reload, select value:', afterReload);

    console.log('[6] Reset back to true');
    const boolSelectLocator2 = page.locator('select').filter({ has: page.locator('option[value="true"]') }).filter({ has: page.locator('option[value="false"]') });
    await boolSelectLocator2.selectOption('true');
    await page.waitForTimeout(500);
    const submitBtn2 = page.locator('button[type="submit"]', { hasText: 'Update' });
    await submitBtn2.first().click();
    await page.waitForTimeout(4000);

    // Verify final state — reopen the edit modal to read the saved value
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    const editBtn3 = page.locator('tr', { hasText: 'default_auto_compress_file_upload' }).locator('button[title="Edit"]');
    await editBtn3.click();
    await page.waitForTimeout(1000);
    const final = await page.evaluate(() => {
        const sel = Array.from(document.querySelectorAll('select')).find(s => {
            const opts = Array.from(s.options).map(o => o.value);
            return opts.includes('true') && opts.includes('false');
        });
        return sel ? sel.value : null;
    });
    console.log('  → final state after reopen:', final);
    await shot(page, '08-final-true.png');

    console.log('Done.');
    await page.waitForTimeout(2000);
    await browser.close();
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
