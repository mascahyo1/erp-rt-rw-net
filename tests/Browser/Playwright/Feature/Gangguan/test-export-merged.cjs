// E2E test: Export Gangguan ke Excel — verify merged cells untuk multiple PIC.
// Test alur:
//   1. Login as admin perusahaan
//   2. GET /operator-perusahaan/gangguan/export → download xlsx
//   3. Verify response status + content-type + parse xlsx + check merged cells
//   4. Verify tiket dengan multi-PIC punya merged rows (rowspan)
//   5. Verify PIC Tambahan muncul di multiple rows

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');


const BASE = require('../../support/baseUrl.cjs');
const PROJECT_ROOT = path.resolve(__dirname, '..', '..', '..', '..', '..').replace(/\\/g, '/');
const RESULT_DIR = path.join(__dirname, 'screenshots-export');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

function phpExec(code) {
    const tmpScript = path.join(PROJECT_ROOT, '.claude', 'tmp_export_test.php');
    const bootstrap = `<?php
require '${PROJECT_ROOT.replace(/\//g, '\\\\')}\\\\vendor\\\\autoload.php';
$app = require '${PROJECT_ROOT.replace(/\//g, '\\\\')}\\\\bootstrap\\\\app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
`;
    fs.writeFileSync(tmpScript, bootstrap + code);
    const out = execSync(`php "${tmpScript}"`, { cwd: PROJECT_ROOT, encoding: 'utf8' });
    try { fs.unlinkSync(tmpScript); } catch (e) {}
    return out.trim();
}

