const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class LoginTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = {
            passed: 0,
            failed: 0,
            errors: []
        };
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Operator Perusahaan Login Tests - Playwright Node.js');
        console.log('========================================\n');

        try {
            await this.helper.launch();
            await this.test_page_renders();
            await this.test_wrong_password_shows_error();
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
            await this.helper.screenshot('OperatorPerusahaan/Login/XX-fatal-error');
        } finally {
            await this.helper.close();
        }
    }

    async test_page_renders() {
        const testName = 'test_page_renders';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/login-perusahaan`);
            await this.helper.waitForText('Masuk Perusahaan', 10000);
            await this.helper.screenshot('OperatorPerusahaan/Login/01-page');

            const hasEmailInput = await this.helper.isVisible('input[type="email"]');
            const hasPasswordInput = await this.helper.isVisible('input[type="password"]');
            const hasSubmitButton = await this.helper.isVisible('button[type="submit"]');
            const hasMasukPerusahaan = await this.helper.getText('body');

            if (!hasEmailInput) throw new Error('Email input not found');
            if (!hasPasswordInput) throw new Error('Password input not found');
            if (!hasSubmitButton) throw new Error('Submit button not found');
            if (!hasMasukPerusahaan.includes('Masuk Perusahaan')) {
                throw new Error('Masuk Perusahaan text not found');
            }

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Login/XX-error');
        }
    }

    async test_wrong_password_shows_error() {
        const testName = 'test_wrong_password_shows_error';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/login-perusahaan`);
            await this.helper.waitForText('Masuk Perusahaan', 10000);
            await this.helper.screenshot('OperatorPerusahaan/Login/02-before-submit');

            await this.helper.fill('input[type="email"]', 'test@example.com');
            await this.helper.fill('input[type="password"]', 'wrong-password');
            await this.helper.click('button[type="submit"]');
            await this.helper.pause(2000);
            await this.helper.screenshot('OperatorPerusahaan/Login/03-error-shown');

            const currentUrl = this.helper.getCurrentUrl();
            if (!currentUrl.includes('login-perusahaan')) {
                throw new Error('Should stay on login page after failed attempt');
            }

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Login/XX-error');
        }
    }

    async test_guest_redirect_to_login() {
        const testName = 'test_guest_redirect_to_login';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/dashboard`);
            await this.helper.pause(800);
            await this.helper.screenshot('OperatorPerusahaan/Login/04-guest-redirect');

            const currentUrl = this.helper.getCurrentUrl();
            if (!currentUrl.includes('login-perusahaan')) {
                throw new Error('Should redirect to login page for guest users');
            }

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Login/XX-error');
        }
    }
}

const test = new LoginTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});