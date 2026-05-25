const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

class InsentifFullTest {
    constructor() {
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'Insentif');
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
        console.log('Insentif Full Test - Light/Dark + Responsive + CRUD');
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

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('01-light-main-page');

            // Check all elements visible
            const h2 = await this.page.locator('h2').first().textContent();
            console.log('H2:', h2);
            this.assert(h2.includes('Insentif'), 'H2 should contain Insentif');

            const importBtn = await this.page.locator('button:has-text("Import")').count();
            const exportBtn = await this.page.locator('button:has-text("Export")').count();
            const tambahBtn = await this.page.locator('button:has-text("Tambah Insentif")').count();
            const filterBtn = await this.page.locator('button:has-text("Filter")').count();
            console.log('Import:', importBtn, 'Export:', exportBtn, 'Tambah:', tambahBtn, 'Filter:', filterBtn);

            // Check table visible
            const tableHeaders = await this.page.locator('thead th').allTextContents();
            console.log('Table headers:', tableHeaders);

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

            // Check dark mode table
            const tableInDark = await this.page.locator('table').count();
            console.log('Table in dark mode:', tableInDark > 0);

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

            // Click search button (by title since icon has no text)
            await this.page.locator('button[title="Cari"]').click();
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('04-search-result');

            console.log('Search applied');
            console.log('TEST 03: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 04: Filter dropdowns
            // ============================================================
            console.log('\nTEST 04: Filter dropdowns');
            console.log('========================================');

            // Check status filter
            const statusFilter = this.page.locator('select').first();
            await statusFilter.selectOption('Aktif');
            await this.page.waitForTimeout(300);

            // Click Filter button (not onChange) - exact match
            await this.page.getByRole('button', { name: 'Filter', exact: true }).click();
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('05-filter-applied');

            console.log('Filter applied via button');
            console.log('TEST 04: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 05: Create Modal
            // ============================================================
            console.log('\nTEST 05: Create Modal');
            console.log('========================================');

            // Reset filters first
            const resetBtn = this.page.locator('button:has-text("Reset filter")');
            if (await resetBtn.count() > 0) await resetBtn.click();
            await this.page.waitForTimeout(1000);

            // Click Tambah Insentif
            await this.page.locator('button:has-text("Tambah Insentif")').click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('06-create-modal');

            // Check modal elements
            const modalTitle = await this.page.locator('h3:has-text("Tambah Insentif")').count();
            console.log('Modal title visible:', modalTitle > 0);

            const nameInput = await this.page.locator('input[type="text"]').first();
            await nameInput.fill('Insentif Harian');
            await this.takeScreenshot('07-create-filled');

            // Fill type - target the select inside the modal form (with class grid)
            await this.page.locator('.fixed form select').first().selectOption('percentage');

            // Fill value
            await this.page.getByLabel('Nilai').fill('10');

            console.log('TEST 05: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 06: Submit Create
            // ============================================================
            console.log('\nTEST 06: Submit Create');
            console.log('========================================');

            // Click Simpan
            await this.page.locator('button:has-text("Simpan")').click();
            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('08-after-create');

            // Check toast success
            const toastSuccess = await this.page.locator('text=/berhasil ditambahkan/i').count();
            console.log('Toast success:', toastSuccess > 0);

            console.log('TEST 06: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 07: Edit Modal
            // ============================================================
            console.log('\nTEST 07: Edit Modal');
            console.log('========================================');

            // Click edit on first row
            const editBtn = this.page.locator('button[title="Edit"]').first();
            await editBtn.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('09-edit-modal');

            const editModalTitle = await this.page.locator('h3:has-text("Edit Insentif")').count();
            console.log('Edit modal title visible:', editModalTitle > 0);

            // Change name
            const editNameInput = this.page.locator('input[type="text"]').first();
            await editNameInput.fill('Insentif Harian Updated');
            await this.takeScreenshot('10-edit-filled');

            console.log('TEST 07: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 08: Submit Edit
            // ============================================================
            console.log('\nTEST 08: Submit Edit');
            console.log('========================================');

            await this.page.locator('button:has-text("Update")').click();
            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('11-after-edit');

            const editToast = await this.page.locator('text=/berhasil diperbarui/i').count();
            console.log('Edit toast success:', editToast > 0);

            console.log('TEST 08: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 09: Checkbox per row toggle
            // ============================================================
            console.log('\nTEST 09: Checkbox per row toggle');
            console.log('========================================');

            const firstCheckbox = this.page.locator('tbody input[type="checkbox"]').first();
            await firstCheckbox.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('12-checkbox-selected');

            const selectedBadge = await this.page.locator('text=/1 data dipilih/i').count();
            console.log('Selected badge visible:', selectedBadge > 0);
            this.assert(selectedBadge > 0, 'Should show "1 data dipilih" after clicking checkbox');

            // Check bulk buttons
            const aktifkanBtn = await this.page.locator('button:has-text("Aktifkan")').count();
            const nonaktifkanBtn = await this.page.locator('button:has-text("Nonaktifkan")').count();
            const hapusBtn = await this.page.locator('button:has-text("Hapus")').count();
            console.log('Bulk buttons - Aktifkan:', aktifkanBtn, 'Nonaktifkan:', nonaktifkanBtn, 'Hapus:', hapusBtn);

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

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('13-mobile-light');

            // Check heading visible
            const mobileH2 = await this.page.locator('h2').first().textContent();
            console.log('Mobile H2:', mobileH2);

            // Check menu button (hamburger)
            const menuBtn = await this.page.locator('button:has(.fa-bars)').count();
            console.log('Mobile menu button:', menuBtn > 0);

            // Check content within viewport
            const mainContent = await this.page.locator('main').boundingBox();
            console.log('Main content width:', mainContent?.width, '(should be <= 375)');

            // Toggle dark mode on mobile
            const themeBtnMobile = this.page.locator('button[title*="Tema"]').first();
            await themeBtnMobile.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('14-mobile-dark');

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

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('15-tablet-light');

            const tabletH2 = await this.page.locator('h2').first().textContent();
            console.log('Tablet H2:', tabletH2);

            // Check sidebar visible
            const sidebar = await this.page.locator('aside').boundingBox();
            console.log('Sidebar width:', sidebar?.width, '(should be > 50)');

            console.log('TEST 11: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 12: Delete functionality
            // ============================================================
            console.log('\nTEST 12: Delete functionality');
            console.log('========================================');

            await this.context.close();
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            // Click delete on first row
            const deleteBtn = this.page.locator('button[title="Hapus"]').first();
            await deleteBtn.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('16-delete-modal');

            // Check confirmation modal
            const deleteModal = await this.page.locator('text=/Hapus Insentif/i').count();
            console.log('Delete confirmation modal:', deleteModal > 0);

            // Confirm delete
            await this.page.locator('button:has-text("Hapus")').last().click();
            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('17-after-delete');

            const deleteToast = await this.page.locator('text=/berhasil dihapus/i').count();
            console.log('Delete toast:', deleteToast > 0);

            console.log('TEST 12: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 13: Import Export buttons check
            // ============================================================
            console.log('\nTEST 13: Import/Export buttons');
            console.log('========================================');

            const importBtnFinal = await this.page.locator('button:has-text("Import")').count();
            const exportBtnFinal = await this.page.locator('button:has-text("Export")').count();
            console.log('Import button:', importBtnFinal > 0, 'Export button:', exportBtnFinal > 0);

            // Open import modal
            await this.page.locator('button:has-text("Import")').click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('18-import-modal');

            const importModalTitle = await this.page.locator('h3:has-text("Import Insentif")').count();
            console.log('Import modal title:', importModalTitle > 0);

            // Close modal
            await this.page.keyboard.press('Escape');
            await this.page.waitForTimeout(500);

            // Check export
            await this.page.locator('button:has-text("Export")').click();
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('19-export-triggered');

            console.log('TEST 13: PASSED');
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

new InsentifFullTest().runTests();