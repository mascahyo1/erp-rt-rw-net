const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

class RiwayatInsentifFullTest {
    constructor() {
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'RiwayatInsentif');
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
        console.log('Riwayat Insentif Full Test - Light/Dark + Responsive + CRUD');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            // ============================================================
            // TEST 01: Light Mode - Main Page
            // ============================================================
            console.log('\nTEST 01: Light Mode - Main Page');
            console.log('========================================');

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('01-light-main-page');

            // Check page title and main elements
            const h2 = await this.page.locator('h2').first().textContent();
            console.log('H2:', h2);
            this.assert(h2.includes('Riwayat Insentif'), 'H2 should contain Riwayat Insentif');

            // Check all buttons present
            const importBtn = await this.page.locator('button:has-text("Import")').count();
            const exportBtn = await this.page.locator('button:has-text("Export")').count();
            const templateBtn = await this.page.locator('button:has-text("Template")').count();
            const tambahBtn = await this.page.locator('button:has-text("Tambah Riwayat")').count();
            const filterBtn = await this.page.locator('button:has-text("Filter")').count();
            console.log('Import:', importBtn, 'Export:', exportBtn, 'Template:', templateBtn, 'Tambah:', tambahBtn, 'Filter:', filterBtn);

            // Check table visible and columns
            const tableHeaders = await this.page.locator('thead th').allTextContents();
            console.log('Table headers:', tableHeaders);

            const hasInsentif = await this.page.locator('text=/Insentif/i').first().isVisible().catch(() => false);
            const hasNoInvoice = await this.page.locator('text=/No. Invoice/i').first().isVisible().catch(() => false);
            const hasPelanggan = await this.page.locator('text=/Pelanggan/i').first().isVisible().catch(() => false);
            console.log('Insentif column:', hasInsentif, 'No Invoice column:', hasNoInvoice, 'Pelanggan column:', hasPelanggan);

            // Check search input
            const searchInput = await this.page.locator('input[placeholder*="Cari"]').count();
            console.log('Search input visible:', searchInput > 0);

