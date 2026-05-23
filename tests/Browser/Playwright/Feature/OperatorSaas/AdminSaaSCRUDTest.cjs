const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class AdminSaaSCRUDTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Admin SaaS CRUD Tests - Playwright');
        console.log('========================================\n');

        try {
            await this.helper.launch();
            await this.helper.loginAsAdminSaaS('admin-saas@rtrwnet.id', 'password123');
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/00-login');

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
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/XX-fatal');
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
            await this.helper.screenshot(`OperatorSaas/AdminSaaS/TestCRUD/XX-${name.replace(/\s/g, '-')}`);
        }
    }

    async test_01_page_renders() {
        await this.safeTest('test_01_page_renders', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-saas/admin-saas`);
            await this.helper.waitForText('Admin SaaS', 10000);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/01-page');

            const hasTable = await this.helper.isVisible('table');
            if (!hasTable) throw new Error('Should have table');

            const pageText = await this.helper.getText('body');
            if (!pageText.includes('Tambah Admin')) {
                throw new Error('Should show Tambah Admin button');
            }
        });
    }

    async test_02_search() {
        await this.safeTest('test_02_search', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-saas/admin-saas?per_page=100`);
            await this.helper.waitForText('Admin SaaS', 10000);

            const searchInput = await this.helper.page.$('input[placeholder="Cari admin..."]');
            if (searchInput) {
                await searchInput.fill('test');
                await searchInput.press('Enter');
                await this.helper.pause(1500);
            }
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/02-search');
        });
    }

    async test_03_filter_status() {
        await this.safeTest('test_03_filter_status', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-saas/admin-saas?per_page=100`);
            await this.helper.waitForText('Admin SaaS', 10000);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/03-filter-all');

            const selects = await this.helper.page.$$('select');
            if (selects.length > 0) {
                await selects[0].selectOption('Aktif');
                await this.helper.pause(1500);
                await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/03-filter-aktif');
            }
        });
    }

    async test_04_sort() {
        await this.safeTest('test_04_sort', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-saas/admin-saas?per_page=100`);
            await this.helper.waitForText('Admin SaaS', 10000);

            const headers = await this.helper.page.$$('th');
            if (headers.length > 1) {
                await headers[1].click();
                await this.helper.pause(1500);
                await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/04-sort');
            }
        });
    }

    async test_05_delete() {
        await this.safeTest('test_05_delete', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-saas/admin-saas?per_page=100`);
            await this.helper.waitForText('Admin SaaS', 10000);

            const deleteBtn = await this.helper.page.$('button[title="Hapus"]');
            if (deleteBtn) {
                await deleteBtn.click();
                await this.helper.pause(1000);
                await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/05-delete-modal');
            }
        });
    }
}

const test = new AdminSaaSCRUDTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});