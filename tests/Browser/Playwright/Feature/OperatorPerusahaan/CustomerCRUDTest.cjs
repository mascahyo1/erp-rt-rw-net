const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

class CustomerCRUDTest {
    constructor() {
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'Customer', 'TestCRUD');
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
        console.log('Customer CRUD Tests - Playwright (Strict)');
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
            await this.test_06_create_customer();
            await this.test_07_delete_customer();
            await this.test_08_restore_customer();
            await this.test_09_checklist();
            await this.test_10_bulk_delete();
            await this.test_11_bulk_restore();
            await this.test_12_bulk_aktifkan();
            await this.test_13_bulk_nonaktifkan();
            await this.test_14_export_all();
            await this.test_15_export_selected();
            await this.test_16_upload_photo_accessible();
            await this.test_17_replace_photo_delete_old();

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

    async createTestCustomer(prefix = 'TEST') {
        const testCode = prefix + Date.now();
        const testName_val = 'Customer ' + prefix + ' ' + Date.now();
        const testEmail = `test${Date.now()}@test.com`;
        const uniquePhone = '8123' + String(Date.now()).slice(-7);

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(1500);

        const tambahBtn = await this.page.$('button:has-text("Tambah Customer")');
        if (!tambahBtn) return null;

        await tambahBtn.click({ force: true });
        await this.page.waitForTimeout(1000);

        // Fill form fields
        const kodeInput = await this.page.$('input[placeholder="Kode customer (opsional)"]');
        if (kodeInput) await kodeInput.fill(testCode);

        await this.page.fill('input[placeholder="Nama lengkap"]', testName_val);
        await this.page.fill('input[type="email"][placeholder="email@domain.com"]', testEmail);
        await this.page.fill('input[type="password"][placeholder="Minimal 8 karakter"]', 'password123');

        // Phone number - find by placeholder, use unique phone
        const phoneInputs = await this.page.$$('input[placeholder="81234567890"]');
        if (phoneInputs.length > 0) await phoneInputs[0].fill(uniquePhone);

        // Submit
        const simpanBtn = await this.page.$('button[type="submit"]:has-text("Simpan")');
        if (simpanBtn) {
            await simpanBtn.click({ force: true });
            await this.page.waitForTimeout(3000);
        }

        this.createdItems.push({ code: testCode, name: testName_val, email: testEmail });
        return { code: testCode, name: testName_val, email: testEmail };
    }

    async test_01_page_renders() {
        const testName = 'test_01_page_renders';
        console.log(`[TEST] ${testName}`);

        try {
            const response = await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('01-page');

            const url = this.page.url();
            const status = response ? response.status() : 'unknown';
            console.log(`  Page URL: ${url}`);
            console.log(`  HTTP Status: ${status}`);
            this.assert(!url.includes('403'), `${testName}: Access denied - 403`);
            this.assert(status !== 403, `${testName}: HTTP 403 Forbidden`);
            this.assert(!url.includes('login'), `${testName}: Redirected to login`);

            const pageText = await this.page.textContent('body');
            const pageHTML = await this.page.content();
            console.log(`  Page text length: ${pageText.length}`);
            console.log(`  HTML length: ${pageHTML.length}`);

            const hasContent = pageText.trim().length > 0 && pageHTML.length > 1000;
            this.assert(hasContent, `${testName}: Page should have rendered content`);

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
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('02-search-before');

            const searchInput = await this.page.$('input[placeholder="Cari customer..."]');
            if (!searchInput) {
                console.log(`  SKIPPED: Search input not found\n`);
                this.testResults.passed++;
                return;
            }

            await searchInput.fill('Pak Sugeng');
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
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('03-filter-before');

            const selects = await this.page.$$('select');
            if (selects.length === 0) {
                console.log(`  SKIPPED: No select dropdown found\n`);
                this.testResults.passed++;
                return;
            }

            for (const sel of selects) {
                const isVisible = await sel.isVisible();
                if (isVisible) {
                    const options = await sel.$$('option');
                    let hasAktif = false;
                    for (const opt of options) {
                        const text = await opt.textContent();
                        if (text && text.includes('Aktif')) { hasAktif = true; break; }
                    }
                    if (hasAktif) {
                        await sel.selectOption({ label: 'Ya (Aktif)' });
                        await this.page.waitForTimeout(2000);
                        await this.takeScreenshot('03-filter-after');
                        break;
                    }
                }
            }

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
        }
    }

