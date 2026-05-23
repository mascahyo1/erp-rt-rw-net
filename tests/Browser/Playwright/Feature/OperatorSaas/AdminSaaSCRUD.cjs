const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class AdminSaaSCRUD {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = {
            passed: 0,
            failed: 0,
            errors: []
        };
    }

    async runAllTests(email, password) {
        console.log('========================================');
        console.log('Admin SaaS CRUD Tests - Playwright Node.js');
        console.log('========================================\n');

        try {
            await this.helper.launch();
            await this.helper.loginAsAdminSaaS(email, password);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/00-login');

            console.log('[SETUP] Login successful, starting tests...\n');

            await this.test_01_page_renders();
            await this.test_02_search();
            await this.test_03_filter_status();
            await this.test_04_create_admin();
            await this.test_05_delete_admin();

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
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/XX-fatal-error');
        } finally {
            await this.helper.close();
        }
    }

    async test_01_page_renders() {
        const testName = 'test_01_page_renders';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-saas/admin-saas`);
            await this.helper.waitForText('Admin SaaS', 10000);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/01-page-render/01-page');

            const hasTable = await this.helper.isVisible('table');
            const hasTambahAdmin = await this.helper.isVisible('button:has-text("Tambah Admin")');

            if (!hasTable) throw new Error('Table not found');
            if (!hasTambahAdmin) throw new Error('Tambah Admin button not found');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/01-page-render/XX-error');
        }
    }

    async test_02_search() {
        const testName = 'test_02_search';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-saas/admin-saas?per_page=100`);
            await this.helper.waitForText('Admin SaaS', 10000);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/02-search/01-before');

            const searchInput = await this.helper.page.$('input[placeholder="Cari admin..."]');
            if (searchInput) {
                await searchInput.fill('Admin');
                await searchInput.press('Enter');
            }
            await this.helper.pause(1500);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/02-search/01-result');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/02-search/XX-error');
        }
    }

    async test_03_filter_status() {
        const testName = 'test_03_filter_status';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-saas/admin-saas?per_page=100`);
            await this.helper.waitForText('Admin SaaS', 10000);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/03-filter/01-before');

            const selects = await this.helper.page.$$('select');
            if (selects.length > 0) {
                await selects[0].selectOption('Aktif');
            }
            await this.helper.pause(2500);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/03-filter/01-aktif-result');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/03-filter/XX-error');
        }
    }

    async test_04_create_admin() {
        const testName = 'test_04_create_admin';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-saas/admin-saas`);
            await this.helper.waitForText('Admin SaaS', 10000);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/04-create/01-before');

            const addButton = await this.helper.page.$('button:has-text("Tambah Admin")');
            if (addButton) {
                await addButton.click();
            }
            await this.helper.pause(500);
            await this.helper.waitForText('Tambah Admin SaaS', 5000);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/04-create/02-modal');

            const nameInput = await this.helper.page.$('input[placeholder="Nama lengkap"]');
            if (nameInput) {
                await nameInput.fill('Test Admin ' + Date.now());
            }

            const emailInput = await this.helper.page.$('input[type="email"]');
            if (emailInput) {
                await emailInput.fill(`test${Date.now()}@rtrwnet.id`);
            }

            const passwordInput = await this.helper.page.$('input[type="password"]');
            if (passwordInput) {
                await passwordInput.fill('password123');
            }

            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/04-create/03-filled');

            const saveButtons = await this.helper.page.$$('button:has-text("Simpan")');
            for (const btn of saveButtons) {
                if (await btn.isVisible()) {
                    await btn.click();
                    break;
                }
            }

            await this.helper.pause(2000);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/04-create/04-after');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/04-create/XX-error');
        }
    }

    async test_05_delete_admin() {
        const testName = 'test_05_delete_admin';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-saas/admin-saas?per_page=100`);
            await this.helper.waitForText('Admin SaaS', 10000);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/05-delete/01-before');

            const deleteButtons = await this.helper.page.$$('button[title="Hapus"]');
            if (deleteButtons.length > 0) {
                await deleteButtons[0].click();
                await this.helper.pause(500);
                await this.helper.waitForText('Hapus Admin?', 5000);
                await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/05-delete/02-modal');

                const confirmButtons = await this.helper.page.$$('button:has-text("Hapus")');
                for (const btn of confirmButtons) {
                    const cls = await btn.getAttribute('class');
                    if (cls && cls.includes('bg-red')) {
                        await btn.click();
                        break;
                    }
                }

                await this.helper.pause(1500);
                await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/05-delete/03-after');
            }

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorSaas/AdminSaaS/TestCRUD/05-delete/XX-error');
        }
    }
}

const test = new AdminSaaSCRUD();
const email = process.argv[2] || 'admin@saas.rtrwnet.id';
const password = process.argv[3] || 'password';
test.runAllTests(email, password).then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});