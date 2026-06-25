// E2E test: Karyawan & Perusahaan bisa buat Midtrans payment (on behalf of customer).
//
// Tujuan: Bukti fitur "karyawan/perusahaan bisa input pembayaran Midtrans atas nama
// customer" jalan end-to-end. Customer gaptek → karyawan/admin perusahaan yang
// inisiasi Snap payment → Snap UI terbuka → siapa saja yang pegang akses bisa selesaikan.
//
// LOCATOR: pakai data-testid (lihat .claude-example/memory/modal-data-testid-convention.md)
//   - data-testid="modal-create"          → form modal Tambah Pembayaran
//   - data-testid="input-code"            → input Kode Pembayaran
//   - data-testid="btn-select-invoice"    → span wrapping SearchableSelectAjax invoice
//   - data-testid="input-amount"          → input Jumlah (Rp)
//   - data-testid="select-provider"       → select Provider
//   - data-testid="select-metode"         → select Metode
//   - data-testid="btn-simpan"            → button Simpan di footer modal
//
// Per workflow: setiap step LOG + SCREENSHOT + VERIFY sebelum lanjut.
//
// Run: node tests/Browser/Playwright/Feature/MidtransVerify/karyawan-perusahaan-midtrans-checkout.cjs

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');


const BASE = require('../../support/baseUrl.cjs');
const PROJECT_ROOT = path.resolve(__dirname, '..', '..', '..', '..', '..').replace(/\\/g, '/');
const RESULT_DIR = path.join(__dirname, 'screenshots-karyawan-perusahaan');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

