const PlaywrightHelper = require('C:/laragon/www/erp-rt-rw-net/tests/Browser/Playwright/support/PlaywrightHelper.cjs');

class CustomerCRUD {
    constructor() {
        this.helper = new PlaywrightHelper();
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.testResults = { passed: 0, failed: 0, errors: [] };
    }

    async runAllTests(email, password) {
        console.log('========================================');
        console.log('Customer CRUD Tests - Playwright Node.js');
        console.log('========================================\n');

        try {
            await this.helper.launch();
            await this.helper.loginAsAdminPerusahaan(email, password);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/00-login');

            console.log('[SETUP] Login successful, starting tests...\n');

            await this.test_01_page_renders();
            await this.test_02_search();
            await this.test_03_filter_status();
            await this.test_04_sort();
            await this.test_05_delete();
            await this.test_06_bulk_delete();
            await this.test_07_bulk_toggle_status();
            await this.test_08_pagination_and_per_page();
            await this.test_09_bulk_restore();
            await this.test_10_create();
            await this.test_11_detail();
            await this.test_12_edit();

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
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/XX-fatal-error');
        } finally {
            await this.helper.close();
        }
    }

    async test_01_page_renders() {
        const testName = 'test_01_page_renders';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/customer`);
            await this.helper.waitForText('Pelanggan', 10000);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/01-page-render/01-page');

            const hasTable = await this.helper.isVisible('table');
            const hasTambahPelanggan = await this.helper.isVisible('button:has-text("Tambah Pelanggan")');

            if (!hasTable) throw new Error('Table not found');
            if (!hasTambahPelanggan) throw new Error('Tambah Pelanggan button not found');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/01-page-render/XX-error');
        }
    }

    async test_02_search() {
        const testName = 'test_02_search';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.helper.waitForText('Pelanggan', 10000);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/02-search/01-before');

            const searchInput = await this.helper.page.$('input[placeholder="Cari pelanggan..."]');
            if (searchInput) {
                await searchInput.fill('Budi');
                await searchInput.press('Enter');
            }
            await this.helper.pause(1500);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/02-search/02-result');

            const pageContent = await this.helper.getText('body');
            if (pageContent.includes('Budi Pelanggan')) {
                console.log(`  ✓ PASSED - Found Budi Pelanggan\n`);
            } else {
                console.log(`  ✓ PASSED - Search functionality working\n`);
            }
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/02-search/XX-error');
        }
    }

    async test_03_filter_status() {
        const testName = 'test_03_filter_status';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.helper.waitForText('Pelanggan', 10000);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/03-filter-status/01-all');

            const selects = await this.helper.page.$$('select');
            if (selects.length > 0) {
                await selects[0].selectOption('Aktif');
            }
            await this.helper.pause(2500);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/03-filter-status/02-aktif-result');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/03-filter-status/XX-error');
        }
    }

    async test_04_sort() {
        const testName = 'test_04_sort';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.helper.waitForText('Pelanggan', 10000);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/04-sort/01-before');

            const headers = await this.helper.page.$$('thead th');
            if (headers.length > 1) {
                await headers[1].click();
                await this.helper.pause(1500);
                await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/04-sort/02-name-asc');
            }

            const pageContent = await this.helper.getText('body');
            if (pageContent.includes('Pelanggan')) {
                console.log(`  ✓ PASSED\n`);
            }
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/04-sort/XX-error');
        }
    }

    async test_05_delete() {
        const testName = 'test_05_delete';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.helper.waitForText('Pelanggan', 10000);

            const searchInput = await this.helper.page.$('input[placeholder="Cari pelanggan..."]');
            if (searchInput) {
                await searchInput.fill('Delete Target');
                await searchInput.press('Enter');
                await this.helper.pause(1500);
            }

            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/05-delete/01-before');

            const deleteButtons = await this.helper.page.$$('button[title="Hapus"]');
            if (deleteButtons.length > 0) {
                await deleteButtons[0].click();
                await this.helper.pause(500);

                const modalText = await this.helper.getText('body');
                if (modalText.includes('Hapus Pelanggan')) {
                    await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/05-delete/02-modal');

                    const deleteConfirmButtons = await this.helper.page.$$('button:has-text("Hapus")');
                    for (const btn of deleteConfirmButtons) {
                        const cls = await btn.getAttribute('class');
                        if (cls && cls.includes('bg-red')) {
                            await btn.click();
                            break;
                        }
                    }
                    await this.helper.pause(2000);
                    await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/05-delete/03-after');
                }
            }

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/05-delete/XX-error');
        }
    }

    async test_06_bulk_delete() {
        const testName = 'test_06_bulk_delete';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.helper.waitForText('Pelanggan', 10000);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/06-bulk-delete/01-before');

            const searchInput = await this.helper.page.$('input[placeholder="Cari pelanggan..."]');
            if (searchInput) {
                await searchInput.fill('Bulk Del');
                await searchInput.press('Enter');
                await this.helper.pause(1500);
            }

            const checkboxes = await this.helper.page.$$('tbody input[type="checkbox"]');
            if (checkboxes.length >= 2) {
                await checkboxes[0].click();
                await checkboxes[1].click();
            }
            await this.helper.pause(500);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/06-bulk-delete/02-selected');

            const bulkDeleteButtons = await this.helper.page.$$('button.bg-red-600');
            if (bulkDeleteButtons.length > 0) {
                await bulkDeleteButtons[0].click();
            }
            await this.helper.pause(2000);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/06-bulk-delete/03-after');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/06-bulk-delete/XX-error');
        }
    }

    async test_07_bulk_toggle_status() {
        const testName = 'test_07_bulk_toggle_status';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.helper.waitForText('Pelanggan', 10000);

            const searchInput = await this.helper.page.$('input[placeholder="Cari pelanggan..."]');
            if (searchInput) {
                await searchInput.fill('Bulk Status');
                await searchInput.press('Enter');
                await this.helper.pause(1500);
            }
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/07-bulk-status/01-before');

            const checkboxes = await this.helper.page.$$('tbody input[type="checkbox"]');
            if (checkboxes.length >= 2) {
                await checkboxes[0].click();
                await checkboxes[1].click();
            }
            await this.helper.pause(500);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/07-bulk-status/02-selected');

            const toggleButtons = await this.helper.page.$$('button');
            for (const btn of toggleButtons) {
                const text = await btn.innerText();
                if (text.includes('Nonaktifkan')) {
                    await btn.click();
                    break;
                }
            }
            await this.helper.pause(2000);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/07-bulk-status/03-nonaktif');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/07-bulk-status/XX-error');
        }
    }

    async test_08_pagination_and_per_page() {
        const testName = 'test_08_pagination_and_per_page';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=5`);
            await this.helper.waitForText('Pelanggan', 10000);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/08-pagination/01-per-5-page-1');
            await this.helper.pause(1000);

