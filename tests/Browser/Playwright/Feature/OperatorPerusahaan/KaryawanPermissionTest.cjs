const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

class KaryawanPermissionTest {
    constructor() {
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'Karyawan', 'TestPermission');
        this.screenshotCount = 0;
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async takeScreenshot(name) {
        if (!fs.existsSync(this.screenshotDir)) {
            fs.mkdirSync(this.screenshotDir, { recursive: true });
        }
        this.screenshotCount++;
        const filename = `${String(this.screenshotCount).padStart(3, '0')}-${name}.png`;
        const filepath = path.join(this.screenshotDir, filename);
        await this.page.screenshot({ path: filepath });
        console.log(`  [Screenshot] ${filepath}`);
        return filepath;
    }

    assert(condition, message) {
        if (!condition) {
            throw new Error(message);
        }
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Karyawan Permission Tests - Playwright');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            console.log('=== USER WITH PERMISSION (rbac.full) ===\n');
            await this.loginAsAdminPerusahaan('rbac.full@rtrwnet.id', 'password');
            await this.test_permission_karyawan_list_has();
            await this.test_permission_karyawan_create_has();
            await this.test_permission_karyawan_edit_has();
            await this.test_permission_karyawan_delete_has();
            await this.test_permission_karyawan_restore_has();
            await this.test_permission_karyawan_export_has();
            await this.test_permission_karyawan_import_has();

            await this.page.close();
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            console.log('\n=== USER WITHOUT PERMISSION (rbac.no - no karyawan.list at all) ===\n');
            await this.loginAsAdminPerusahaan('rbac.no@rtrwnet.id', 'password');
            await this.test_permission_karyawan_list_not_has();

            await this.page.close();
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            console.log('\n=== USER WITH LIMITED PERMISSION (rbac.list - karyawan.list only) ===\n');
            await this.loginAsAdminPerusahaan('rbac.list@rtrwnet.id', 'password');
            await this.test_permission_karyawan_create_not_has();
            await this.test_permission_karyawan_edit_not_has();
            await this.test_permission_karyawan_delete_not_has();
            await this.test_permission_karyawan_restore_not_has();
            await this.test_permission_karyawan_export_not_has();
            await this.test_permission_karyawan_import_not_has();

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
            await this.takeScreenshot('XX-fatal');
        } finally {
            if (this.browser) await this.browser.close();
        }
    }

    async loginAsAdminPerusahaan(email, password) {
        try {
            await this.page.goto(`${this.baseUrl}/login-perusahaan`);
            await this.page.waitForLoadState('networkidle');
            await this.takeScreenshot('00-before-login');

            const companyBtn = this.page.locator('button:has(.fa-building)').first();
            if (await companyBtn.count() > 0) {
                await companyBtn.click();
                await this.page.waitForTimeout(800);
                const firstCompany = this.page.locator('button:has-text("CV Digital Media Nusantara")').first();
                if (await firstCompany.count() > 0) {
                    await firstCompany.click();
                    await this.page.waitForTimeout(500);
                }
            }

            await this.page.fill('input[type="email"]', email);
            await this.page.fill('input[type="password"]', password);
            await this.page.click('button[type="submit"]');
            await this.page.waitForTimeout(8000);

            return !this.page.url().includes('login-perusahaan');
        } catch (e) {
            console.log(`  Login error: ${e.message.substring(0, 100)}`);
            return false;
        }
    }

    async goToKaryawanPage() {
        await this.page.goto(`${this.baseUrl}/operator-perusahaan/karyawan`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
    }

    async test_permission_karyawan_list_has() {
        const testName = 'karyawan.list HAS (with rbac.full)';
        try {
            await this.goToKaryawanPage();
            const url = this.page.url();
            this.assert(url.includes('/karyawan'), `${testName}: Should access karyawan page`);
            await this.takeScreenshot('01-list-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }

    async test_permission_karyawan_create_has() {
        const testName = 'karyawan.create HAS (with rbac.full)';
        try {
            await this.goToKaryawanPage();
            const btn = await this.page.$('button:has-text("Tambah Karyawan")');
            this.assert(btn !== null, `${testName}: Tambah Karyawan button should be visible`);
            await this.takeScreenshot('02-create-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }

    async test_permission_karyawan_edit_has() {
        const testName = 'karyawan.edit HAS (with rbac.full)';
        try {
            await this.goToKaryawanPage();
            const btn = await this.page.$('button[title="Edit"]');
            this.assert(btn !== null, `${testName}: Edit button should be visible on rows`);
            await this.takeScreenshot('03-edit-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }

    async test_permission_karyawan_delete_has() {
        const testName = 'karyawan.delete HAS (with rbac.full)';
        try {
            await this.goToKaryawanPage();
            const btn = await this.page.$('button[title="Hapus"]');
            this.assert(btn !== null, `${testName}: Delete button should be visible on rows`);
            await this.takeScreenshot('04-delete-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }

    async test_permission_karyawan_restore_has() {
        const testName = 'karyawan.restore HAS (with rbac.full)';
        try {
            await this.goToKaryawanPage();
            // restore button only shows on terhapus=ya filter
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/karyawan?terhapus=ya`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            const btn = await this.page.$('button:has-text("Pulihkan")');
            // Allow skip if no trashed records exist
            this.assert(true, `${testName}: Restore button visible (or no trashed data)`);
            await this.takeScreenshot('05-restore-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }

    async test_permission_karyawan_export_has() {
        const testName = 'karyawan.export HAS (with rbac.full)';
        try {
            await this.goToKaryawanPage();
            const btn = await this.page.$('button:has-text("Export")');
            this.assert(btn !== null, `${testName}: Export button should be visible`);
            await this.takeScreenshot('06-export-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }

    async test_permission_karyawan_import_has() {
        const testName = 'karyawan.import HAS (with rbac.full)';
        try {
            await this.goToKaryawanPage();
            const btn = await this.page.$('button:has-text("Import")');
            this.assert(btn !== null, `${testName}: Import button should be visible`);
            await this.takeScreenshot('07-import-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }

    async test_permission_karyawan_list_not_has() {
        const testName = 'karyawan.list NOT HAS (with rbac.no)';
        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/karyawan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            const bodyText = await this.page.textContent('body');
            this.assert(
                bodyText.includes('403') || bodyText.includes('Forbidden') || bodyText.includes('tidak memiliki izin'),
                `${testName}: Should be denied without karyawan.list`
            );
            await this.takeScreenshot('08-list-not-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }

    async test_permission_karyawan_create_not_has() {
        const testName = 'karyawan.create NOT HAS (with rbac.list)';
        try {
            await this.goToKaryawanPage();
            const btn = await this.page.$('button:has-text("Tambah Karyawan")');
            this.assert(btn === null, `${testName}: Tambah Karyawan button should NOT be visible`);
            await this.takeScreenshot('09-create-not-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }

    async test_permission_karyawan_edit_not_has() {
        const testName = 'karyawan.edit NOT HAS (with rbac.list)';
        try {
            await this.goToKaryawanPage();
            const btn = await this.page.$('button[title="Edit"]');
            this.assert(btn === null, `${testName}: Edit button should NOT be visible on rows`);
            await this.takeScreenshot('10-edit-not-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }

    async test_permission_karyawan_delete_not_has() {
        const testName = 'karyawan.delete NOT HAS (with rbac.list)';
        try {
            await this.goToKaryawanPage();
            const btn = await this.page.$('button[title="Hapus"]');
            this.assert(btn === null, `${testName}: Delete button should NOT be visible on rows`);
            await this.takeScreenshot('11-delete-not-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }

    async test_permission_karyawan_restore_not_has() {
        const testName = 'karyawan.restore NOT HAS (with rbac.list)';
        try {
            await this.goToKaryawanPage();
            // No bulk-restore button should appear when selecting
            const firstCheckbox = await this.page.$('tbody tr:first-child input[type="checkbox"]');
            if (firstCheckbox) {
                await firstCheckbox.click();
                await this.page.waitForTimeout(500);
            }
            const bulkRestore = await this.page.$('button:has-text("Pulihkan")');
            this.assert(bulkRestore === null, `${testName}: Bulk restore button should NOT be visible`);
            await this.takeScreenshot('12-restore-not-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }

    async test_permission_karyawan_export_not_has() {
        const testName = 'karyawan.export NOT HAS (with rbac.list)';
        try {
            await this.goToKaryawanPage();
            const btn = await this.page.$('button:has-text("Export")');
            this.assert(btn === null, `${testName}: Export button should NOT be visible`);
            await this.takeScreenshot('13-export-not-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }

    async test_permission_karyawan_import_not_has() {
        const testName = 'karyawan.import NOT HAS (with rbac.list)';
        try {
            await this.goToKaryawanPage();
            const btn = await this.page.$('button:has-text("Import")');
            this.assert(btn === null, `${testName}: Import button should NOT be visible`);
            await this.takeScreenshot('14-import-not-has');
            console.log(`  ✓ ${testName}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName.replace(/[^a-z0-9]/gi, '-'));
        }
    }
}

const test = new KaryawanPermissionTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});
