// Quick verify Konfigurasi Perusahaan page renders (light + dark).
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'KonfigurasiPerusahaanQuickVerify');
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

    console.log('[1] Login');
    await page.goto('http://erp-rt-rw-net.test/login-perusahaan', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.locator('button:has(.fa-building)').first().click();
    await page.waitForTimeout(1200);
    for (const b of await page.locator('button').all()) {
        const t = (await b.textContent()) || '';
        if (t.includes('admin@netsejahtera.com')) { await b.click(); break; }
    }
    await page.waitForTimeout(500);
    await page.fill('input[type="email"]', 'admin@netsejahtera.com');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(6000);

    console.log('[2] Goto Konfigurasi');
    await page.goto('http://erp-rt-rw-net.test/operator-perusahaan/konfigurasi-perusahaan?per_page=10', { waitUntil: 'domcontentloaded' });
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
        return { h2, tableRows, permButtons, typeFilterButtons, totalButtons: buttons.length };
    });
    console.log('  → Page info:', JSON.stringify(info, null, 2));
    await shot(page, '01-light.png');

    console.log('[3] Open Create modal (light)');
    const tambahBtn = page.locator('button:has-text("Tambah")').first();
    if (await tambahBtn.count() > 0) {
        await tambahBtn.click();
        await page.waitForTimeout(800);
        await shot(page, '02-create-modal-light.png');
        // verify type field is dynamic - change type to number
        const typeSelect = page.locator('select').filter({ hasText: 'Teks' }).first();
        await typeSelect.selectOption('number');
        await page.waitForTimeout(500);
        await shot(page, '03-create-type-number.png');
        // change to boolean
        await typeSelect.selectOption('boolean');
        await page.waitForTimeout(500);
        await shot(page, '04-create-type-boolean.png');
        // kredensial — verify password input
        await typeSelect.selectOption('kredensial');
        await page.waitForTimeout(500);
        const isPasswordInput = await page.evaluate(() => {
            const inputs = document.querySelectorAll('input[type="password"]');
            return inputs.length;
        });
        console.log('  → password input count after type=kredensial:', isPasswordInput);
        await shot(page, '05-create-type-kredensial.png');
        // close
        await page.keyboard.press('Escape');
        await page.locator('.fa-times').first().click();
        await page.waitForTimeout(500);
    } else {
        console.log('  ! Tambah button not found');
    }

    console.log('[4] Toggle dark mode');
    await page.evaluate(() => document.documentElement.classList.add('dark'));
    await page.waitForTimeout(800);
    await shot(page, '05-dark.png');

    console.log('[5] Open Detail modal in dark');
    const eyeBtn = page.locator('button[title="Detail"]').first();
    if (await eyeBtn.count() > 0) {
        await eyeBtn.click();
        await page.waitForTimeout(800);
        await shot(page, '06-detail-dark.png');
        await page.keyboard.press('Escape');
        await page.locator('.fa-times').first().click();
        await page.waitForTimeout(500);
    }

    console.log('[6] Mobile viewport');
    await page.setViewportSize({ width: 390, height: 800 });
    await page.waitForTimeout(500);
    await shot(page, '07-mobile.png');

    console.log('Done.');
    await page.waitForTimeout(2000);
    await browser.close();
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
