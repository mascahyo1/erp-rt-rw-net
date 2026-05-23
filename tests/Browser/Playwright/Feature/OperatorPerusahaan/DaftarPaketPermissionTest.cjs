const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class DaftarPaketPermissionTest {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Daftar Paket Permission Tests - Playwright');
        console.log('========================================\n');

        try {
            await this.helper.launch();
            await this.helper.loginAsAdminPerusahaan('admin-perusahaan@rtrwnet.id', 'password123');
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestPermission/00-login');

            await this.test_01_list_without_permission_blocked();
            await this.test_02_list_with_permission_visible();
            await this.test_03_create_without_permission_hidden();
            await this.test_04_create_with_permission_visible();
            await this.test_05_export_without_permission_hidden();
            await this.test_06_export_with_permission_visible();
            await this.test_07_import_without_permission_hidden();
            await this.test_08_import_with_permission_visible();

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
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestPermission/XX-fatal');
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
            await this.helper.screenshot(`OperatorPerusahaan/DaftarPaket/TestPermission/XX-${name.replace(/\s/g, '-')}`);
        }
    }

    async test_01_list_without_permission_blocked() {
        await this.safeTest('test_01_list_without_permission_blocked', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/daftar-paket`);
            await this.helper.pause(1000);
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestPermission/01-list-blocked');

            const url = this.helper.getCurrentUrl();
            if (!url.includes('403')) {
                const pageText = await this.helper.getText('body');
                if (!pageText.includes('403')) {
                    throw new Error('Should show 403 for user without list permission');
                }
            }
        });
    }

    async test_02_list_with_permission_visible() {
        await this.safeTest('test_02_list_with_permission_visible', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/daftar-paket`);
            await this.helper.waitForText('Paket Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestPermission/02-list-visible');

            const pageText = await this.helper.getText('body');
            if (!pageText.includes('Paket Customer')) {
                throw new Error('Should show Paket Customer for user with list permission');
            }

            if (pageText.includes('Tambah Paket')) {
                throw new Error('Should NOT show Tambah Paket without create permission');
            }
        });
    }

    async test_03_create_without_permission_hidden() {
        await this.safeTest('test_03_create_without_permission_hidden', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/daftar-paket`);
            await this.helper.waitForText('Paket Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestPermission/03-create-hidden');

            const pageText = await this.helper.getText('body');
            if (pageText.includes('Tambah Paket')) {
                throw new Error('Should NOT show Tambah Paket without create permission');
            }
        });
    }

    async test_04_create_with_permission_visible() {
        await this.safeTest('test_04_create_with_permission_visible', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/daftar-paket`);
            await this.helper.waitForText('Paket Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestPermission/04-create-visible');

            const pageText = await this.helper.getText('body');
            if (!pageText.includes('Tambah Paket')) {
                throw new Error('Should show Tambah Paket with create permission');
            }
        });
    }

    async test_05_export_without_permission_hidden() {
        await this.safeTest('test_05_export_without_permission_hidden', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/daftar-paket`);
            await this.helper.waitForText('Paket Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestPermission/05-export-hidden');

            const pageText = await this.helper.getText('body');
            if (pageText.includes('Export')) {
                throw new Error('Should NOT show Export without export permission');
            }
        });
    }

    async test_06_export_with_permission_visible() {
        await this.safeTest('test_06_export_with_permission_visible', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/daftar-paket`);
            await this.helper.waitForText('Paket Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestPermission/06-export-visible');

            const pageText = await this.helper.getText('body');
            if (!pageText.includes('Export')) {
                throw new Error('Should show Export with export permission');
            }
        });
    }

    async test_07_import_without_permission_hidden() {
        await this.safeTest('test_07_import_without_permission_hidden', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/daftar-paket`);
            await this.helper.waitForText('Paket Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestPermission/07-import-hidden');

            const pageText = await this.helper.getText('body');
            if (pageText.includes('Import') || pageText.includes('Template')) {
                throw new Error('Should NOT show Import/Template without import permission');
            }
        });
    }

    async test_08_import_with_permission_visible() {
        await this.safeTest('test_08_import_with_permission_visible', async () => {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/daftar-paket`);
            await this.helper.waitForText('Paket Customer', 10000);
            await this.helper.screenshot('OperatorPerusahaan/DaftarPaket/TestPermission/08-import-visible');

            const pageText = await this.helper.getText('body');
            if (!pageText.includes('Import') || !pageText.includes('Template')) {
                throw new Error('Should show Import and Template with import permission');
            }
        });
    }
}

const test = new DaftarPaketPermissionTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});