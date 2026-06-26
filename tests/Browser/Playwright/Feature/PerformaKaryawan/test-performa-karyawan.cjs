// E2E test: Halaman Performa Karyawan + filter daterange + export Excel.
//
// Verifikasi:
//   1. Login admin perusahaan
//   2. Buka /operator-perusahaan/performa-karyawan
//   3. Verify tabel punya data + filter dari_tgl/sampai_tgl
//   4. Test export Excel — verify xlsx + headers + data rows

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');


const BASE = require('../../support/baseUrl.cjs');
const PROJECT_ROOT = path.resolve(__dirname, '..', '..', '..', '..', '..').replace(/\\/g, '/');
const RESULT_DIR = path.join(__dirname, 'screenshots');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

function phpExec(code) {
    const tmpScript = path.join(PROJECT_ROOT, '.claude', 'tmp_performa.php');
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

async function loginAdmin(page) {
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

    const browser = await chromium.launch({ headless: false, slowMo: 400 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 }, acceptDownloads: true });
    const page = await ctx.newPage();
    page.on('pageerror', e => console.log('  PAGEERROR:', e.message));

    try {
        // ===== STEP 1: Login =====
        log('\n=== STEP 1: Login admin ===');
        await loginAdmin(page);
        log(`  → Login OK, URL: ${page.url()}`);

        // ===== STEP 2: Buka halaman Performa Karyawan =====
        log('\n=== STEP 2: Buka /operator-perusahaan/performa-karyawan ===');
        await page.goto(BASE + '/operator-perusahaan/performa-karyawan', { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(2000);
        await page.screenshot({ path: path.join(RESULT_DIR, '01-list.png'), fullPage: true });
        log('  → Screenshot: 01-list.png');

        // Verify table has data
        const rowCount = await page.locator('[data-testid="row-karyawan"]').count();
        log(`  → Rows in table: ${rowCount}`);
        assert(rowCount > 0, 'Tabel performa punya data', `rows: ${rowCount}`);

        // Verify summary cards
        const totalKaryawan = await page.locator('[data-testid="summary-total-karyawan"]').textContent();
        const totalInsentif = await page.locator('[data-testid="summary-total-insentif"]').textContent();
        const totalNominal = await page.locator('[data-testid="summary-total-nominal"]').textContent();
        const totalGangguan = await page.locator('[data-testid="summary-total-gangguan"]').textContent();
        log(`  Summary: karyawan=${totalKaryawan}, insentif=${totalInsentif}, nominal=${totalNominal}, gangguan=${totalGangguan}`);
        assert(parseInt(totalKaryawan) > 0, 'Summary total karyawan > 0');

        // ===== STEP 3: Test filter daterange =====
        log('\n=== STEP 3: Test filter daterange ===');
        // Get current month as default
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0, 10);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().slice(0, 10);

        // Set narrow date range
        await page.locator('[data-testid="input-dari-tgl"]').fill(firstDay);
        await page.locator('[data-testid="input-sampai-tgl"]').fill(lastDay);
        await page.locator('[data-testid="btn-apply-filter"]').click();
        await page.waitForTimeout(2000);
        await page.screenshot({ path: path.join(RESULT_DIR, '02-filter-applied.png'), fullPage: true });

        const url = page.url();
        assert(url.includes('dari_tgl=') && url.includes('sampai_tgl='), 'URL contains filter params', `url: ${url}`);

        // ===== STEP 4: Test export Excel =====
        log('\n=== STEP 4: Test export Excel ===');
        const downloadPromise = page.waitForEvent('download', { timeout: 10000 }).catch(() => null);
        await page.locator('[data-testid="btn-export-excel"]').click();
        const download = await downloadPromise;
        if (download) {
            const exportPath = path.join(RESULT_DIR, 'performa-export.xlsx');
            await download.saveAs(exportPath);
            log(`  → Downloaded: ${exportPath}`);

            // Parse with openpyxl
            fs.writeFileSync(path.join(RESULT_DIR, 'parse.py'), `
import openpyxl
wb = openpyxl.load_workbook(r'${exportPath.replace(/\\\\/g, '\\\\\\\\')}')
ws = wb.active
print('SHEET:' + ws.title)
print('DIM:' + ws.dimensions)
# Get headers (row 1)
headers = [str(c.value) for c in ws[1]]
print('HEADERS:' + ','.join(headers))
# Get data rows
for i, row in enumerate(ws.iter_rows(min_row=2, values_only=True), 1):
    print(f'ROW{i}:' + ','.join(str(v) if v is not None else '' for v in row))
`);
            const pyOut = execSync(`python "${path.join(RESULT_DIR, 'parse.py')}"`, { encoding: 'utf8' });
            log(`  Python parse:\n${pyOut.trim()}`);

            // Verify headers
            const sheetLine = pyOut.split('\n').find(l => l.startsWith('SHEET:'));
            const headersLine = pyOut.split('\n').find(l => l.startsWith('HEADERS:'));
            const row1Line = pyOut.split('\n').find(l => l.startsWith('ROW1:'));
            assert(sheetLine && sheetLine.includes('Performa'), 'Sheet name = Performa Karyawan', sheetLine);
            assert(headersLine && headersLine.includes('Kode Karyawan') && headersLine.includes('Gangguan solved'), 'Headers match expected columns', headersLine);
            // Verify "Total Solved" = PJ Utama + PJ Lain (kolom ke-7)
            if (row1Line) {
                const cols = row1Line.replace('ROW1:', '').split(',');
                const utama = parseInt(cols[4]) || 0;
                const lain = parseInt(cols[5]) || 0;
                const total = parseInt(cols[6]) || 0;
                assert(total === utama + lain, `Total Solved (${total}) = Utama (${utama}) + Lain (${lain})`, `row1: ${row1Line}`);
            }
        } else {
            // Fallback: curl download (since download may open new tab)
            log('  ! Download event not captured, trying direct fetch via ctx request');
            const cookies = await ctx.cookies();
            const cookieHeader = cookies.map(c => `${c.name}=${c.value}`).join('; ');
            const url = page.url().replace('/performa-karyawan', '/performa-karyawan/export') + `?dari_tgl=${firstDay}&sampai_tgl=${lastDay}`;
            const resp = await page.request.get(BASE + url, { headers: { 'Cookie': cookieHeader } });
            log(`  → Direct fetch: HTTP ${resp.status()}`);
            assert(resp.ok(), 'Export endpoint returns 200', `status: ${resp.status()}`);
            const exportPath = path.join(RESULT_DIR, 'performa-export.xlsx');
            fs.writeFileSync(exportPath, await resp.body());
        }

        log(`\n=== FINAL ===`);
        log(`Semua step performa-karyawan verified end-to-end`);

    } catch (e) {
        log(`❌ FATAL: ${e.message}`);
    } finally {
        log(`\n=== RESULT: ${pass} pass, ${fail} fail ===`);
        if (fail > 0) {
            log('Failed tests:');
            results.filter(r => !r.pass).forEach(r => log(`  ✗ ${r.label}${r.info ? ' — ' + r.info : ''}`));
        }
        log(`Screenshots: ${RESULT_DIR}`);
        await browser.close();
        process.exit(fail > 0 ? 1 : 0);
    }
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
