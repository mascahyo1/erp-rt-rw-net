const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class InsentifTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
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
        await this.page.goto(`${BASE}/login-perusahaan`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(1000);

        // Click company dropdown button
        const companyBtn = this.page.locator('button:has(.fa-building)').first();
        await companyBtn.click();
        await this.page.waitForTimeout(800);

        // Select first company "CV Digital Media Nusantara" from dropdown
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
        console.log('Insentif CRUD Test - Playwright');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false, slowMo: 350 });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            // Login as admin@digitalmedia.id (has full RBAC permissions)
            await this.login('admin@digitalmedia.id', 'password123');

            // ============================================================
            // TEST 01: Check Import/Export buttons visible (permission check)
            // ============================================================
            console.log('\nTEST 01: Import/Export buttons visible');
            console.log('========================================');

            await this.page.goto(`${BASE}/operator-perusahaan/insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('01-insentif-page');

            // Check Import button
            const importBtn = this.page.locator('button:has-text("Import")').count();
            console.log('Import button count:', importBtn);
            this.assert(importBtn > 0, 'Import button should be visible - user has insentif.import permission');

            // Check Export button
            const exportBtn = this.page.locator('button:has-text("Export")').count();
            console.log('Export button count:', exportBtn);
            this.assert(exportBtn > 0, 'Export button should be visible - user has insentif.export permission');

            console.log('TEST 01: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 02: Checkbox per row toggle
            // ============================================================
            console.log('\nTEST 02: Checkbox per row toggle');
            console.log('========================================');

            // Get initial selected count
            const selectedBadge = await this.page.locator('text=/data dipilih/i').count();
            console.log('Selected badge visible:', selectedBadge > 0);

            // Click first checkbox in tbody (not the header select all)
            const firstCheckbox = this.page.locator('tbody input[type="checkbox"]').first();
            const firstCheckboxExists = await firstCheckbox.count() > 0;

            if (firstCheckboxExists) {
                await firstCheckbox.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('02-after-checkbox-click');

                // Check if selected badge appears
                const selectedAfterClick = await this.page.locator('text=/1 data dipilih/i').count();
                console.log('After click - 1 data dipilih visible:', selectedAfterClick > 0);
                this.assert(selectedAfterClick > 0, 'Should show "1 data dipilih" after clicking checkbox');

                console.log('TEST 02: PASSED');
                this.testResults.passed++;
            } else {
                console.log('No data rows to test checkbox');
                console.log('TEST 02: PASSED (no data)');
                this.testResults.passed++;
            }

            // ============================================================
            // TEST 03: Bulk actions appear when item selected
            // ============================================================
            console.log('\nTEST 03: Bulk actions visible when item selected');
            console.log('========================================');

            const bulkActionsVisible = await this.page.locator('button:has-text("Aktifkan")').count();
            console.log('Bulk Aktifkan button:', bulkActionsVisible);
            this.assert(bulkActionsVisible > 0, 'Bulk action buttons should appear when item selected');

            const bulkNonaktifkan = await this.page.locator('button:has-text("Nonaktifkan")').count();
            console.log('Bulk Nonaktifkan button:', bulkNonaktifkan);
            this.assert(bulkNonaktifkan > 0, 'Bulk Nonaktifkan button should appear');

            const bulkHapus = await this.page.locator('button:has-text("Hapus")').count();
            console.log('Bulk Hapus button:', bulkHapus);
            this.assert(bulkHapus > 0, 'Bulk Hapus button should appear');

            console.log('TEST 03: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 04: Toggle select all
            // ============================================================
            console.log('\nTEST 04: Toggle select all');
            console.log('========================================');

            const selectAllCheckbox = this.page.locator('thead input[type="checkbox"]');
            await selectAllCheckbox.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('03-after-select-all');

            // Check if "semua data dipilih" appears
            const allSelectedText = await this.page.locator('text=/data dipilih/i').count();
            console.log('After select all - selected text count:', allSelectedText);
            this.assert(allSelectedText > 0, 'Should show selected count after select all');

            // Click again to deselect
            await selectAllCheckbox.click();
            await this.page.waitForTimeout(500);

            // Badge should disappear
            const badgeAfterDeselect = await this.page.locator('text=/data dipilih/i').count();
            console.log('After deselect - selected text count:', badgeAfterDeselect);
            this.assert(badgeAfterDeselect === 0, 'Badge should disappear after deselect all');

            console.log('TEST 04: PASSED');
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

new InsentifTest().runTests();