async function main() {
    const log = (m) => console.log(`[${new Date().toISOString().slice(11, 23)}] ${m}`);
    let pass = 0, fail = 0;
    const results = [];
    const assert = (cond, label, info) => {
        if (cond) { log(`✅ ${label}${info ? ' — ' + info : ''}`); pass++; }
        else { log(`❌ ${label}${info ? ' — ' + info : ''}`); fail++; }
        results.push({ label, pass: cond, info });
    };

    // ===== STEP 0: Siapkan tiket dengan multi-PIC =====
    log('\n=== STEP 0: Siapkan tiket dgn 1 main + 2 additional PIC ===');
    const setupResult = phpExec(`
        $admin = \\App\\Models\\AdminCompany::where('email', 'admin@netsejahtera.com')->first();
        $companyId = $admin->company_id;
        $ci = \\App\\Models\\CustInternet::whereHas('customer', fn($q) => $q->where('company_id', $companyId))->where('internet_status', 'active')->first();
        $employees = \\App\\Models\\Employee::where('company_id', $companyId)->take(3)->get();
        $main = $employees[0];
        $add1 = $employees[1];
        $add2 = $employees[2];
        $g = new \\App\\Models\\Gangguan();
        $g->code = 'TKT-EXPORT-TEST-' . strtoupper(\\Str::random(4));
        $g->cust_internet_id = $ci->id;
        $g->catatan = 'E2E test merged cells export';
        $g->status_pengerjaan = 'open';
        $g->status_verifikasi = 'pending';
        $g->issue_dimulai_dari = now();
        $g->save();
        \\App\\Models\\SupportTicketPic::create(['support_ticket_id' => $g->id, 'employee_id' => $main->id, 'is_main_pic' => true]);
        \\App\\Models\\SupportTicketPic::create(['support_ticket_id' => $g->id, 'employee_id' => $add1->id, 'is_main_pic' => false]);
        \\App\\Models\\SupportTicketPic::create(['support_ticket_id' => $g->id, 'employee_id' => $add2->id, 'is_main_pic' => false]);
        echo $g->code . '|' . $main->name . '|' . $add1->name . '|' . $add2->name;
    `);
    const [testCode, mainName, add1Name, add2Name] = setupResult.split('|');
    log(`  Tiket test: ${testCode} (main=${mainName}, add1=${add1Name}, add2=${add2Name})`);

    // ===== STEP 1: Login admin via Playwright =====
    log('\n=== STEP 1: Login admin perusahaan ===');
    const browser = await chromium.launch({ headless: false, slowMo: 400 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 }, acceptDownloads: true });
    const page = await ctx.newPage();
    page.on('pageerror', e => console.log('  PAGEERROR:', e.message));

    await page.goto(BASE + '/login-perusahaan', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    await page.locator('button:has(.fa-building)').first().click();
    await page.waitForTimeout(1500);
    await page.locator('input[placeholder*="Cari perusahaan"]').first().fill('Net Sejahtera');
    await page.waitForTimeout(2000);
    await page.locator(`button:has-text("PT Net Sejahtera Abadi")`).first().click();
    await page.waitForTimeout(800);
    await page.fill('input[type="email"]', 'admin@netsejahtera.com');
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);
    log(`  → Login OK, URL: ${page.url()}`);

    // ===== STEP 2: Download export via direct request =====
    log('\n=== STEP 2: GET /export → download xlsx ===');
    const cookies = await ctx.cookies();
    const cookieHeader = cookies.map(c => `${c.name}=${c.value}`).join('; ');

    const xlsxPath = path.join(RESULT_DIR, 'gangguan-export.xlsx');
    const resp = await page.request.get(BASE + '/operator-perusahaan/gangguan/export', {
        headers: { 'Cookie': cookieHeader }
    });
    log(`  → Response: HTTP ${resp.status()} content-type=${resp.headers()['content-type']}`);
    assert(resp.ok(), 'Export endpoint returns 200', `status: ${resp.status()}`);
    assert((resp.headers()['content-type'] || '').includes('spreadsheet'), 'Content-Type = xlsx');

    const buffer = await resp.body();
    fs.writeFileSync(xlsxPath, buffer);
    log(`  → Saved ${buffer.length} bytes to ${xlsxPath}`);

    // ===== STEP 3: Parse xlsx + verify merged cells =====
    log('\n=== STEP 3: Parse xlsx + verify merged cells untuk test tiket ===');
    // Use python via spawn (openpyxl ada di Python)
    const pyScript = `
import openpyxl
wb = openpyxl.load_workbook(r'${xlsxPath.replace(/\\/g, '\\\\')}')
ws = wb.active
print(f'SHEET:{ws.title}')
print(f'DIM:{ws.dimensions}')

# Find test tiket
test_code = '${testCode}'
test_row = None
for row in ws.iter_rows():
    for c in row:
        if c.value and test_code in str(c.value):
            test_row = c.row
            break
    if test_row: break
print(f'TEST_ROW:{test_row}')

# Get merged cells for this tiket
if test_row:
    merged_for_tiket = [str(r) for r in ws.merged_cells.ranges if test_row <= r.min_row and r.max_row <= test_row + 3]
    print(f'MERGED:{",".join(merged_for_tiket)}')

# Get values of test tiket rows (first 3 rows after test_row)
if test_row:
    for offset in range(4):
        vals = [ws.cell(test_row+offset, cc).value for cc in range(1, 13)]
        print(f'VAL:{test_row+offset}:{vals}')
`;
    fs.writeFileSync(path.join(RESULT_DIR, 'parse.py'), pyScript);
    const pyOut = execSync(`python "${path.join(RESULT_DIR, 'parse.py')}"`, { encoding: 'utf8' });
    log(`Python parse:\n${pyOut.trim()}`);

    // Parse output
    const lines = pyOut.trim().split('\n');
    const sheetLine = lines.find(l => l.startsWith('SHEET:'));
    const dimLine = lines.find(l => l.startsWith('DIM:'));
    const rowLine = lines.find(l => l.startsWith('TEST_ROW:'));
    const mergedLine = lines.find(l => l.startsWith('MERGED:'));
    const valLines = lines.filter(l => l.startsWith('VAL:'));

    assert(sheetLine && sheetLine.includes('Gangguan'), 'Sheet name = Gangguan');
    assert(rowLine && !rowLine.includes('None'), 'Test tiket ditemukan di export', `row: ${rowLine}`);
    assert(mergedLine && mergedLine.includes('A2:') || mergedLine.includes('A3:'), 'Test tiket punya merged cells', `merged: ${mergedLine}`);

    // Verify row data
    if (valLines.length >= 3) {
        const row1Match = valLines[0].includes(mainName) && valLines[0].includes(add1Name);
        const row2Match = valLines[1].includes(add2Name) || valLines[1].endsWith('None, -]');
        const row3Match = valLines[2].endsWith('None, -]') || valLines[2].includes('-');
        assert(row1Match, `Row 1: PJ Utama=${mainName}, PJ Tambahan Row1=${add1Name}`);
        assert(row2Match, `Row 2: PJ Tambahan = ${add2Name} atau -`);
    } else {
        assert(false, 'Tidak cukup rows untuk verify multi-PIC', `got ${valLines.length} rows`);
    }

    // ===== STEP 4: Verify merged cells cover No/Kode/PJ Utama =====
    log('\n=== STEP 4: Verify merged cells cover No, Kode, PJ Utama (rowspan) ===');
    if (rowLine && mergedLine) {
        const testRow = parseInt(rowLine.split(':')[1]);
        // Ambil semua setelah "MERGED:" prefix (kolam "A2:A4,C2:C4,..." yang ada banyak ":")
        const idx = mergedLine.indexOf(':');
        const merged = idx >= 0 ? mergedLine.substring(idx + 1) : '';
        log(`  Test tiket row: ${testRow}, merged cells: ${merged}`);
        const noMatch = new RegExp(`\\bA${testRow}:A\\d+\\b`).test(merged);
        const kodeMatch = new RegExp(`\\bB${testRow}:B\\d+\\b`).test(merged);
        const picUtamaMatch = new RegExp(`\\bE${testRow}:E\\d+\\b`).test(merged);
        assert(noMatch, 'No (A) di-merge (rowspan)');
        assert(kodeMatch, 'Kode (B) di-merge (rowspan)');
        assert(picUtamaMatch, 'PJ Utama (E) di-merge (rowspan)');
    }

    log(`\n=== SCREENSHOTS ===`);
    log(`Files in ${RESULT_DIR}`);
    fs.readdirSync(RESULT_DIR).forEach(f => log(`  ${f}`));

    log(`\n=== FINAL ===`);
    log(`Tiket ${testCode} dengan 1 main + 2 additional PIC verified end-to-end`);

    log(`\n=== RESULT: ${pass} pass, ${fail} fail ===`);
    if (fail > 0) {
        log('Failed tests:');
        results.filter(r => !r.pass).forEach(r => log(`  ✗ ${r.label}${r.info ? ' — ' + r.info : ''}`));
    }
    await browser.close();
    process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
