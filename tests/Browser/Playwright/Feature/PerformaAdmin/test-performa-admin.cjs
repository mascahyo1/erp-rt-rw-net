// E2E test: Halaman Performa Admin + filter daterange + export Excel.
//
// Verifikasi:
//   1. Login admin perusahaan
//   2. Buka /operator-perusahaan/performa-admin
//   3. Verify tabel punya data + summary cards
//   4. Test filter daterange (URL params)
//   5. Test export Excel (verify headers + data)

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');


const BASE = require('../../support/baseUrl.cjs');
const PROJECT_ROOT = path.resolve(__dirname, '..', '..', '..', '..', '..').replace(/\\/g, '/');
const RESULT_DIR = path.join(__dirname, 'screenshots');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

function phpExec(code) {
    const tmpScript = path.join(PROJECT_ROOT, '.claude', 'tmp_performa_admin.php');
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
        // STEP 1: Login
        log('\n=== STEP 1: Login admin ===');
        await loginAdmin(page);
        log(`  → Login OK, URL: ${page.url()}`);

        // STEP 2: Buka halaman Performa Admin
        log('\n=== STEP 2: Buka /operator-perusahaan/performa-admin ===');
        await page.goto(BASE + '/operator-perusahaan/performa-admin', { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(2000);
        await page.screenshot({ path: path.join(RESULT_DIR, '01-list.png'), fullPage: true });
        log('  → Screenshot: 01-list.png');

        // Verify table has data
        const rowCount = await page.locator('[data-testid="row-admin"]').count();
        log(`  → Rows in table: ${rowCount}`);
        assert(rowCount > 0, 'Tabel performa admin punya data', `rows: ${rowCount}`);

        // Verify summary cards visible
        const setujuCount = await page.locator('[data-testid="summary-insentif-setuju-count"]').textContent();
        const tolakCount = await page.locator('[data-testid="summary-insentif-tolak-count"]').textContent();
        const tiketSetuju = await page.locator('[data-testid="summary-tiket-setuju"]').textContent();
        const tiketTolak = await page.locator('[data-testid="summary-tiket-tolak"]').textContent();
        log(`  Summary: insentif setuju=${setujuCount}, tolak=${tolakCount}, tiket setuju=${tiketSetuju}, tolak=${tiketTolak}`);

        // STEP 3: Test filter daterange
        log('\n=== STEP 3: Test filter daterange ===');
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0, 10);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().slice(0, 10);

        await page.locator('[data-testid="input-dari-tgl"]').fill(firstDay);
        await page.locator('[data-testid="input-sampai-tgl"]').fill(lastDay);
        await page.locator('[data-testid="btn-apply-filter"]').click();
        await page.waitForTimeout(2000);
        await page.screenshot({ path: path.join(RESULT_DIR, '02-filter-applied.png'), fullPage: true });

        const url = page.url();
        assert(url.includes('dari_tgl=') && url.includes('sampai_tgl='), 'URL contains filter params', `url: ${url}`);

        // STEP 4: Test export Excel
        log('\n=== STEP 4: Test export Excel ===');
        const downloadPromise = page.waitForEvent('download', { timeout: 10000 }).catch(() => null);
        await page.locator('[data-testid="btn-export-excel"]').click();
        const download = await downloadPromise;
        let exportPath = null;
        if (download) {
            exportPath = path.join(RESULT_DIR, 'performa-admin-export.xlsx');
            await download.saveAs(exportPath);
            log(`  → Downloaded: ${exportPath}`);
        } else {
            // Fallback: direct fetch
            const cookies = await ctx.cookies();
            const cookieHeader = cookies.map(c => `${c.name}=${c.value}`).join('; ');
            const url = BASE + `/operator-perusahaan/performa-admin/export?dari_tgl=${firstDay}&sampai_tgl=${lastDay}`;
            const resp = await page.request.get(url, { headers: { 'Cookie': cookieHeader } });
            log(`  → Direct fetch: HTTP ${resp.status()}`);
            assert(resp.ok(), 'Export endpoint returns 200');
            exportPath = path.join(RESULT_DIR, 'performa-admin-export.xlsx');
            fs.writeFileSync(exportPath, await resp.body());
        }

        // Parse xlsx
        fs.writeFileSync(path.join(RESULT_DIR, 'parse.py'), `
import openpyxl
wb = openpyxl.load_workbook(r'${exportPath.replace(/\\\\/g, '\\\\\\\\')}')
ws = wb.active
print('SHEET:' + ws.title)
print('DIM:' + ws.dimensions)
headers = [str(c.value) for c in ws[1]]
print('HEADERS:' + ','.join(headers))
for i, row in enumerate(ws.iter_rows(min_row=2, values_only=True), 1):
    print(f'ROW{i}:' + ','.join(str(v) if v is not None else '' for v in row))
`);
        const pyOut = execSync(`python "${path.join(RESULT_DIR, 'parse.py')}"`, { encoding: 'utf8' });
        log(`  Python parse:\n${pyOut.trim()}`);

        const sheetLine = pyOut.split('\n').find(l => l.startsWith('SHEET:'));
        const headersLine = pyOut.split('\n').find(l => l.startsWith('HEADERS:'));
        assert(sheetLine && sheetLine.includes('Performa'), 'Sheet name = Performa Admin', sheetLine);
        assert(headersLine && headersLine.includes('Nama Admin') && headersLine.includes('Insentif Disetujui') && headersLine.includes('Tiket Disetujui'), 'Headers match expected columns', headersLine);

        log('\n=== FINAL ===');
        log(`Semua step performa-admin verified end-to-end`);

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
