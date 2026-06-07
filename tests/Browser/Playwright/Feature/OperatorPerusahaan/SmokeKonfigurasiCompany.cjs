// Quick smoke: verify company-scoped Konfigurasi Perusahaan page works
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'KonfigurasiCompanySmoke');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

async function shot(page, name) {
    await page.screenshot({ path: path.join(RESULT_DIR, name), fullPage: false });
    console.log('  →', name);
}

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 300 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();
    const errors = [];
    page.on('pageerror', e => errors.push('pageerror: ' + e.message));
    page.on('console', m => { if (m.type() === 'error') errors.push('console.error: ' + m.text()); });

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

    console.log('[2] Goto Konfigurasi Perusahaan');
    await page.goto('http://erp-rt-rw-net.test/operator-perusahaan/konfigurasi-perusahaan?per_page=10', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    const info = await page.evaluate(() => {
        const buttons = Array.from(document.querySelectorAll('button')).map(b => b.textContent.trim());
        const valueCells = Array.from(document.querySelectorAll('table tbody tr td code.font-mono.text-xs'));
        const dataRows = document.querySelectorAll('table tbody tr td:nth-child(2)').length;
        return {
            h2: document.querySelector('h2')?.textContent.trim(),
            dataRows,
            hasTambah: buttons.includes('Tambah'),
            hasImport: buttons.includes('Import'),
            hasTemplate: buttons.includes('Template'),
            hasExport: buttons.some(b => b.includes('Export ')),
            hasAktif: buttons.includes('Aktif'),
            hasTerhapus: buttons.includes('Terhapus'),
            firstRowKey: document.querySelector('table tbody tr td:nth-child(2) code')?.textContent.trim(),
            firstRowType: document.querySelector('table tbody tr td:nth-child(3) span')?.textContent.trim(),
            firstRowValue: document.querySelector('table tbody tr td:nth-child(4) code')?.textContent.trim(),
            valueCellsMasked: valueCells.length > 0 && valueCells.every(c => c.textContent.includes('•')),
        };
    });
    console.log('  → Page state:', JSON.stringify(info, null, 2));
    await shot(page, '01-list-company.png');

    if (info.dataRows === 0) {
        console.log('  ! No data rows; aborting');
        await browser.close();
        return;
    }

    console.log('[3] Toggle value mask on first row');
    const firstEye = page.locator('table tbody tr td button[title*="value"]').first();
    if (await firstEye.count() > 0) {
        await firstEye.click();
        await page.waitForTimeout(500);
        const revealed = await page.evaluate(() => {
            const cells = Array.from(document.querySelectorAll('table tbody tr td code.font-mono.text-xs'));
            return cells[0]?.textContent.trim();
        });
        console.log('  → First value after reveal:', revealed);
        await shot(page, '02-value-revealed.png');
    }

    console.log('[4] Click row body to toggle checkbox');
    const checkBefore = await page.locator('table tbody tr input[type="checkbox"]:checked').count();
    await page.locator('table tbody tr td').nth(4).click();
    await page.waitForTimeout(500);
    const checkAfter = await page.locator('table tbody tr input[type="checkbox"]:checked').count();
    console.log(`  → Checked: ${checkBefore} → ${checkAfter}`);
    await shot(page, '03-row-clicked.png');

    // Uncheck
    await page.locator('table tbody tr td').nth(4).click();
    await page.waitForTimeout(500);

    console.log('[5] Soft-delete a row');
    const delBtn = page.locator('button[title="Hapus"]').first();
    if (await delBtn.count() > 0) {
        await delBtn.click();
        await page.waitForTimeout(500);
        // confirm
        await page.locator('div.relative.bg-white button:has-text("Hapus")').last().click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1500);
    }

    console.log('[6] Go to Terhapus filter');
    await page.locator('button:has-text("Terhapus")').first().click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    const trashed = await page.evaluate(() => {
        const rows = document.querySelectorAll('table tbody tr');
        return {
            rowCount: rows.length,
            hasOpacity: rows[0]?.classList.contains('opacity-60'),
            bulkActionPulihkan: !!Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Pulihkan'),
        };
    });
    console.log('  → Trashed view:', JSON.stringify(trashed));
    await shot(page, '04-trashed-view.png');

    console.log('[7] Click row + Pulihkan to restore');
    // Click description cell to toggle row selection
    await page.locator('table tbody tr td').nth(4).click();
    await page.waitForTimeout(800);
    const banner = await page.evaluate(() => ({
        bannerVisible: !!Array.from(document.querySelectorAll('span')).find(s => s.textContent.includes('data dipilih')),
    }));
    console.log('  → Banner state:', JSON.stringify(banner));
    await shot(page, '05-trashed-selected.png');
    if (banner.bannerVisible) {
        await page.locator('button:has-text("Pulihkan")').last().click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1500);
        const after = await page.evaluate(() => ({
            trashedRows: document.querySelectorAll('table tbody tr').length,
        }));
        console.log('  → After restore:', JSON.stringify(after));
        await shot(page, '06-after-restore.png');
    }

    console.log('\n[FINAL] Errors:', errors.length === 0 ? 'NONE' : errors);
    await page.waitForTimeout(2000);
    await browser.close();
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
