const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

class RiwayatPembayaranFullTest {
    constructor() {
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'RiwayatPembayaran');
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
        await this.page.screenshot({ path: filepath, fullPage: false });
        console.log(`  [Screenshot] ${filepath}`);
        return filepath;
    }

    assert(condition, message) {
        if (!condition) {
            throw new Error(message);
        }
    }

    async login(email, password) {
        await this.page.goto(`${this.baseUrl}/login-perusahaan`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(1000);

        const companyBtn = this.page.locator('button:has(.fa-building)').first();
        await companyBtn.click();
        await this.page.waitForTimeout(800);

        const firstCompany = this.page.locator('button:has-text("CV Digital Media Nusantara")').first();
        await firstCompany.click();
        await this.page.waitForTimeout(500);

        await this.page.fill('input[type="email"]', email);
        await this.page.fill('input[type="password"]', password);

        await this.page.click('button[type="submit"]');
        await this.page.waitForTimeout(8000);

        console.log('After login URL:', this.page.url());
    }

    async runTests() {
        console.log('========================================');
        console.log('Operator Perusahaan Riwayat Pembayaran Full Test');
        console.log('CRUD + Import/Export + Bulk + Responsive + Dark Mode');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            await this.test_01_light_mode_main_page();
            await this.test_02_dark_mode_main_page();
            await this.test_03_search_functionality();
            await this.test_04_filter_with_button();
            await this.test_05_checkbox_bulk_action();
            await this.test_06_detail_modal();
            await this.test_07_create_modal();
            await this.test_08_edit_modal();
            await this.test_09_delete_modal();
            await this.test_10_responsive_desktop();
            await this.test_11_responsive_tablet();
            await this.test_12_import_export_buttons();

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
            await this.browser.close();
        }
    }

    async safeTest(name, fn) {
        try {
            await fn();
            console.log(`  ✓ ${name}`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${name}: ${e.message.substring(0, 100)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${name}: ${e.message.substring(0, 150)}`);
            await this.takeScreenshot(`XX-${name.replace(/\s/g, '-')}`);
        }
    }

    async test_01_light_mode_main_page() {
        await this.safeTest('TEST 01: Light Mode - Main Page', async () => {
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-pembayaran`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('01-light-main-page');

            const h2 = await this.page.locator('h2').first().textContent();
            if (!h2.includes('Riwayat Pembayaran')) {
                throw new Error('H2 should contain Riwayat Pembayaran');
            }

            const tableHeaders = await this.page.locator('thead th').allTextContents();
            console.log('  Table headers:', tableHeaders);

            const importBtn = await this.page.locator('button:has-text("Import")').count();
            const exportBtn = await this.page.locator('button:has-text("Export")').count();
            const templateBtn = await this.page.locator('button:has-text("Template")').count();
            const tambahBtn = await this.page.locator('button:has-text("Tambah")').count();
            const filterBtn = await this.page.locator('button:has-text("Filter")').count();
            console.log('  Import:', importBtn, 'Export:', exportBtn, 'Template:', templateBtn, 'Tambah:', tambahBtn, 'Filter:', filterBtn);

            if (importBtn === 0) throw new Error('Import button not found');
            if (exportBtn === 0) throw new Error('Export button not found');
            if (tambahBtn === 0) throw new Error('Tambah button not found');
            if (filterBtn === 0) throw new Error('Filter button not found');
        });
    }

    async test_02_dark_mode_main_page() {
        await this.safeTest('TEST 02: Dark Mode - Main Page', async () => {
            const themeBtn = this.page.locator('button[title*="Tema"]').first();
            if (await themeBtn.count() > 0) {
                const themeTitle = await themeBtn.getAttribute('title');
                console.log('  Theme button title:', themeTitle);

                await this.page.evaluate(() => localStorage.setItem('theme', 'dark'));
                await this.page.reload();
                await this.page.waitForTimeout(1500);
                await this.takeScreenshot('02-dark-main-page');

                const isDark = await this.page.evaluate(() => document.documentElement.classList.contains('dark'));
                console.log('  Dark mode active:', isDark);
                if (!isDark) throw new Error('Dark mode should be active after setting localStorage');
            }
        });
    }

    async test_03_search_functionality() {
        await this.safeTest('TEST 03: Search functionality', async () => {
            const searchInput = this.page.locator('input[placeholder*="Cari"]');
            if (await searchInput.count() > 0) {
                await searchInput.fill('pembayaran');
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('03-search-filled');

                const searchBtn = this.page.locator('button[title="Cari"]');
                if (await searchBtn.count() > 0) {
                    await searchBtn.click();
                    await this.page.waitForTimeout(2000);
                    await this.takeScreenshot('03-search-result');
                }
            }
        });
    }

    async test_04_filter_with_button() {
        await this.safeTest('TEST 04: Filter with button trigger', async () => {
            const resetBtn = this.page.locator('button:has-text("Reset filter")');
            if (await resetBtn.count() > 0) {
                await resetBtn.click();
                await this.page.waitForTimeout(1000);
            }

            const providerSelect = this.page.locator('select').first();
            if (await providerSelect.count() > 0) {
                const options = await providerSelect.locator('option').allTextContents();
                console.log('  Provider options:', options);
            }

            const filterBtn = this.page.locator('button:has-text("Filter")').first();
            if (await filterBtn.count() > 0) {
                await filterBtn.click();
                await this.page.waitForTimeout(2000);
                await this.takeScreenshot('04-filter-applied');
            }
        });
    }

    async test_05_checkbox_bulk_action() {
        await this.safeTest('TEST 05: Checkbox and Bulk Action', async () => {
            const resetBtn = this.page.locator('button:has-text("Reset filter")');
            if (await resetBtn.count() > 0) {
                await resetBtn.click();
                await this.page.waitForTimeout(1000);
            }

            const firstCheckbox = this.page.locator('tbody input[type="checkbox"]').first();
            if (await firstCheckbox.count() > 0) {
                await firstCheckbox.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('05-checkbox-selected');

                const selectedBadge = await this.page.locator('text=/data dipilih/i').count();
                console.log('  Selected badge visible:', selectedBadge > 0);
                if (selectedBadge === 0) throw new Error('Should show "X data dipilih" after clicking checkbox');
            }
        });
    }

    async test_06_detail_modal() {
        await this.safeTest('TEST 06: Detail Modal', async () => {
            const detailBtn = this.page.locator('button[title="Detail"]').first();
            if (await detailBtn.count() > 0) {
                await detailBtn.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('06-detail-modal');

                const modalTitle = await this.page.locator('text=/Detail.*Pembayaran/i').count();
                console.log('  Detail modal title visible:', modalTitle > 0);

                const closeBtn = this.page.locator('button:has(.fa-times)').last();
                if (await closeBtn.count() > 0) {
                    await closeBtn.click();
                    await this.page.waitForTimeout(500);
                }
            } else {
                console.log('  Detail button not found, checking if table has data');
            }
        });
    }

    async test_07_create_modal() {
        await this.safeTest('TEST 07: Create Modal', async () => {
            const tambahBtn = this.page.locator('button:has-text("Tambah")').first();
            if (await tambahBtn.count() > 0) {
                await tambahBtn.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('07-create-modal');

                const modalTitle = await this.page.locator('text=/Tambah.*Pembayaran/i').count();
                console.log('  Create modal title visible:', modalTitle > 0);

                const closeBtn = this.page.locator('button:has-text("Batal")');
                if (await closeBtn.count() > 0) {
                    await closeBtn.click();
                    await this.page.waitForTimeout(500);
                }
            }
        });
    }

    async test_08_edit_modal() {
        await this.safeTest('TEST 08: Edit Modal', async () => {
            const editBtn = this.page.locator('button[title="Edit"]').first();
            if (await editBtn.count() > 0) {
                await editBtn.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('08-edit-modal');

                const modalTitle = await this.page.locator('text=/Edit.*Pembayaran/i').count();
                console.log('  Edit modal title visible:', modalTitle > 0);

                const closeBtn = this.page.locator('button:has-text("Batal")');
                if (await closeBtn.count() > 0) {
                    await closeBtn.click();
                    await this.page.waitForTimeout(500);
                }
            } else {
                console.log('  Edit button not found or no editable data');
            }
        });
    }

    async test_09_delete_modal() {
        await this.safeTest('TEST 09: Delete Modal', async () => {
            const deleteBtn = this.page.locator('button[title="Hapus"]').first();
            if (await deleteBtn.count() > 0) {
                await deleteBtn.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('09-delete-modal');

                const modalTitle = await this.page.locator('text=/Hapus.*Pembayaran/i').count();
                console.log('  Delete modal title visible:', modalTitle > 0);

                const closeBtn = this.page.locator('button:has-text("Batal")');
                if (await closeBtn.count() > 0) {
                    await closeBtn.click();
                    await this.page.waitForTimeout(500);
                }
            } else {
                console.log('  Delete button not found or no deletable data');
            }
        });
    }

    async test_10_responsive_desktop() {
        await this.safeTest('TEST 10: Responsive - Desktop 1280px', async () => {
            await this.context.close();
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-pembayaran`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('10-responsive-desktop-1280');

            const table = await this.page.locator('table').count();
            if (table === 0) throw new Error('Table should be visible on desktop');
        });
    }

    async test_11_responsive_tablet() {
        await this.safeTest('TEST 11: Responsive - Tablet 768px', async () => {
            await this.context.close();
            this.context = await this.browser.newContext({ viewport: { width: 768, height: 1024 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-pembayaran`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('11-responsive-tablet-768');

            const table = await this.page.locator('table').count();
            console.log('  Table visible on tablet:', table > 0);
        });
    }

    async test_12_import_export_buttons() {
        await this.safeTest('TEST 12: Import/Export Buttons Exist', async () => {
            await this.context.close();
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-pembayaran`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('12-import-export');

            const importBtn = await this.page.locator('button:has-text("Import")').count();
            const exportBtn = await this.page.locator('button:has-text("Export")').count();
            const templateBtn = await this.page.locator('button:has-text("Template")').count();

            console.log('  Import button:', importBtn > 0);
            console.log('  Export button:', exportBtn > 0);
            console.log('  Template button:', templateBtn > 0);

            if (importBtn === 0) throw new Error('Import button should exist');
            if (exportBtn === 0) throw new Error('Export button should exist');
            if (templateBtn === 0) throw new Error('Template button should exist');
        });
    }
}

const test = new RiwayatPembayaranFullTest();
test.runTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});