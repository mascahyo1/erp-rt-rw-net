// Full E2E test for Konfigurasi Perusahaan (headed Playwright).
// Run: node tests/Browser/Playwright/Feature/OperatorPerusahaan/KonfigurasiPerusahaanCRUDHeaded.cjs
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'KonfigurasiPerusahaanCRUD');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

const BASE = 'http://erp-rt-rw-net.test';
const LOG = (...a) => console.log(...a);
const shot = async (page, name) => {
    const p = path.join(RESULT_DIR, name);
    await page.screenshot({ path: p, fullPage: false });
    LOG('  → screenshot:', name);
};

async function login(page) {
    LOG('[1] Login as admin@netsejahtera.com');
    await page.goto(`${BASE}/login-perusahaan`, { waitUntil: 'domcontentloaded' });
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
    LOG('  → URL:', page.url());
}

async function goKonfig(page) {
    await page.goto(`${BASE}/operator-perusahaan/konfigurasi-perusahaan?per_page=10`, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
}

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 250 });
    const ctx = await browser.newContext({
        viewport: { width: 1280, height: 800 },
        acceptDownloads: true,
    });
    const page = await ctx.newPage();
    page.on('pageerror', e => LOG('  ! pageerror:', e.message));
    page.on('console', m => { if (m.type() === 'error') LOG('  ! console.error:', m.text()); });

    await login(page);

    LOG('\n[2] Navigate to Konfigurasi Perusahaan');
    await goKonfig(page);
    const initialInfo = await page.evaluate(() => ({
        h2: document.querySelector('h2')?.textContent.trim(),
        tableRows: document.querySelectorAll('table tbody tr').length,
        hasTambah: !!Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Tambah'),
        hasImport: !!Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Import'),
        hasTemplate: !!Array.from(document.querySelectorAll('button')).find(b => b.textContent.trim() === 'Template'),
        hasExport: !!Array.from(document.querySelectorAll('button')).find(b => b.textContent.includes('Export ')),
    }));
    LOG('  → Initial:', JSON.stringify(initialInfo));
    await shot(page, '01-list-light.png');

    LOG('\n[3] Test SEARCH');
    const searchInput = page.locator('input[placeholder*="Cari key"]');
    await searchInput.fill('landing');
    await page.keyboard.press('Enter');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    const searchedRows = await page.locator('table tbody tr').count();
    LOG('  → Rows after search "landing":', searchedRows);
    await shot(page, '02-search.png');
    // Clear
    await page.locator('button[title="Clear"]').first().click();
    await page.waitForTimeout(500);
    await page.keyboard.press('Enter');
    await page.waitForTimeout(1000);

    LOG('\n[4] Test TYPE FILTER (Boolean)');
    await page.locator('button:has-text("Boolean")').first().click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    const booleanRows = await page.locator('table tbody tr').count();
    LOG('  → Boolean rows:', booleanRows);
    await shot(page, '03-filter-boolean.png');
    // Reset filter
    await page.locator('button:has-text("Reset Filter")').click();
    await page.waitForTimeout(1000);

    LOG('\n[5] Test SORT (Key asc then desc)');
    await page.locator('th:has-text("Key")').click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(800);
    const firstKeyAsc = await page.locator('table tbody tr td code').first().textContent();
    LOG('  → First key asc:', firstKeyAsc);
    await page.locator('th:has-text("Key")').click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(800);
    const firstKeyDesc = await page.locator('table tbody tr td code').first().textContent();
    LOG('  → First key desc:', firstKeyDesc);
    await shot(page, '04-sort.png');
    // Reset
    await page.locator('th:has-text("Key")').click();
    await page.waitForTimeout(800);

    LOG('\n[6] Test CREATE — type=text');
    const uniqueKey = 'test.e2e.' + Date.now();
    await page.locator('button:has-text("Tambah")').first().click();
    await page.waitForTimeout(500);
    await page.locator('input[placeholder*="company.tagline"]').fill(uniqueKey);
    // Type select — keep as 'text' (default)
    await page.locator('textarea[placeholder*="Teks bebas"]').fill('Hello from E2E test');
    // description
    const descTextarea = page.locator('form textarea').last();
    await descTextarea.fill('Created by Playwright headed test');
    await shot(page, '05-create-text-form.png');
    await page.locator('button:has-text("Simpan")').click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    // verify the new row is in the list (search by uniqueKey)
    await searchInput.fill(uniqueKey);
    await page.keyboard.press('Enter');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    const newRowCount = await page.locator('table tbody tr').count();
    LOG('  → After create, rows with uniqueKey:', newRowCount);
    await shot(page, '06-after-create.png');

    LOG('\n[7] Test EDIT — change value');
    const editBtn = page.locator('button[title="Edit"]').first();
    if (await editBtn.count() > 0) {
        await editBtn.click();
        await page.waitForTimeout(500);
        const valueTextarea = page.locator('textarea[placeholder*="Teks bebas"]').first();
        await valueTextarea.fill('Hello EDITED from E2E test');
        await shot(page, '07-edit-modal.png');
        await page.locator('button:has-text("Update")').click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1500);
        await shot(page, '08-after-edit.png');
    }

    LOG('\n[8] Test DETAIL modal');
    const eyeBtn = page.locator('button[title="Detail"]').first();
    if (await eyeBtn.count() > 0) {
        await eyeBtn.click();
        await page.waitForTimeout(1200);
        await shot(page, '09-detail-modal.png');
        // modal X button — i.fa-times without .text-xs (search clear has text-xs)
        const modalX = page.locator('div.relative button:has(i.fa-times:not(.text-xs))').first();
        await modalX.click();
        await page.waitForTimeout(800);
    }

    LOG('\n[9] Test DELETE single');
    const delBtn = page.locator('button[title="Hapus"]').first();
    if (await delBtn.count() > 0) {
        await delBtn.click();
        await page.waitForTimeout(500);
        await shot(page, '10-delete-confirm.png');
        await page.locator('button:has-text("Hapus")').last().click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1500);
        await shot(page, '11-after-delete.png');
    }

    LOG('\n[10] Clear search & verify list');
    await searchInput.fill('');
    await page.keyboard.press('Enter');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    LOG('\n[11] Test CREATE — type=number');
    const numKey = 'test.e2e.num.' + Date.now();
    await page.locator('button:has-text("Tambah")').first().click();
    await page.waitForTimeout(500);
    await page.locator('input[placeholder*="company.tagline"]').fill(numKey);
    const typeSelect = page.locator('form select').first();
    await typeSelect.selectOption('number');
    await page.waitForTimeout(500);
    await page.locator('input[type="number"]').fill('42');
    await shot(page, '12-create-number.png');
    await page.locator('button:has-text("Simpan")').click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    LOG('\n[12] Test CREATE — type=boolean');
    const boolKey = 'test.e2e.bool.' + Date.now();
    await page.locator('button:has-text("Tambah")').first().click();
    await page.waitForTimeout(500);
    await page.locator('input[placeholder*="company.tagline"]').fill(boolKey);
    await typeSelect.selectOption('boolean');
    await page.waitForTimeout(500);
    // boolean select is the last <select> in the form
    const boolSelect = page.locator('form select').last();
    await boolSelect.selectOption('true');
    await shot(page, '13-create-boolean.png');
    await page.locator('button:has-text("Simpan")').click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    LOG('\n[13] Test BULK DELETE — select 2 test rows');
    await searchInput.fill('test.e2e.');
    await page.keyboard.press('Enter');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    const testRowCount = await page.locator('table tbody tr').count();
    LOG('  → Test rows visible:', testRowCount);
    // Select all
    await page.locator('thead input[type="checkbox"]').check();
    await page.waitForTimeout(500);
    await shot(page, '14-bulk-selected.png');
    await page.locator('button:has-text("Hapus")').last().click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    const afterBulkCount = await page.locator('table tbody tr').count();
    LOG('  → Rows after bulk delete:', afterBulkCount);
    await shot(page, '15-after-bulk-delete.png');

    LOG('\n[14] Test DOWNLOAD TEMPLATE');
    // First clear search
    await searchInput.fill('');
    await page.keyboard.press('Enter');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(800);
    const [tmplDownload] = await Promise.all([
        page.waitForEvent('download', { timeout: 10000 }),
        page.locator('button:has-text("Template")').click(),
    ]);
    const tmplPath = path.join(RESULT_DIR, 'template-konfigurasi.xlsx');
    await tmplDownload.saveAs(tmplPath);
    const tmplSize = fs.statSync(tmplPath).size;
    LOG('  → Template saved:', tmplPath, '(', tmplSize, 'bytes )');

    LOG('\n[15] Test EXPORT ALL');
    const [expDownload] = await Promise.all([
        page.waitForEvent('download', { timeout: 15000 }),
        // Open export dropdown by hovering
        page.locator('button:has-text("Export")').first().hover(),
        page.waitForTimeout(400),
        page.locator('button:has-text("Export Semua")').click(),
    ]);
    const expPath = path.join(RESULT_DIR, 'export-konfigurasi-all.xlsx');
    await expDownload.saveAs(expPath);
    const expSize = fs.statSync(expPath).size;
    LOG('  → Export saved:', expPath, '(', expSize, 'bytes )');

    LOG('\n[16] Test IMPORT (use template)');
    await page.locator('button:has-text("Import")').first().click();
    await page.waitForTimeout(500);
    await shot(page, '16-import-modal.png');
    await page.locator('input[type="file"]').setInputFiles(tmplPath);
    await page.waitForTimeout(500);
    await page.locator('button[type="submit"]:has-text("Import")').click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await shot(page, '17-after-import.png');

    LOG('\n[17] DARK MODE — toggle and re-verify');
    // Use html class since theme toggle may not be obvious
    await page.evaluate(() => document.documentElement.classList.add('dark'));
    await page.waitForTimeout(800);
    // close any open modal
    await page.keyboard.press('Escape');
    await page.waitForTimeout(300);
    await shot(page, '18-dark.png');
    // Open detail in dark
    const eyeBtnDark = page.locator('button[title="Detail"]').first();
    if (await eyeBtnDark.count() > 0) {
        await eyeBtnDark.click();
        await page.waitForTimeout(1200);
        await shot(page, '19-detail-dark.png');
        const modalXDark = page.locator('div.relative button:has(i.fa-times:not(.text-xs))').first();
        await modalXDark.click();
        await page.waitForTimeout(800);
    }
    // Open create in dark + show all type fields
    await page.locator('button:has-text("Tambah")').first().click();
    await page.waitForTimeout(500);
    await shot(page, '20-create-dark-text.png');
    await typeSelect.selectOption('number');
    await page.waitForTimeout(400);
    await shot(page, '21-create-dark-number.png');
    await typeSelect.selectOption('boolean');
    await page.waitForTimeout(400);
    await shot(page, '22-create-dark-boolean.png');
    // close create modal
    const closeCreateX = page.locator('div.relative button:has(i.fa-times:not(.text-xs))').first();
    await closeCreateX.click();
    await page.waitForTimeout(800);

    LOG('\n[18] RESPONSIVE — tablet');
    await page.setViewportSize({ width: 768, height: 1024 });
    await page.waitForTimeout(500);
    await shot(page, '23-tablet-dark.png');
    await page.evaluate(() => document.documentElement.classList.remove('dark'));
    await page.waitForTimeout(500);
    await shot(page, '24-tablet-light.png');

    LOG('\n[19] RESPONSIVE — mobile');
    await page.setViewportSize({ width: 390, height: 800 });
    await page.waitForTimeout(500);
    await shot(page, '25-mobile-dark.png');
    await page.evaluate(() => document.documentElement.classList.remove('dark'));
    await page.waitForTimeout(500);
    await shot(page, '26-mobile-light.png');

    LOG('\n[20] RESPONSIVE — desktop dark final');
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.waitForTimeout(500);
    await page.evaluate(() => document.documentElement.classList.add('dark'));
    await page.waitForTimeout(500);
    await shot(page, '27-desktop-dark.png');

    LOG('\nDone.');
    await page.waitForTimeout(2000);
    await browser.close();
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
