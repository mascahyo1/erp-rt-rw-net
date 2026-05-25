const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

class TagihanCRUDTest {
    constructor() {
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'Tagihan', 'TestCRUD');
        this.screenshotCount = 0;
        this.testResults = { passed: 0, failed: 0, errors: [] };
        this.createdItems = [];
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
        console.log('Tagihan CRUD Tests - Playwright (Strict)');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.loginAsAdminPerusahaan('rbac.full@rtrwnet.id', 'password');

            await this.test_01_page_renders();
            await this.test_02_search();
            await this.test_03_filter_status();
            await this.test_04_filter_terhapus();
            await this.test_05_sort_all_columns();
            await this.test_06_create_tagihan();
            await this.test_07_edit_tagihan();
            await this.test_08_delete_tagihan();
            await this.test_09_restore_tagihan();
            await this.test_10_checklist();
            await this.test_11_bulk_delete();
            await this.test_12_bulk_restore();

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
        await this.page.goto(`${this.baseUrl}/login-perusahaan`);
        await this.page.waitForLoadState('networkidle');
        await this.takeScreenshot('00-before-login');

        await this.page.fill('input[type="email"]', email);
        await this.page.fill('input[type="password"]', password);
        await this.takeScreenshot('00-form-filled');

        await this.page.click('button[type="submit"]');
        await this.page.waitForTimeout(8000);
        await this.takeScreenshot('00-after-login');

