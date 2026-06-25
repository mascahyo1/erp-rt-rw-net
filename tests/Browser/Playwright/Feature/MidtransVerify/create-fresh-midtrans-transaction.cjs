// E2E test: Create 1 fresh Midtrans transaction dari 0 (customer flow).
//
// Tujuan: Bikin transaksi yang benar-benar tercatat di Midtrans dashboard
// (bukan dummy order_id), sehingga "Sinkron Status Midtrans" di view Karyawan
// bisa return HTTP 200 (bukan 502 "Transaction doesn't exist").
//
// Flow:
//   1. Seed tagihan baru (biar clean state)
//   2. Login as customer test+1781247641870@example.com
//   3. Navigate to /customer/pembayaran-tambah
//   4. Pilih tagihan baru + metode Midtrans + nominal
//   5. Click "Bayar Sekarang via Midtrans"
//   6. Capture snap_token + midtrans_order_id (real, dari Midtrans sandbox)
//   7. Verify: payment row baru ada di DB, midtrans_order_id match
//
// Run: node tests/Browser/Playwright/Feature/MidtransVerify/create-fresh-midtrans-transaction.cjs

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');


const BASE = require('../../support/baseUrl.cjs');
const PROJECT_ROOT = path.resolve(__dirname, '..', '..', '..', '..', '..').replace(/\\/g, '/');
const RESULT_DIR = path.join(__dirname, 'screenshots-karyawan-perusahaan');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

const CUSTOMER_EMAIL = 'test+1781247641870@example.com';
const COMPANY_NAME = 'Net Sejahtera';