function phpExec(code) {
    const tmpScript = path.join(PROJECT_ROOT, '.claude', 'tmp_kpmidtrans.php');
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

async function pickInvoiceViaSearchable(page, modalTestId, invNoFragment, log) {
    // Klik button di dalam SearchableSelectAjax (di dalam modal Tambah Pembayaran)
    const modal = page.getByTestId(modalTestId);
    // The SearchableSelectAjax renders a button with the placeholder text
    const selectBtn = modal.locator('button').filter({ hasText: 'Pilih Invoice' }).first();
    await selectBtn.click();
    await page.waitForTimeout(1200);
    // Cari di dropdown search input
    const searchInput = page.locator('input[placeholder="Cari..."]').last();
    if (await searchInput.count() > 0 && await searchInput.isVisible()) {
        await searchInput.fill(invNoFragment);
        await page.waitForTimeout(1500);
    }
    // Klik option yang mengandung invoice number
    const clicked = await page.evaluate((frag) => {
        const candidates = document.querySelectorAll('li, button, [role="option"]');
        for (const el of candidates) {
            if (el.offsetParent === null) continue;
            const t = el.textContent || '';
            if (t.includes(frag)) {
                el.click();
                return el.tagName + ': ' + t.substring(0, 60);
            }
        }
        return null;
    }, invNoFragment);
    if (!clicked) throw new Error(`Option dengan "${invNoFragment}" tidak ditemukan di dropdown`);
    await page.waitForTimeout(500);
}

async function runPortal({ ctx, log, assert, label, email, loginUrl, navUrl, postUrl, tagihanPrefix, amount }) {
    log(`\n========== ${label} ==========`);

    // ====== STEP 0: Seed fresh tagihan ======
    log(`\n[${label}] STEP 0: Seed fresh tagihan (prefix: ${tagihanPrefix})`);
    const seedResult = phpExec(`
        $c = \\App\\Models\\Customer::where('email', 'test+1781247641870@example.com')->first();
        $ci = \\App\\Models\\CustInternet::where('customer_id', $c->id)->where('internet_status','active')->latest()->first();
        \\App\\Models\\CustInternetPayment::whereHas('custInternetInvc.custInternet', fn($q) => $q->where('customer_id',$c->id))
            ->whereHas('custInternetInvc', fn($q) => $q->where('invoice_number','LIKE','${tagihanPrefix}-%'))->forceDelete();
        \\App\\Models\\CustInternetInvc::whereHas('custInternet', fn($q) => $q->where('customer_id',$c->id))
            ->where('invoice_number','LIKE','${tagihanPrefix}-%')->forceDelete();
        $inv = \\App\\Models\\CustInternetInvc::create([
            'cust_internet_id' => $ci->id,
            'invoice_number' => '${tagihanPrefix}-' . substr(uniqid(), -6),
            'amount' => ${amount},
            'total_amount' => ${amount},
            'grand_total' => ${amount},
            'due_date' => now()->addDays(7),
            'status' => 'unpaid',
            'payment_status' => 'unpaid',
            'created_by' => $c->id,
            'updated_by' => $c->id,
        ]);
        echo $inv->id . '|' . $inv->invoice_number;
    `);
    const [invId, invNo] = seedResult.split('|');
    log(`  → Seeded: id=${invId}, no=${invNo}`);

    // ====== STEP 1: Login ======
    log(`\n[${label}] STEP 1: Login as ${email}`);
    const page = await ctx.newPage();
    page.on('pageerror', e => console.log(`  [${label}] PAGEERROR:`, e.message));
    page.on('console', msg => { if (msg.type() === 'error') console.log(`  [${label}] CONSOLE-ERR:`, msg.text()); });

    await loginAs(page, email, loginUrl, log);
    const urlAfterLogin = page.url();
    log(`  → URL after login: ${urlAfterLogin}`);
    assert(urlAfterLogin.includes('dashboard') || urlAfterLogin.includes('karyawan') || urlAfterLogin.includes('admin-perusahaan'),
        `[${label}] Login sukses`, `URL: ${urlAfterLogin}`);

    // ====== STEP 2: Navigate to Riwayat Pembayaran ======
    log(`\n[${label}] STEP 2: Navigate to ${navUrl}`);
    await page.goto(BASE + navUrl, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(RESULT_DIR, `kp-${label.toLowerCase()}-01-list.png`), fullPage: true });
    log(`  → Screenshot: kp-${label.toLowerCase()}-01-list.png`);

    // ====== STEP 3: Click "Tambah Pembayaran" → modal terbuka ======
    log(`\n[${label}] STEP 3: Click "Tambah Pembayaran" button`);
    const addBtn = page.locator('button:has-text("Tambah Pembayaran")').first();
    if (await addBtn.count() === 0) throw new Error('Tombol "Tambah Pembayaran" tidak ditemukan');
    await addBtn.click();
    await page.waitForTimeout(1500);
    await page.screenshot({ path: path.join(RESULT_DIR, `kp-${label.toLowerCase()}-02-modal-open.png`), fullPage: true });
    log(`  → Screenshot: kp-${label.toLowerCase()}-02-modal-open.png`);

    // Verify modal visible (via data-testid)
    const modal = page.getByTestId('modal-create');
    await modal.waitFor({ state: 'visible', timeout: 5000 });
    assert(await modal.isVisible(), `[${label}] Modal Tambah Pembayaran visible`);

    // ====== STEP 4: Isi Kode Pembayaran ======
    log(`\n[${label}] STEP 4: Isi input-code`);
    const code = 'BYR-' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '-' + Math.random().toString(36).substring(2, 6).toUpperCase();
    await page.getByTestId('input-code').fill(code);
    await page.waitForTimeout(300);
    const codeValue = await page.getByTestId('input-code').inputValue();
    assert(codeValue === code, `[${label}] input-code terisi dengan benar`, `value: ${codeValue}`);

    // ====== STEP 5: Pilih invoice ======
    log(`\n[${label}] STEP 5: Pilih invoice ${invNo} (via btn-select-invoice)`);
    await pickInvoiceViaSearchable(page, 'modal-create', invNo, log);
    await page.screenshot({ path: path.join(RESULT_DIR, `kp-${label.toLowerCase()}-03-invoice-selected.png`), fullPage: true });
    log(`  → Screenshot: kp-${label.toLowerCase()}-03-invoice-selected.png`);

    // ====== STEP 6: Isi amount ======
    log(`\n[${label}] STEP 6: Isi input-amount = ${amount}`);
    await page.getByTestId('input-amount').fill(String(amount));
    await page.waitForTimeout(300);
    const amountValue = await page.getByTestId('input-amount').inputValue();
    assert(parseFloat(amountValue) === amount, `[${label}] input-amount terisi dengan benar`, `value: ${amountValue}`);

    // ====== STEP 7: Pilih Provider = Midtrans ======
    log(`\n[${label}] STEP 7: Set select-provider = "midtrans"`);
    await page.getByTestId('select-provider').selectOption('midtrans');
    await page.waitForTimeout(500);
    const providerValue = await page.getByTestId('select-provider').inputValue();
    assert(providerValue === 'midtrans', `[${label}] select-provider = "midtrans"`, `actual: ${providerValue}`);
    await page.screenshot({ path: path.join(RESULT_DIR, `kp-${label.toLowerCase()}-04-midtrans-selected.png`), fullPage: true });
    log(`  → Screenshot: kp-${label.toLowerCase()}-04-midtrans-selected.png`);

    // Listen for POST response + new tab (Snap UI)
    let snapResp = null;
    const postHandler = async (response) => {
        if (response.request().method() === 'POST' && response.url().includes(postUrl) && !response.url().includes('/search/')) {
            try {
                const body = await response.text();
                let json = null;
                try { json = JSON.parse(body); } catch (e) {}
                snapResp = { status: response.status(), body: body.substring(0, 500), json };
            } catch (e) { /* ignore */ }
        }
    };
    page.on('response', postHandler);
    const newPagePromise = ctx.waitForEvent('page', { timeout: 15000 }).catch(() => null);

    // ====== STEP 8: Click Simpan → Midtrans flow ======
    log(`\n[${label}] STEP 8: Click btn-simpan → trigger Midtrans Snap`);
    await page.getByTestId('btn-simpan').click();
    await page.waitForTimeout(6000);
    page.off('response', postHandler);

    // Verify POST response
    log(`  → POST response: ${JSON.stringify(snapResp)}`);
    assert(snapResp !== null, `[${label}] POST endpoint dipanggil`);
    assert(snapResp?.status === 200, `[${label}] POST response status 200`);
    assert(snapResp?.json?.redirect_url?.includes('midtrans.com'),
        `[${label}] Response berisi redirect_url Midtrans`, `redirect_url: ${snapResp?.json?.redirect_url}`);
    assert(snapResp?.json?.snap_token, `[${label}] Response berisi snap_token`);
    assert(snapResp?.json?.midtrans_order_id?.startsWith('PAY-'),
        `[${label}] Response berisi midtrans_order_id (PAY-...)`, `order_id: ${snapResp?.json?.midtrans_order_id}`);

    // ====== STEP 9: Verify DB state ======
    log(`\n[${label}] STEP 9: Verify DB state`);
    if (snapResp?.json?.midtrans_order_id) {
        const dbCheck = phpExec(`
            $p = \\App\\Models\\CustInternetPayment::where('midtrans_order_id', '${snapResp.json.midtrans_order_id}')->first();
            if (!$p) { echo 'NOT_FOUND'; exit; }
            echo $p->status . '|' . $p->provider . '|' . $p->payment_method . '|' . $p->amount_paid . '|' . ($p->snap_token ? 'has_snap_token' : 'no_token') . '|' . ($p->data['created_by_portal'] ?? 'no_portal_label');
        `);
        log(`  → DB state: ${dbCheck}`);
        const [status, provider, method, amt, tok, portal] = dbCheck.split('|');
        assert(status === 'pending', `[${label}] DB payment.status=pending`);
        assert(provider === 'midtrans', `[${label}] DB payment.provider=midtrans`);
        assert(method === 'midtrans', `[${label}] DB payment.payment_method=midtrans`);
        assert(parseFloat(amt) === amount, `[${label}] DB payment.amount_paid=${amount}`);
        assert(tok === 'has_snap_token', `[${label}] DB snap_token saved`);
        const expectedPortal = label === 'Karyawan' ? 'karyawan' : 'operator-perusahaan';
        assert(portal === expectedPortal, `[${label}] DB portal label = ${expectedPortal}`, `actual: ${portal}`);
    }

    // ====== STEP 10: Verify Snap UI opened di tab baru ======
    log(`\n[${label}] STEP 10: Verify Snap UI tab opened`);
    const snapPage = await newPagePromise;
    if (snapPage) {
        try {
            await snapPage.waitForLoadState('domcontentloaded', { timeout: 20000 });
            await snapPage.waitForTimeout(3000);
            const snapTitle = await snapPage.title();
            const snapUrl = snapPage.url();
            log(`  → Snap page: title="${snapTitle}", url=${snapUrl.substring(0, 80)}`);
            await snapPage.screenshot({ path: path.join(RESULT_DIR, `kp-${label.toLowerCase()}-05-snap-ui.png`), fullPage: true });
            log(`  → Screenshot: kp-${label.toLowerCase()}-05-snap-ui.png`);
            assert(snapUrl.includes('midtrans.com'), `[${label}] Snap UI loaded (URL contains midtrans.com)`);
            assert(snapTitle.toLowerCase().includes('snap') || snapTitle.toLowerCase().includes('midtrans'),
                `[${label}] Snap page title mentions Midtrans`);
            await snapPage.close().catch(() => {});
        } catch (e) {
            log(`  ! Snap tab error: ${e.message}`);
        }
    } else {
        log(`  ! No new tab opened`);
    }

    await page.waitForTimeout(2000);
    await page.close();
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

    try {
        // ===== Karyawan portal =====
        await runPortal({
            ctx, log, assert, label: 'Karyawan',
            email: 'ahmad@netsejahtera.com',
            loginUrl: '/login-karyawan',
            navUrl: '/karyawan/riwayat-pembayaran',
            postUrl: '/karyawan/riwayat-pembayaran',
            tagihanPrefix: 'INV-KPMID',
            amount: 75000,
        });

        // ===== Perusahaan portal =====
        await runPortal({
            ctx, log, assert, label: 'Perusahaan',
            email: 'admin@netsejahtera.com',
            loginUrl: '/login-perusahaan',
            navUrl: '/operator-perusahaan/riwayat-pembayaran',
            postUrl: '/operator-perusahaan/riwayat-pembayaran',
            tagihanPrefix: 'INV-OPMID',
            amount: 85000,
        });

    } finally {
        log(`\n=== RESULT: ${pass} pass, ${fail} fail ===`);
        if (fail > 0) {
            log('Failed tests:');
            results.filter(r => !r.pass).forEach(r => log(`  ✗ ${r.label}${r.info ? ' — ' + r.info : ''}`));
        }
        log(`\nScreenshots di: ${RESULT_DIR}`);
        await browser.close();
        process.exit(fail > 0 ? 1 : 0);
    }
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
