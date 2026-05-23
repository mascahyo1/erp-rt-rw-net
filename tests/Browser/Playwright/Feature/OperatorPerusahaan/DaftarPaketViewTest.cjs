const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class DaftarPaketViewTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Daftar Paket View Tests - Playwright');
        console.log('========================================\n');

        try {
            await this.helper.launch();
            await this.helper.loginAsAdminPerusahaan('admin-perusahaan@rtrwnet.id', 'password123');
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestView/00-login');

            await this.test_01_page_renders();
            await this.test_02_columns_visible();

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
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestView/XX-fatal');
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
            await this.helper.screenshot(`OperatorPerusahaan/DaftarPaket/TestView/XX-${name.replace(/\s/g, '-')}`);
        }
    }

    async test_01_page_renders() {
        await this.safeTest('test_01_page_renders', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/daftar-paket`);
            await this.helper.waitForText('Paket Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestView/01-page');

            const pageText = await this.helper.getText('body');
            if (!pageText.includes('Paket Customer')) {
                throw new Error('Page should show "Paket Customer"');
            }

            const hasTable = await this.helper.isVisible('table');
            if (!hasTable) {
                throw new Error('Page should have table');
            }

            const expectedElements = ['Langganan Aktif', 'Estimasi Pendapatan', 'Tambah Paket', 'Import', 'Export'];
            for (const text of expectedElements) {
                if (!pageText.includes(text)) {
                    throw new Error(`Page should show "${text}"`);
                }
            }
        });
    }

    async test_02_columns_visible() {
        await this.safeTest('test_02_columns_visible', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/daftar-paket?per_page=100`);
            await this.helper.waitForText('Paket Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestView/02-columns');

            const pageText = await this.helper.getText('body');
            const expectedColumns = ['Nama Paket', 'Harga', 'Speed', 'Quota', 'Billing', 'Langganan Aktif', 'Estimasi Pendapatan', 'Status'];
            for (const col of expectedColumns) {
                if (!pageText.includes(col)) {
                    throw new Error(`Table should have column "${col}"`);
                }
            }
        });
    }
}

const test = new DaftarPaketViewTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});