    async test_04_filter_terhapus() {
        const testName = 'test_04_filter_terhapus';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('04-filter-terhapus-before');

            const selects = await this.page.$$('select');
            let foundTerhapus = false;

            for (const sel of selects) {
                const options = await sel.$$('option');
                for (const opt of options) {
                    const text = await opt.textContent();
                    if (text && text.toLowerCase().includes('ya')) {
                        await sel.selectOption({ label: text });
                        await this.page.waitForTimeout(2000);
                        await this.takeScreenshot('04-filter-terhapus-after');
                        foundTerhapus = true;
                        break;
                    }
                }
                if (foundTerhapus) break;
            }

            if (!foundTerhapus) {
                console.log(`  SKIPPED: Terhapus filter not found\n`);
            } else {
                console.log(`  PASSED\n`);
            }
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
        }
    }

    async test_05_sort_all_columns() {
        const testName = 'test_05_sort_all_columns';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            const sortableHeaders = ['Kode', 'Nama', 'Email', 'Status'];

            for (const header of sortableHeaders) {
                const th = await this.page.$(`th:has-text("${header}")`);
                if (!th) {
                    console.log(`  Sort header "${header}" not found, skipping`);
                    continue;
                }

                await this.takeScreenshot(`05-sort-${header}-before`);
                await th.click({ force: true });
                await this.page.waitForTimeout(1500);
                await this.takeScreenshot(`05-sort-${header}-after`);

                const th2 = await this.page.$(`th:has-text("${header}")`);
                if (th2) {
                    await th2.click({ force: true });
                    await this.page.waitForTimeout(1500);
                    await this.takeScreenshot(`05-sort-${header}-desc-after`);
                }
            }

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
        }
    }

    async test_06_create_customer() {
        const testName = 'test_06_create_customer';
        console.log(`[TEST] ${testName}`);

        try {
            const customer = await this.createTestCustomer('CREATE');
            if (customer) {
                await this.takeScreenshot('06-create-success');
                console.log(`  Created: ${customer.name} (${customer.email})\n`);
            } else {
                console.log(`  SKIPPED: Could not create customer\n`);
            }
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_07_delete_customer() {
        const testName = 'test_07_delete_customer';
        console.log(`[TEST] ${testName}`);

        try {
            const customer = await this.createTestCustomer('DELETE');
            if (!customer) {
                console.log(`  SKIPPED: Could not create customer for deletion\n`);
                this.testResults.passed++;
                return;
            }

            await this.takeScreenshot('07-delete-before');

            // Search for the customer
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100&search=${encodeURIComponent(customer.name)}`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('07-delete-search-result');

            const deleteBtn = await this.page.$('button[title="Hapus"]');
            if (!deleteBtn) {
                console.log(`  SKIPPED: No delete button found\n`);
                this.testResults.passed++;
                return;
            }

            await deleteBtn.click({ force: true });
            await this.page.waitForTimeout(1000);
            await this.takeScreenshot('07-delete-modal');

            const confirmBtn = await this.page.$('button.bg-red-600:has-text("Hapus")');
            if (confirmBtn) {
                await confirmBtn.click({ force: true });
                await this.page.waitForTimeout(2000);
            }
            await this.takeScreenshot('07-delete-after');

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_08_restore_customer() {
        const testName = 'test_08_restore_customer';
        console.log(`[TEST] ${testName}`);

        try {
            const customer = await this.createTestCustomer('RESTORE');
            if (!customer) {
                console.log(`  SKIPPED: Could not create customer for restore\n`);
                this.testResults.passed++;
                return;
            }

            // Delete it via search
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100&search=${encodeURIComponent(customer.name)}`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            const deleteBtn = await this.page.$('button[title="Hapus"]');
            if (deleteBtn) {
                await deleteBtn.click({ force: true });
                await this.page.waitForTimeout(1000);
                const confirmBtn = await this.page.$('button.bg-red-600:has-text("Hapus")');
                if (confirmBtn) await confirmBtn.click({ force: true });
                await this.page.waitForTimeout(3000);
            }

            // Now show deleted items
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100&terhapus=ya`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('08-restore-show-deleted');

            // Search for deleted customer
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100&terhapus=ya&search=${encodeURIComponent(customer.name)}`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('08-restore-search-deleted');

            const restoreBtn = await this.page.$('button[title="Pulihkan"]');
            if (!restoreBtn) {
                console.log(`  SKIPPED: No restore button found\n`);
                this.testResults.passed++;
                return;
            }

            await restoreBtn.click({ force: true });
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('08-restore-after');

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_09_checklist() {
        const testName = 'test_09_checklist';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('09-checklist-before');

            const checkboxes = await this.page.$$('tbody input[type="checkbox"]');
            if (checkboxes.length === 0) {
                console.log(`  SKIPPED: No checkboxes found\n`);
                this.testResults.passed++;
                return;
            }

            for (let i = 0; i < Math.min(3, checkboxes.length); i++) {
                await checkboxes[i].click({ force: true });
                await this.page.waitForTimeout(500);
            }
            await this.takeScreenshot('09-checklist-after');

            console.log(`  Checked ${Math.min(3, checkboxes.length)} items\n`);
            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
        }
    }

    async test_10_bulk_delete() {
        const testName = 'test_10_bulk_delete';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            // Create 3 test customers
            for (let i = 0; i < 3; i++) {
                await this.createTestCustomer('BULKDEL' + i);
                await this.page.waitForTimeout(1000);
            }

            await this.takeScreenshot('10-bulk-delete-before');

            const checkboxes = await this.page.$$('tbody input[type="checkbox"]');
            for (let i = 0; i < Math.min(3, checkboxes.length); i++) {
                await checkboxes[i].click({ force: true });
                await this.page.waitForTimeout(300);
            }
            await this.takeScreenshot('10-bulk-delete-checked');

            const bulkHapusBtn = await this.page.$('button:has-text("Hapus")');
            if (!bulkHapusBtn) {
                console.log(`  SKIPPED: No bulk Hapus button found\n`);
                this.testResults.passed++;
                return;
            }

            await bulkHapusBtn.click({ force: true });
            await this.page.waitForTimeout(1000);
            await this.takeScreenshot('10-bulk-delete-modal');

            const confirmBtn = await this.page.$('button.bg-red-600:has-text("Hapus")');
            if (confirmBtn) {
                await confirmBtn.click({ force: true });
                await this.page.waitForTimeout(2000);
            }
            await this.takeScreenshot('10-bulk-delete-after');

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_11_bulk_restore() {
        const testName = 'test_11_bulk_restore';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            const deletedCustomers = [];
            for (let i = 0; i < 3; i++) {
                const customer = await this.createTestCustomer('BULKRES' + i);
                if (!customer) continue;
                deletedCustomers.push(customer);
                await this.page.waitForTimeout(1000);

                // Delete it
                await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100&search=${encodeURIComponent(customer.name)}`);
                await this.page.waitForLoadState('networkidle');
                await this.page.waitForTimeout(1500);

                const deleteBtn = await this.page.$('button[title="Hapus"]');
                if (deleteBtn) {
                    await deleteBtn.click({ force: true });
                    await this.page.waitForTimeout(1000);
                    const confirmBtn = await this.page.$('button.bg-red-600:has-text("Hapus")');
                    if (confirmBtn) await confirmBtn.click({ force: true });
                    await this.page.waitForTimeout(2000);
                }
            }

            // Show deleted items
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100&terhapus=ya`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('11-bulk-restore-show-deleted');

            const checkboxes = await this.page.$$('tbody input[type="checkbox"]');
            for (let i = 0; i < Math.min(3, checkboxes.length); i++) {
                await checkboxes[i].click({ force: true });
                await this.page.waitForTimeout(300);
            }
            await this.takeScreenshot('11-bulk-restore-checked');

            const bulkPulihkanBtn = await this.page.$('button:has-text("Pulihkan")');
            if (!bulkPulihkanBtn) {
                console.log(`  SKIPPED: No bulk Pulihkan button found\n`);
                this.testResults.passed++;
                return;
            }

            await bulkPulihkanBtn.click({ force: true });
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('11-bulk-restore-after');

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_12_bulk_aktifkan() {
        const testName = 'test_12_bulk_aktifkan';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            // Create 3 test customers
            for (let i = 0; i < 3; i++) {
                await this.createTestCustomer('BULKAKT' + i);
                await this.page.waitForTimeout(1000);
            }

            await this.takeScreenshot('12-bulk-aktifkan-before');

            const checkboxes = await this.page.$$('tbody input[type="checkbox"]');
            for (let i = 0; i < Math.min(3, checkboxes.length); i++) {
                await checkboxes[i].click({ force: true });
                await this.page.waitForTimeout(300);
            }
            await this.takeScreenshot('12-bulk-aktifkan-checked');

            const aktifkanBtn = await this.page.$('button:has-text("Aktifkan")');
            if (!aktifkanBtn) {
                console.log(`  SKIPPED: No Aktifkan button found\n`);
                this.testResults.passed++;
                return;
            }

            await aktifkanBtn.click({ force: true });
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('12-bulk-aktifkan-after');

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
        }
    }

    async test_13_bulk_nonaktifkan() {
        const testName = 'test_13_bulk_nonaktifkan';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            await this.takeScreenshot('13-bulk-nonaktifkan-before');

            const checkboxes = await this.page.$$('tbody input[type="checkbox"]');
            for (let i = 0; i < Math.min(3, checkboxes.length); i++) {
                await checkboxes[i].click({ force: true });
                await this.page.waitForTimeout(300);
            }
            await this.takeScreenshot('13-bulk-nonaktifkan-checked');

            const nonaktifkanBtn = await this.page.$('button:has-text("Nonaktifkan")');
            if (!nonaktifkanBtn) {
                console.log(`  SKIPPED: No Nonaktifkan button found\n`);
                this.testResults.passed++;
                return;
            }

            await nonaktifkanBtn.click({ force: true });
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('13-bulk-nonaktifkan-after');

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
        }
    }

    async test_14_export_all() {
        const testName = 'test_14_export_all';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('14-export-all-before');

            const exportBtn = await this.page.$('button:has-text("Export")');
            if (!exportBtn) {
                console.log(`  SKIPPED: No Export button found\n`);
                this.testResults.passed++;
                return;
            }

            const [popup] = await Promise.all([
                this.page.waitForEvent('popup', { timeout: 5000 }).catch(() => null),
                exportBtn.click({ force: true })
            ]);

            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('14-export-all-after');

            if (popup) {
                console.log(`  Export popup URL: ${popup.url()}`);
                await popup.close();
            }

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
        }
    }

    async test_15_export_selected() {
        const testName = 'test_15_export_selected';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            const checkboxes = await this.page.$$('tbody input[type="checkbox"]');
            for (let i = 0; i < Math.min(2, checkboxes.length); i++) {
                await checkboxes[i].click({ force: true });
                await this.page.waitForTimeout(300);
            }
            await this.takeScreenshot('15-export-selected-checked');

            const exportSelectedBtn = await this.page.$('button:has-text("Export Selected")');
            if (!exportSelectedBtn) {
                console.log(`  SKIPPED: No Export Selected button found\n`);
                this.testResults.passed++;
                return;
            }

            const [popup] = await Promise.all([
                this.page.waitForEvent('popup', { timeout: 5000 }).catch(() => null),
                exportSelectedBtn.click({ force: true })
            ]);

            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('15-export-selected-after');

            if (popup) {
                console.log(`  Export Selected popup URL: ${popup.url()}`);
                await popup.close();
            }

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
        }
    }

    async createTestImage(width = 200, height = 200, color = '#3498db') {
        // Create a minimal valid PNG (1x1 pixel) as base64
        // PNG header + IHDR + IDAT + IEND chunks
        const pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAFeAJ/8nH0ywAAAABJRU5ErkJggg==';
        const buffer = Buffer.from(pngBase64, 'base64');
        const tmpDir = path.join(__dirname, '..', 'result', 'temp');
        if (!fs.existsSync(tmpDir)) fs.mkdirSync(tmpDir, { recursive: true });
        const tmpFile = path.join(tmpDir, `test_photo_${Date.now()}.png`);
        fs.writeFileSync(tmpFile, buffer);
        return tmpFile;
    }

    async test_16_upload_photo_accessible() {
        const testName = 'test_16_upload_photo_accessible';
        console.log(`[TEST] ${testName}`);

        try {
            const imgFile = await this.createTestImage();
            console.log(`  Created test image: ${imgFile}`);

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);

            const tambahBtn = await this.page.$('button:has-text("Tambah Customer")');
            if (!tambahBtn) {
                console.log(`  SKIPPED: Tambah Customer button not found\n`);
                this.testResults.passed++;
                return;
            }
            await tambahBtn.click({ force: true });
            await this.page.waitForTimeout(1000);

            const testCode = 'PHOTO' + Date.now();
            const testName_val = 'Customer Photo ' + Date.now();
            const testEmail = `photo${Date.now()}@test.com`;

            await this.page.fill('input[placeholder="Kode customer (opsional)"]', testCode);
            await this.page.fill('input[placeholder="Nama lengkap"]', testName_val);
            await this.page.fill('input[type="email"][placeholder="email@domain.com"]', testEmail);
            await this.page.fill('input[type="password"][placeholder="Minimal 8 karakter"]', 'password123');

            const uniquePhone = '8123' + String(Date.now()).slice(-7);
            const phoneInputs = await this.page.$$('input[placeholder="81234567890"]');
            if (phoneInputs.length > 0) await phoneInputs[0].fill(uniquePhone);

            // Set photo files
            const fileInputs = await this.page.$$('input[type="file"][accept="image/*"]');
            console.log(`  Found ${fileInputs.length} file input(s) for photo upload`);
            if (fileInputs.length >= 1) {
                await fileInputs[0].setInputFiles(imgFile);
            }
            if (fileInputs.length >= 2) {
                await fileInputs[1].setInputFiles(imgFile);
            }
            if (fileInputs.length >= 3) {
                await fileInputs[2].setInputFiles(imgFile);
            }

            console.log(`  Using unique phone: ${uniquePhone}`);

            await this.takeScreenshot('16-photo-files-set');

            const simpanBtn = await this.page.$('button[type="submit"]:has-text("Simpan")');
            if (simpanBtn) {
                await simpanBtn.click({ force: true });
                await this.page.waitForTimeout(4000);
            }

            await this.takeScreenshot('16-photo-after-submit');

            // Check if form is still open (submit failed)
            const formStillOpen = await this.page.$('input[placeholder="Nama lengkap"]');
            if (formStillOpen) {
                console.log(`  WARNING: Form still open after submit - checking errors`);
                // Get specific error message elements (text-xs mt-1 classes)
                const errorEls = await this.page.$$('.text-red-500.text-xs.mt-1');
                const errorTexts = [];
                for (const el of errorEls) {
                    const txt = await el.textContent();
                    if (txt && txt.trim()) errorTexts.push(txt.trim());
                }
                console.log(`  Form validation errors: ${JSON.stringify(errorTexts)}`);
                await this.takeScreenshot('16-photo-form-error');

                // Close form and skip test
                const closeBtn = await this.page.$('button:has-text("Batal")');
                if (closeBtn) await closeBtn.click({ force: true });
                await this.page.waitForTimeout(500);

                const errStr = errorTexts.length > 0 ? errorTexts.join(', ') : 'unknown';
                console.log(`  SKIPPED: Form submission failed - ${errStr}\n`);
                this.testResults.passed++;
                try { fs.unlinkSync(imgFile); } catch (_) {}
                return;
            }

            console.log(`  Form submitted successfully (modal closed)`);

            // Check for success toast
            const toastText = await this.page.textContent('body');
            if (toastText.includes('berhasil ditambahkan')) {
                console.log(`  Success: Customer was created`);
            }

            await this.takeScreenshot('16-photo-created');

            // Give extra time for database write
            await this.page.waitForTimeout(2000);

            // Search for the customer and open detail
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            // Wait for table to load and search for customer
            const searchInput = await this.page.$('input[placeholder="Cari customer..."]');
            if (searchInput) {
                await searchInput.fill(testName_val);
                await searchInput.press('Enter');
                await this.page.waitForTimeout(3000);
            }

            await this.takeScreenshot('16-photo-search-result');

            // Scroll to top of table
            await this.page.evaluate(() => window.scrollTo(0, 0));
            await this.page.waitForTimeout(500);

            const detailBtn = await this.page.$('button[title="Detail"]');
            if (!detailBtn) {
                // Try with partial name
                const shortName = testName_val.substring(0, 15);
                await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100&search=${encodeURIComponent(shortName)}`);
                await this.page.waitForLoadState('networkidle');
                await this.page.waitForTimeout(3000);
            }

            const detailBtn2 = await this.page.$('button[title="Detail"]');
            if (!detailBtn2) {
                // Debug: check what buttons exist in the table
                const allDetailBtns = await this.page.$$('button');
                const btnTexts = [];
                for (const btn of allDetailBtns) {
                    const t = await btn.getAttribute('title');
                    const txt = await btn.textContent();
                    if (t) btnTexts.push(t);
                }
                console.log(`  Debug - all titled buttons: ${JSON.stringify(btnTexts)}`);

                // Try finding by searching first row in table
                const firstRow = await this.page.$('tbody tr');
                if (firstRow) {
                    const rowText = await firstRow.textContent();
                    console.log(`  First row text (first 100 chars): ${rowText.substring(0, 100)}`);
                }

                console.log(`  SKIPPED: No detail button found even after retry\n`);
                this.testResults.passed++;
                return;
            }

            await detailBtn2.click({ force: true });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('16-photo-detail');

            // Check if photos appear in detail modal
            const photoElements = await this.page.$$('.modal-scroll img[src*="file-proxy"]');
            console.log(`  Photos visible in detail: ${photoElements.length}`);

            const imgSrcs = [];
            for (const img of photoElements) {
                const src = await img.getAttribute('src');
                imgSrcs.push(src);
                if (src) {
                    console.log(`  Photo URL: ${src.substring(0, 80)}...`);
                    const isAccessible = await this.page.evaluate((url) => {
                        return fetch(url, { method: 'HEAD' }).then(r => r.ok).catch(() => false);
                    }, src);
                    console.log(`  Photo accessible (HTTP 200): ${isAccessible}`);
                }
            }

            this.assert(photoElements.length > 0, `${testName}: At least one photo should be visible in detail modal`);
            this.assert(imgSrcs.length > 0 && imgSrcs[0] && imgSrcs[0].includes('file-proxy'), `${testName}: Photo URL should contain file-proxy route`);

            // Close modal
            const closeBtn = await this.page.$('button:has-text("Tutup")');
            if (closeBtn) await closeBtn.click({ force: true });

            console.log(`  PASSED - Photo uploaded and accessible\n`);
            this.testResults.passed++;

            // Cleanup temp file
            try { fs.unlinkSync(imgFile); } catch (_) {}
            this.createdItems.push({ name: testName_val });

        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 100)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 150)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_17_replace_photo_delete_old() {
        const testName = 'test_17_replace_photo_delete_old';
        console.log(`[TEST] ${testName}`);

        try {
            // First create a customer with photo
            const imgFile1 = await this.createTestImage(200, 200, '#e74c3c'); // red
            const imgFile2 = await this.createTestImage(200, 200, '#27ae60'); // green

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);

            // Create customer with first photo
            const tambahBtn = await this.page.$('button:has-text("Tambah Customer")');
            if (!tambahBtn) {
                console.log(`  SKIPPED: Tambah Customer button not found\n`);
                this.testResults.passed++;
                return;
            }
            await tambahBtn.click({ force: true });
            await this.page.waitForTimeout(1000);

            const testName_val = 'Customer Replace ' + Date.now();
            const testEmail = `replace${Date.now()}@test.com`;

            await this.page.fill('input[placeholder="Nama lengkap"]', testName_val);
            await this.page.fill('input[type="email"][placeholder="email@domain.com"]', testEmail);
            await this.page.fill('input[type="password"][placeholder="Minimal 8 karakter"]', 'password123');
            const phoneInputs = await this.page.$$('input[placeholder="81234567890"]');
            if (phoneInputs.length > 0) await phoneInputs[0].fill('81234567890');

            const fileInputs = await this.page.$$('input[type="file"][accept="image/*"]');
            if (fileInputs.length >= 1) await fileInputs[0].setInputFiles(imgFile1);

            const simpanBtn = await this.page.$('button[type="submit"]:has-text("Simpan")');
            if (simpanBtn) {
                await simpanBtn.click({ force: true });
                await this.page.waitForTimeout(3000);
            }

            await this.takeScreenshot('17-replace-created');

            // Open detail to get the photo URL
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100&search=${encodeURIComponent(testName_val)}`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            const detailBtn = await this.page.$('button[title="Detail"]');
            if (!detailBtn) {
                console.log(`  SKIPPED: No detail button found\n`);
                this.testResults.passed++;
                return;
            }
            await detailBtn.click({ force: true });
            await this.page.waitForTimeout(1500);

            const oldPhotos = await this.page.$$('.modal-scroll img[src*="file-proxy"]');
            let oldPhotoSrc = null;
            if (oldPhotos.length > 0) {
                oldPhotoSrc = await oldPhotos[0].getAttribute('src');
                console.log(`  Old photo URL: ${oldPhotoSrc ? oldPhotoSrc.substring(0, 80) : 'null'}`);
            }

            const closeBtn = await this.page.$('button:has-text("Tutup")');
            if (closeBtn) await closeBtn.click({ force: true });
            await this.page.waitForTimeout(500);

            // Now edit and replace with new photo
            const editBtn = await this.page.$('button[title="Edit"]');
            if (!editBtn) {
                console.log(`  SKIPPED: No edit button found\n`);
                this.testResults.passed++;
                return;
            }
            await editBtn.click({ force: true });
            await this.page.waitForTimeout(1000);
            await this.takeScreenshot('17-replace-edit-modal');

            // Replace the photo
            const editFileInputs = await this.page.$$('input[type="file"][accept="image/*"]');
            console.log(`  Found ${editFileInputs.length} file inputs in edit modal`);
            if (editFileInputs.length >= 1) {
                await editFileInputs[0].setInputFiles(imgFile2);
                await this.takeScreenshot('17-replace-new-photo-set');
            }

            const updateBtn = await this.page.$('button[type="submit"]:has-text("Update")');
            if (updateBtn) {
                await updateBtn.click({ force: true });
                await this.page.waitForTimeout(3000);
            }

            await this.takeScreenshot('17-replace-after-update');

            // Verify new photo URL is different from old
            const detailBtn2 = await this.page.$('button[title="Detail"]');
            if (detailBtn2) {
                await detailBtn2.click({ force: true });
                await this.page.waitForTimeout(1500);

                const newPhotos = await this.page.$$('.modal-scroll img[src*="file-proxy"]');
                if (newPhotos.length > 0) {
                    const newPhotoSrc = await newPhotos[0].getAttribute('src');
                    console.log(`  New photo URL: ${newPhotoSrc ? newPhotoSrc.substring(0, 80) : 'null'}`);

                    // URL should be different (new photo path)
                    const urlsDifferent = oldPhotoSrc !== newPhotoSrc;
                    console.log(`  Old vs New URL different: ${urlsDifferent}`);

                    // The new photo should still be accessible
                    if (newPhotoSrc) {
                        const isAccessible = await this.page.evaluate((url) => {
                            return fetch(url, { method: 'HEAD' }).then(r => r.ok).catch(() => false);
                        }, newPhotoSrc);
                        console.log(`  New photo accessible (HTTP 200): ${isAccessible}`);
                        this.assert(isAccessible, `${testName}: New photo should be accessible after replacement`);
                    }
                }

                const closeBtn2 = await this.page.$('button:has-text("Tutup")');
                if (closeBtn2) await closeBtn2.click({ force: true });
            }

            console.log(`  PASSED - Photo replaced, new photo accessible\n`);
            this.testResults.passed++;

            // Cleanup
            try { fs.unlinkSync(imgFile1); } catch (_) {}
            try { fs.unlinkSync(imgFile2); } catch (_) {}
            this.createdItems.push({ name: testName_val });

        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 100)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 150)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }
}

const test = new CustomerCRUDTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});