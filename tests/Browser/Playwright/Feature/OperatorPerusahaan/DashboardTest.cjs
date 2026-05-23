const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class DashboardOperatorPerusahaanTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Operator Perusahaan Dashboard Tests - Playwright');
        console.log('========================================\n');

        try {
            await this.helper.launch();
            await this.helper.loginAsAdminPerusahaan('admin-perusahaan@rtrwnet.id', 'password123');
            await this.helper.screenshot('OperatorPerusahaan/Dashboard/00-after-login');

            await this.test_01_page_renders();
            await this.test_02_stats_displayed();

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
            await this.helper.screenshot('OperatorPerusahaan/Dashboard/XX-fatal');
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
            await this.helper.screenshot(`OperatorPerusahaan/Dashboard/XX-${name.replace(/\s/g, '-')}`);
        }
    }

    async test_01_page_renders() {
        await this.safeTest('test_01_page_renders', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/dashboard`);
            await this.helper.waitForText('Dashboard', 10000);
            await this.helper.screenshot('OperatorPerusahaan/Dashboard/01-page');

            const pageText = await this.helper.getText('body');
            if (!pageText.includes('Dashboard')) {
                throw new Error('Page should show Dashboard text');
            }

            const hasNav = await this.helper.isVisible('nav');
            if (!hasNav) {
                throw new Error('Page should have navigation');
            }
        });
    }

    async test_02_stats_displayed() {
        await this.safeTest('test_02_stats_displayed', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/dashboard`);
            await this.helper.waitForText('Dashboard', 10000);
            await this.helper.pause(1000);
            await this.helper.screenshot('OperatorPerusahaan/Dashboard/02-stats');

            const hasMain = await this.helper.isVisible('main');
            if (!hasMain) {
                throw new Error('Page should have main content');
            }
        });
    }
}

const test = new DashboardOperatorPerusahaanTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});