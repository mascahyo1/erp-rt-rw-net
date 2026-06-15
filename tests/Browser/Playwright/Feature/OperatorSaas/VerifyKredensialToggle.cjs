// Verify kredensial toggle behavior in Tambah (create) modal.
// New pattern: input element stays, only `type` attribute toggles between "password" and "text".
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorSaas', 'KonfigurasiKredensialToggle');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

async function shot(page, name) {
    await page.screenshot({ path: path.join(RESULT_DIR, name), fullPage: false });
    console.log('  →', name);
}

async function getKredInputState(page) {
    return page.evaluate(() => {
        // Find the input with kredensial placeholder
        const inputs = Array.from(document.querySelectorAll('input'));
        const kredInput = inputs.find(i => i.placeholder && i.placeholder.includes('API key'));
        if (!kredInput) return null;
        const eyeBtn = Array.from(document.querySelectorAll('button')).find(b => {
            const t = b.getAttribute('title') || '';
            return t.includes('value') || t.includes('Sembunyikan') || t.includes('Tampilkan');
        });
        return {
            type: kredInput.type,
            value: kredInput.value,
            placeholder: kredInput.placeholder,
            eyeTitle: eyeBtn?.getAttribute('title') || null,
            eyeIconClass: eyeBtn?.querySelector('i')?.className || null,
        };
    });
}

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 500 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();
    page.on('pageerror', e => console.log('  ! pageerror:', e.message));

    console.log('[1] Login as Operator SaaS');
    await page.goto(BASE + '/login-operator-saas', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    await page.fill('input[type="email"]', 'admin@demo.test');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);

    console.log('[2] Goto Konfigurasi');
    await page.goto(BASE + '/operator-saas/konfigurasi?per_page=10', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);

    console.log('[3] Open Tambah modal');
    await page.click('button:has-text("Tambah")');
    await page.waitForTimeout(1000);

    console.log('[4] Switch type to kredensial');
    const typeSelect = page.locator('select').filter({ has: page.locator('option[value="kredensial"]') });
    await typeSelect.selectOption('kredensial');
    await page.waitForTimeout(800);
    await shot(page, '01-kredensial-default.png');
    const s1 = await getKredInputState(page);
    console.log('  → default state (just switched to kredensial):', JSON.stringify(s1));

    console.log('[5] Type value "sk_live_abc123"');
    const kredInput = page.locator('input[placeholder*="API key"]');
    await kredInput.fill('sk_live_abc123');
    await page.waitForTimeout(500);
    await shot(page, '02-kredensial-typed.png');
    const s2 = await getKredInputState(page);
    console.log('  → state after typing:', JSON.stringify(s2));

    console.log('[6] Click eye toggle to reveal as text');
    const eyeBtn = page.locator('button[title*="value"], button[title*="Sembunyikan"], button[title*="Tampilkan"]');
    await eyeBtn.first().click();
    await page.waitForTimeout(800);
    await shot(page, '03-kredensial-revealed.png');
    const s3 = await getKredInputState(page);
    console.log('  → state after eye click (reveal):', JSON.stringify(s3));

    console.log('[7] Click eye toggle to mask as password again');
    await eyeBtn.first().click();
    await page.waitForTimeout(800);
    await shot(page, '04-kredensial-masked-again.png');
    const s4 = await getKredInputState(page);
    console.log('  → state after eye click (mask):', JSON.stringify(s4));

    console.log('[8] Verify value preserved through toggle roundtrip');
    const preserved = await page.evaluate(() => {
        const inputs = Array.from(document.querySelectorAll('input'));
        const kredInput = inputs.find(i => i.placeholder && i.placeholder.includes('API key'));
        return kredInput?.value;
    });
    console.log('  → preserved value:', preserved);

    console.log('[9] Switch to text type — should remove eye, no more input type password');
    await typeSelect.selectOption('text');
    await page.waitForTimeout(500);
    const s5 = await getKredInputState(page);
    console.log('  → state after switch to text:', JSON.stringify(s5));
    await shot(page, '05-back-to-text.png');

    console.log('Done.');
    await page.waitForTimeout(2000);
    await browser.close();
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
