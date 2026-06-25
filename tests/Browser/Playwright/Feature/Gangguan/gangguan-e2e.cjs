// E2E test: Gangguan flow end-to-end (3 portal).
//
// Tujuan: Verifikasi fitur Issue/Gangguan bekerja lintas portal:
//   1. Customer lapor tiket dari web customer
//   2. Karyawan lihat + assign ke dirinya + kerjakan (status=in_progress) + tandai selesai
//   3. Perusahaan verify hasil resolution (approve/reject)
//
// Flow per step: LOG + SCREENSHOT + VERIFY (per [teliti-workflow] + [modal-data-testid-convention])
//
// Run: node tests/Browser/Playwright/Feature/Gangguan/gangguan-e2e.cjs

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');


const BASE = require('../../support/baseUrl.cjs');
const PROJECT_ROOT = path.resolve(__dirname, '..', '..', '..', '..', '..').replace(/\\/g, '/');
const RESULT_DIR = path.join(__dirname, 'screenshots');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

function phpExec(code) {
    const tmpScript = path.join(PROJECT_ROOT, '.claude', 'tmp_gangguan_test.php');
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

async function loginAs(page, email, loginUrl, log) {
    log(`  → Login URL: ${BASE}${loginUrl}`);
    await page.goto(BASE + loginUrl, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    await page.locator('button:has(.fa-building)').first().click();
    await page.waitForTimeout(1500);
    await page.locator('input[placeholder*="Cari perusahaan"]').first().fill('Net Sejahtera');
    await page.waitForTimeout(2000);
    await page.locator(`button:has-text("PT Net Sejahtera Abadi")`).first().click();
    await page.waitForTimeout(800);
    await page.fill('input[type="email"]', email);
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);
}

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 400 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });

    const log = (m) => console.log(`[${new Date().toISOString().slice(11, 23)}] ${m}`);
    let pass = 0, fail = 0;
    const results = [];
    const assert = (cond, label, info) => {
        if (cond) { log(`✅ ${label}${info ? ' — ' + info : ''}`); pass++; }
        else { log(`❌ ${label}${info ? ' — ' + info : ''}`); fail++; }
        results.push({ label, pass: cond, info });
    };

    // ===== STEP 0: Get customer + cust_internet IDs =====
    log('\n=== STEP 0: Get customer + cust_internet IDs ===');
    const seedResult = phpExec(`
        $c = \\App\\Models\\Customer::where('email', 'test+1781247641870@example.com')->first();
        $ci = \\App\\Models\\CustInternet::where('customer_id', $c->id)->where('internet_status','active')->latest()->first();
        echo $c->id . '|' . $ci->id . '|' . $ci->account_number;
    `);
    const [customerId, custInetId, accountNumber] = seedResult.split('|');
    log(`  Customer: ${customerId}`);
    log(`  CustInternet: ${custInetId} (acc: ${accountNumber})`);

    const gangguanId = { value: null };

    try {
        // ===== PHASE 1: Customer lapor tiket =====
        log('\n========== PHASE 1: Customer Lapor Tiket ==========');
        const page1 = await ctx.newPage();
        page1.on('pageerror', e => console.log('  [Customer] PAGEERROR:', e.message));

        log('\n[Customer] STEP 1.1: Login');
        await loginAs(page1, 'test+1781247641870@example.com', '/login-pelanggan', log);
        const url1 = page1.url();
        log(`  → URL: ${url1}`);
        assert(url1.includes('/customer/'), '[Customer] Login sukses');

        log('\n[Customer] STEP 1.2: Navigate to /customer/gangguan');
        await page1.goto(BASE + '/customer/gangguan', { waitUntil: 'domcontentloaded' });
        await page1.waitForLoadState('networkidle');
        await page1.waitForTimeout(2000);
        await page1.screenshot({ path: path.join(RESULT_DIR, '01-customer-01-list.png'), fullPage: true });
        log('  → Screenshot: 01-customer-01-list.png');

        log('\n[Customer] STEP 1.3: Click "Buat Laporan"');
        await page1.getByTestId('btn-buat-laporan').click();
        await page1.waitForTimeout(1500);
        await page1.screenshot({ path: path.join(RESULT_DIR, '01-customer-02-modal.png'), fullPage: true });

        // Verify modal visible
        await page1.getByTestId('modal-create').waitFor({ state: 'visible', timeout: 5000 });
        assert(await page1.getByTestId('modal-create').isVisible(), '[Customer] Modal Create visible');

        log('\n[Customer] STEP 1.4: Pilih kode langganan + isi catatan');
        // Pilih cust_internet dari dropdown
        await page1.getByTestId('select-cust-internet').selectOption(custInetId);
        await page1.waitForTimeout(500);
        const selectedValue = await page1.getByTestId('select-cust-internet').inputValue();
        assert(selectedValue === custInetId, '[Customer] Kode langganan ter-select', `value: ${selectedValue}`);

        const catatan = 'Internet putus sejak pagi, mohon dicek';
        await page1.getByTestId('textarea-catatan').fill(catatan);
        await page1.waitForTimeout(300);
        const catatanValue = await page1.getByTestId('textarea-catatan').inputValue();
        assert(catatanValue === catatan, '[Customer] Catatan terisi', `value: ${catatanValue}`);

        log('\n[Customer] STEP 1.5: Click "Kirim Laporan"');
        // Listen for POST response
        let postResp = null;
        const postHandler = async (response) => {
            if (response.request().method() === 'POST' && response.url().includes('/customer/gangguan') && !response.url().includes('/search/')) {
                postResp = { status: response.status(), url: response.url() };
            }
        };
        page1.on('response', postHandler);
        await page1.getByTestId('btn-kirim').click();
        await page1.waitForTimeout(3000);
        page1.off('response', postHandler);
        log(`  → POST response: ${JSON.stringify(postResp)}`);
        assert(postResp?.status === 302 || postResp?.status === 200, `[Customer] POST status ${postResp?.status}`);
        await page1.screenshot({ path: path.join(RESULT_DIR, '01-customer-03-after-submit.png'), fullPage: true });

        // Verify DB
        const dbCheck = phpExec(`
            $g = \\App\\Models\\Gangguan::whereHas('custInternet', fn($q) => $q->where('customer_id', '${customerId}'))
                ->where('catatan', '${catatan}')
                ->latest('created_at')->first();
            if (!$g) { echo 'NOT_FOUND'; exit; }
            echo $g->id . '|' . $g->code . '|' . $g->status_pengerjaan->value . '|' . $g->status_verifikasi->value;
        `);
        log(`  → DB: ${dbCheck}`);
        const [gid, gcode, gsp, gsv] = dbCheck.split('|');
        gangguanId.value = gid;
        assert(gsp === 'open', '[Customer] DB status_pengerjaan=open');
        assert(gsv === 'pending', '[Customer] DB status_verifikasi=pending');
        await page1.close();

        // ===== PHASE 2: Karyawan edit (assign + in_progress) =====
        log('\n========== PHASE 2: Karyawan Edit Tiket ==========');
        const page2 = await ctx.newPage();
        page2.on('pageerror', e => console.log('  [Karyawan] PAGEERROR:', e.message));

        log('\n[Karyawan] STEP 2.1: Login as ahmad');
        await loginAs(page2, 'ahmad@netsejahtera.com', '/login-karyawan', log);
        const url2 = page2.url();
        log(`  → URL: ${url2}`);
        assert(url2.includes('karyawan'), '[Karyawan] Login sukses');

        log('\n[Karyawan] STEP 2.2: Navigate to /karyawan/gangguan');
        await page2.goto(BASE + '/karyawan/gangguan', { waitUntil: 'domcontentloaded' });
        await page2.waitForLoadState('networkidle');
        await page2.waitForTimeout(2000);
        await page2.screenshot({ path: path.join(RESULT_DIR, '02-karyawan-01-list.png'), fullPage: true });

        log('\n[Karyawan] STEP 2.3: Click Edit on the new ticket');
        // Cari row dengan code yang baru dibuat
        const editClicked = await page2.evaluate((code) => {
            const rows = document.querySelectorAll('tbody tr');
            for (const row of rows) {
                if (row.textContent && row.textContent.includes(code)) {
                    const editBtn = row.querySelector('button[title="Edit"]');
                    if (editBtn) { editBtn.click(); return true; }
                }
            }
            return false;
        }, gcode);
        assert(editClicked, '[Karyawan] Edit button clicked for new ticket');
        await page2.waitForTimeout(1500);
        await page2.screenshot({ path: path.join(RESULT_DIR, '02-karyawan-02-edit-modal.png'), fullPage: true });

        // Verify modal edit
        await page2.getByTestId('modal-edit').waitFor({ state: 'visible', timeout: 5000 });
        assert(await page2.getByTestId('modal-edit').isVisible(), '[Karyawan] Modal Edit visible');

        log('\n[Karyawan] STEP 2.4: Set assigned + status in_progress');
        // Get employee ID for ahmad
        const employeeId = phpExec(`echo \\App\\Models\\Employee::where('email', 'ahmad@netsejahtera.com')->value('id');`);
        await page2.getByTestId('select-assigned').selectOption(employeeId);
        await page2.waitForTimeout(300);
        await page2.getByTestId('select-status-pengerjaan').selectOption('in_progress');
        await page2.waitForTimeout(300);

        log('\n[Karyawan] STEP 2.5: Click Update');
        log(`  → gid = ${gid}`);
        let putResp = null;
        const allResponses = [];
        const putHandler = async (response) => {
            allResponses.push({ method: response.request().method(), url: response.url(), status: response.status() });
            log(`  → response: ${response.request().method()} ${response.url()} → ${response.status()}`);
            if (response.request().method() === 'POST' && response.url().includes(`/karyawan/gangguan/${gid}`)) {
                putResp = { status: response.status() };
            }
        };
        page2.on('response', putHandler);
        await page2.getByTestId('btn-update').click();
        await page2.waitForTimeout(3000);
        page2.off('response', putHandler);
        log(`  → Update response: ${JSON.stringify(putResp)}`);
        log(`  → All responses during update: ${JSON.stringify(allResponses)}`);
        await page2.screenshot({ path: path.join(RESULT_DIR, '02-karyawan-02b-after-update.png'), fullPage: true });
        assert(putResp !== null, '[Karyawan] Update endpoint dipanggil');

        // Verify DB
        const dbCheck2 = phpExec(`
            $g = \\App\\Models\\Gangguan::find('${gid}');
            echo $g->status_pengerjaan->value . '|' . ($g->assigned_to_employee_id ?? 'null');
        `);
        log(`  → DB: ${dbCheck2}`);
        const [sp2, eid2] = dbCheck2.split('|');
        assert(sp2 === 'in_progress', '[Karyawan] DB status_pengerjaan=in_progress');
        assert(eid2 === employeeId, '[Karyawan] DB assigned_to_employee_id=ahmad');

        log('\n[Karyawan] STEP 2.6: Click "Tandai Selesai" (resolve)');
        // Re-open resolve modal
        const resolveClicked = await page2.evaluate((code) => {
            const rows = document.querySelectorAll('tbody tr');
            for (const row of rows) {
                if (row.textContent && row.textContent.includes(code)) {
                    const resolveBtn = row.querySelector('button[title="Tandai Selesai"]');
                    if (resolveBtn) { resolveBtn.click(); return true; }
                }
            }
            return false;
        }, gcode);
        if (resolveClicked) {
            await page2.waitForTimeout(1500);
            await page2.screenshot({ path: path.join(RESULT_DIR, '02-karyawan-03-resolve-modal.png'), fullPage: true });
            await page2.getByTestId('modal-resolve').waitFor({ state: 'visible', timeout: 5000 });
            await page2.getByTestId('btn-confirm-resolve').click();
            await page2.waitForTimeout(3000);
            await page2.screenshot({ path: path.join(RESULT_DIR, '02-karyawan-04-after-resolve.png'), fullPage: true });
        }

        // Verify DB
        const dbCheck3 = phpExec(`
            $g = \\App\\Models\\Gangguan::find('${gid}');
            echo $g->status_pengerjaan->value . '|' . ($g->issue_diselesaikan_pada ? 'yes' : 'no');
        `);
        log(`  → DB: ${dbCheck3}`);
        const [sp3, resolved3] = dbCheck3.split('|');
        assert(sp3 === 'resolved', '[Karyawan] DB status_pengerjaan=resolved');
        assert(resolved3 === 'yes', '[Karyawan] DB issue_diselesaikan_pada set');
        await page2.close();

        // ===== PHASE 3: Perusahaan verify (approve) =====
        log('\n========== PHASE 3: Perusahaan Verify ==========');
        const page3 = await ctx.newPage();
        page3.on('pageerror', e => console.log('  [Perusahaan] PAGEERROR:', e.message));

        log('\n[Perusahaan] STEP 3.1: Login as admin');
        await loginAs(page3, 'admin@netsejahtera.com', '/login-perusahaan', log);
        const url3 = page3.url();
        log(`  → URL: ${url3}`);
        assert(url3.includes('admin-perusahaan') || url3.includes('operator-perusahaan'), '[Perusahaan] Login sukses');

        log('\n[Perusahaan] STEP 3.2: Navigate to /operator-perusahaan/gangguan');
        await page3.goto(BASE + '/operator-perusahaan/gangguan', { waitUntil: 'domcontentloaded' });
        await page3.waitForLoadState('networkidle');
        await page3.waitForTimeout(2000);
        await page3.screenshot({ path: path.join(RESULT_DIR, '03-perusahaan-01-list.png'), fullPage: true });

        log('\n[Perusahaan] STEP 3.3: Click Verify on resolved ticket');
        const verifyClicked = await page3.evaluate((code) => {
            const rows = document.querySelectorAll('tbody tr');
            for (const row of rows) {
                if (row.textContent && row.textContent.includes(code)) {
                    const verifyBtn = row.querySelector('button[title="Verifikasi"]');
                    if (verifyBtn) { verifyBtn.click(); return true; }
                }
            }
            return false;
        }, gcode);
        assert(verifyClicked, '[Perusahaan] Verify button clicked');
        await page3.waitForTimeout(1500);
        await page3.screenshot({ path: path.join(RESULT_DIR, '03-perusahaan-02-verify-modal.png'), fullPage: true });

        // Verify modal
        await page3.getByTestId('modal-verify').waitFor({ state: 'visible', timeout: 5000 });
        assert(await page3.getByTestId('modal-verify').isVisible(), '[Perusahaan] Modal Verify visible');

        log('\n[Perusahaan] STEP 3.4: Set status=approved + alasan');
        await page3.getByTestId('select-verify-status').selectOption('approved');
        await page3.waitForTimeout(300);
        await page3.getByTestId('textarea-alasan').fill('Sudah ditindaklanjuti dengan baik, issue resolved');
        await page3.waitForTimeout(300);

        log('\n[Perusahaan] STEP 3.5: Click Verifikasi');
        let verifyResp = null;
        const verifyHandler = async (response) => {
            if (response.request().method() === 'POST' && response.url().includes(`/verify`)) {
                verifyResp = { status: response.status() };
            }
        };
        page3.on('response', verifyHandler);
        await page3.getByTestId('btn-confirm-verify').click();
        await page3.waitForTimeout(3000);
        page3.off('response', verifyHandler);
        log(`  → Verify response: ${JSON.stringify(verifyResp)}`);
        assert(verifyResp !== null, '[Perusahaan] Verify endpoint dipanggil');
        await page3.screenshot({ path: path.join(RESULT_DIR, '03-perusahaan-03-after-verify.png'), fullPage: true });

        // Verify final DB
        const finalDb = phpExec(`
            $g = \\App\\Models\\Gangguan::find('${gid}');
            echo $g->status_pengerjaan->value . '|' . $g->status_verifikasi->value . '|' . $g->alasan_verifikasi;
        `);
        log(`  → Final DB: ${finalDb}`);
        const [fsp, fsv, falasan] = finalDb.split('|');
        assert(fsp === 'resolved', '[Perusahaan FINAL] status_pengerjaan=resolved');
        assert(fsv === 'approved', '[Perusahaan FINAL] status_verifikasi=approved');
        assert(falasan.includes('Sudah ditindaklanjuti'), '[Perusahaan FINAL] alasan_verifikasi saved');
        await page3.close();

        log('\n========== E2E FLOW COMPLETE ==========');
        log(`Tiket ${gcode}:`);
        log(`  - Customer (${accountNumber}) buat laporan ✓`);
        log(`  - Karyawan (ahmad) assign + kerjakan + resolve ✓`);
        log(`  - Perusahaan (admin) verify approved ✓`);

    } catch (e) {
        log(`❌ FATAL: ${e.message}\n${e.stack}`);
    } finally {
        log(`\n=== RESULT: ${pass} pass, ${fail} fail ===`);
        if (fail > 0) {
            log('Failed tests:');
            results.filter(r => !r.pass).forEach(r => log(`  ✗ ${r.label}${r.info ? ' — ' + r.info : ''}`));
        }
        log(`\nScreenshots: ${RESULT_DIR}`);
        await browser.close();
        process.exit(fail > 0 ? 1 : 0);
    }
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
