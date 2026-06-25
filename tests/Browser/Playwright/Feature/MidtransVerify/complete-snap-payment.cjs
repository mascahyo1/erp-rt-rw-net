// E2E test: Complete Snap payment flow agar Midtrans sandbox recognize transaction.
//
// Tujuan: Order "PAY-1NRBW6GHGKVN" sudah berhasil dibuat via Snap API call
// (snap_token, redirect_url valid), TAPI Midtrans sandbox /v2/{order_id}/status
// return 404 karena Snap transaction baru "fully registered" setelah customer
// initiate payment di Snap UI (pilih metode bayar).
//
// Flow ini LENGKAPI create-fresh-midtrans-transaction.cjs:
//   1. Login customer
//   2. Seed fresh tagihan
//   3. Create snap token
//   4. Open Snap UI di tab baru
//   5. Pilih Credit Card (test card 4811 1111 1111 1114)
//   6. Isi form: card number, exp, CVV
//   7. Submit → 3DS challenge → OTP 112233
//   8. Tunggu webhook settle payment.status=paid
//   9. Setelah paid, verify-midtrans akan return 200 (status final, no API call)
//
// Setelah ini, re-run verify-midtrans-karyawan-perusahaan.cjs.
// Order dengan status=paid akan langsung return "Status sudah final" tanpa call Midtrans.

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
const TEST_CARD = '4811 1111 1111 1114';
const TEST_CARD_EXP = '12/30';
const TEST_CARD_CVV = '123';
const TEST_OTP = '112233';

