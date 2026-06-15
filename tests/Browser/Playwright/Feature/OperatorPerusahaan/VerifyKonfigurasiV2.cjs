// Verify v2: terhapus filter + value masking + show/hide + row click.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'KonfigurasiPerusahaanV2Verify');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 300 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();
    page.on('pageerror', e => console.log('  ! pageerror:', e.message));
    page.on('console', m => { if (m.type() === 'error') console.log('  ! console.error:', m.text()); });

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

    // Goto
    await page.goto(BASE + '/operator-perusahaan/konfigurasi-perusahaan?per_page=10', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    // Initial state
    const init = await page.evaluate(() => {
        const buttons = Array.from(document.querySelectorAll('button')).map(b => b.textContent.trim());
        const valueCells = Array.from(document.querySelectorAll('table tbody tr td code.font-mono.text-xs'));
        return {
            hasTambah: buttons.includes('Tambah'),
            hasFilterAktif: !!buttons.find(b => b === 'Aktif'),
            hasFilterTerhapus: !!buttons.find(b => b === 'Terhapus'),
            valueCellsMasked: valueCells.length > 0 && valueCells.every(c => c.textContent.includes('•')),
            valueCellCount: valueCells.length,
            sampleValueText: valueCells[0]?.textContent.trim(),
        };
    });
    console.log('→ Initial:', JSON.stringify(init, null, 2));
    await page.screenshot({ path: path.join(RESULT_DIR, '01-list-masked.png') });

    // 1) Test VALUE MASKING: click eye on first row
    console.log('\n[1] Click eye toggle on first row to reveal value');
    const firstEye = page.locator('table tbody tr td button[title*="value"]').first();
    const eyeCount = await firstEye.count();
    console.log('  → Eye button count:', eyeCount);
    if (eyeCount > 0) {
        await firstEye.click();
        await page.waitForTimeout(500);
        const afterReveal = await page.evaluate(() => {
            const cells = Array.from(document.querySelectorAll('table tbody tr td code.font-mono.text-xs'));
            return cells.map(c => ({ text: c.textContent.trim(), masked: c.textContent.includes('•') }));
        });
        console.log('  → After reveal (first 3):', JSON.stringify(afterReveal.slice(0, 3)));
        await page.screenshot({ path: path.join(RESULT_DIR, '02-one-revealed.png') });
    }

    // 2) Test ROW CLICK TOGGLE
    console.log('\n[2] Click row body to toggle checkbox');
    const checkBefore = await page.locator('table tbody tr input[type="checkbox"]:checked').count();
    console.log('  → Checked before row click:', checkBefore);
    // Click on a non-button/input cell, e.g., the description cell
    const descCell = page.locator('table tbody tr td').nth(4); // description column
    if (await descCell.count() > 0) {
        await descCell.click();
        await page.waitForTimeout(500);
        const checkAfter = await page.locator('table tbody tr input[type="checkbox"]:checked').count();
        console.log('  → Checked after row click:', checkAfter);
        await page.screenshot({ path: path.join(RESULT_DIR, '03-row-click-checked.png') });
    }

    // 3) Test TERHAPUS FILTER
    console.log('\n[3] Click "Terhapus" filter (empty list since no trashed)');
    // Uncheck first
    await descCell.click();
    await page.waitForTimeout(300);
    // Click Terhapus
    const terhapusBtn = page.locator('button:has-text("Terhapus")').first();
    if (await terhapusBtn.count() > 0) {
        await terhapusBtn.click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);
        const trashed = await page.evaluate(() => {
            return {
                rows: document.querySelectorAll('table tbody tr').length,
                tambahVisible: !!Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Tambah'),
                noData: document.body.textContent.includes('Tidak ada data konfigurasi'),
            };
        });
        console.log('  → Trashed view:', JSON.stringify(trashed));
        await page.screenshot({ path: path.join(RESULT_DIR, '04-terhapus-empty.png') });
    }

    // 4) Soft-delete a row, then go to Terhapus
    console.log('\n[4] Switch back to Aktif, soft-delete a test row, then check Terhapus');
    await page.locator('button:has-text("Aktif")').first().click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    // Delete the first row
    const delBtn = page.locator('button[title="Hapus"]').first();
    if (await delBtn.count() > 0) {
        await delBtn.click();
        await page.waitForTimeout(500);
        // Click Hapus in confirm modal (last Hapus button)
        await page.locator('div.relative.bg-white button:has-text("Hapus")').last().click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1500);
    }
    // Now go to Terhapus
    await page.locator('button:has-text("Terhapus")').first().click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    const trashedNow = await page.evaluate(() => {
        return {
            rows: document.querySelectorAll('table tbody tr').length,
            firstRowHasOpacity: document.querySelector('table tbody tr')?.classList.contains('opacity-60'),
            bulkPulihkan: !!Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Pulihkan'),
        };
    });
    console.log('  → After soft-delete, trashed view:', JSON.stringify(trashedNow));
    await page.screenshot({ path: path.join(RESULT_DIR, '05-trashed-with-row.png') });

    // 5) Test Pulihkan
    console.log('\n[5] Click row + Pulihkan to restore');
    // First click the row body to enable bulk action (row click toggles)
    const firstRow = page.locator('table tbody tr').first();
    if (await firstRow.count() > 0) {
        // Click on a non-checkbox cell (e.g., the description column)
        const descCell = firstRow.locator('td').nth(4);
        await descCell.click();
        await page.waitForTimeout(800);
        // Now bulk action banner should show Pulihkan
        const bannerCheck = await page.evaluate(() => ({
            bannerVisible: !!Array.from(document.querySelectorAll('span')).find(s => s.textContent.includes('data dipilih')),
            pulihkanVisible: !!Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Pulihkan'),
        }));
        console.log('  → Banner state:', JSON.stringify(bannerCheck));
        await page.screenshot({ path: path.join(RESULT_DIR, '06-trashed-bulk-selected.png') });
        // Click Pulihkan
        const pulihkanBtn = page.locator('button:has-text("Pulihkan")').last();
        if (await pulihkanBtn.count() > 0) {
            await pulihkanBtn.click();
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(1500);
            const afterRestore = await page.evaluate(() => ({
                rows: document.querySelectorAll('table tbody tr').length,
            }));
            console.log('  → After restore:', JSON.stringify(afterRestore));
            await page.screenshot({ path: path.join(RESULT_DIR, '07-after-restore.png') });
        }
    }

    // 6) DETAIL MODAL value masking
    console.log('\n[6] Detail modal: value masked by default');
    await page.locator('button:has-text("Aktif")').first().click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    await page.locator('button[title="Detail"]').first().click();
    await page.waitForTimeout(1200);
    const detailValue = await page.evaluate(() => {
        const pre = document.querySelector('div.relative pre');
        const div = document.querySelector('div.relative div.font-mono.text-sm');
        return {
            preText: pre?.textContent,
            divText: div?.textContent,
            hasDots: (pre?.textContent || div?.textContent || '').includes('•'),
        };
    });
    console.log('  → Detail value state:', JSON.stringify(detailValue));
    await page.screenshot({ path: path.join(RESULT_DIR, '08-detail-masked.png') });
    // Click eye to reveal
    const detailEye = page.locator('div.relative button[title*="value"]').first();
    if (await detailEye.count() > 0) {
        await detailEye.click();
        await page.waitForTimeout(500);
        const detailValueAfter = await page.evaluate(() => {
            const pre = document.querySelector('div.relative pre');
            return { preText: pre?.textContent, hasReal: pre && !pre.textContent.includes('•') };
        });
        console.log('  → After reveal:', JSON.stringify(detailValueAfter));
        await page.screenshot({ path: path.join(RESULT_DIR, '09-detail-revealed.png') });
        // Close
        const closeX = page.locator('div.relative.bg-white button:has(i.fa-times:not(.text-xs))').first();
        await closeX.click();
        await page.waitForTimeout(500);
    }

    // 7) EDIT MODAL: value masked by default
    console.log('\n[7] Edit modal: value masked by default');
    await page.locator('button[title="Edit"]').first().click();
    await page.waitForTimeout(1200);
    const editValue = await page.evaluate(() => {
        const dotsDiv = Array.from(document.querySelectorAll('form div')).find(d => d.textContent.includes('disembunyikan'));
        const input = document.querySelector('form input[type="text"], form input[type="number"], form textarea');
        return {
            hasDots: !!dotsDiv,
            inputType: input?.type || input?.tagName,
        };
    });
    console.log('  → Edit value state:', JSON.stringify(editValue));
    await page.screenshot({ path: path.join(RESULT_DIR, '10-edit-masked.png') });
    // Click eye to reveal
    const editEye = page.locator('form button[title*="value"]').first();
    if (await editEye.count() > 0) {
        await editEye.click();
        await page.waitForTimeout(500);
        const editValueAfter = await page.evaluate(() => {
            const input = document.querySelector('form input[type="text"], form input[type="number"], form textarea');
            return { inputType: input?.type || input?.tagName, hasInput: !!input };
        });
        console.log('  → After reveal:', JSON.stringify(editValueAfter));
        await page.screenshot({ path: path.join(RESULT_DIR, '11-edit-revealed.png') });
    }

    console.log('\nDone.');
    await page.waitForTimeout(2000);
    await browser.close();
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
