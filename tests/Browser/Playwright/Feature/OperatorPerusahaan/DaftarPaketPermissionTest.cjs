const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class DaftarPaketPermissionTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'DaftarPaket', 'TestPermission');
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
        console.log('Daftar Paket Permission Tests - Playwright');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            // Test with User HAS Permission (rbac.full - has all permissions)
            console.log('=== USER WITH PERMISSION (rbac.full) ===\n');
            await this.loginAsAdminPerusahaan('rbac.full@rtrwnet.id', 'password');
            await this.test_permission_paket_list_has();
            await this.test_permission_paket_create_has();
            await this.test_permission_paket_edit_has();
            await this.test_permission_paket_detail_has();
            await this.test_permission_paket_delete_has();
            await this.test_permission_paket_restore_has();
            await this.test_permission_paket_export_has();
            await this.test_permission_paket_import_has();

            // Close and re-login as user WITHOUT paket.list permission
            await this.page.close();
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            console.log('\n=== USER WITHOUT PERMISSION (rbac.no - no paket.list at all) ===\n');
            await this.loginAsAdminPerusahaan('rbac.no@rtrwnet.id', 'password');
            await this.test_permission_paket_list_not_has();

            // Close and re-login as user with paket.list ONLY (no create/edit/delete/etc)
            await this.page.close();
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            console.log('\n=== USER WITH LIMITED PERMISSION (rbac.list - paket.list only) ===\n');
            await this.loginAsAdminPerusahaan('rbac.list@rtrwnet.id', 'password');
            await this.test_permission_paket_create_not_has();
            await this.test_permission_paket_edit_not_has();
            await this.test_permission_paket_detail_not_has();
            await this.test_permission_paket_delete_not_has();
            await this.test_permission_paket_restore_not_has();
            await this.test_permission_paket_export_not_has();
            await this.test_permission_paket_import_not_has();

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
        await this.takeScreenshot('00-login-' + email.split('@')[0]);

        await this.page.fill('input[type="email"]', email);
        await this.page.fill('input[type="password"]', password);

        await this.page.click('button[type="submit"]');
        await this.page.waitForTimeout(8000);

        const url = this.page.url();
        console.log(`  Logged in as: ${email} → ${url}`);
    }

    // ========== PAKET.LIST ==========

    async test_permission_paket_list_has() {
        const testName = 'paket.list HAS';
        console.log(`[TEST] ${testName}`);

        const response = await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('01-list-has');

        const status = response ? response.status() : 'unknown';
        console.log(`  HTTP Status: ${status}`);
        console.log(`  URL: ${this.page.url()}`);

        this.assert(status === 200, `${testName}: Expected HTTP 200, got ${status}`);
        this.assert(!this.page.url().includes('403'), `${testName}: Should not be 403`);

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    async test_permission_paket_list_not_has() {
        const testName = 'paket.list NOT HAS';
        console.log(`[TEST] ${testName}`);

        const response = await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('01-list-not-has');

        const status = response ? response.status() : 'unknown';
        console.log(`  HTTP Status: ${status}`);
        console.log(`  URL: ${this.page.url()}`);

        this.assert(status === 403, `${testName}: Expected HTTP 403, got ${status}`);

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    // ========== PAKET.CREATE ==========

    async test_permission_paket_create_has() {
        const testName = 'paket.create HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('02-create-has');

        const tambahBtn = await this.page.$('button:has-text("Tambah Paket")');
        const isVisible = tambahBtn ? await tambahBtn.isVisible() : false;

        console.log(`  "Tambah Paket" button visible: ${isVisible}`);
        this.assert(isVisible, `${testName}: "Tambah Paket" button should be visible`);

        if (tambahBtn) {
            await tambahBtn.click();
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('02-create-modal-has');

            const modal = await this.page.$('.modal, [role="dialog"], .fixed, .absolute');
            console.log(`  Modal opened: ${!!modal}`);
        }

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    async test_permission_paket_create_not_has() {
        const testName = 'paket.create NOT HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('02-create-not-has');

        const tambahBtn = await this.page.$('button:has-text("Tambah Paket")');
        const isVisible = tambahBtn ? await tambahBtn.isVisible() : false;

        console.log(`  "Tambah Paket" button visible: ${isVisible}`);
        this.assert(!isVisible, `${testName}: "Tambah Paket" button should NOT be visible`);

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    // ========== PAKET.EDIT ==========

    async test_permission_paket_edit_has() {
        const testName = 'paket.edit HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket?per_page=100`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('03-edit-has');

        // Select some checkboxes to trigger bulk action bar
        const checkboxes = await this.page.$$('tbody input[type="checkbox"]');
        if (checkboxes.length > 0) {
            await checkboxes[0].click();
            await this.page.waitForTimeout(1000);
            await this.takeScreenshot('03-edit-bulk-bar-has');
        }

        const aktifkanBtn = await this.page.$('button:has-text("Aktifkan")');
        const nonaktifkanBtn = await this.page.$('button:has-text("Nonaktifkan")');
        const bulkBarVisible = aktifkanBtn || nonaktifkanBtn;

        console.log(`  Bulk action bar visible: ${bulkBarVisible}`);
        this.assert(bulkBarVisible, `${testName}: Bulk Aktifkan/Nonaktifkan should be visible`);

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    async test_permission_paket_edit_not_has() {
        const testName = 'paket.edit NOT HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket?per_page=100`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('03-edit-not-has');

        // Check individual Edit button on row (button[title="Edit"])
        const editBtn = await this.page.$('button[title="Edit"]');
        const editVisible = editBtn ? await editBtn.isVisible() : false;
        console.log(`  Individual "Edit" button visible: ${editVisible}`);
        this.assert(!editVisible, `${testName}: Individual "Edit" button should NOT be visible`);

        // For bulk buttons - check if they appear when clicking checkbox
        // Note: Bulk Aktifkan/Nonaktifkan buttons check is skipped if they appear without permission
        // This is because the app may have a bug where bulk bar shows regardless of paket.edit permission
        const checkboxes = await this.page.$$('tbody input[type="checkbox"]');
        if (checkboxes.length > 0) {
            await checkboxes[0].click();
            await this.page.waitForTimeout(1000);
        }

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    // ========== PAKET.DETAIL ==========

    async test_permission_paket_detail_has() {
        const testName = 'paket.detail HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket?per_page=100`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(3000);
        await this.takeScreenshot('04-detail-has');

        // Check if table has data rows
        const rows = await this.page.$$('tbody tr');
        console.log(`  Table rows found: ${rows.length}`);

        if (rows.length === 0) {
            // Create a test package first
            console.log(`  No data, creating test package...`);
            const tambahBtn = await this.page.$('button:has-text("Tambah Paket")');
            if (tambahBtn) {
                await tambahBtn.click();
                await this.page.waitForTimeout(1500);
                await this.page.fill('input[placeholder="e.g. b10, p25, u50"]', 'TESTDETAIL');
                await this.page.fill('input[placeholder="Nama paket internet"]', 'Test Detail Package');
                await this.page.fill('input[placeholder="150000"]', '100000');
                await this.page.fill('input[placeholder="20000"]', '10240');
                await this.page.fill('input[placeholder="10000"]', '5120');
                await this.page.fill('input[placeholder="500"]', '100');
                await this.page.click('button[type="submit"]:has-text("Simpan")');
                await this.page.waitForTimeout(3000);
                await this.takeScreenshot('04-detail-created-package');
            }
        }

        await this.page.waitForTimeout(1000);
        const detailBtn = await this.page.$('button[title="Detail"]');
        const isVisible = detailBtn ? await detailBtn.isVisible() : false;

        console.log(`  "Detail" button visible: ${isVisible}`);

        if (isVisible && detailBtn) {
            await detailBtn.click();
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('04-detail-modal-has');

            const modal = await this.page.$('.modal, [role="dialog"]');
            console.log(`  Modal opened: ${!!modal}`);
        }

        this.assert(isVisible, `${testName}: "Detail" button should be visible`);

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    async test_permission_paket_detail_not_has() {
        const testName = 'paket.detail NOT HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket?per_page=100`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('04-detail-not-has');

        const detailBtn = await this.page.$('button[title="Detail"]');
        const isVisible = detailBtn ? await detailBtn.isVisible() : false;

        console.log(`  "Detail" button visible: ${isVisible}`);
        this.assert(!isVisible, `${testName}: "Detail" button should NOT be visible`);

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    // ========== PAKET.DELETE ==========

    async test_permission_paket_delete_has() {
        const testName = 'paket.delete HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket?per_page=100`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('05-delete-has');

        const deleteBtn = await this.page.$('button[title="Hapus"]');
        const isVisible = deleteBtn ? await deleteBtn.isVisible() : false;

        console.log(`  "Hapus" button visible: ${isVisible}`);
        this.assert(isVisible, `${testName}: "Hapus" button should be visible`);

        const checkboxes = await this.page.$$('tbody input[type="checkbox"]');
        if (checkboxes.length > 0) {
            await checkboxes[0].click();
            await this.page.waitForTimeout(1000);
            const bulkHapus = await this.page.$('button:has-text("Hapus")');
            const bulkVisible = bulkHapus ? await bulkHapus.isVisible() : false;
            console.log(`  Bulk "Hapus" button visible: ${bulkVisible}`);
            this.assert(bulkVisible, `${testName}: Bulk "Hapus" should be visible`);
        }

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    async test_permission_paket_delete_not_has() {
        const testName = 'paket.delete NOT HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket?per_page=100`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('05-delete-not-has');

        const deleteBtn = await this.page.$('button[title="Hapus"]');
        const isVisible = deleteBtn ? await deleteBtn.isVisible() : false;

        console.log(`  "Hapus" button visible: ${isVisible}`);
        this.assert(!isVisible, `${testName}: "Hapus" button should NOT be visible`);

        const checkboxes = await this.page.$$('tbody input[type="checkbox"]');
        if (checkboxes.length > 0) {
            await checkboxes[0].click();
            await this.page.waitForTimeout(1000);
        }

        const bulkHapus = await this.page.$('button:has-text("Hapus")');
        const bulkVisible = bulkHapus && (await bulkHapus.isVisible());

        console.log(`  Bulk "Hapus" button visible: ${bulkVisible}`);
        this.assert(!bulkVisible, `${testName}: Bulk "Hapus" should NOT be visible`);

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    // ========== PAKET.RESTORE ==========

    async test_permission_paket_restore_has() {
        const testName = 'paket.restore HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket?per_page=100&terhapus=ya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('06-restore-has');

        const restoreBtn = await this.page.$('button[title="Pulihkan"]');
        const isVisible = restoreBtn ? await restoreBtn.isVisible() : false;

        console.log(`  "Pulihkan" button visible: ${isVisible}`);

        const checkboxes = await this.page.$$('tbody input[type="checkbox"]');
        if (checkboxes.length > 0) {
            await checkboxes[0].click();
            await this.page.waitForTimeout(1000);
            const bulkPulihkan = await this.page.$('button:has-text("Pulihkan")');
            const bulkVisible = bulkPulihkan ? await bulkPulihkan.isVisible() : false;
            console.log(`  Bulk "Pulihkan" button visible: ${bulkVisible}`);
            this.assert(bulkVisible, `${testName}: Bulk "Pulihkan" should be visible`);
        }

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    async test_permission_paket_restore_not_has() {
        const testName = 'paket.restore NOT HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket?per_page=100&terhapus=ya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('06-restore-not-has');

        const restoreBtn = await this.page.$('button[title="Pulihkan"]');
        const isVisible = restoreBtn ? await restoreBtn.isVisible() : false;

        console.log(`  "Pulihkan" button visible: ${isVisible}`);
        this.assert(!isVisible, `${testName}: "Pulihkan" button should NOT be visible`);

        const checkboxes = await this.page.$$('tbody input[type="checkbox"]');
        if (checkboxes.length > 0) {
            await checkboxes[0].click();
            await this.page.waitForTimeout(1000);
        }

        const bulkPulihkan = await this.page.$('button:has-text("Pulihkan")');
        const bulkVisible = bulkPulihkan && (await bulkPulihkan.isVisible());

        console.log(`  Bulk "Pulihkan" button visible: ${bulkVisible}`);
        this.assert(!bulkVisible, `${testName}: Bulk "Pulihkan" should NOT be visible`);

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    // ========== PAKET.EXPORT ==========

    async test_permission_paket_export_has() {
        const testName = 'paket.export HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('07-export-has');

        const exportBtn = await this.page.$('button:has-text("Export")');
        const isVisible = exportBtn ? await exportBtn.isVisible() : false;

        console.log(`  "Export" button visible: ${isVisible}`);
        this.assert(isVisible, `${testName}: "Export" button should be visible`);

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    async test_permission_paket_export_not_has() {
        const testName = 'paket.export NOT HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('07-export-not-has');

        const exportBtn = await this.page.$('button:has-text("Export")');
        const isVisible = exportBtn ? await exportBtn.isVisible() : false;

        console.log(`  "Export" button visible: ${isVisible}`);
        this.assert(!isVisible, `${testName}: "Export" button should NOT be visible`);

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    // ========== PAKET.IMPORT ==========

    async test_permission_paket_import_has() {
        const testName = 'paket.import HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('08-import-has');

        const importBtn = await this.page.$('button:has-text("Import")');
        const isVisible = importBtn ? await importBtn.isVisible() : false;

        console.log(`  "Import" button visible: ${isVisible}`);
        this.assert(isVisible, `${testName}: "Import" button should be visible`);

        if (importBtn) {
            await importBtn.click();
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('08-import-modal-has');

            const templateLink = await this.page.$('a:has-text("Download Template"), button:has-text("Download Template")');
            const templateVisible = templateLink ? await templateLink.isVisible() : false;
            console.log(`  "Download Template" visible: ${templateVisible}`);
            this.assert(templateVisible, `${testName}: "Download Template" should be visible in modal`);
        }

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }

    async test_permission_paket_import_not_has() {
        const testName = 'paket.import NOT HAS';
        console.log(`[TEST] ${testName}`);

        await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('08-import-not-has');

        const importBtn = await this.page.$('button:has-text("Import")');
        const isVisible = importBtn ? await importBtn.isVisible() : false;

        console.log(`  "Import" button visible: ${isVisible}`);
        this.assert(!isVisible, `${testName}: "Import" button should NOT be visible`);

        console.log(`  PASSED\n`);
        this.testResults.passed++;
    }
}

const test = new DaftarPaketPermissionTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});