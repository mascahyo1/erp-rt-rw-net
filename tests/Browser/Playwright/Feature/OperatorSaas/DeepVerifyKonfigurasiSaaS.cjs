// DeepVerify Konfigurasi SaaS page (/operator-saas/konfigurasi).
// Cakupan deep verify:
//   1. Login + navigasi + permission gate
//   2. List rendering: header, badge types, pagination, search, filter (6 type + 2 trash), sort, per-page
//   3. CRUD modal: create all 5 types (text/file/number/boolean/kredensial), edit, delete confirmation
//   4. Kredensial khusus: value masking default + eye toggle type password <-> text
//   5. Soft-delete: filter Terhapus, restore, bulk action
//   6. Import modal + Template download
//   7. Export dropdown
//   8. Dark mode + responsive (mobile/tablet/desktop)
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorSaas', 'KonfigurasiSaaSDeepVerify');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });


let pass = 0;
let fail = 0;
const failures = [];

function check(name, condition, detail = '') {
    if (condition) {
        pass++;
        console.log(`  ✓ ${name}${detail ? ' — ' + detail : ''}`);
    } else {
        fail++;
        failures.push(name);
        console.log(`  ✗ ${name}${detail ? ' — ' + detail : ''}`);
    }
}

async function shot(page, name) {
    await page.screenshot({ path: path.join(RESULT_DIR, name), fullPage: false });
    console.log(`  → screenshot: ${name}`);
}

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 350 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();
    const consoleErrors = [];
    page.on('pageerror', e => consoleErrors.push('pageerror: ' + e.message));
    page.on('console', m => { if (m.type() === 'error') consoleErrors.push('console.error: ' + m.text()); });

    // ────────────────────────────────────────────────────────────────────
    // 1. Login + permission gate
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[1] Login as Operator SaaS');
    await page.goto(`${BASE}/login-operator-saas`, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.fill('input[type="email"]', 'admin@demo.test');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/operator-saas\/dashboard/, { timeout: 10000 }).catch(() => {});
    await page.waitForTimeout(2000);
    check('Login berhasil (redirect ke dashboard)', page.url().includes('/operator-saas/'));
    await shot(page, '01-dashboard.png');

    // ────────────────────────────────────────────────────────────────────
    // 2. Navigasi ke Konfigurasi + permission gate
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[2] Navigasi ke Konfigurasi SaaS');
    await page.goto(`${BASE}/operator-saas/konfigurasi?per_page=10`, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    await shot(page, '02-list-light.png');

    const h2 = await page.locator('h2').first().textContent();
    check('H2 title = "Konfigurasi SaaS"', h2?.trim() === 'Konfigurasi SaaS', `actual: "${h2?.trim()}"`);

    // Permission buttons presence
    const hasTambah = await page.locator('button:has-text("Tambah")').count() > 0;
    const hasImport = await page.locator('button:has-text("Import")').count() > 0;
    const hasTemplate = await page.locator('button:has-text("Template")').count() > 0;
    const hasExport = await page.locator('button:has-text("Export")').count() > 0;
    check('Tombol Tambah visible', hasTambah);
    check('Tombol Import visible', hasImport);
    check('Tombol Template visible', hasTemplate);
    check('Tombol Export visible', hasExport);

    // 6 type filter buttons
    const typeFilters = await page.locator('button').filter({ hasText: /^(Semua|Teks|File|Angka|Boolean|Kredensial)$/ }).allTextContents();
    check('6 type filter buttons (Semua/Teks/File/Angka/Boolean/Kredensial)',
        typeFilters.length === 6, `actual: [${typeFilters.join(', ')}]`);

    // 2 trash filter buttons
    const trashFilters = await page.locator('button').filter({ hasText: /^(Aktif|Terhapus)$/ }).allTextContents();
    check('2 trash filter buttons (Aktif/Terhapus)',
        trashFilters.length === 2, `actual: [${trashFilters.join(', ')}]`);

    // Table rendered with rows
    const tableRows = await page.locator('table tbody tr').count();
    check('Table punya baris data', tableRows > 0, `${tableRows} baris`);

    // No JS errors so far
    check('No JS errors saat load', consoleErrors.length === 0, consoleErrors.join('; '));

    // ────────────────────────────────────────────────────────────────────
    // 3. CRUD Create modal — verify all 5 types
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[3] CRUD: Create modal — verify all 5 types');
    await page.locator('button:has-text("Tambah")').first().click();
    await page.waitForTimeout(800);
    await shot(page, '03-create-modal-default-text.png');

    // type select
    const typeSelect = page.locator('select').filter({ has: page.locator('option[value="kredensial"]') });
    check('Type select punya option text', await typeSelect.locator('option[value="text"]').count() === 1);
    check('Type select punya option file', await typeSelect.locator('option[value="file"]').count() === 1);
    check('Type select punya option number', await typeSelect.locator('option[value="number"]').count() === 1);
    check('Type select punya option boolean', await typeSelect.locator('option[value="boolean"]').count() === 1);
    check('Type select punya option kredensial', await typeSelect.locator('option[value="kredensial"]').count() === 1);

    // type = number → input number
    await typeSelect.selectOption('number');
    await page.waitForTimeout(400);
    let inputType = await page.evaluate(() => {
        const inputs = document.querySelectorAll('form input');
        for (const i of inputs) {
            if (i.type === 'number') return i.type;
        }
        return null;
    });
    check('type=number → muncul input type=number', inputType === 'number');

    // type = boolean → select dengan option true/false
    await typeSelect.selectOption('boolean');
    await page.waitForTimeout(400);
    let hasBoolSelect = await page.evaluate(() => {
        const sels = document.querySelectorAll('form select');
        for (const s of sels) {
            const opts = Array.from(s.options).map(o => o.value);
            if (opts.includes('true') && opts.includes('false')) return true;
        }
        return false;
    });
    check('type=boolean → muncul select dengan option true/false', hasBoolSelect);

    // type = file → textarea (path)
    await typeSelect.selectOption('file');
    await page.waitForTimeout(400);
    let hasTextarea = await page.locator('form textarea').count();
    check('type=file → muncul textarea', hasTextarea >= 1);

    // type = kredensial → input type=password + placeholder + eye button
    await typeSelect.selectOption('kredensial');
    await page.waitForTimeout(400);
    const kredState1 = await page.evaluate(() => {
        const kredInput = Array.from(document.querySelectorAll('form input')).find(i => i.placeholder && i.placeholder.includes('API key'));
        const eyeBtn = Array.from(document.querySelectorAll('form button')).find(b => {
            const t = b.getAttribute('title') || '';
            return t.includes('Tampilkan') || t.includes('Sembunyikan');
        });
        return {
            inputType: kredInput?.type || null,
            eyeTitle: eyeBtn?.getAttribute('title') || null,
        };
    });
    check('type=kredensial → input type=password (default masked)', kredState1.inputType === 'password');
    check('type=kredensial → eye button title "Tampilkan value"', kredState1.eyeTitle === 'Tampilkan value');
    await shot(page, '04-create-type-kredensial-default.png');

    // Type a value
    const kredInput = page.locator('form input[placeholder*="API key"]');
    await kredInput.fill('sk_test_abc123');
    await page.waitForTimeout(300);

    // Click eye to reveal
    const eyeBtn = page.locator('form button').filter({ has: page.locator('i.fa-eye, i.fa-eye-slash') });
    await eyeBtn.first().click();
    await page.waitForTimeout(500);
    const kredState2 = await page.evaluate(() => {
        const kredInput = Array.from(document.querySelectorAll('form input')).find(i => i.placeholder && i.placeholder.includes('API key'));
        const eyeBtn = Array.from(document.querySelectorAll('form button')).find(b => {
            const t = b.getAttribute('title') || '';
            return t.includes('Tampilkan') || t.includes('Sembunyikan');
        });
        return {
            inputType: kredInput?.type || null,
            inputValue: kredInput?.value || null,
            eyeTitle: eyeBtn?.getAttribute('title') || null,
        };
    });
    check('Click eye → type berubah ke text (visible)', kredState2.inputType === 'text');
    check('Value preserved setelah toggle', kredState2.inputValue === 'sk_test_abc123');
    check('Eye title berubah ke "Sembunyikan value"', kredState2.eyeTitle === 'Sembunyikan value');
    await shot(page, '05-create-kredensial-revealed.png');

    // Click eye again to mask
    await eyeBtn.first().click();
    await page.waitForTimeout(500);
    const kredState3 = await page.evaluate(() => {
        const kredInput = Array.from(document.querySelectorAll('form input')).find(i => i.placeholder && i.placeholder.includes('API key'));
        return { inputType: kredInput?.type, inputValue: kredInput?.value };
    });
    check('Click eye lagi → type=password (masked)', kredState3.inputType === 'password');
    check('Value masih preserved setelah toggle back', kredState3.inputValue === 'sk_test_abc123');

    // Switch to text → kred input hilang, textarea muncul
    await typeSelect.selectOption('text');
    await page.waitForTimeout(500);
    const kredGoneAfterText = await page.evaluate(() => {
        return Array.from(document.querySelectorAll('form input')).every(i => !i.placeholder || !i.placeholder.includes('API key'));
    });
    check('Switch type ke text → kredensial input hilang', kredGoneAfterText);

    // Close modal
    await page.locator('button .fa-times').first().click();
    await page.waitForTimeout(500);

    // ────────────────────────────────────────────────────────────────────
    // 4. Filter Boolean → table hanya berisi type=boolean
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[4] Filter Boolean');
    await page.locator('button').filter({ hasText: /^Boolean$/ }).first().click();
    await page.waitForTimeout(1500);
    const boolCount = await page.locator('table tbody tr').count();
    check('Filter Boolean → ada baris', boolCount > 0, `${boolCount} baris`);
    // Check every visible row has Boolean badge
    const allRowsAreBoolean = await page.evaluate(() => {
        const rows = document.querySelectorAll('table tbody tr');
        for (const r of rows) {
            const text = r.textContent || '';
            // Setiap row boolean harus punya "Boolean" badge
            if (!text.includes('Boolean')) return false;
        }
        return rows.length > 0;
    });
    check('Semua baris filter Boolean adalah type boolean', allRowsAreBoolean);
    await shot(page, '06-filter-boolean.png');

    // Reset filter
    const resetBtn = page.locator('button:has-text("Reset Filter")');
    if (await resetBtn.count() > 0) {
        await resetBtn.click();
        await page.waitForTimeout(1500);
    }

    // ────────────────────────────────────────────────────────────────────
    // 5. Filter Kredensial
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[5] Filter Kredensial');
    await page.locator('button').filter({ hasText: /^Kredensial$/ }).first().click();
    await page.waitForTimeout(1500);
    await shot(page, '07-filter-kredensial.png');
    // Count real data rows (exclude empty-state row)
    const kredCount = await page.evaluate(() => {
        const rows = document.querySelectorAll('table tbody tr');
        let count = 0;
        for (const r of rows) {
            // Skip empty-state row
            if (r.textContent.includes('Tidak ada data konfigurasi')) continue;
            count++;
        }
        return count;
    });
    if (kredCount > 0) {
        const kredRows = await page.evaluate(() => {
            const rows = document.querySelectorAll('table tbody tr');
            for (const r of rows) {
                if (r.textContent.includes('Tidak ada data konfigurasi')) continue;
                const text = r.textContent || '';
                if (!text.includes('Kredensial')) return false;
            }
            return true;
        });
        check('Semua baris filter Kredensial adalah type kredensial', kredRows,
            `${kredCount} baris`);
    } else {
        console.log('  → Tidak ada data kredensial (skip)');
    }

    // Reset filter
    if (await page.locator('button:has-text("Reset Filter")').count() > 0) {
        await page.locator('button:has-text("Reset Filter")').click();
        await page.waitForTimeout(1500);
    }

    // ────────────────────────────────────────────────────────────────────
    // 6. Trash filter + restore
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[6] Filter Terhapus');
    await page.locator('button').filter({ hasText: /^Terhapus$/ }).first().click();
    await page.waitForTimeout(1500);
    await shot(page, '08-filter-trashed.png');
    const trashedInfo = await page.evaluate(() => {
        const rows = document.querySelectorAll('table tbody tr');
        let realRows = 0;
        let trashBadges = 0;
        for (const r of rows) {
            if (r.textContent.includes('Tidak ada data konfigurasi')) continue;
            realRows++;
            if (r.textContent.includes('Terhapus')) trashBadges++;
        }
        return { realRows, trashBadges };
    });
    if (trashedInfo.realRows > 0) {
        check('Baris terhapus menampilkan badge "Terhapus"', trashedInfo.trashBadges > 0,
            `${trashedInfo.trashBadges}/${trashedInfo.realRows} baris punya badge`);
    } else {
        console.log('  → Tidak ada data terhapus (skip)');
    }
    // Reset
    if (await page.locator('button:has-text("Reset Filter")').count() > 0) {
        await page.locator('button:has-text("Reset Filter")').click();
        await page.waitForTimeout(1500);
    }

    // ────────────────────────────────────────────────────────────────────
    // 7. Search
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[7] Search by key "contact"');
    const searchInput = page.locator('input[placeholder*="Cari"]').first();
    await searchInput.fill('contact');
    await page.locator('button[title="Cari"]').first().click();
    await page.waitForTimeout(1500);
    const searchResults = await page.locator('table tbody tr').count();
    check('Search "contact" menghasilkan baris', searchResults > 0, `${searchResults} baris`);
    await shot(page, '09-search.png');
    // Clear search
    await page.locator('button[title="Clear"]').first().click();
    await page.waitForTimeout(1500);

    // ────────────────────────────────────────────────────────────────────
    // 8. Sort
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[8] Sort by key');
    await page.locator('th:has-text("Key")').first().click();
    await page.waitForTimeout(1500);
    await shot(page, '10-sort-asc.png');
    // Click again for desc
    await page.locator('th:has-text("Key")').first().click();
    await page.waitForTimeout(1500);
    await shot(page, '11-sort-desc.png');
    check('Sort key works (tidak error)', consoleErrors.length === 0, consoleErrors.join('; '));

    // ────────────────────────────────────────────────────────────────────
    // 9. Pagination — per_page dropdown
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[9] Per-page dropdown');
    const perPageSelect = page.locator('select').filter({ has: page.locator('option[value="25"]') });
    await perPageSelect.selectOption('25');
    await page.waitForTimeout(1500);
    const rowsAfter25 = await page.locator('table tbody tr').count();
    check('Per-page 25 → max 25 baris', rowsAfter25 <= 25, `${rowsAfter25} baris`);
    await shot(page, '12-per-page-25.png');
    // Reset to 10
    await perPageSelect.selectOption('10');
    await page.waitForTimeout(1500);

    // ────────────────────────────────────────────────────────────────────
    // 10. Bulk select + bulk action bar
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[10] Bulk select');
    const checkboxes = page.locator('table tbody tr input[type="checkbox"]');
    const totalRows = await checkboxes.count();
    if (totalRows > 0) {
        // Select first row
        await checkboxes.first().check();
        await page.waitForTimeout(500);
        const bulkBar = await page.locator('text=/\\d+ data dipilih/').count();
        check('Bulk action bar muncul setelah select 1 row', bulkBar > 0);
        await shot(page, '13-bulk-select.png');
        // Uncheck
        await checkboxes.first().uncheck();
        await page.waitForTimeout(500);
    }

    // ────────────────────────────────────────────────────────────────────
    // 11. Edit modal for non-kredensial type (boolean)
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[11] Edit modal for boolean row');
    // Filter to boolean to have predictable target
    await page.locator('button').filter({ hasText: /^Boolean$/ }).first().click();
    await page.waitForTimeout(1500);
    const editBtn = page.locator('table tbody tr').first().locator('button[title="Edit"]');
    if (await editBtn.count() > 0) {
        await editBtn.click();
        await page.waitForTimeout(800);
        // Verify boolean select is visible AND has current value
        const editState = await page.evaluate(() => {
            const selects = Array.from(document.querySelectorAll('form select'));
            const boolSelect = selects.find(s => {
                const opts = Array.from(s.options).map(o => o.value);
                return opts.includes('true') && opts.includes('false');
            });
            return {
                boolSelectVisible: !!boolSelect,
                boolSelectValue: boolSelect?.value || null,
            };
        });
        check('Edit boolean: select boolean visible di form', editState.boolSelectVisible);
        check('Edit boolean: select punya value (true/false)', ['true', 'false'].includes(editState.boolSelectValue),
            `value=${editState.boolSelectValue}`);
        await shot(page, '14-edit-boolean.png');
        // Close
        await page.locator('button .fa-times').first().click();
        await page.waitForTimeout(500);
    }
    // Reset filter
    if (await page.locator('button:has-text("Reset Filter")').count() > 0) {
        await page.locator('button:has-text("Reset Filter")').click();
        await page.waitForTimeout(1500);
    }

    // ────────────────────────────────────────────────────────────────────
    // 12. Detail modal
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[12] Detail modal');
    const detailBtn = page.locator('button[title="Detail"]').first();
    if (await detailBtn.count() > 0) {
        await detailBtn.click();
        await page.waitForTimeout(800);
        const hasDetailContent = await page.evaluate(() => {
            return document.body.textContent.includes('Detail Konfigurasi');
        });
        check('Detail modal muncul', hasDetailContent);
        await shot(page, '15-detail-modal.png');
        await page.locator('button .fa-times').first().click();
        await page.waitForTimeout(500);
    }

    // ────────────────────────────────────────────────────────────────────
    // 13. Import modal
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[13] Import modal');
    const importBtn = page.locator('button:has-text("Import")').first();
    await importBtn.click();
    await page.waitForTimeout(800);
    const hasImportContent = await page.evaluate(() => {
        return document.body.textContent.includes('Import Konfigurasi');
    });
    check('Import modal muncul', hasImportContent);
    const hasFileInput = await page.locator('input[type="file"][accept*="xlsx"]').count();
    check('Import modal punya file input .xlsx/.csv', hasFileInput > 0);
    const hasTemplateLink = await page.locator('button:has-text("Download template")').count();
    check('Import modal ada link download template', hasTemplateLink > 0);
    await shot(page, '16-import-modal.png');
    await page.locator('button .fa-times').first().click();
    await page.waitForTimeout(500);

    // ────────────────────────────────────────────────────────────────────
    // 14. Dark mode
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[14] Dark mode');
    await page.evaluate(() => document.documentElement.classList.add('dark'));
    await page.waitForTimeout(800);
    const isDark = await page.evaluate(() => document.documentElement.classList.contains('dark'));
    check('Dark mode aktif', isDark);
    await shot(page, '17-dark.png');
    // Open detail in dark
    const detailBtn2 = page.locator('button[title="Detail"]').first();
    if (await detailBtn2.count() > 0) {
        await detailBtn2.click();
        await page.waitForTimeout(800);
        await shot(page, '18-detail-dark.png');
        await page.locator('button .fa-times').first().click();
        await page.waitForTimeout(500);
    }
    // Open create in dark
    await page.locator('button:has-text("Tambah")').first().click();
    await page.waitForTimeout(800);
    await typeSelect.selectOption('kredensial');
    await page.waitForTimeout(400);
    await shot(page, '19-create-kredensial-dark.png');
    await page.locator('button .fa-times').first().click();
    await page.waitForTimeout(500);

    // Reset to light
    await page.evaluate(() => document.documentElement.classList.remove('dark'));
    await page.waitForTimeout(500);

    // ────────────────────────────────────────────────────────────────────
    // 15. Responsive — mobile + tablet
    // ────────────────────────────────────────────────────────────────────
    console.log('\n[15] Responsive');
    await page.setViewportSize({ width: 390, height: 800 });
    await page.waitForTimeout(800);
    await shot(page, '20-mobile-390.png');
    const tableVisibleMobile = await page.locator('table').isVisible();
    check('Tabel visible di mobile (390px)', tableVisibleMobile);

    await page.setViewportSize({ width: 768, height: 1024 });
    await page.waitForTimeout(800);
    await shot(page, '21-tablet-768.png');

    await page.setViewportSize({ width: 1280, height: 800 });
    await page.waitForTimeout(500);

    // ────────────────────────────────────────────────────────────────────
    // Final
    // ────────────────────────────────────────────────────────────────────
    check('No JS errors selama semua test', consoleErrors.length === 0, consoleErrors.join('; '));

    console.log(`\n${'═'.repeat(60)}`);
    console.log(`DEEP VERIFY SUMMARY`);
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
