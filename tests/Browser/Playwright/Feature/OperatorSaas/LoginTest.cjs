const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class LoginOperatorSaaSTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = { passed: 0, failed: 0, errors: [] };
        this.email = 'admin-saas@rtrwnet.id';
        this.password = 'password123';
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Operator SaaS Login Tests - Playwright');
        console.log('========================================\n');

        try {
            await this.helper.launch();

            await this.test_page_renders();
            await this.test_wrong_password_shows_error();
            await this.test_inactive_user_rejected();
            await this.test_guest_redirect_to_login();

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

        } catch (error) {
            console.error('[FATAL ERROR]', error.message);
            await this.helper.screenshot('OperatorSaas/Login/XX-fatal');
        } finally {
            await this.helper.close();
        }
    }

    async safeTest(name, fn) {
        try {
            await fn();
            console.log(`  ✓ ${name}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${name}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${name}: ${e.message.substring(0, 100)}`);
            await this.helper.screenshot(`OperatorSaas/Login/XX-error-${name.replace(/\s/g, '-')}`);
        }
    }

    async test_page_renders() {
        await this.safeTest('test_page_renders', async () => {
            await this.helper.page.goto(`${this.baseUrl}/login-operator-saas`);
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.helper.screenshot('OperatorSaas/Login/01-page');

            const hasEmailInput = await this.helper.isVisible('input[type="email"]');
            const hasPasswordInput = await this.helper.isVisible('input[type="password"]');
            const hasSubmitButton = await this.helper.isVisible('button[type="submit"]');

            if (!hasEmailInput) throw new Error('Email input not found');
            if (!hasPasswordInput) throw new Error('Password input not found');
            if (!hasSubmitButton) throw new Error('Submit button not found');
        });
    }

    async test_wrong_password_shows_error() {
        await this.safeTest('test_wrong_password_shows_error', async () => {
            await this.helper.page.goto(`${this.baseUrl}/login-operator-saas`);
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.helper.screenshot('OperatorSaas/Login/02-before-submit');

            await this.helper.fill('input[type="email"]', this.email);
            await this.helper.fill('input[type="password"]', 'wrong-password');
            await this.helper.click('button[type="submit"]');
            await this.helper.pause(3000);
            await this.helper.screenshot('OperatorSaas/Login/03-error-shown');

            const currentUrl = this.helper.getCurrentUrl();
            if (!currentUrl.includes('login-operator-saas')) {
                throw new Error('Should stay on login page after failed attempt');
            }
        });
    }

    async test_inactive_user_rejected() {
        await this.safeTest('test_inactive_user_rejected', async () => {
            await this.helper.page.goto(`${this.baseUrl}/login-operator-saas`);
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.helper.screenshot('OperatorSaas/Login/04-inactive-before');

            await this.helper.fill('input[type="email"]', 'inactive@example.com');
            await this.helper.fill('input[type="password"]', 'password');
            await this.helper.click('button[type="submit"]');
            await this.helper.pause(2000);
            await this.helper.screenshot('OperatorSaas/Login/05-inactive-after');

            const currentUrl = this.helper.getCurrentUrl();
            if (!currentUrl.includes('login-operator-saas')) {
                throw new Error('Should stay on login page for inactive user');
            }
        });
    }

    async test_guest_redirect_to_login() {
        await this.safeTest('test_guest_redirect_to_login', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-saas/dashboard`);
            await this.helper.pause(1000);
            await this.helper.screenshot('OperatorSaas/Login/06-guest-redirect');

            const currentUrl = this.helper.getCurrentUrl();
            if (!currentUrl.includes('login-operator-saas')) {
                throw new Error('Should redirect to login page for guest users');
            }
        });
    }
}

const test = new LoginOperatorSaaSTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});