/**
 * E2E Test: Login Portal Operator SaaS (DEEP)
 *
 * Berbeda dari 3 portal lain: Operator SaaS TIDAK butuh company_id.
 *  - test_page_renders: cek element login
 *  - test_wrong_password_shows_error: error "credentials" muncul
 *  - test_inactive_user_rejected: USER inactive REAL → error "dinonaktifkan"
 *  - test_guest_redirect_to_login: /operator-saas/dashboard → login
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
const tmpPhp = path.join(PROJECT_ROOT, '.claude', 'tmp_saas_login_test.php');
function phpExec(code) {
    fs.writeFileSync(tmpPhp, BOOTSTRAP + code);
    try { return execSync(`php "${tmpPhp}"`, { cwd: PROJECT_ROOT }).toString().trim(); }
    catch (e) { const s = e.stdout ? e.stdout.toString().trim() : ''; if (s) return s; throw e; }
}

class LoginOperatorSaaSTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.testResults = { passed: 0, failed: 0, errors: [] };
        this.testUsers = [];
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Operator SaaS Login Tests - DEEP');
        console.log('========================================\n');

        try {
            // Pre-create inactive admin SaaS (no company_id)
            const inactive = createInactiveUser('admin_saas', null);
            this.testUsers.push({ type: 'admin_saas', id: inactive.id });
            this.inactiveEmail = inactive.email;
            this.inactivePassword = inactive.password;

            await this.helper.launch();
            await this.test_page_renders();
            await this.test_wrong_password_shows_error();
            await this.test_inactive_user_rejected();
            await this.test_guest_redirect_to_login();
            this.printSummary();
        } catch (error) {
            console.error('[FATAL]', error.message);
            try { await this.helper.screenshot('OperatorSaas/Login/XX-fatal'); } catch (e) {}
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
            try { await this.helper.screenshot(`OperatorSaas/Login/XX-error-${name.replace(/\s/g, '-')}`); } catch (s) {}
        }
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
            await this.helper.page.goto(BASE + '/login-operator-saas');
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.helper.screenshot('OperatorSaas/Login/01-page');

            if (!await this.helper.isVisible('input[type="email"]')) throw new Error('Input email tidak ada');
            if (!await this.helper.isVisible('input[type="password"]')) throw new Error('Input password tidak ada');
            if (!await this.helper.isVisible('button[type="submit"]')) throw new Error('Tombol submit tidak ada');
            const bodyText = await this.helper.getText('body');
            if (!bodyText.includes('Login Operator SaaS') && !bodyText.includes('Operator SaaS') && !bodyText.includes('SaaS')) {
                throw new Error('Page harusnya mention "Operator SaaS" atau "SaaS"');
            }
        });
    }

    async test_wrong_password_shows_error() {
        await this.safeTest('test_wrong_password_shows_error', async () => {
            await this.helper.page.goto(BASE + '/login-operator-saas');
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.helper.fill('input[type="email"]', 'superadmin@demo.test');
            await this.helper.fill('input[type="password"]', 'wrong-password-xxx');
            await this.injectTurnstile();
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(2500);
            await this.helper.screenshot('OperatorSaas/Login/02-wrong-password');

            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-operator-saas')) {
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

    async test_inactive_user_rejected() {
        await this.safeTest('test_inactive_user_rejected', async () => {
            const isActive = phpExec(`
                $u = \\App\\Models\\AdminSaas::find('${this.testUsers[0].id}');
                echo $u && !$u->is_active ? 'INACTIVE' : 'NOT_INACTIVE';
            `);
            if (isActive !== 'INACTIVE') throw new Error(`Pre-flight: user harus inactive, dapat: ${isActive}`);

            await this.helper.page.goto(BASE + '/login-operator-saas');
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.helper.fill('input[type="email"]', this.inactiveEmail);
            await this.helper.fill('input[type="password"]', this.inactivePassword);
            await this.injectTurnstile();
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(2500);
            await this.helper.screenshot('OperatorSaas/Login/03-inactive-rejected');

            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-operator-saas')) {
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
            await this.helper.page.goto(BASE + '/operator-saas/dashboard');
            await this.helper.page.waitForTimeout(1500);
            await this.helper.screenshot('OperatorSaas/Login/04-guest-redirect');
            const url = this.helper.getCurrentUrl();
            if (!url.includes('login-operator-saas')) {
                throw new Error(`Guest harusnya redirect ke login, dapat: ${url}`);
            }
        });
    }
}

const test = new LoginOperatorSaaSTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});