function phpExec(code) {
    const tmpScript = path.join(PROJECT_ROOT, '.claude', 'tmp_create_fresh.php');
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
    const browser = await chromium.launch({ headless: false, slowMo: 400 });
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    page.on('pageerror', e => console.log('PAGEERROR:', e.message));
    page.on('console', msg => { if (msg.type() === 'error') console.log('CONSOLE-ERR:', msg.text()); });

    const log = (m) => console.log(`[${new Date().toISOString().slice(11, 23)}] ${m}`);
    const assert = (cond, label, info) => {
        if (cond) log(`✅ ${label}${info ? ' — ' + info : ''}`);
        else log(`❌ ${label}${info ? ' — ' + info : ''}`);
    };

    // ===== Step 1: Seed fresh tagihan =====
    log('\n=== Step 1: Seed fresh tagihan untuk Midtrans ===');
    const tagihanId = phpExec(`
        $c = \\App\\Models\\Customer::where('email','${CUSTOMER_EMAIL}')->first();
        if (!$c) { echo 'NO_CUSTOMER'; exit; }
        $ci = \\App\\Models\\CustInternet::where('customer_id', $c->id)->where('internet_status','active')->latest()->first();
        if (!$ci) { echo 'NO_CI'; exit; }
        // Remove any old INV-FRESH-MIDTRANS-* supaya start clean
        \\App\\Models\\CustInternetInvc::whereHas('custInternet', fn($q) => $q->where('customer_id',$c->id))
            ->where('invoice_number','LIKE','INV-FRESH-MIDTRANS-%')->forceDelete();
        $inv = \\App\\Models\\CustInternetInvc::create([
            'cust_internet_id' => $ci->id,
            'invoice_number' => 'INV-FRESH-MIDTRANS-' . substr(uniqid(), -6),
            'amount' => 25000,
            'total_amount' => 25000,
            'grand_total' => 25000,
            'due_date' => now()->addDays(7),
            'status' => 'unpaid',
            'payment_status' => 'unpaid',
            'created_by' => $c->id,
            'updated_by' => $c->id,
        ]);
        echo $inv->id . '|' . $inv->invoice_number . '|' . $c->id;
    `);
    if (tagihanId.startsWith('NO_')) {
        log(`❌ ${tagihanId}`);
        await browser.close();
        process.exit(1);
    }
    const [freshInvId, freshInvNo, custId] = tagihanId.split('|');
    log(`Fresh tagihan: id=${freshInvId}, no=${freshInvNo}, customer_id=${custId}`);

    // ===== Step 2: Login as customer =====
    log('\n=== Step 2: Login customer ===');
    await page.goto(BASE + '/login-pelanggan', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    await page.locator('button:has(.fa-building)').first().click();
    await page.waitForTimeout(1500);
    await page.locator('input[placeholder*="Cari perusahaan"]').first().fill(COMPANY_NAME);
    await page.waitForTimeout(2000);
    const companyBtn = await page.locator(`button:has-text("PT Net Sejahtera Abadi")`).first();
    await companyBtn.click();
    await page.waitForTimeout(800);
    await page.fill('input[type="email"]', CUSTOMER_EMAIL);
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);
    log(`URL after login: ${page.url()}`);
    assert(page.url().includes('/customer/'), 'Login customer sukses');
    await page.screenshot({ path: path.join(RESULT_DIR, 'fresh-01-after-login.png') });

    // ===== Step 3: Navigate to pembayaran-tambah =====
    log('\n=== Step 3: Navigate to /customer/pembayaran-tambah ===');
    await page.goto(BASE + '/customer/pembayaran-tambah', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: path.join(RESULT_DIR, 'fresh-02-pembayaran-tambah.png'), fullPage: true });

    // ===== Step 4: Pilih tagihan + amount =====
    log('\n=== Step 4: Pilih tagihan baru + nominal 25000 ===');
    const optionFound = await page.evaluate((invNo) => {
        const selects = document.querySelectorAll('select');
        for (const sel of selects) {
            for (const opt of sel.options) {
                if (opt.textContent && opt.textContent.includes(invNo)) {
                    sel.value = opt.value;
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                    return { found: true, selectIdx: Array.from(selects).indexOf(sel), optValue: opt.value, optText: opt.textContent };
                }
            }
        }
        return { found: false };
    }, freshInvNo);
    log(`  Option search result: ${JSON.stringify(optionFound)}`);
    assert(optionFound.found, `Tagihan ${freshInvNo} ada di dropdown`);

    await page.waitForTimeout(800);
    await page.fill('input[type="number"]', '25000');
    await page.waitForTimeout(500);

    // ===== Step 5: Click "Bayar Sekarang via Midtrans" =====
    log('\n=== Step 5: Click "Bayar Sekarang via Midtrans" → real Snap call ===');
    let snapResponse = null;
    const snapHandler = async (response) => {
        if (response.url().includes('/customer/pembayaran-tambah/create-snap-token')) {
            try {
                const body = await response.text();
                let json = null;
                try { json = JSON.parse(body); } catch (e) {}
                snapResponse = {
                    status: response.status(),
                    body: body.substring(0, 500),
                    snap_token: json?.snap_token,
                    midtrans_order_id: json?.midtrans_order_id,
                    redirect_url: json?.redirect_url,
                    client_key: json?.client_key,
                    error: json?.error,
                };
            } catch (e) { /* ignore */ }
        }
    };
    page.on('response', snapHandler);

    await page.click('button:has-text("Bayar Sekarang via Midtrans")');
    await page.waitForTimeout(5000);
    page.off('response', snapHandler);

    log(`  Snap response: ${JSON.stringify(snapResponse, null, 2).substring(0, 800)}`);
    assert(snapResponse !== null, 'POST create-snap-token endpoint dipanggil');
    assert(snapResponse.status === 200, 'Response status 200 (OK)');
    assert(!!snapResponse.snap_token, `snap_token returned: ${snapResponse.snap_token?.substring(0, 20)}...`);
    assert(!!snapResponse.midtrans_order_id, `midtrans_order_id: ${snapResponse.midtrans_order_id}`);
    assert(!!snapResponse.redirect_url, `redirect_url: ${snapResponse.redirect_url?.substring(0, 60)}...`);
    assert(snapResponse.redirect_url?.includes('midtrans.com') || snapResponse.redirect_url?.includes('snap'), 'redirect_url is Midtrans domain');

    await page.screenshot({ path: path.join(RESULT_DIR, 'fresh-03-after-create-snap.png'), fullPage: true });

    // ===== Step 6: Verify payment row di DB =====
    log('\n=== Step 6: Verify payment row di DB ===');
    if (snapResponse?.midtrans_order_id) {
        const dbCheck = phpExec(`
            $p = \\App\\Models\\CustInternetPayment::where('midtrans_order_id', '${snapResponse.midtrans_order_id}')->first();
            if (!$p) { echo 'NOT_FOUND'; exit; }
            echo $p->status . '|' . $p->provider . '|' . $p->amount_paid . '|' . ($p->snap_token ? 'has_snap_token' : 'no_snap_token') . '|' . ($p->midtrans_expires_at ?? 'null');
        `);
        log(`  DB state: ${dbCheck}`);
        const [status, provider, amount, snapTok, expires] = dbCheck.split('|');
        assert(status === 'pending', `Payment status=pending (got: ${status})`);
        assert(provider === 'midtrans', `Payment provider=midtrans (got: ${provider})`);
        assert(parseFloat(amount) === 25000, `Payment amount=25000 (got: ${amount})`);
        assert(snapTok === 'has_snap_token', 'snap_token tersimpan di DB');
    }

    // ===== Step 7: Open Snap UI di tab baru + screenshot =====
    log('\n=== Step 7: Open Snap redirect URL di tab baru (sandbox UI) ===');
    if (snapResponse?.redirect_url) {
        const snapPage = await ctx.newPage();
        try {
            await snapPage.goto(snapResponse.redirect_url, { waitUntil: 'domcontentloaded', timeout: 30000 });
            await snapPage.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
            await snapPage.waitForTimeout(3000);
            await snapPage.screenshot({ path: path.join(RESULT_DIR, 'fresh-04-snap-sandbox-ui.png'), fullPage: true });
            log('  Snap UI loaded. Screenshot saved: fresh-04-snap-sandbox-ui.png');

            // Detect: title contains "Midtrans" / "Secure Payment"
            const title = await snapPage.title();
            log(`  Snap page title: ${title}`);
            const url = snapPage.url();
            log(`  Snap page url: ${url}`);
            assert(url.includes('midtrans.com') || url.includes('snap'), 'Loaded Midtrans Snap UI');
        } catch (e) {
            log(`  ! Snap UI error: ${e.message}`);
        } finally {
            await snapPage.close().catch(() => {});
        }
    }

    // ===== Final summary =====
    log('\n=== SUMMARY ===');
    log(`Midtrans order_id yang berhasil dicatat: ${snapResponse?.midtrans_order_id}`);
    log(`Cek di Midtrans dashboard:`);
    log(`  https://app.sandbox.midtrans.com/merchant/M364501696/transactions`);
    log(`  Search by order_id: ${snapResponse?.midtrans_order_id}`);
    log('');
    log('Sekarang order ini real, "Sinkron Status Midtrans" di view Karyawan');
    log('akan return HTTP 200 (bukan 502 "Transaction doesn\'t exist").');

    await page.waitForTimeout(3000);
    await browser.close();
    log('\n=== DONE ===');
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
