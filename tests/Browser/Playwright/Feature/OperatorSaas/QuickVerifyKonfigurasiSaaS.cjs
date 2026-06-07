// Quick verify Konfigurasi SaaS page renders (light + dark + dynamic type + import/export).
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorSaas', 'KonfigurasiSaaSQuickVerify');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

async function shot(page, name) {
    await page.screenshot({ path: path.join(RESULT_DIR, name), fullPage: false });
    console.log('  →', name);
}

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 300 });
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

    console.log('[2] Goto Konfigurasi SaaS');
    await page.goto('http://erp-rt-rw-net.test/operator-saas/konfigurasi?per_page=10', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    const info = await page.evaluate(() => {
        const h2 = document.querySelector('h2')?.textContent.trim();
        const buttons = Array.from(document.querySelectorAll('button')).map(b => b.textContent.trim()).filter(Boolean);
        const tableRows = document.querySelectorAll('table tbody tr').length;
        const permButtons = {
            tambah: !!Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Tambah')),
            import: !!Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Import')),
            template: !!Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Template')),
            export: !!Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Export')),
        };
        const typeFilterButtons = Array.from(document.querySelectorAll('button')).filter(b => ['Semua','Teks','File','Angka','Boolean'].some(x => b.textContent.trim() === x)).map(b => b.textContent.trim());
        const trashFilterButtons = Array.from(document.querySelectorAll('button')).filter(b => ['Aktif','Terhapus'].some(x => b.textContent.trim() === x)).map(b => b.textContent.trim());
        return { h2, tableRows, permButtons, typeFilterButtons, trashFilterButtons, totalButtons: buttons.length };
    });
    console.log('  → Page info:', JSON.stringify(info, null, 2));
    await shot(page, '01-light.png');

    console.log('[3] Open Create modal (light)');
    const tambahBtn = page.locator('button:has-text("Tambah")').first();
    if (await tambahBtn.count() > 0) {
        await tambahBtn.click();
        await page.waitForTimeout(800);
        await shot(page, '02-create-modal-light.png');
        const typeSelect = page.locator('select').filter({ hasText: 'Teks' }).first();
        await typeSelect.selectOption('number');
        await page.waitForTimeout(500);
        await shot(page, '03-create-type-number.png');
        await typeSelect.selectOption('boolean');
        await page.waitForTimeout(500);
        await shot(page, '04-create-type-boolean.png');
        await typeSelect.selectOption('file');
        await page.waitForTimeout(500);
        await shot(page, '05-create-type-file.png');
        // close
        await page.keyboard.press('Escape');
        await page.locator('.fa-times').first().click();
        await page.waitForTimeout(500);
    } else {
        console.log('  ! Tambah button not found');
    }

    console.log('[4] Apply Boolean filter');
    const booleanFilter = page.locator('button:has-text("Boolean")').first();
    if (await booleanFilter.count() > 0) {
        await booleanFilter.click();
        await page.waitForTimeout(1500);
        await shot(page, '06-filter-boolean.png');
        const resetBtn = page.locator('button:has-text("Reset Filter")');
        if (await resetBtn.count() > 0) {
            await resetBtn.click();
            await page.waitForTimeout(1500);
        }
    }

    console.log('[5] Toggle dark mode');
    await page.evaluate(() => document.documentElement.classList.add('dark'));
    await page.waitForTimeout(800);
    await shot(page, '07-dark.png');

    console.log('[6] Open Detail modal in dark');
    const eyeBtn = page.locator('button[title="Detail"]').first();
    if (await eyeBtn.count() > 0) {
        await eyeBtn.click();
        await page.waitForTimeout(800);
        await shot(page, '08-detail-dark.png');
        await page.keyboard.press('Escape');
        await page.locator('.fa-times').first().click();
        await page.waitForTimeout(500);
    }

    console.log('[7] Open Import modal');
    const importBtn = page.locator('button:has-text("Import")').first();
    if (await importBtn.count() > 0) {
        await importBtn.click();
        await page.waitForTimeout(800);
        await shot(page, '09-import-modal.png');
        await page.keyboard.press('Escape');
        await page.locator('.fa-times').first().click();
        await page.waitForTimeout(500);
    }

    console.log('[8] Mobile viewport');
    await page.setViewportSize({ width: 390, height: 800 });
    await page.waitForTimeout(500);
    await shot(page, '10-mobile.png');

    console.log('Done.');
    await page.waitForTimeout(2000);
    await browser.close();
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