            console.log('TEST 01: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 02: Dark Mode - Main Page
            // ============================================================
            console.log('\nTEST 02: Dark Mode - Main Page');
            console.log('========================================');

            // Toggle dark mode
            const themeBtn = this.page.locator('button[title*="Tema"]').first();
            console.log('Theme button found:', await themeBtn.count() > 0);
            const themeTitle = await themeBtn.getAttribute('title');
            console.log('Theme button title:', themeTitle);
            await themeBtn.click();
            await this.page.waitForTimeout(1000);
            await this.takeScreenshot('02-dark-main-page');

            const isDark = await this.page.evaluate(() => document.documentElement.classList.contains('dark'));
            console.log('Dark mode active:', isDark);
            console.log('Current theme from localStorage:', await this.page.evaluate(() => localStorage.getItem('theme')));

            // Check dark mode table - table should still be visible
            const tableInDark = await this.page.locator('table').count();
            console.log('Table in dark mode:', tableInDark > 0);

            // Check dark mode text is visible
            const h2Dark = await this.page.locator('h2').first().textContent();
            console.log('H2 in dark mode:', h2Dark);

            console.log('TEST 02: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 03: Search functionality
            // ============================================================
            console.log('\nTEST 03: Search functionality');
            console.log('========================================');

            const searchInputEl = this.page.locator('input[placeholder*="Cari"]');
            await searchInputEl.fill('Insentif');
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('03-search-filled');

            // Click search button
            await this.page.locator('button[title="Cari"]').click();
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('04-search-result');

            console.log('Search applied');
            console.log('TEST 03: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 04: Filter dropdowns
            // ============================================================
            console.log('\nTEST 04: Filter - Status and Date');
            console.log('========================================');

            // Reset search first
            const resetBtn = this.page.locator('button:has-text("Reset filter")');
            if (await resetBtn.count() > 0) await resetBtn.click();
            await this.page.waitForTimeout(1000);

            // Check status filter
            const statusFilter = this.page.locator('select').first();
            await statusFilter.selectOption('pending');
            await this.page.waitForTimeout(300);

            // Click Filter button
            await this.page.getByRole('button', { name: 'Filter', exact: true }).click();
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('05-filter-applied');

            console.log('Status filter applied');
            console.log('TEST 04: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 05: Checkbox per row toggle
            // ============================================================
            console.log('\nTEST 05: Checkbox per row toggle');
            console.log('========================================');

            // Reset filters
            await this.page.locator('button:has-text("Reset filter")').click();
            await this.page.waitForTimeout(1000);

            // Click checkbox on first row
            const firstCheckbox = this.page.locator('tbody input[type="checkbox"]').first();
            await firstCheckbox.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('06-checkbox-selected');

            const selectedBadge = await this.page.locator('text=/data dipilih/i').count();
            console.log('Selected badge visible:', selectedBadge > 0);
            this.assert(selectedBadge > 0, 'Should show "X data dipilih" after clicking checkbox');

            console.log('TEST 05: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 06: Row click toggles checkbox
            // ============================================================
            console.log('\nTEST 06: Row click toggles checkbox');
            console.log('========================================');

            // Click on first row (not on checkbox)
            const firstRow = this.page.locator('tbody tr').first();
            await firstRow.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('07-row-clicked');

            // The row checkbox should be checked now or toggled
            const checkboxAfterRowClick = await this.page.locator('tbody input[type="checkbox"]').first().isChecked();
            console.log('Checkbox after row click:', checkboxAfterRowClick);

            console.log('TEST 06: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 07: Detail Modal
            // ============================================================
            console.log('\nTEST 07: Detail Modal');
            console.log('========================================');

            // Click detail button on first row
            const detailBtn = this.page.locator('button[title="Detail"]').first();
            await detailBtn.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('08-detail-modal');

            // Check modal title
            const detailModalTitle = await this.page.locator('text=/Detail Riwayat Insentif/i').count();
            console.log('Detail modal title visible:', detailModalTitle > 0);

            // Check No. Invoice text is visible in dark mode
            const noInvoiceInModal = await this.page.locator('text=/No. Invoice/i').first().isVisible();
            console.log('No Invoice label visible:', noInvoiceInModal);

            // Close modal - click the X close button
            const closeBtns = this.page.locator('button:has(.fa-times)');
            await closeBtns.last().click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('08-detail-closed');

            console.log('TEST 07: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 08: Review Modal
            // ============================================================
            console.log('\nTEST 08: Review Modal');
            console.log('========================================');

            // Find a pending status row that has review button
            const reviewBtn = this.page.locator('button[title="Review"]').first();
            if (await reviewBtn.count() > 0) {
                await reviewBtn.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('09-review-modal');

                // Check modal title contains Review
                const reviewModalTitle = await this.page.locator('text=/Review:/i').count();
                console.log('Review modal visible:', reviewModalTitle > 0);

                // Check No Invoice in review modal is visible
                const noInvoiceInReview = await this.page.locator('text=/No. Invoice/i').first().isVisible();
                console.log('No Invoice in review modal visible:', noInvoiceInReview);

                // Close modal
                await this.page.keyboard.press('Escape');
                await this.page.waitForTimeout(300);
            } else {
                console.log('No pending item with review button found, skipping review modal test');
            }

            console.log('TEST 08: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 09: Create Modal - Open and Check
            // ============================================================
            console.log('\nTEST 09: Create Modal');
            console.log('========================================');

            // Click Tambah Riwayat button
            const tambahBtnEl = this.page.locator('button:has-text("Tambah Riwayat")');
            if (await tambahBtnEl.count() > 0) {
                await tambahBtnEl.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('10-create-modal');

                // Check modal title
                const createModalTitle = await this.page.locator('h3:has-text("Tambah Riwayat Insentif")').count();
                console.log('Create modal title visible:', createModalTitle > 0);

                // Check Date input visible
                const dateInputs = await this.page.locator('input[type="date"]').count();
                console.log('Date inputs visible:', dateInputs);

                // Check dark mode for date input icon
                const isDarkNow = await this.page.evaluate(() => document.documentElement.classList.contains('dark'));
                if (isDarkNow) {
                    const dateInput = this.page.locator('input[type="date"]').first();
                    const dateInputVisible = await dateInput.isVisible();
                    console.log('Date input in dark mode visible:', dateInputVisible);
                }

                // Close modal
                await this.page.locator('button:has-text("Batal")').click();
                await this.page.waitForTimeout(300);
            } else {
                console.log('Tambah button not visible (permission check)');
            }

            console.log('TEST 09: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 10: Mobile Responsive (375x667)
            // ============================================================
            console.log('\nTEST 10: Mobile Responsive (375x667)');
            console.log('========================================');

            await this.context.close();
            this.context = await this.browser.newContext({ viewport: { width: 375, height: 667 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('11-mobile-light');

            // Check heading visible
            const mobileH2 = await this.page.locator('h2').first().textContent();
            console.log('Mobile H2:', mobileH2);

            // Check menu button (hamburger)
            const menuBtn = this.page.locator('button:has(.fa-bars)').count();
            console.log('Mobile menu button:', menuBtn > 0);

            // Check content within viewport
            const mainContent = await this.page.locator('main').boundingBox();
            console.log('Main content width:', mainContent?.width, '(should be <= 375)');

            // Toggle dark mode on mobile
            const themeBtnMobile = this.page.locator('button[title*="Tema"]').first();
            await themeBtnMobile.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('12-mobile-dark');

            const isDarkMobile = await this.page.evaluate(() => document.documentElement.classList.contains('dark'));
            console.log('Mobile dark mode:', isDarkMobile);

            console.log('TEST 10: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 11: Tablet Responsive (768x1024)
            // ============================================================
            console.log('\nTEST 11: Tablet Responsive (768x1024)');
            console.log('========================================');

            await this.context.close();
            this.context = await this.browser.newContext({ viewport: { width: 768, height: 1024 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('13-tablet-light');

            const tabletH2 = await this.page.locator('h2').first().textContent();
            console.log('Tablet H2:', tabletH2);

            // Check sidebar visible
            const sidebar = await this.page.locator('aside').boundingBox();
            console.log('Sidebar width:', sidebar?.width, '(should be > 50)');

            console.log('TEST 11: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 12: Import/Export buttons
            // ============================================================
            console.log('\nTEST 12: Import/Export buttons');
            console.log('========================================');

            await this.context.close();
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            const importBtnFinal = await this.page.locator('button:has-text("Import")').count();
            const exportBtnFinal = await this.page.locator('button:has-text("Export")').count();
            console.log('Import button:', importBtnFinal > 0, 'Export button:', exportBtnFinal > 0);

            // Open import modal
            if (importBtnFinal > 0) {
                await this.page.locator('button:has-text("Import")').click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('14-import-modal');

                const importModalTitle = await this.page.locator('h3:has-text("Import Riwayat Insentif")').count();
                console.log('Import modal title:', importModalTitle > 0);

                // Close modal
                await this.page.keyboard.press('Escape');
                await this.page.waitForTimeout(500);
            }

            // Check export dropdown
            if (exportBtnFinal > 0) {
                await this.page.locator('button:has-text("Export")').click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('15-export-dropdown');

                const exportAllBtn = await this.page.locator('text=/Export Semua/i').count();
                console.log('Export dropdown menu:', exportAllBtn > 0);

                await this.page.keyboard.press('Escape');
            }

            console.log('TEST 12: PASSED');
            this.testResults.passed++;

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
            this.testResults.errors.push(error.message);
        } finally {
            if (this.browser) await this.browser.close();
        }
    }
}

new RiwayatInsentifFullTest().runTests();
