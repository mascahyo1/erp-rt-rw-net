const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class DashboardPelangganTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Pelanggan Dashboard Tests - Playwright');
        console.log('========================================\n');

        try {
            await this.helper.launch();
            await this.helper.loginAsPelanggan('pelanggan@rtrwnet.id', 'password123');
            await this.helper.screenshot('Pelanggan/Dashboard/00-after-login');

            await this.test_01_page_renders();
            await this.test_02_stats_displayed();
            await this.test_03_navigation();

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
            await this.helper.screenshot('Pelanggan/Dashboard/XX-fatal');
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
            await this.helper.screenshot(`Pelanggan/Dashboard/XX-${name.replace(/\s/g, '-')}`);
        }
    }

    async test_01_page_renders() {
        await this.safeTest('test_01_page_renders', async () => {
            await this.helper.page.goto(`${this.baseUrl}/customer/dashboard`);
            await this.helper.waitForText('Dashboard', 10000);
            await this.helper.pause(1000);
            await this.helper.screenshot('Pelanggan/Dashboard/01-page');

            const currentUrl = this.helper.getCurrentUrl();
            if (!currentUrl.includes('/customer/dashboard')) {
                throw new Error('Should be on customer dashboard');
            }
        });
    }

    async test_02_stats_displayed() {
        await this.safeTest('test_02_stats_displayed', async () => {
            await this.helper.page.goto(`${this.baseUrl}/customer/dashboard`);
            await this.helper.waitForText('Dashboard', 10000);
            await this.helper.pause(1000);
            await this.helper.screenshot('Pelanggan/Dashboard/02-stats');

            const pageText = await this.helper.getText('body');
            const expectedTexts = ['Paket Aktif', 'Tagihan Bulan Ini', 'Riwayat Pembayaran'];
            for (const text of expectedTexts) {
                if (!pageText.includes(text)) {
                    throw new Error(`Page should show "${text}"`);
                }
            }
        });
    }

    async test_03_navigation() {
        await this.safeTest('test_03_navigation', async () => {
            await this.helper.page.goto(`${this.baseUrl}/customer/dashboard`);
            await this.helper.waitForText('Dashboard', 10000);
            await this.helper.pause(1000);
            await this.helper.screenshot('Pelanggan/Dashboard/03-nav');

            const pageText = await this.helper.getText('body');
            const expectedNavs = ['Profil Saya', 'Paket Saya', 'Tagihan Saya', 'Riwayat Pembayaran'];
            for (const nav of expectedNavs) {
                if (!pageText.includes(nav)) {
                    throw new Error(`Navigation should include "${nav}"`);
                }
            }
        });
    }
}

const test = new DashboardPelangganTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});