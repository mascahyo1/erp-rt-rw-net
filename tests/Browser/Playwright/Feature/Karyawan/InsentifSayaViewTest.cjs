const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class InsentifSayaViewTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Karyawan Insentif Saya View Tests - Playwright');
        console.log('========================================\n');

        try {
            await this.helper.launch();
            await this.helper.loginAsKaryawan('karyawan@rtrwnet.id', 'password123');
            await this.helper.screenshot('Karyawan/InsentifSaya/TestView/00-login');

            await this.test_01_page_renders();

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
            await this.helper.screenshot('Karyawan/InsentifSaya/TestView/XX-fatal');
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
            await this.helper.screenshot(`Karyawan/InsentifSaya/TestView/XX-${name.replace(/\s/g, '-')}`);
        }
    }

    async test_01_page_renders() {
        await this.safeTest('test_01_page_renders', async () => {
            await this.helper.page.goto(`${this.baseUrl}/karyawan/insentif-saya`);
            await this.helper.waitForText('Insentif', 10000);
            await this.helper.screenshot('Karyawan/InsentifSaya/TestView/01-page');
        });
    }
}

const test = new InsentifSayaViewTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});