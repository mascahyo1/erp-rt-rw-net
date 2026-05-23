const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class PaketSayaViewTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Pelanggan Paket Saya View Tests - Playwright');
        console.log('========================================\n');

        try {
            await this.helper.launch();
            await this.helper.loginAsPelanggan('pelanggan@rtrwnet.id', 'password123');
            await this.helper.screenshot('Pelanggan/PaketSaya/TestView/00-login');

            await this.test_01_page_renders();
            await this.test_02_navigate_to_tambah();
            await this.test_03_navigate_to_detail();

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
            await this.helper.screenshot('Pelanggan/PaketSaya/TestView/XX-fatal');
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
            await this.helper.screenshot(`Pelanggan/PaketSaya/TestView/XX-${name.replace(/\s/g, '-')}`);
        }
    }

    async test_01_page_renders() {
        await this.safeTest('test_01_page_renders', async () => {
            await this.helper.page.goto(`${this.baseUrl}/customer/paket-saya`);
            await this.helper.waitForText('Paket Saya', 10000);
            await this.helper.pause(1000);
            await this.helper.screenshot('Pelanggan/PaketSaya/TestView/01-page');

            const currentUrl = this.helper.getCurrentUrl();
            if (!currentUrl.includes('/customer/paket-saya')) {
                throw new Error('Should be on Paket Saya page');
            }
        });
    }

    async test_02_navigate_to_tambah() {
        await this.safeTest('test_02_navigate_to_tambah', async () => {
            await this.helper.page.goto(`${this.baseUrl}/customer/paket-saya`);
            await this.helper.waitForText('Paket Saya', 10000);
            await this.helper.pause(1000);
            await this.helper.screenshot('Pelanggan/PaketSaya/TestView/02-tambah-list');

            const pageText = await this.helper.getText('body');
            if (!pageText.includes('Tambah Paket')) {
                throw new Error('Should show Tambah Paket');
            }

            const tambahLink = await this.helper.page.$('a:has-text("Tambah Paket"), button:has-text("Tambah Paket")');
            if (tambahLink) {
                await tambahLink.click();
                await this.helper.pause(1000);
                await this.helper.screenshot('Pelanggan/PaketSaya/TestView/02-tambah-form');
            }
        });
    }

    async test_03_navigate_to_detail() {
        await this.safeTest('test_03_navigate_to_detail', async () => {
            await this.helper.page.goto(`${this.baseUrl}/customer/paket-saya`);
            await this.helper.waitForText('Paket Saya', 10000);
            await this.helper.pause(1000);
            await this.helper.screenshot('Pelanggan/PaketSaya/TestView/03-detail-list');

            const detailLink = await this.helper.page.$('a:has-text("Detail"), button:has-text("Detail")');
            if (detailLink) {
                await detailLink.click();
                await this.helper.pause(1000);
                await this.helper.screenshot('Pelanggan/PaketSaya/TestView/03-detail-page');
            }
        });
    }
}

const test = new PaketSayaViewTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});