function phpExec(code) {
    const tmpScript = path.join(PROJECT_ROOT, '.claude', 'tmp_complete_snap.php');
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

    try {
        // ===== Step 1: Seed fresh tagihan =====
        log('\n=== Step 1: Seed fresh tagihan ===');
        const tagihanId = phpExec(`
            $c = \\App\\Models\\Customer::where('email','${CUSTOMER_EMAIL}')->first();
            $ci = \\App\\Models\\CustInternet::where('customer_id', $c->id)->where('internet_status','active')->latest()->first();
            // Hapus payment rows dulu (FK constraint), baru hapus invoice
            \\App\\Models\\CustInternetPayment::whereHas('custInternetInvc.custInternet', fn($q) => $q->where('customer_id',$c->id))
                ->whereHas('custInternetInvc', fn($q) => $q->where('invoice_number','LIKE','INV-SNAPCOMPLETE-%'))->forceDelete();
            \\App\\Models\\CustInternetInvc::whereHas('custInternet', fn($q) => $q->where('customer_id',$c->id))
                ->where('invoice_number','LIKE','INV-SNAPCOMPLETE-%')->forceDelete();
            $inv = \\App\\Models\\CustInternetInvc::create([
                'cust_internet_id' => $ci->id,
                'invoice_number' => 'INV-SNAPCOMPLETE-' . substr(uniqid(), -6),
                'amount' => 50000,
                'total_amount' => 50000,
                'grand_total' => 50000,
                'due_date' => now()->addDays(7),
                'status' => 'unpaid',
                'payment_status' => 'unpaid',
                'created_by' => $c->id,
                'updated_by' => $c->id,
            ]);
            echo $inv->id . '|' . $c->id;
        `);
        const [freshInvId, custId] = tagihanId.split('|');
        log(`Fresh tagihan: id=${freshInvId}, customer_id=${custId}`);

        // ===== Step 2: Login customer =====
        log('\n=== Step 2: Login customer ===');
        await page.goto(BASE + '/login-pelanggan', { waitUntil: 'domcontentloaded' });
        await page.waitForTimeout(2000);
        await page.locator('button:has(.fa-building)').first().click();
        await page.waitForTimeout(1500);
        await page.locator('input[placeholder*="Cari perusahaan"]').first().fill(COMPANY_NAME);
        await page.waitForTimeout(2000);
        await page.locator(`button:has-text("PT Net Sejahtera Abadi")`).first().click();
        await page.waitForTimeout(800);
        await page.fill('input[type="email"]', CUSTOMER_EMAIL);
        await page.fill('input[type="password"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(5000);
        log(`URL: ${page.url()}`);
        assert(page.url().includes('/customer/'), 'Login customer sukses');

        // ===== Step 3: Navigate to pembayaran-tambah =====
        await page.goto(BASE + '/customer/pembayaran-tambah', { waitUntil: 'domcontentloaded' });
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // ===== Step 4: Pilih tagihan + amount 50000 =====
        log('\n=== Step 4: Pilih tagihan baru + 50000 ===');
        const freshInvNo = await phpExec(`echo \\App\\Models\\CustInternetInvc::find('${freshInvId}')->invoice_number;`);
        log(`  Invoice no: ${freshInvNo}`);

        const optionFound = await page.evaluate((invNo) => {
            const selects = document.querySelectorAll('select');
            for (const sel of selects) {
                for (const opt of sel.options) {
                    if (opt.textContent && opt.textContent.includes(invNo)) {
                        sel.value = opt.value;
                        sel.dispatchEvent(new Event('change', { bubbles: true }));
                        return { found: true, optValue: opt.value };
                    }
                }
            }
            return { found: false };
        }, freshInvNo);
        assert(optionFound.found, `Tagihan ${freshInvNo} ada di dropdown`);

        await page.waitForTimeout(500);
        await page.fill('input[type="number"]', '50000');
        await page.waitForTimeout(500);

        // ===== Step 5: Click Bayar → capture snap_token =====
        log('\n=== Step 5: Click "Bayar Sekarang via Midtrans" ===');
        let snapResponse = null;
        const snapHandler = async (response) => {
            if (response.url().includes('/customer/pembayaran-tambah/create-snap-token')) {
                try {
                    const body = await response.text();
                    const j = JSON.parse(body);
                    snapResponse = { status: response.status(), snap_token: j.snap_token, midtrans_order_id: j.midtrans_order_id, redirect_url: j.redirect_url };
                } catch (e) { /* ignore */ }
            }
        };
        page.on('response', snapHandler);
        await page.click('button:has-text("Bayar Sekarang via Midtrans")');
        await page.waitForTimeout(5000);
        page.off('response', snapHandler);
        log(`  Snap response: ${JSON.stringify(snapResponse)}`);
        assert(snapResponse?.snap_token, 'Snap token received');
        assert(snapResponse?.midtrans_order_id, `order_id: ${snapResponse?.midtrans_order_id}`);

        if (!snapResponse?.redirect_url) {
            log('❌ No redirect_url, abort');
            await browser.close();
            process.exit(1);
        }

        // ===== Step 6: Open Snap UI di tab baru =====
        log('\n=== Step 6: Open Snap UI di tab baru ===');
        const snapPage = await ctx.newPage();
        snapPage.on('pageerror', e => console.log('SNAP-PAGEERROR:', e.message));
        snapPage.on('console', msg => { if (msg.type() === 'error') console.log('SNAP-CONSOLE-ERR:', msg.text()); });

        await snapPage.goto(snapResponse.redirect_url, { waitUntil: 'domcontentloaded', timeout: 30000 });
        await snapPage.waitForLoadState('networkidle', { timeout: 30000 }).catch(() => {});
        await snapPage.waitForTimeout(3000);
        await snapPage.screenshot({ path: path.join(RESULT_DIR, 'complete-01-snap-ui.png'), fullPage: true });
        log(`  Snap page title: ${await snapPage.title()}`);

        // ===== Step 7: Pilih Credit Card =====
        log('\n=== Step 7: Pilih Credit Card ===');
        // Snap UI pakai label "Card Payment" (bukan "Credit Card")
        const ccOption = snapPage.locator('text=/Card Payment|Credit\\s*\\/\\s*debit card|Credit Card|Kartu Kredit/i').first();
        if (await ccOption.count() === 0) {
            log('❌ Card Payment option not found');
            await snapPage.screenshot({ path: path.join(RESULT_DIR, 'complete-FAIL-no-cc.png'), fullPage: true });
            await browser.close();
            process.exit(1);
        }
        await ccOption.click();
        await snapPage.waitForTimeout(2000);
        await snapPage.screenshot({ path: path.join(RESULT_DIR, 'complete-02-cc-form.png'), fullPage: true });

        // ===== Step 8: Isi form card =====
        log('\n=== Step 8: Isi form card (4811 1111 1111 1114) ===');
        const cardInput = snapPage.locator('input[placeholder*="1234"]').first();
        if (await cardInput.count() === 0) {
            log('❌ Card input not found');
            await snapPage.screenshot({ path: path.join(RESULT_DIR, 'complete-FAIL-no-card-input.png'), fullPage: true });
            await browser.close();
            process.exit(1);
        }
        await cardInput.fill(TEST_CARD);
        await snapPage.waitForTimeout(500);

        const expInput = snapPage.locator('input[placeholder*="MM/YY"]').first();
        if (await expInput.count() > 0) await expInput.fill(TEST_CARD_EXP);

        const cvvInput = snapPage.locator('input[placeholder="123"]').first();
        if (await cvvInput.count() > 0) await cvvInput.fill(TEST_CARD_CVV);

        await snapPage.waitForTimeout(500);
        await snapPage.screenshot({ path: path.join(RESULT_DIR, 'complete-03-cc-filled.png'), fullPage: true });

        // ===== Step 9: Submit Pay Now =====
        log('\n=== Step 9: Click Pay Now → 3DS challenge ===');
        const payBtn = snapPage.locator('button:has-text("Pay now"), button:has-text("Pay Now"), button:has-text("Bayar")').first();
        if (await payBtn.count() === 0) {
            log('❌ Pay button not found');
            await snapPage.screenshot({ path: path.join(RESULT_DIR, 'complete-FAIL-no-pay.png'), fullPage: true });
            await browser.close();
            process.exit(1);
        }
        await payBtn.click();
        log('  Pay button clicked, waiting for 3DS iframe (8s)...');
        await snapPage.waitForTimeout(8000);
        await snapPage.screenshot({ path: path.join(RESULT_DIR, 'complete-04-3ds-challenge.png'), fullPage: true });

        // ===== Step 10: Handle 3DS iframe =====
        log('\n=== Step 10: Handle 3DS iframe (OTP 112233) ===');
        const frame3ds = snapPage.frameLocator('iframe[title="3ds-iframe"]');
        const otpInput = frame3ds.locator('input[type="password"]').first();
        let otpHandled = false;
        if (await otpInput.count() > 0) {
            await otpInput.fill(TEST_OTP);
            await snapPage.waitForTimeout(500);
            const okBtn = frame3ds.locator('button:has-text("OK")').first();
            if (await okBtn.count() > 0) {
                await okBtn.click();
                log('  3DS OTP "OK" clicked, waiting for settlement webhook (8s)...');
                otpHandled = true;
            }
        }
        if (!otpHandled) {
            log('  ! 3DS iframe not found or no OTP input. Waiting anyway for auto-settlement...');
        }
        await snapPage.waitForTimeout(8000);
        await snapPage.screenshot({ path: path.join(RESULT_DIR, 'complete-05-after-3ds.png'), fullPage: true });

        // ===== Step 11: Poll DB for settlement =====
        log('\n=== Step 11: Poll DB for payment.status=paid (webhook async) ===');
        let dbPaid = false;
        for (let i = 0; i < 20; i++) {
            const out = phpExec(`
                $p = \\App\\Models\\CustInternetPayment::where('midtrans_order_id', '${snapResponse.midtrans_order_id}')->first();
                $inv = $p ? \\App\\Models\\CustInternetInvc::find($p->cust_internet_invc_id) : null;
                echo ($p ? $p->status : 'no_payment') . '|' . ($inv ? $inv->payment_status : 'no_inv') . '|' . ($p && $p->midtrans_payment_type ? $p->midtrans_payment_type : 'no_type');
            `);
            const [ps, is, pt] = out.split('|');
            log(`  Poll ${(i + 1) * 2}s: payment=${ps}, invoice=${is}, type=${pt}`);
            if (ps === 'paid' && is === 'paid') {
                dbPaid = true;
                break;
            }
            await new Promise(r => setTimeout(r, 2000));
        }
        assert(dbPaid, dbPaid ? 'Webhook settlement received → payment.status=paid' : 'Timeout 40s, no webhook');

        await snapPage.close().catch(() => {});

        // ===== Step 12: Final DB state =====
        log('\n=== Step 12: Final DB state ===');
        const finalState = phpExec(`
            $p = \\App\\Models\\CustInternetPayment::where('midtrans_order_id', '${snapResponse.midtrans_order_id}')->first();
            echo $p->status . '|' . (\$p->midtrans_payment_type ?? '') . '|' . (\$p->midtrans_settled_at ?? 'null');
        `);
        log(`  Final: ${finalState}`);

        log('\n=== SUMMARY ===');
        log(`Order ID: ${snapResponse.midtrans_order_id}`);
        log(`Status: ${dbPaid ? 'PAID (webhook OK)' : 'STILL PENDING'}`);
        log('');
        if (dbPaid) {
            log('Sekarang re-run verify-midtrans-karyawan-perusahaan.cjs:');
            log('  - Order ini status=paid (final)');
            log('  - verify-midtrans return HTTP 200 dengan "Status sudah final (paid)"');
            log('  - Tombol Sinkron tidak perlu call Midtrans API untuk paid order');
        } else {
            log('❌ Webhook tidak settle dalam 40s. Cek:');
            log('  - Apakah MIDTRANS_NOTIFICATION_URL di .env reachable dari Midtrans sandbox?');
            log('  - Apakah ada firewall blocking?');
        }

    } catch (e) {
        log(`❌ FATAL: ${e.message}\n${e.stack}`);
    } finally {
        await page.waitForTimeout(3000);
        await browser.close();
        log('\n=== DONE ===');
    }
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
