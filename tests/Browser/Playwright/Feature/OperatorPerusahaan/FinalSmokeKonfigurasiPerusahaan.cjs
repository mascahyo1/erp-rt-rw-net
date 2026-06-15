// Final smoke test: verify clean state of Konfigurasi Perusahaan page.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'KonfigurasiPerusahaanFinal');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 200 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();
    const errors = [];
    page.on('pageerror', e => errors.push('pageerror: ' + e.message));
    page.on('console', m => { if (m.type() === 'error') errors.push('console.error: ' + m.text()); });

    // Login
    await page.goto(BASE + '/login-perusahaan', { waitUntil: 'domcontentloaded' });
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

    // Konfig page
    await page.goto(BASE + '/operator-perusahaan/konfigurasi-perusahaan?per_page=10', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    const info = await page.evaluate(() => {
        const tableRows = document.querySelectorAll('table tbody tr').length;
        const testRows = Array.from(document.querySelectorAll('table tbody tr')).filter(tr => tr.textContent.includes('test.e2e.')).length;
        return {
            tableRows,
            testRows,
            h2: document.querySelector('h2')?.textContent.trim(),
            typeFilters: Array.from(document.querySelectorAll('button')).filter(b => ['Semua','Teks','File','Angka','Boolean'].includes(b.textContent.trim())).map(b => b.textContent.trim()),
        };
    });
    console.log('→ Final state:', JSON.stringify(info, null, 2));
    console.log('→ Console errors:', errors.length === 0 ? 'NONE' : errors);

    await page.screenshot({ path: path.join(RESULT_DIR, 'final-light.png') });

    // Toggle dark
    await page.evaluate(() => document.documentElement.classList.add('dark'));
    await page.waitForTimeout(500);
    await page.screenshot({ path: path.join(RESULT_DIR, 'final-dark.png') });

    await browser.close();
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
