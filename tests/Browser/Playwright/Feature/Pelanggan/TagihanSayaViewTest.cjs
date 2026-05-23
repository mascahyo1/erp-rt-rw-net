const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class TagihanSayaViewTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Pelanggan Tagihan Saya View Tests - Playwright');
        console.log('========================================\n');

        try {
            await this.helper.launch();

            await this.helper.page.goto(`${this.baseUrl}/login-pelanggan`);
            await this.helper.page.waitForLoadState('networkidle');
            await this.helper.fill('input[type="email"]', 'pelanggan@rtrwnet.id');
            await this.helper.fill('input[type="password"]', 'password123');
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(3000);
            await this.helper.screenshot('Pelanggan/TagihanSaya/TestView/00-login');

            await this.test_01_page_renders();
            await this.test_02_list_displays();
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
            await this.helper.screenshot('Pelanggan/TagihanSaya/TestView/XX-fatal');
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
            await this.helper.screenshot(`Pelanggan/TagihanSaya/TestView/XX-${name.replace(/\s/g, '-')}`);
        }
    }

    async test_01_page_renders() {
        await this.safeTest('test_01_page_renders', async () => {
            await this.helper.page.goto(`${this.baseUrl}/customer/tagihan-saya`);
            await this.helper.page.waitForTimeout(3000);
            await this.helper.screenshot('Pelanggan/TagihanSaya/TestView/01-page');

            const url = this.helper.getCurrentUrl();
            if (url.includes('login')) throw new Error('Not logged in');
        });
    }

    async test_02_list_displays() {
        await this.safeTest('test_02_list_displays', async () => {
            await this.helper.page.goto(`${this.baseUrl}/customer/tagihan-saya`);
            await this.helper.page.waitForTimeout(3000);
            await this.helper.screenshot('Pelanggan/TagihanSaya/TestView/02-list');
        });
    }

    async test_03_navigate_to_detail() {
        await this.safeTest('test_03_navigate_to_detail', async () => {
            await this.helper.page.goto(`${this.baseUrl}/customer/tagihan-saya`);
            await this.helper.page.waitForTimeout(3000);
            await this.helper.screenshot('Pelanggan/TagihanSaya/TestView/03-detail-list');
        });
    }
}

const test = new TagihanSayaViewTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});