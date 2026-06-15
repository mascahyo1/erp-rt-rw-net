const PlaywrightHelper = require('../../support/PlaywrightHelper.cjs');


const BASE = require('../../support/baseUrl.cjs');
class ProfilSayaViewTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Karyawan Profil Saya View Tests - Playwright');
        console.log('========================================\n');

        try {
            await this.helper.launch();

            await this.helper.page.goto(`${BASE}/login-karyawan`);
            await this.helper.page.waitForLoadState('networkidle');
            await this.helper.fill('input[type="email"]', 'karyawan@rtrwnet.id');
            await this.helper.fill('input[type="password"]', 'password123');
            await this.helper.click('button[type="submit"]');
            await this.helper.page.waitForTimeout(5000);

            await this.helper.screenshot('Karyawan/ProfilSaya/TestView/00-after-login');

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
            await this.helper.screenshot('Karyawan/ProfilSaya/TestView/XX-fatal');
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
            await this.helper.screenshot(`Karyawan/ProfilSaya/TestView/XX-${name.replace(/\s/g, '-')}`);
        }
    }

    async test_01_page_renders() {
        await this.safeTest('test_01_page_renders', async () => {
            await this.helper.page.goto(`${BASE}/karyawan/profil-saya`);
            await this.helper.page.waitForTimeout(3000);
            await this.helper.screenshot('Karyawan/ProfilSaya/TestView/01-page');

            const pageText = await this.helper.getText('body');
            console.log('  Page text length:', pageText.length);
            if (pageText.length < 50) {
                throw new Error('Page appears empty');
            }
        });
    }
}

const test = new ProfilSayaViewTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});