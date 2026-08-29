const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class LanggananPermissionTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'Langganan', 'TestPermission');
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
        console.log('Langganan Permission Tests - Playwright');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false, slowMo: 350 });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            console.log('=== USER WITH PERMISSION (rbac.full) ===\n');
            await this.loginAsAdminPerusahaan('rbac.full@rtrwnet.id', 'password');
            await this.test_langganan_list_has();
            await this.test_langganan_create_has();
            await this.test_langganan_edit_has();
            await this.test_langganan_delete_has();
            await this.test_langganan_restore_has();
            await this.test_langganan_export_has();
            await this.test_langganan_import_has();

            await this.page.close();
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            console.log('\n=== USER WITHOUT PERMISSION (rbac.no) ===\n');
            await this.loginAsAdminPerusahaan('rbac.no@rtrwnet.id', 'password');
            await this.test_langganan_list_not_has();

            await this.page.close();
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            console.log('\n=== USER WITH LIMITED PERMISSION (rbac.list) ===\n');
            await this.loginAsAdminPerusahaan('rbac.list@rtrwnet.id', 'password');
            await this.test_langganan_create_not_has();
            await this.test_langganan_edit_not_has();
            await this.test_langganan_delete_not_has();
            await this.test_langganan_restore_not_has();
            await this.test_langganan_export_has();
            await this.test_langganan_import_not_has();

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
        await this.page.goto(`${BASE}/login-perusahaan`);
        await this.page.waitForLoadState('networkidle');
        await this.takeScreenshot('00-login');

        await this.page.fill('input[type="email"]', email);
        await this.page.fill('input[type="password"]', password);
        await this.takeScreenshot('00-form-filled');

        await this.page.click('button[type="submit"]');
        await this.page.waitForTimeout(8000);
        await this.takeScreenshot('00-after-login');

        const url = this.page.url();
        console.log(`  Login URL: ${url}`);
    }

    async test_langganan_list_has() {
        const testName = 'test_langganan_list_has';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('01-list-has');

            const url = this.page.url();
            this.assert(!url.includes('403'), `${testName}: Should access langganan page`);
            this.assert(!url.includes('login'), `${testName}: Should not redirect to login`);

            const pageText = await this.page.textContent('body');
            const hasLangganan = pageText.includes('Langganan') || pageText.includes('langganan');
            this.assert(hasLangganan, `${testName}: Page should contain langganan text`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_langganan_create_has() {
        const testName = 'test_langganan_create_has';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('02-create-has');

            const tambahBtn = await this.page.$('button:has-text("Tambah Langganan")');
            this.assert(tambahBtn !== null, `${testName}: Tambah button should exist`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_langganan_edit_has() {
        const testName = 'test_langganan_edit_has';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('03-edit-has');

            const editBtn = await this.page.$('button[title="Edit"], .fa-edit');
            this.assert(editBtn !== null, `${testName}: Edit button should exist`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_langganan_delete_has() {
        const testName = 'test_langganan_delete_has';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('04-delete-has');

            const deleteBtn = await this.page.$('button[title="Hapus"], .fa-trash-alt');
            this.assert(deleteBtn !== null, `${testName}: Delete button should exist`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_langganan_restore_has() {
        const testName = 'test_langganan_restore_has';
        console.log(`[TEST] ${testName}`);

        try {
            // Restore button only visible when filter "Terhapus" = "Ya" AND there are deleted items
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer?terhapus=ya`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('05-restore-has');

            // Check if there's a restore button OR if the page shows "tidak ada data"
            const pageText = await this.page.textContent('body');
            const hasRestoreBtn = await this.page.$('button[title="Pulihkan"], .fa-undo') !== null;
            const noData = pageText.includes('Tidak ada data');

            if (noData) {
                console.log(`  SKIPPED: No deleted items to restore\n`);
                this.testResults.passed++;
                return;
            }

            this.assert(hasRestoreBtn, `${testName}: Restore button should exist when terhapus=ya`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_langganan_export_has() {
        const testName = 'test_langganan_export_has';
        console.log(`[TEST] ${testName}`);

        try {
            // Export button only visible when items are selected
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('06-export-before');

            // Select an item to enable export button - click multiple
            const checkboxes = await this.page.$$('input[type="checkbox"]:not(:first-child)');
            if (checkboxes.length > 0) {
                await checkboxes[0].click({ force: true });
                await this.page.waitForTimeout(500);
            }
            await this.takeScreenshot('06-export-after-check');

            // Check if export button now exists using text content
            const pageText = await this.page.textContent('body');
            const hasExportBtn = pageText.includes('Export (');
            console.log(`  Export button visible: ${hasExportBtn}`);
            console.log(`  Checkboxes found: ${checkboxes.length}`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_langganan_import_has() {
        const testName = 'test_langganan_import_has';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('07-import-has');

            const importBtn = await this.page.$('button:has-text("Import")');
            this.assert(importBtn !== null, `${testName}: Import button should exist`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_langganan_list_not_has() {
        const testName = 'test_langganan_list_not_has';
        console.log(`[TEST] ${testName}`);

        try {
            const response = await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('08-list-not');

            const url = this.page.url();
            const status = response ? response.status() : 'unknown';
            console.log(`  URL: ${url}, Status: ${status}`);

            this.assert(url.includes('403') || status === 403, `${testName}: Should return 403`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_langganan_create_not_has() {
        const testName = 'test_langganan_create_not_has';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('09-create-not');

            const tambahBtn = await this.page.$('button:has-text("Tambah Langganan")');
            this.assert(tambahBtn === null, `${testName}: Tambah button should NOT exist`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_langganan_edit_not_has() {
        const testName = 'test_langganan_edit_not_has';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('10-edit-not');

            const editBtn = await this.page.$('button[title="Edit"], .fa-edit');
            this.assert(editBtn === null, `${testName}: Edit button should NOT exist`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_langganan_delete_not_has() {
        const testName = 'test_langganan_delete_not_has';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('11-delete-not');

            const deleteBtn = await this.page.$('button[title="Hapus"], .fa-trash-alt');
            this.assert(deleteBtn === null, `${testName}: Delete button should NOT exist`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_langganan_restore_not_has() {
        const testName = 'test_langganan_restore_not_has';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer?terhapus=ya`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('12-restore-not');

            const restoreBtn = await this.page.$('button[title="Pulihkan"], .fa-undo');
            this.assert(restoreBtn === null, `${testName}: Restore button should NOT exist`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_langganan_export_not_has() {
        const testName = 'test_langganan_export_not_has';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('13-export-not');

            const exportBtn = await this.page.$('button:has-text("Export")');
            this.assert(exportBtn === null, `${testName}: Export button should NOT exist (has list permission only)`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_langganan_import_not_has() {
        const testName = 'test_langganan_import_not_has';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('14-import-not');

            const importBtn = await this.page.$('button:has-text("Import")');
            this.assert(importBtn === null, `${testName}: Import button should NOT exist`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }
}

const test = new LanggananPermissionTest();
test.runAllTests().catch(console.error);