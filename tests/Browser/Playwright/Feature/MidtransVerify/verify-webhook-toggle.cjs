// E2E test: Webhook ON/OFF toggle behavior + Sinkron Status fallback
// Phase A: webhook_midtrans=true → POST signed webhook → status auto-paid
// Phase B: webhook_midtrans=false → POST signed webhook → noop, status tidak berubah
// Phase C: klik Sinkron Status → verifyStatus() panggil Midtrans API real-time

const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');
const crypto = require('crypto');
const { execSync } = require('child_process');
const PlaywrightHelper = require('../../support/PlaywrightHelper.cjs');


const BASE = require('../../support/baseUrl.cjs');
const PROJECT_BASH = path.resolve(__dirname, '..', '..', '..', '..').replace(/\\/g, '/');
const SCREENSHOT_DIR = path.join(__dirname, 'screenshots-webhook');
if (!fs.existsSync(SCREENSHOT_DIR)) fs.mkdirSync(SCREENSHOT_DIR, { recursive: true });

// Tinker helper — execute PHP one-liner, return stdout
function phpExec(expression) {
    const escaped = expression.replace(/"/g, '\\"');
    const result = execSync(`cd ${PROJECT_BASH} && php artisan tinker --no-interaction --execute="${escaped}"`, {
        encoding: 'utf8',
        shell: 'C:\\Program Files\\Git\\bin\\bash.exe',
    });
    return result.trim();
}

async function run() {
    const helper = new PlaywrightHelper(BASE);
    let pass = 0, fail = 0;
    const log = (m) => console.log(`[${new Date().toISOString().slice(11, 23)}] ${m}`);
    const assert = (cond, label) => {
        if (cond) { log(`✅ ${label}`); pass++; }
        else { log(`❌ ${label}`); fail++; }
    };

    const serverKey = phpExec("echo config('midtrans.server_key');");
    log(`Midtrans server key: ${serverKey.substring(0, 12)}...`);

    const orderId = phpExec("echo \\App\\Models\\CustInternetPayment::where('provider', 'midtrans')->where('status', 'pending')->value('midtrans_order_id');");
    const paymentId = phpExec("echo \\App\\Models\\CustInternetPayment::where('provider', 'midtrans')->where('status', 'pending')->value('id');");
    const grossAmount = phpExec("echo (string) \\App\\Models\\CustInternetPayment::where('provider', 'midtrans')->where('status', 'pending')->value('amount_paid');");
    log(`Test payment: order=${orderId} id=${paymentId} amount=${grossAmount}`);

    // Build signature: SHA512(order_id + status_code + gross_amount + server_key)
    function makeSignature(orderId, statusCode, grossAmount, serverKey) {
        return crypto.createHash('sha512')
            .update(orderId + statusCode + grossAmount + serverKey)
            .digest('hex');
    }

    async function postWebhook(body) {
        const res = await fetch(`${BASE}/webhooks/midtrans`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
        return { status: res.status, body: await res.json().catch(() => ({})) };
    }

    async function getPaymentStatus() {
        return phpExec(`echo \\App\\Models\\CustInternetPayment::where('id', '${paymentId}')->value('status');`);
    }

    try {
        await helper.launch();
        helper.screenshotCount = 0;

        // ============================================================
        // PHASE A: webhook_midtrans=true → POST valid webhook → status paid
        // ============================================================
        log('=== PHASE A: webhook ON (auto paid) ===');
        phpExec("echo \\App\\Models\\SaasConfig::where('key', 'webhook_midtrans')->update(['value' => 'true']);");
        const cfgA = phpExec("echo \\App\\Models\\SaasConfig::getValue('webhook_midtrans', 'unset');");
        log(`Config webhook_midtrans: ${cfgA}`);

        // Setup status to pending
        phpExec(`\\App\\Models\\CustInternetPayment::where('id', '${paymentId}')->update(['status' => 'pending']);`);
        log(`Status sebelum webhook: ${await getPaymentStatus()}`);
        assert((await getPaymentStatus()) === 'pending', 'Status awal: pending');

        // Kirim webhook settlement (paid)
        const statusCode = '200';
        const sigA = makeSignature(orderId, statusCode, grossAmount, serverKey);
        const webhookA = await postWebhook({
            transaction_id: 'test-tx-001',
            order_id: orderId,
            gross_amount: grossAmount,
            payment_type: 'qris',
            status_code: statusCode,
            transaction_status: 'settlement',
            fraud_status: 'accept',
            signature_key: sigA,
            transaction_time: new Date().toISOString(),
        });
        log(`Webhook response: HTTP ${webhookA.status} ${JSON.stringify(webhookA.body)}`);
        assert(webhookA.status === 200, 'Webhook return 200');
        assert(webhookA.body.status === 'ok' && !webhookA.body.noop, 'Webhook processed (no noop)');

        const statusAfterA = await getPaymentStatus();
        log(`Status setelah webhook: ${statusAfterA}`);
        assert(statusAfterA === 'paid', 'Status auto-paid setelah webhook');

        // ============================================================
        // PHASE B: webhook_midtrans=false → POST webhook → noop, status tidak berubah
        // ============================================================
        log('=== PHASE B: webhook OFF (manual verify only) ===');
        phpExec("echo \\App\\Models\\SaasConfig::where('key', 'webhook_midtrans')->update(['value' => 'false']);");
        const cfgB = phpExec("echo \\App\\Models\\SaasConfig::getValue('webhook_midtrans', 'unset');");
        log(`Config webhook_midtrans: ${cfgB}`);
        assert(cfgB === 'false' || cfgB === '0', 'Config updated to false');

        // Reset status ke pending
        phpExec(`\\App\\Models\\CustInternetPayment::where('id', '${paymentId}')->update(['status' => 'pending']);`);
        assert((await getPaymentStatus()) === 'pending', 'Status reset ke pending');

        // Kirim webhook dengan signature valid → harus di-reject sebagai noop
        const sigB = makeSignature(orderId, '200', grossAmount, serverKey);
        const webhookB = await postWebhook({
            transaction_id: 'test-tx-002',
            order_id: orderId,
            gross_amount: grossAmount,
            payment_type: 'qris',
            status_code: '200',
            transaction_status: 'settlement',
            fraud_status: 'accept',
            signature_key: sigB,
        });
        log(`Webhook response (OFF): HTTP ${webhookB.status} ${JSON.stringify(webhookB.body)}`);
        assert(webhookB.status === 200, 'Webhook return 200 (no error)');
        assert(webhookB.body.noop === 'webhook_disabled', 'Response berisi noop=webhook_disabled');

        const statusAfterB = await getPaymentStatus();
        log(`Status setelah webhook (OFF): ${statusAfterB}`);
        assert(statusAfterB === 'pending', 'Status TIDAK berubah (tetap pending) saat webhook OFF');

        // ============================================================
        // PHASE C: klik Sinkron Status → verifyStatus() dipanggil
        // ============================================================
        log('=== PHASE C: klik tombol Sinkron Status (webhook OFF) ===');
        // Login sebagai customer dewi.w
        await helper.page.context().clearCookies();
        await helper.page.goto(`${BASE}/login-pelanggan`);
        await helper.page.waitForLoadState('networkidle');
        await helper.page.waitForTimeout(2500);
        await helper.page.locator('button:has-text("Cari perusahaan")').first().click();
        await helper.page.waitForTimeout(2000);
        await helper.page.locator('input[placeholder="Cari perusahaan..."]').first().fill('Net Sejahtera');
        await helper.page.waitForTimeout(2000);
        await helper.page.locator('button[data-testid^="company-item-"]:has-text("PT Net Sejahtera Abadi")').first().click();
        await helper.page.waitForTimeout(1000);
        await helper.page.fill('input[type="email"]', 'dewi.w@gmail.com');
        await helper.page.fill('input[type="password"]', 'password123');
        await helper.page.click('form button[type="submit"]');
        await helper.page.waitForLoadState('networkidle');
        await helper.page.waitForTimeout(3000);
        log(`Customer URL: ${helper.page.url()}`);

        // Buka Riwayat Pembayaran
        await helper.page.goto(`${BASE}/customer/riwayat-pembayaran`);
        await helper.page.waitForLoadState('networkidle');
        await helper.page.waitForTimeout(2000);
        await helper.screenshot('webhook-off-customer-riwayat');

        // Cari row midtrans+pending, click Sinkron Status
        const rowsC = helper.page.locator('tbody tr');
        const countC = await rowsC.count();
        log(`Customer rows: ${countC}`);
        let sinkronClicked = false;
        for (let i = 0; i < countC; i++) {
            const row = rowsC.nth(i);
            const text = (await row.textContent() || '').toLowerCase();
            if (text.includes('midtrans') && text.includes('menunggu')) {
                const sinkronBtn = row.locator('button:has(.fa-sync-alt)').first();
                if (await sinkronBtn.isVisible({ timeout: 500 }).catch(() => false)) {
                    log(`Clicking Sinkron Status di row ${i}`);
                    // Listen untuk response API
                    const responsePromise = helper.page.waitForResponse(
                        r => r.url().includes('/verify-midtrans') && r.request().method() === 'POST',
                        { timeout: 10000 }
                    ).catch(() => null);
                    await sinkronBtn.click();
                    const apiResp = await responsePromise;
                    if (apiResp) {
                        const respText = (await apiResp.text()).substring(0, 200);
                        log(`API response: HTTP ${apiResp.status()} ${respText}`);
                        // Note: order TEST-ORDER-WIE2ZRVD bukan real Midtrans order (dibuat via tinker),
                        // jadi Midtrans API return 404 "Transaction doesn't exist". Yang penting API call TERJADI.
                        assert(apiResp.status() === 502 || apiResp.status() === 200, 'Verify-midtrans API dipanggil (200 atau 502 untuk order non-Midtrans)');
                    } else {
                        log('⚠️ No API response captured');
                        assert(false, 'API verify-midtrans terpanggil');
                    }
                    await helper.page.waitForTimeout(3000);
                    await helper.screenshot('webhook-off-after-sinkron');
                    sinkronClicked = true;
                    break;
                }
            }
        }
        assert(sinkronClicked, 'Tombol Sinkron Status berhasil di-click');

        // ============================================================
        // PHASE D: cleanup — set webhook back to true
        // ============================================================
        log('=== PHASE D: cleanup ===');
        phpExec("echo \\App\\Models\\SaasConfig::where('key', 'webhook_midtrans')->update(['value' => 'true']);");
        const cfgFinal = phpExec("echo \\App\\Models\\SaasConfig::getValue('webhook_midtrans', 'unset');");
        log(`Config webhook_midtrans (final): ${cfgFinal}`);
        assert(cfgFinal === 'true' || cfgFinal === '1', 'Config restored to true');

        log('='.repeat(60));
        log(`RESULT: ${pass} passed, ${fail} failed`);
    } catch (err) {
        log(`ERROR: ${err.message}`);
        console.error(err);
        fail++;
    } finally {
        // Ensure cleanup even on error
        try { phpExec("echo \\App\\Models\\SaasConfig::where('key', 'webhook_midtrans')->update(['value' => 'true']);"); } catch {}
        await helper.close();
    }

    process.exit(fail > 0 ? 1 : 0);
}

run();
