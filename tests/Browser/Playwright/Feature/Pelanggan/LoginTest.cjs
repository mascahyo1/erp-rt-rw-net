/**
 * E2E Test: Login Portal Pelanggan (DEEP)
 *
 * Test yang divalidasi secara deep, BUKAN smoke:
 *  - test_page_renders: cek semua element (input, button, heading, link verifikasi)
 *  - test_wrong_password_shows_error: cek error message muncul, URL tetap di login
 *  - test_company_required: form tanpa company → cek error "Pilih perusahaan"
 *  - test_company_mismatch: company lain → error "tidak terdaftar di perusahaan"
 *  - test_inactive_user_rejected: USER inactive REAL dari DB → cek error
 *    "Akun anda dinonaktifkan" muncul
 *  - test_unverified_email_rejected: USER active tapi email_verified_at=null →
 *    cek error "belum diverifikasi"
 *  - test_guest_redirect_to_login: /customer/dashboard → redirect ke login
 *
 * Menggunakan helper testUsers.cjs untuk create inactive / unverified user
 * dengan timestamp suffix supaya tidak clash dengan data existing.
 */

const path = require('path');
const fs = require('fs');
const { createInactiveUser, deleteUser, cleanup: cleanupUsers } = require('../../support/testUsers.cjs');
const PlaywrightHelper = require('../../support/PlaywrightHelper.cjs');
const BASE = require('../../support/baseUrl.cjs');
const { execSync } = require('child_process');

// Path dinamis untuk PHP script inline (bootstrap Laravel).
// __dirname = tests/Browser/Playwright/Feature/Pelanggan/ → naik 5 = project root.
const PROJECT_ROOT = path.resolve(__dirname, '..', '..', '..', '..', '..');
const BOOTSTRAP = `<?php
require '${PROJECT_ROOT.replace(/\\/g, '\\\\')}\\\\vendor\\\\autoload.php';
$app = require '${PROJECT_ROOT.replace(/\\/g, '\\\\')}\\\\bootstrap\\\\app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
`;
const tmpPhp = path.join(PROJECT_ROOT, '.claude', 'tmp_pelanggan_login_test.php');
function phpExec(code) {
    fs.writeFileSync(tmpPhp, BOOTSTRAP + code);
    try { return execSync(`php "${tmpPhp}"`, { cwd: PROJECT_ROOT }).toString().trim(); }
    catch (e) { const s = e.stdout ? e.stdout.toString().trim() : ''; if (s) return s; throw e; }
}

class LoginPelangganTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.testResults = { passed: 0, failed: 0, errors: [] };
        this.testUsers = []; // for cleanup
        this.companyId = null;
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Pelanggan Login Tests - Playwright (DEEP)');
        console.log('========================================\n');

        try {
            // Pre-flight: get company id
            this.companyId = phpExec(`
                $c = \\App\\Models\\Company::where('name', 'PT Net Sejahtera Abadi')->first();
                echo $c ? $c->id : 'NOT_FOUND';
            `);
            if (this.companyId === 'NOT_FOUND') throw new Error('Company Net Sejahtera tidak ada di DB — jalankan migrate:fresh --seed dulu');

            // Pre-create test users (inactive + unverified) — pakai timestamp suffix
            const ts = Date.now();
            const inactiveCustomer = createInactiveUser('customer', this.companyId);
            this.testUsers.push({ type: 'customer', id: inactiveCustomer.id });
            this.inactiveEmail = inactiveCustomer.email;
            this.inactivePassword = inactiveCustomer.password;

            const unverifiedCustomer = phpExec(`
                $c = \\App\\Models\\Customer::create([
                    'name' => 'Unverified Test ${ts}', 'email' => 'unverif+${ts}@test.local',
                    'phone_country_code' => '+62', 'phone_number' => '8${ts}',
                    'company_id' => '${this.companyId}', 'password' => bcrypt('password123'),
                    'is_active' => true, 'email_verified_at' => null,
                ]);
                echo $c->id . '|' . $c->email;
            `);
            const [unverifiedId, unverifiedEmail] = unverifiedCustomer.split('|');
            this.testUsers.push({ type: 'customer', id: unverifiedId });
            this.unverifiedEmail = unverifiedEmail;
            this.unverifiedPassword = 'password123';

            await this.helper.launch();
            await this.test_page_renders();
            await this.test_wrong_password_shows_error();
            await this.test_company_required();
            await this.test_company_mismatch();
            await this.test_inactive_user_rejected();
            await this.test_unverified_email_rejected();
            await this.test_guest_redirect_to_login();

            this.printSummary();
        } catch (error) {
            console.error('[FATAL]', error.message);
            try { await this.helper.screenshot('Pelanggan/Login/XX-fatal'); } catch (e) {}
        } finally {
            // Cleanup test users
            this.testUsers.forEach(u => { try { deleteUser(u.type, u.id); } catch (e) {} });
            cleanupUsers();
            try { fs.unlinkSync(tmpPhp); } catch (e) {}
            try { await this.helper.close(); } catch (e) {}
        }
    }

    printSummary() {
        console.log('\n========================================');
        console.log('TEST SUMMARY');
        console.log('========================================');
        console.log(`Passed: ${this.testResults.passed}`);
        console.log(`Failed: ${this.testResults.failed}`);
        if (this.testResults.errors.length > 0) {
            console.log('\nErrors:');
            this.testResults.errors.forEach(e => console.log(`  - ${e}`));
        }
        console.log('========================================\n');
    }

    async safeTest(name, fn) {
        try {
            await fn();
            console.log(`  ✓ ${name}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${name}: ${e.message.substring(0, 150)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${name}: ${e.message.substring(0, 200)}`);
            try { await this.helper.screenshot(`Pelanggan/Login/XX-error-${name.replace(/\s/g, '-')}`); } catch (s) {}
        }
    }

    // Helper: pilih perusahaan via native setter pattern
    async pickCompany(companyName) {
        const trigger = this.helper.page.locator('button:has-text("Cari perusahaan")').first();
        if (await trigger.count() > 0) {
            await trigger.click();
            await this.helper.page.waitForTimeout(800);
        }
        const ok = await this.helper.page.evaluate(async (name) => {
            const input = document.querySelector('input[placeholder*="Cari perusahaan"]');
            if (!input) return false;
            const nativeSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
            nativeSetter.call(input, name);
            input.dispatchEvent(new Event('input', { bubbles: true }));
            await new Promise(r => setTimeout(r, 2500));
            const item = document.querySelector('[data-testid^="company-item-"]');
            if (item) { item.click(); return true; }
            return false;
        }, companyName);
        if (!ok) throw new Error(`Gagal pilih perusahaan: ${companyName}`);
        await this.helper.page.waitForTimeout(500);
    }

    // Helper: inject Turnstile token (testing key 1x... = always pass)
    async injectTurnstile() {
        await this.helper.page.evaluate(() => {
            if (typeof window.onLoginTurnstileSuccess === 'function') {
                window.onLoginTurnstileSuccess('XXXX.DUMMY.TOKEN.XXXX');
            } else if (typeof window.onTurnstileSuccess === 'function') {
                window.onTurnstileSuccess('XXXX.DUMMY.TOKEN.XXXX');
            }
        });
        await this.helper.page.waitForTimeout(300);
    }

    // Helper: cari text error Inertia (biasanya class "text-red-500")
    async getErrorTexts() {
        return await this.helper.page.evaluate(() => {
            const els = document.querySelectorAll('.text-red-500, .text-red-600, .text-red-700, [role="alert"]');
            return Array.from(els).map(e => e.textContent.trim()).filter(t => t.length > 0);
        });
    }

    async test_page_renders() {
        await this.safeTest('test_page_renders', async () => {
            await this.helper.page.goto(BASE + '/login-pelanggan');
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.helper.screenshot('Pelanggan/Login/01-page');

            if (!await this.helper.isVisible('input[type="email"]')) throw new Error('Input email tidak ada');
            if (!await this.helper.isVisible('input[type="password"]')) throw new Error('Input password tidak ada');
            if (!await this.helper.isVisible('button[type="submit"]')) throw new Error('Tombol submit tidak ada');
            // CompanySearchInput — input dengan placeholder "Cari perusahaan..." atau button trigger
            const companyInputCount = await this.helper.page.locator('input[placeholder*="Cari perusahaan"]').count();
            const companyBtnCount = await this.helper.page.locator('button:has-text("Cari perusahaan")').count();
            if (companyInputCount === 0 && companyBtnCount === 0) {
                throw new Error('Trigger pilih perusahaan tidak ada (cek selector input[placeholder*="Cari perusahaan"] atau button:has-text("Cari perusahaan"))');
            }

            const bodyText = await this.helper.getText('body');
            if (!bodyText.includes('Masuk')) throw new Error('Heading "Masuk" tidak ada');
            if (!bodyText.includes('Belum verifikasi email')) {
                throw new Error('Link "Belum verifikasi email" tidak ada');
            }
        });
    }

    async test_wrong_password_shows_error() {
        await this.safeTest('test_wrong_password_shows_error', async () => {
            await this.helper.page.goto(BASE + '/login-pelanggan');
            await this.helper.waitForSelector('button[type="submit"]', 10000);

            await this.pickCompany('PT Net Sejahtera Abadi');
            await this.helper.fill('input[type="email"]', 'pelanggan@rtrwnet.id');
            await this.helper.fill('input[type="password"]', 'wrong-password-xxx');
            await this.injectTurnstile();
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(2500);
            await this.helper.screenshot('Pelanggan/Login/02-wrong-password');

            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-pelanggan')) {
                throw new Error(`URL harusnya tetap di login, dapat: ${url}`);
            }

            const errors = await this.getErrorTexts();
            if (errors.length === 0) {
                throw new Error('Tidak ada error message yang muncul');
            }
            const allErrText = errors.join(' ').toLowerCase();
            if (!allErrText.includes('credentials') && !allErrText.includes('tidak')) {
                throw new Error(`Error message tidak match expected: ${errors.join(' | ')}`);
            }
        });
    }

    async test_company_required() {
        await this.safeTest('test_company_required', async () => {
            await this.helper.page.goto(BASE + '/login-pelanggan');
            await this.helper.waitForSelector('button[type="submit"]', 10000);

            await this.helper.fill('input[type="email"]', 'pelanggan@rtrwnet.id');
            await this.helper.fill('input[type="password"]', 'password123');
            await this.injectTurnstile();
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(2000);
            await this.helper.screenshot('Pelanggan/Login/03-company-required');

            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-pelanggan')) {
                throw new Error(`URL harusnya tetap di login, dapat: ${url}`);
            }

            const errors = await this.getErrorTexts();
            const allErrText = errors.join(' ').toLowerCase();
            if (!allErrText.includes('perusahaan')) {
                throw new Error(`Error message harusnya sebut "perusahaan", dapat: ${errors.join(' | ') || '(kosong)'}`);
            }
        });
    }

    async test_company_mismatch() {
        await this.safeTest('test_company_mismatch', async () => {
            // Pre-flight: ambil customer Jaringan Prima yang aktif & verified
            const jpEmail = phpExec(`
                $c = \\App\\Models\\Company::where('name', 'PT Jaringan Prima')->first();
                if (! $c) { echo 'NO_COMPANY'; exit; }
                $u = \\App\\Models\\Customer::where('company_id', $c->id)
                    ->where('is_active', true)
                    ->whereNotNull('email_verified_at')
                    ->first();
                echo $u ? $u->email : 'NO_CUST';
            `);
            if (jpEmail === 'NO_COMPANY') throw new Error('Company Jaringan Prima tidak ada');
            if (jpEmail === 'NO_CUST') throw new Error('Customer Jaringan Prima yang aktif+verified tidak ada');

            await this.helper.page.goto(BASE + '/login-pelanggan');
            await this.helper.waitForSelector('button[type="submit"]', 10000);

            // Email JP + password benar, TAPI pilih company Net Sejahtera → mismatch
            await this.pickCompany('PT Net Sejahtera Abadi');
            await this.helper.fill('input[type="email"]', jpEmail);
            await this.helper.fill('input[type="password"]', 'password123');
            await this.injectTurnstile();
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(2500);
            await this.helper.screenshot('Pelanggan/Login/04-company-mismatch');

            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-pelanggan')) {
                throw new Error(`URL harusnya tetap di login, dapat: ${url}`);
            }

            const errors = await this.getErrorTexts();
            const allErrText = errors.join(' ').toLowerCase();
            if (!allErrText.includes('tidak terdaftar di perusahaan')) {
                throw new Error(`Error harusnya "tidak terdaftar di perusahaan", dapat: ${errors.join(' | ') || '(kosong)'}`);
            }
        });
    }

    async test_inactive_user_rejected() {
        await this.safeTest('test_inactive_user_rejected', async () => {
            // Pre-flight: verify user inactive di DB
            const isActive = phpExec(`
                $u = \\App\\Models\\Customer::find('${this.testUsers[0].id}');
                echo $u && !$u->is_active ? 'INACTIVE' : 'NOT_INACTIVE';
            `);
            if (isActive !== 'INACTIVE') {
                throw new Error(`Pre-flight: user harus inactive, dapat: ${isActive}`);
            }

            await this.helper.page.goto(BASE + '/login-pelanggan');
            await this.helper.waitForSelector('button[type="submit"]', 10000);

            await this.pickCompany('PT Net Sejahtera Abadi');
            await this.helper.fill('input[type="email"]', this.inactiveEmail);
            await this.helper.fill('input[type="password"]', this.inactivePassword);
            await this.injectTurnstile();
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(2500);
            await this.helper.screenshot('Pelanggan/Login/05-inactive-rejected');

            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-pelanggan')) {
                throw new Error(`URL harusnya tetap di login, dapat: ${url}`);
            }

            // DEEP: error message harus "Akun anda dinonaktifkan"
            const errors = await this.getErrorTexts();
            const allErrText = errors.join(' ');
            if (!allErrText.includes('dinonaktifkan')) {
                throw new Error(`Error harusnya "Akun anda dinonaktifkan", dapat: ${errors.join(' | ') || '(kosong)'}`);
            }
        });
    }

    async test_unverified_email_rejected() {
        await this.safeTest('test_unverified_email_rejected', async () => {
            const verified = phpExec(`
                $u = \\App\\Models\\Customer::find('${this.testUsers[1].id}');
                echo is_null($u->email_verified_at) ? 'UNVERIFIED' : 'VERIFIED';
            `);
            if (verified !== 'UNVERIFIED') throw new Error(`Pre-flight: user harus unverified, dapat: ${verified}`);

            await this.helper.page.goto(BASE + '/login-pelanggan');
            await this.helper.waitForSelector('button[type="submit"]', 10000);

            await this.pickCompany('PT Net Sejahtera Abadi');
            await this.helper.fill('input[type="email"]', this.unverifiedEmail);
            await this.helper.fill('input[type="password"]', this.unverifiedPassword);
            await this.injectTurnstile();
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(2500);
            await this.helper.screenshot('Pelanggan/Login/06-unverified-rejected');

            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-pelanggan')) {
                throw new Error(`URL harusnya tetap di login, dapat: ${url}`);
            }

            const errors = await this.getErrorTexts();
            const allErrText = errors.join(' ');
            if (!allErrText.toLowerCase().includes('belum diverifikasi')) {
                throw new Error(`Error harusnya "belum diverifikasi", dapat: ${errors.join(' | ') || '(kosong)'}`);
            }
        });
    }

    async test_guest_redirect_to_login() {
        await this.safeTest('test_guest_redirect_to_login', async () => {
            await this.helper.page.goto(BASE + '/customer/dashboard');
            await this.helper.page.waitForTimeout(1500);
            await this.helper.screenshot('Pelanggan/Login/07-guest-redirect');

            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-pelanggan')) {
                throw new Error(`Guest harusnya redirect ke login, dapat: ${url}`);
            }
        });
    }
}

const test = new LoginPelangganTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});