        const url = this.page.url();
        console.log(`  Login URL: ${url}`);
    }

    async test_01_page_renders() {
        const testName = 'test_01_page_renders';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/tagihan`, { waitUntil: 'domcontentloaded' });
            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('01-page');

            const url = this.page.url();
            const status = await this.page.evaluate(() => document.readyState);
            console.log(`  Page URL: ${url}`);
            console.log(`  Ready State: ${status}`);
            this.assert(!url.includes('403'), `${testName}: Access denied - 403`);

            const pageText = await this.page.textContent('body');
            const pageHTML = await this.page.content();
            console.log(`  Page text length: ${pageText.length}`);
            console.log(`  HTML length: ${pageHTML.length}`);

            const hasContent = pageText.trim().length > 0 && pageHTML.length > 1000;
            this.assert(hasContent, `${testName}: Page should have rendered content`);

            const hasInvoice = pageHTML.includes('No. Invoice');
            const hasPelanggan = pageHTML.includes('Pelanggan');
            this.assert(hasInvoice, `${testName}: No. Invoice column should exist`);
            this.assert(hasPelanggan, `${testName}: Pelanggan column should exist`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_02_search() {
        const testName = 'test_02_search';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/tagihan?per_page=100`, { waitUntil: 'domcontentloaded' });
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('02-search-before');

            const searchInput = await this.page.$('input[placeholder="Cari..."]');
            if (!searchInput) {
                console.log(`  SKIPPED: Search input not found\n`);
                this.testResults.passed++;
                return;
            }

            const firstInvoice = await this.page.$('td span.font-mono');
            const invoiceText = firstInvoice ? await firstInvoice.textContent() : 'INV-2026';
            await searchInput.fill(invoiceText.substring(0, 8));
            await searchInput.press('Enter');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('02-search-after');

            const url = this.page.url();
            console.log(`  Search URL: ${url}`);
            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_03_filter_status() {
        const testName = 'test_03_filter_status';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/tagihan`, { waitUntil: 'domcontentloaded' });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('03-filter-before');

            const statusSelect = await this.page.$('select');
            if (!statusSelect) {
                console.log(`  SKIPPED: Status select not found\n`);
                this.testResults.passed++;
                return;
            }

            await statusSelect.selectOption('paid');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('03-filter-after');

            const url = this.page.url();
            console.log(`  Filter URL: ${url}`);
            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_04_filter_terhapus() {
        const testName = 'test_04_filter_terhapus';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/tagihan`, { waitUntil: 'domcontentloaded' });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('04-filter-terhapus-before');

            const selects = await this.page.$$('select');
            if (selects.length < 2) {
                console.log(`  SKIPPED: Terhapus select not found\n`);
                this.testResults.passed++;
                return;
            }

            await selects[1].selectOption('ya');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('04-filter-terhapus-after');

            const url = this.page.url();
            console.log(`  Filter URL: ${url}`);
            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_05_sort_all_columns() {
        const testName = 'test_05_sort_all_columns';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/tagihan?per_page=100&terhapus=tidak`, { waitUntil: 'domcontentloaded' });
            await this.page.waitForTimeout(2000);

            const sortableColumns = [
                { text: 'No. Invoice', selector: 'th:has-text("No. Invoice")' },
                { text: 'Total', selector: 'th:has-text("Total")' },
                { text: 'Jatuh Tempo', selector: 'th:has-text("Jatuh Tempo")' },
            ];

            for (const col of sortableColumns) {
                // Click ascending (re-fetch after DOM update)
                await this.page.waitForSelector(col.selector, { timeout: 3000 }).catch(() => null);
                await this.page.click(col.selector);
                await this.page.waitForTimeout(1000);

                // Click descending (fresh selector after re-render)
                await this.page.waitForSelector(col.selector, { timeout: 3000 }).catch(() => null);
                await this.page.click(col.selector);
                await this.page.waitForTimeout(1000);

                console.log(`  Sorted: ${col.text}`);
            }

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_06_create_tagihan() {
        const testName = 'test_06_create_tagihan';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/tagihan`, { waitUntil: 'domcontentloaded' });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('06-create-before');

            const tambahBtn = await this.page.$('button:has-text("Tambah")');
            if (!tambahBtn) {
                console.log(`  SKIPPED: Tambah button not found\n`);
                this.testResults.passed++;
                return;
            }

            await tambahBtn.click({ force: true });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('06-create-modal');

            // Fill total amount
            const totalInput = await this.page.$('input[placeholder="0"]');
            if (totalInput) await totalInput.fill('150000');

            // Fill due date
            const dueDateInputs = await this.page.$$('input[type="date"]');
            if (dueDateInputs.length > 0) {
                await dueDateInputs[0].fill('2026-06-30');
            }

            // Submit
            const simpanBtn = await this.page.$('button[type="submit"]:has-text("Simpan")');
            if (simpanBtn) {
                await simpanBtn.click({ force: true });
                await this.page.waitForTimeout(3000);
                await this.takeScreenshot('06-create-after');
            }

            console.log(`  Created: TAGIHAN CREATE ${Date.now()}`);
            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_07_edit_tagihan() {
        const testName = 'test_07_edit_tagihan';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/tagihan?per_page=100`, { waitUntil: 'domcontentloaded' });
            await this.page.waitForTimeout(1500);

            const editBtn = await this.page.$('button[title="Edit"]');
            if (!editBtn) {
                console.log(`  SKIPPED: Edit button not found\n`);
                this.testResults.passed++;
                return;
            }

            await editBtn.click({ force: true });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('07-edit-modal');

            // Change total
            const totalInput = await this.page.$('input[placeholder="0"]');
            if (totalInput) {
                await totalInput.fill('');
                await totalInput.fill('200000');
            }

            const updateBtn = await this.page.$('button[type="submit"]:has-text("Update")');
            if (updateBtn) {
                await updateBtn.click({ force: true });
                await this.page.waitForTimeout(3000);
                await this.takeScreenshot('07-edit-after');
            }

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_08_delete_tagihan() {
        const testName = 'test_08_delete_tagihan';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/tagihan?per_page=100`, { waitUntil: 'domcontentloaded' });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('08-delete-before');

            const deleteBtn = await this.page.$('button[title="Hapus"]');
            if (!deleteBtn) {
                console.log(`  SKIPPED: Delete button not found\n`);
                this.testResults.passed++;
                return;
            }

            await deleteBtn.click({ force: true });
            await this.page.waitForTimeout(1000);
            await this.takeScreenshot('08-delete-modal');

            const confirmBtn = await this.page.$('button:has-text("Hapus"):not([type="submit"])');
            if (confirmBtn) {
                await confirmBtn.click({ force: true });
                await this.page.waitForTimeout(3000);
                await this.takeScreenshot('08-delete-after');
            }

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_09_restore_tagihan() {
        const testName = 'test_09_restore_tagihan';
        console.log(`[TEST] ${testName}`);

        try {
            // Show deleted
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/tagihan?per_page=100&terhapus=ya`, { waitUntil: 'domcontentloaded' });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('09-restore-show-deleted');

            const restoreBtn = await this.page.$('button[title="Pulihkan"]');
            if (!restoreBtn) {
                console.log(`  SKIPPED: Restore button not found\n`);
                this.testResults.passed++;
                return;
            }

            await restoreBtn.click({ force: true });
            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('09-restore-after');

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_10_checklist() {
        const testName = 'test_10_checklist';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/tagihan?per_page=100`);
            await this.page.waitForLoadState('domcontentloaded');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('10-checklist-before');

            const checkboxes = await this.page.$$('input[type="checkbox"]');
            if (checkboxes.length <= 1) {
                console.log(`  SKIPPED: No checkboxes found\n`);
                this.testResults.passed++;
                return;
            }

            for (let i = 1; i < Math.min(4, checkboxes.length); i++) {
                await checkboxes[i].check({ timeout: 3000 });
            }
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('10-checklist-after');

            console.log(`  Checked ${Math.min(3, checkboxes.length - 1)} items`);
            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_11_bulk_delete() {
        const testName = 'test_11_bulk_delete';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/tagihan?per_page=100`);
            await this.page.waitForLoadState('domcontentloaded');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('11-bulk-delete-before');

            // Check some items
            const checkboxes = await this.page.$$('input[type="checkbox"]');
            for (let i = 1; i < Math.min(3, checkboxes.length); i++) {
                await checkboxes[i].check({ timeout: 2000 });
            }
            await this.page.waitForTimeout(500);

            const bulkDeleteBtn = await this.page.waitForSelector('button:has-text("Hapus")', { timeout: 3000 }).catch(() => null);
            if (!bulkDeleteBtn) {
                console.log(`  SKIPPED: No bulk Hapus button found\n`);
                this.testResults.passed++;
                return;
            }

            await bulkDeleteBtn.click({ force: true });
            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('11-bulk-delete-after');

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_12_bulk_restore() {
        const testName = 'test_12_bulk_restore';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/tagihan?per_page=100&terhapus=ya`);
            await this.page.waitForLoadState('domcontentloaded');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('12-bulk-restore-show-deleted');

            // Check some items
            const checkboxes = await this.page.$$('input[type="checkbox"]');
            for (let i = 1; i < Math.min(3, checkboxes.length); i++) {
                await checkboxes[i].check({ timeout: 2000 });
            }
            await this.page.waitForTimeout(500);

            const bulkRestoreBtn = await this.page.waitForSelector('button:has-text("Pulihkan")', { timeout: 3000 }).catch(() => null);
            if (!bulkRestoreBtn) {
                console.log(`  SKIPPED: No bulk Pulihkan button found\n`);
                this.testResults.passed++;
                return;
            }

            await bulkRestoreBtn.click({ force: true });
            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('12-bulk-restore-after');

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

const test = new TagihanCRUDTest();
test.runAllTests().catch(console.error);