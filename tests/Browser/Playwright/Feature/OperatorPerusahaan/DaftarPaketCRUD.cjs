const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class DaftarPaketCRUD {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests(email, password) {
        console.log('========================================');
        console.log('Daftar Paket CRUD Tests - Playwright');
        console.log('========================================\n');

        try {
            await this.helper.launch();
            await this.helper.loginAsAdminPerusahaan(email, password);

            const dashUrl = this.helper.getCurrentUrl();
            console.log('[OK] Login - URL:', dashUrl.includes('dashboard') ? 'dashboard' : dashUrl);

            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestCRUD/00-login');

            // Test 1
            await this.test_page_renders();

            // Test 2
            await this.test_02_search();

            // Test 3
            await this.test_03_filter_status();

            // Test 4
            await this.test_04_sort();

            // Test 5
            await this.test_05_delete();

        } catch (error) {
            console.error('[FATAL]', error.message);
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestCRUD/XX-fatal');
        } finally {
            await this.helper.close();
        }

        console.log('\n========================================');
        console.log('RESULT:', this.testResults.passed, 'passed,', this.testResults.failed, 'failed');
        console.log('========================================');
    }

    async safeTest(name, fn) {
        try {
            await fn();
            console.log('  ✓', name);
            this.testResults.passed++;
        } catch (e) {
            console.log('  ✗', name, '-', e.message.substring(0, 100));
            this.testResults.failed++;
            this.testResults.errors.push(name + ': ' + e.message.substring(0, 100));
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestCRUD/XX-' + name.replace(/\s/g, '-'));
        }
    }

    async test_page_renders() {
        await this.helper.page.goto(this.baseUrl + '/operator-perusahaan/daftar-paket');
        await this.helper.page.waitForTimeout(3000);

        const url = this.helper.getCurrentUrl();
        console.log('  URL:', url);

        if (url.includes('403') || url.includes('login')) {
            throw new Error('Access denied - ' + url);
        }

        const html = await this.helper.getText('body');
        await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestCRUD/01-page');

        if (!html.includes('Paket')) {
            throw new Error('Page not expected content: ' + html.substring(0, 200));
        }
    }

    async test_02_search() {
        const searchInput = await this.helper.page.$('input[placeholder="Cari..."]');
        if (searchInput) {
            await searchInput.fill('test');
            await searchInput.press('Enter');
            await this.helper.page.waitForTimeout(2000);
        }
        await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestCRUD/02-search');
    }

    async test_03_filter_status() {
        const selects = await this.helper.page.$$('select');
        if (selects.length > 0) {
            await selects[0].selectOption('Aktif');
            await this.helper.page.waitForTimeout(2000);
        }
        await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestCRUD/03-filter');
    }

    async test_04_sort() {
        const headers = await this.helper.page.$$('th');
        if (headers.length > 1) {
            await headers[1].click();
            await this.helper.page.waitForTimeout(2000);
        }
        await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestCRUD/04-sort');
    }

    async test_05_delete() {
        const deleteBtn = await this.helper.page.$('button[title="Hapus"]');
        if (deleteBtn) {
            await deleteBtn.click();
            await this.helper.page.waitForTimeout(2000);
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestCRUD/05-delete-modal');
        }
    }
}

const email = process.argv[2] || 'test@playwright.dev';
const password = process.argv[3] || 'password123';
new DaftarPaketCRUD().runAllTests(email, password).then(() => {
    process.exit(0);
});