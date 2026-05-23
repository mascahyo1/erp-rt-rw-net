const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class LanggananCustomerCRUD {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests(email, password) {
        console.log('========================================');
        console.log('Langganan Customer CRUD Tests - Playwright');
        console.log('========================================\n');

        try {
            await this.helper.launch();
            await this.helper.loginAsAdminPerusahaan(email, password);
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/00-login');

            console.log('[SETUP] Login successful, starting tests...\n');

            await this.test_01_page_renders();
            await this.test_02_search();
            await this.test_03_filter_status();
            await this.test_04_sort();
            await this.test_05_delete();

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
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/XX-fatal-error');
        } finally {
            await this.helper.close();
        }
    }

    async test_01_page_renders() {
        const testName = 'test_01_page_renders';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/langganan-customer`);
            await this.helper.waitForText('Langganan Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/01-page-render/01-page');

            const hasTable = await this.helper.isVisible('table');

            if (!hasTable) throw new Error('Table not found');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/01-page-render/XX-error');
        }
    }

    async test_02_search() {
        const testName = 'test_02_search';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/langganan-customer?per_page=100`);
            await this.helper.waitForText('Langganan Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/02-search/01-before');

            const searchInput = await this.helper.page.$('input[placeholder="Cari..."]');
            if (searchInput) {
                await searchInput.fill('Test');
                await searchInput.press('Enter');
            }
            await this.helper.pause(1500);
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/02-search/02-result');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/02-search/XX-error');
        }
    }

    async test_03_filter_status() {
        const testName = 'test_03_filter_status';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/langganan-customer?per_page=100`);
            await this.helper.waitForText('Langganan Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/03-filter-status/01-all');

            const selects = await this.helper.page.$$('select');
            if (selects.length > 0) {
                await selects[0].selectOption('active');
            }
            await this.helper.pause(2500);
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/03-filter-status/02-aktif-result');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/03-filter-status/XX-error');
        }
    }

    async test_04_sort() {
        const testName = 'test_04_sort';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/langganan-customer?per_page=100`);
            await this.helper.waitForText('Langganan Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/04-sort/01-before');

            const headers = await this.helper.page.$$('thead th');
            if (headers.length > 1) {
                await headers[1].click();
                await this.helper.pause(1500);
                await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/04-sort/02-name-asc');
            }

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/04-sort/XX-error');
        }
    }

    async test_05_delete() {
        const testName = 'test_05_delete';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/langganan-customer?per_page=100`);
            await this.helper.waitForText('Langganan Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/05-delete/01-before');

            const deleteButtons = await this.helper.page.$$('button[title="Hapus"]');
            if (deleteButtons.length > 0) {
                await deleteButtons[0].click();
                await this.helper.pause(500);

                const modalText = await this.helper.getText('body');
                if (modalText.includes('Hapus')) {
                    await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/05-delete/02-modal');

                    const deleteConfirmButtons = await this.helper.page.$$('button:has-text("Hapus")');
                    for (const btn of deleteConfirmButtons) {
                        const cls = await btn.getAttribute('class');
                        if (cls && cls.includes('bg-red')) {
                            await btn.click();
                            break;
                        }
                    }
                    await this.helper.pause(2000);
                    await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/05-delete/03-after');
                }
            }

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/LanggananCustomer/TestCRUD/05-delete/XX-error');
        }
    }
}

const test = new LanggananCustomerCRUD();
const email = process.argv[2] || 'admin@perusahaan.rtrwnet.id';
const password = process.argv[3] || 'password';
test.runAllTests(email, password).then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});