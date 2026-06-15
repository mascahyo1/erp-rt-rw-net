/**
 * E2E Test: Login Portal Operator Perusahaan (DEEP)
 *
 * Test yang divalidasi secara deep, BUKAN smoke:
 *  - test_page_renders: cek element + heading "Masuk Perusahaan"
 *  - test_wrong_password_shows_error: error muncul, URL tetap
 *  - test_company_required: form tanpa company → error "Pilih perusahaan"
 *  - test_company_mismatch: admin NS + company JP → error "tidak terdaftar"
 *  - test_inactive_user_rejected: USER inactive REAL → error "dinonaktifkan"
 *  - test_guest_redirect_to_login: /operator-perusahaan/dashboard → login
 */

const path = require('path');
const fs = require('fs');
const { createInactiveUser, deleteUser, cleanup: cleanupUsers } = require('../../support/testUsers.cjs');
const PlaywrightHelper = require('../../support/PlaywrightHelper.cjs');
const BASE = require('../../support/baseUrl.cjs');
const { execSync } = require('child_process');

const PROJECT_ROOT = path.resolve(__dirname, '..', '..', '..', '..', '..');
const BOOTSTRAP = `<?php
require '${PROJECT_ROOT.replace(/\\/g, '\\\\')}\\\\vendor\\\\autoload.php';
$app = require '${PROJECT_ROOT.replace(/\\/g, '\\\\')}\\\\bootstrap\\\\app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
`;
const tmpPhp = path.join(PROJECT_ROOT, '.claude', 'tmp_admin_company_login_test.php');
function phpExec(code) {
    fs.writeFileSync(tmpPhp, BOOTSTRAP + code);
    try { return execSync(`php "${tmpPhp}"`, { cwd: PROJECT_ROOT }).toString().trim(); }
    catch (e) { const s = e.stdout ? e.stdout.toString().trim() : ''; if (s) return s; throw e; }
}

class LoginOperatorPerusahaanTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.testResults = { passed: 0, failed: 0, errors: [] };
        this.testUsers = [];
        this.companyId = null;
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Operator Perusahaan Login Tests - DEEP');
        console.log('========================================\n');

        try {
            this.companyId = phpExec(`
                $c = \\App\\Models\\Company::where('name', 'PT Net Sejahtera Abadi')->first();
                echo $c ? $c->id : 'NOT_FOUND';
            `);
            if (this.companyId === 'NOT_FOUND') throw new Error('Company Net Sejahtera tidak ada');

            const inactive = createInactiveUser('admin_company', this.companyId);
            this.testUsers.push({ type: 'admin_company', id: inactive.id });
            this.inactiveEmail = inactive.email;
            this.inactivePassword = inactive.password;

            await this.helper.launch();
            await this.test_page_renders();
            await this.test_wrong_password_shows_error();
            await this.test_company_required();
            await this.test_company_mismatch();
            await this.test_inactive_user_rejected();
            await this.test_guest_redirect_to_login();
            this.printSummary();
        } catch (error) {
            console.error('[FATAL]', error.message);
            try { await this.helper.screenshot('OperatorPerusahaan/Login/XX-fatal'); } catch (e) {}
        } finally {
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
            try { await this.helper.screenshot(`OperatorPerusahaan/Login/XX-error-${name.replace(/\s/g, '-')}`); } catch (s) {}
        }
    }

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

    async injectTurnstile() {
        await this.helper.page.evaluate(() => {
            if (typeof window.onTurnstileSuccess === 'function') {
                window.onTurnstileSuccess('XXXX.DUMMY.TOKEN.XXXX');
            } else if (typeof window.onLoginTurnstileSuccess === 'function') {
                window.onLoginTurnstileSuccess('XXXX.DUMMY.TOKEN.XXXX');
            }
        });
        await this.helper.page.waitForTimeout(300);
    }

    async getErrorTexts() {
        return await this.helper.page.evaluate(() => {
            const els = document.querySelectorAll('.text-red-500, .text-red-600, .text-red-700, [role="alert"]');
            return Array.from(els).map(e => e.textContent.trim()).filter(t => t.length > 0);
        });
    }

    async test_page_renders() {
        await this.safeTest('test_page_renders', async () => {
            await this.helper.page.goto(BASE + '/login-perusahaan');
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.helper.screenshot('OperatorPerusahaan/Login/01-page');

            if (!await this.helper.isVisible('input[type="email"]')) throw new Error('Input email tidak ada');
            if (!await this.helper.isVisible('input[type="password"]')) throw new Error('Input password tidak ada');
            if (!await this.helper.isVisible('button[type="submit"]')) throw new Error('Tombol submit tidak ada');
            const companyInputCount = await this.helper.page.locator('input[placeholder*="Cari perusahaan"]').count();
            const companyBtnCount = await this.helper.page.locator('button:has-text("Cari perusahaan")').count();
            if (companyInputCount === 0 && companyBtnCount === 0) {
                throw new Error('Trigger pilih perusahaan tidak ada');
            }
            const bodyText = await this.helper.getText('body');
            if (!bodyText.includes('Masuk Perusahaan')) {
                throw new Error('Heading "Masuk Perusahaan" tidak ada');
            }
        });
    }

    async test_wrong_password_shows_error() {
        await this.safeTest('test_wrong_password_shows_error', async () => {
            await this.helper.page.goto(BASE + '/login-perusahaan');
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.pickCompany('PT Net Sejahtera Abadi');
            await this.helper.fill('input[type="email"]', 'admin@netsejahtera.com');
            await this.helper.fill('input[type="password"]', 'wrong-password-xxx');
            await this.injectTurnstile();
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(2500);
            await this.helper.screenshot('OperatorPerusahaan/Login/02-wrong-password');

            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-perusahaan')) {
                throw new Error(`URL harusnya tetap di login, dapat: ${url}`);
            }
            const errors = await this.getErrorTexts();
            if (errors.length === 0) throw new Error('Tidak ada error message yang muncul');
            const all = errors.join(' ').toLowerCase();
            if (!all.includes('credentials') && !all.includes('tidak')) {
                throw new Error(`Error message tidak match: ${errors.join(' | ')}`);
            }
        });
    }

    async test_company_required() {
        await this.safeTest('test_company_required', async () => {
            await this.helper.page.goto(BASE + '/login-perusahaan');
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.helper.fill('input[type="email"]', 'admin@netsejahtera.com');
            await this.helper.fill('input[type="password"]', 'password123');
            await this.injectTurnstile();
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(2000);
            await this.helper.screenshot('OperatorPerusahaan/Login/03-company-required');

            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-perusahaan')) {
                throw new Error(`URL harusnya tetap di login, dapat: ${url}`);
            }
            const errors = await this.getErrorTexts();
            const all = errors.join(' ').toLowerCase();
            if (!all.includes('perusahaan')) {
                throw new Error(`Error harusnya sebut "perusahaan", dapat: ${errors.join(' | ') || '(kosong)'}`);
            }
        });
    }

    async test_company_mismatch() {
        await this.safeTest('test_company_mismatch', async () => {
            await this.helper.page.goto(BASE + '/login-perusahaan');
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.pickCompany('PT Jaringan Prima');
            await this.helper.fill('input[type="email"]', 'admin@netsejahtera.com');
            await this.helper.fill('input[type="password"]', 'password123');
            await this.injectTurnstile();
            await this.helper.click('button[type="submit"]');
            // Tunggu Inertia render ulang dengan error
            await this.helper.page.waitForTimeout(3000);
            await this.helper.screenshot('OperatorPerusahaan/Login/04-company-mismatch');

            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-perusahaan')) {
                throw new Error(`URL harusnya tetap di login, dapat: ${url}`);
            }
            // DEEP: cek error class + pageText (Inertia kadang render di tempat lain)
            const errors = await this.getErrorTexts();
            const pageText = (await this.helper.getText('body')).toLowerCase();
            const all = (errors.join(' ') + ' ' + pageText).toLowerCase();
            if (!all.includes('tidak terdaftar di perusahaan')) {
                throw new Error(`Error harusnya "tidak terdaftar di perusahaan", dapat: ${errors.join(' | ') || '(kosong error class)'} | pageText contains "tidak": ${pageText.includes('tidak')}`);
            }
        });
    }

    async test_inactive_user_rejected() {
        await this.safeTest('test_inactive_user_rejected', async () => {
            const isActive = phpExec(`
                $u = \\App\\Models\\AdminCompany::find('${this.testUsers[0].id}');
                echo $u && !$u->is_active ? 'INACTIVE' : 'NOT_INACTIVE';
            `);
            if (isActive !== 'INACTIVE') throw new Error(`Pre-flight: user harus inactive, dapat: ${isActive}`);

            await this.helper.page.goto(BASE + '/login-perusahaan');
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.pickCompany('PT Net Sejahtera Abadi');
            await this.helper.fill('input[type="email"]', this.inactiveEmail);
            await this.helper.fill('input[type="password"]', this.inactivePassword);
            await this.injectTurnstile();
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(2500);
            await this.helper.screenshot('OperatorPerusahaan/Login/05-inactive-rejected');

            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-perusahaan')) {
                throw new Error(`URL harusnya tetap di login, dapat: ${url}`);
            }
            const errors = await this.getErrorTexts();
            const all = errors.join(' ');
            if (!all.includes('dinonaktifkan')) {
                throw new Error(`Error harusnya "Akun anda dinonaktifkan", dapat: ${errors.join(' | ') || '(kosong)'}`);
            }
        });
    }

    async test_guest_redirect_to_login() {
        await this.safeTest('test_guest_redirect_to_login', async () => {
            await this.helper.page.goto(BASE + '/operator-perusahaan/dashboard');
            await this.helper.page.waitForTimeout(1500);
            await this.helper.screenshot('OperatorPerusahaan/Login/06-guest-redirect');
            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-perusahaan')) {
                throw new Error(`Guest harusnya redirect ke login, dapat: ${url}`);
            }
        });
    }
}

const test = new LoginOperatorPerusahaanTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});
