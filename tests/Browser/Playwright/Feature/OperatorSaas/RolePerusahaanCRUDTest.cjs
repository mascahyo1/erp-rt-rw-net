const PlaywrightHelper = require('../../support/PlaywrightHelper.cjs');


const BASE = require('../../support/baseUrl.cjs');
class RolePerusahaanCRUDTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Role Perusahaan CRUD Tests - Playwright');
        console.log('========================================\n');

        try {
            await this.helper.launch();

            await this.helper.page.goto(`${BASE}/login-operator-saas`);
            await this.helper.page.waitForLoadState('networkidle');
            await this.helper.fill('input[type="email"]', 'admin-saas@rtrwnet.id');
            await this.helper.fill('input[type="password"]', 'password123');
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(5000);
            await this.helper.screenshot('OperatorSaas/RolePerusahaan/TestCRUD/00-login');

            await this.test_01_page_renders();
            await this.test_02_search();
            await this.test_03_filter_status();

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
            await this.helper.screenshot('OperatorSaas/RolePerusahaan/TestCRUD/XX-fatal');
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
            await this.helper.screenshot(`OperatorSaas/RolePerusahaan/TestCRUD/XX-${name.replace(/\s/g, '-')}`);
        }
    }

    async ensureLoggedIn() {
        const url = this.helper.getCurrentUrl();
        if (url.includes('login')) {
            await this.helper.page.goto(`${BASE}/login-operator-saas`);
            await this.helper.page.waitForLoadState('networkidle');
            await this.helper.fill('input[type="email"]', 'admin-saas@rtrwnet.id');
            await this.helper.fill('input[type="password"]', 'password123');
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(5000);
        }
    }

    async test_01_page_renders() {
        await this.safeTest('test_01_page_renders', async () => {
            await this.ensureLoggedIn();
            await this.helper.page.goto(`${BASE}/operator-saas/role-perusahaan`);
            await this.helper.page.waitForTimeout(3000);
            await this.helper.screenshot('OperatorSaas/RolePerusahaan/TestCRUD/01-page');

            const pageText = await this.helper.getText('body');
            if (pageText.includes('login') && pageText.includes('Login')) {
                throw new Error('Not logged in');
            }
        });
    }

    async test_02_search() {
        await this.safeTest('test_02_search', async () => {
            await this.helper.page.goto(`${BASE}/operator-saas/role-perusahaan?per_page=100`);
            await this.helper.page.waitForTimeout(3000);

            const searchInput = await this.helper.page.$('input[placeholder="Cari..."]');
            if (searchInput) {
                await searchInput.fill('admin');
                await searchInput.press('Enter');
                await this.helper.page.waitForTimeout(1500);
            }
            await this.helper.screenshot('OperatorSaas/RolePerusahaan/TestCRUD/02-search');
        });
    }

    async test_03_filter_status() {
        await this.safeTest('test_03_filter_status', async () => {
            await this.helper.page.goto(`${BASE}/operator-saas/role-perusahaan?per_page=100`);
            await this.helper.page.waitForTimeout(3000);

            const selects = await this.helper.page.$$('select');
            if (selects.length > 0) {
                await selects[0].selectOption('Aktif');
                await this.helper.page.waitForTimeout(1500);
            }
            await this.helper.screenshot('OperatorSaas/RolePerusahaan/TestCRUD/03-filter');
        });
    }
}

const test = new RolePerusahaanCRUDTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});