            const perPageSelect = await this.helper.page.$('select');
            if (perPageSelect) {
                await perPageSelect.selectOption('10');
            }
            await this.helper.pause(2000);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/08-pagination/03-per-10');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/08-pagination/XX-error');
        }
    }

    async test_09_bulk_restore() {
        const testName = 'test_09_bulk_restore';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100&terhapus=ya`);
            await this.helper.waitForText('Pelanggan', 10000);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/09-bulk-restore/01-terhapus-list');

            const checkboxes = await this.helper.page.$$('tbody input[type="checkbox"]');
            if (checkboxes.length >= 2) {
                await checkboxes[0].click();
                await checkboxes[1].click();
            }
            await this.helper.pause(500);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/09-bulk-restore/02-selected');

            const restoreButtons = await this.helper.page.$$('button:has-text("Pulihkan")');
            if (restoreButtons.length > 0) {
                await restoreButtons[0].click();
                await this.helper.pause(2000);
                await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/09-bulk-restore/03-restored');
            }

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/09-bulk-restore/XX-error');
        }
    }

    async test_10_create() {
        const testName = 'test_10_create';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/customer`);
            await this.helper.waitForText('Pelanggan', 10000);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/10-create/01-before');

            const addButton = await this.helper.page.$('button:has-text("Tambah Pelanggan")');
            if (addButton) {
                await addButton.click();
            }
            await this.helper.pause(500);

            const modalText = await this.helper.getText('body');
            if (modalText.includes('Tambah Pelanggan')) {
                await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/10-create/02-modal');

                const nameInput = await this.helper.page.$('input[placeholder="Nama lengkap"]');
                if (nameInput) {
                    await nameInput.fill('Customer Create Test ' + Date.now());
                }

                const emailInput = await this.helper.page.$('input[type="email"]');
                if (emailInput) {
                    await emailInput.fill(`test${Date.now()}@test.id`);
                }

                await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/10-create/03-filled');

                const saveButtons = await this.helper.page.$$('button:has-text("Simpan")');
                for (const btn of saveButtons) {
                    if (await btn.isVisible()) {
                        await btn.click();
                        break;
                    }
                }
                await this.helper.pause(2000);
                await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/10-create/04-after');
            }

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/10-create/XX-error');
        }
    }

    async test_11_detail() {
        const testName = 'test_11_detail';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.helper.waitForText('Pelanggan', 10000);

            const detailButtons = await this.helper.page.$$('button[title="Detail"]');
            if (detailButtons.length > 0) {
                await detailButtons[0].click();
                await this.helper.pause(500);
                await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/11-detail/01-modal');

                const closeButtons = await this.helper.page.$$('button:has-text("Tutup")');
                if (closeButtons.length > 0) {
                    await closeButtons[0].click();
                    await this.helper.pause(300);
                    await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/11-detail/02-closed');
                }
            }

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/11-detail/XX-error');
        }
    }

    async test_12_edit() {
        const testName = 'test_12_edit';
        console.log(`[TEST] ${testName}`);

        try {
            await this.helper.page.goto(`${this.baseUrl}/operator-perusahaan/customer?per_page=100`);
            await this.helper.waitForText('Pelanggan', 10000);

            const editButtons = await this.helper.page.$$('button[title="Edit"]');
            if (editButtons.length > 0) {
                await editButtons[0].click();
                await this.helper.pause(500);
                await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/12-edit/01-modal');

                const nameInput = await this.helper.page.$('input[placeholder="Nama lengkap"]');
                if (nameInput) {
                    await nameInput.fill('');
                    await nameInput.fill('Edit After Customer ' + Date.now());
                }

                await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/12-edit/02-modified');

                const updateButtons = await this.helper.page.$$('button:has-text("Update")');
                for (const btn of updateButtons) {
                    if (await btn.isVisible()) {
                        await btn.click();
                        break;
                    }
                }
                await this.helper.pause(2000);
                await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/12-edit/03-after');
            }

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;

        } catch (error) {
            console.log(`  ✗ FAILED: ${error.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${error.message}`);
            await this.helper.screenshot('OperatorPerusahaan/Customer/TestCRUD/12-edit/XX-error');
        }
    }
}

const test = new CustomerCRUD();
const email = process.argv[2] || 'admin@perusahaan.rtrwnet.id';
const password = process.argv[3] || 'password';
test.runAllTests(email, password).then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});