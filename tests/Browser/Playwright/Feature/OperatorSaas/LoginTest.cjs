const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class LoginOperatorSaaS {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = { passed: 0, failed: 0, errors: [] };
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
            await this.helper.screenshot('OperatorSaas/Login/XX-fatal-error');
        } finally {
            await this.helper.close();
        }
    }

    async test_page_renders() {
        const testName = 'test_page_renders';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/login-operator-saas`);
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.helper.screenshot('OperatorSaas/Login/01-page');

            const hasEmailInput = await this.helper.isVisible('input[type="email"]');
            const hasPasswordInput = await this.helper.isVisible('input[type="password"]');
            const hasSubmitButton = await this.helper.isVisible('button[type="submit"]');

            if (!hasEmailInput) throw new Error('Email input not found');
            if (!hasPasswordInput) throw new Error('Password input not found');
            if (!hasSubmitButton) throw new Error('Submit button not found');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorSaas/Login/XX-error');
        }
    }

    async test_wrong_password_shows_error() {
        const testName = 'test_wrong_password_shows_error';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/login-operator-saas`);
            await this.helper.waitForSelector('button[type="submit"]', 10000);
            await this.helper.screenshot('OperatorSaas/Login/02-before-submit');

            await this.helper.fill('input[type="email"]', 'test@example.com');
            await this.helper.fill('input[type="password"]', 'wrong-password');
            await this.helper.click('button[type="submit"]');
            await this.helper.pause(3000);
            await this.helper.screenshot('OperatorSaas/Login/03-error-shown');

            const currentUrl = this.helper.getCurrentUrl();
            if (!currentUrl.includes('login-operator-saas')) {
                throw new Error('Should stay on login page after failed attempt');
            }

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorSaas/Login/XX-error');
        }
    }

    async test_inactive_user_rejected() {
        const testName = 'test_inactive_user_rejected';
        console.log(`[TEST] ${testName}`);

        try {
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

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorSaas/Login/XX-error');
        }
    }

    async test_guest_redirect_to_login() {
        const testName = 'test_guest_redirect_to_login';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-saas/dashboard`);
            await this.helper.pause(1000);
            await this.helper.screenshot('OperatorSaas/Login/06-guest-redirect');

            const currentUrl = this.helper.getCurrentUrl();
            if (!currentUrl.includes('login-operator-saas')) {
                throw new Error('Should redirect to login page for guest users');
            }

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorSaas/Login/XX-error');
        }
    }
}

const test = new LoginOperatorSaaS();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});