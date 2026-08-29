const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class InsentifCheckboxTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'InsentifCheckbox');
        this.screenshotCount = 0;
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

    async login(email, password) {
        await this.page.goto(`${BASE}/login-perusahaan`);
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
        console.log('Insentif Checkbox Test');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false, slowMo: 350 });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            await this.page.goto(`${BASE}/operator-perusahaan/insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('01-insentif-page');

            // ============================================================
            // TEST 01: Initial state - no checkbox selected
            // ============================================================
            console.log('\nTEST 01: Initial state');
            console.log('========================================');

            const initialSelected = await this.page.locator('text=/data dipilih/i').count();
            console.log('Initial selected badge:', initialSelected);
            console.log('TEST 01:', initialSelected === 0 ? 'PASSED' : 'FAILED');

            // ============================================================
            // TEST 02: Click first row checkbox
            // ============================================================
            console.log('\nTEST 02: Click first row checkbox');
            console.log('========================================');

            // Get first checkbox in tbody
            const firstCheckbox = this.page.locator('tbody input[type="checkbox"]').first();
            console.log('First checkbox found:', await firstCheckbox.count() > 0);

            // Check if checkbox is initially unchecked
            const initiallyChecked = await firstCheckbox.isChecked();
            console.log('Initially checked:', initiallyChecked);

            // Click the checkbox
            await firstCheckbox.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('02-after-click-checkbox');

            // Check if checkbox is now checked
            const afterClickChecked = await firstCheckbox.isChecked();
            console.log('After click checked:', afterClickChecked);

            // Check if selected badge appears
            const selectedBadge = await this.page.locator('text=/1 data dipilih/i').count();
            console.log('Selected badge visible:', selectedBadge > 0);

            // Check if bulk buttons appear
            const bulkAktifkan = await this.page.locator('button:has-text("Aktifkan")').count();
            const bulkNonaktifkan = await this.page.locator('button:has-text("Nonaktifkan")').count();
            const bulkHapus = await this.page.locator('button:has-text("Hapus")').count();
            console.log('Bulk buttons - Aktifkan:', bulkAktifkan, 'Nonaktifkan:', bulkNonaktifkan, 'Hapus:', bulkHapus);

            const test02Pass = afterClickChecked === true && selectedBadge > 0;
            console.log('TEST 02:', test02Pass ? 'PASSED' : 'FAILED');

            // ============================================================
            // TEST 03: Click same checkbox again to deselect
            // ============================================================
            console.log('\nTEST 03: Click same checkbox to deselect');
            console.log('========================================');

            await firstCheckbox.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('03-after-deselect');

            const afterDeselectChecked = await firstCheckbox.isChecked();
            console.log('After deselect checked:', afterDeselectChecked);

            const afterDeselectBadge = await this.page.locator('text=/data dipilih/i').count();
            console.log('After deselect badge count:', afterDeselectBadge);

            const test03Pass = afterDeselectChecked === false && afterDeselectBadge === 0;
            console.log('TEST 03:', test03Pass ? 'PASSED' : 'FAILED');

            // ============================================================
            // TEST 04: Select all checkboxes
            // ============================================================
            console.log('\nTEST 04: Select all checkboxes');
            console.log('========================================');

            const selectAllCheckbox = this.page.locator('thead input[type="checkbox"]').first();
            await selectAllCheckbox.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('04-after-select-all');

            // Count how many rows are checked
            const checkedBoxes = await this.page.locator('tbody input[type="checkbox"]:checked').count();
            console.log('Checked boxes count:', checkedBoxes);

            // Check if badge shows correct count
            const allSelectedBadge = await this.page.locator('text=/data dipilih/i').textContent().catch(() => '');
            console.log('Selected badge text:', allSelectedBadge);

            console.log('TEST 04:', checkedBoxes > 0 ? 'PASSED' : 'FAILED');

            // ============================================================
            // TEST 05: Deselect all
            // ============================================================
            console.log('\nTEST 05: Deselect all');
            console.log('========================================');

            await selectAllCheckbox.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('05-after-deselect-all');

            const deselectedBoxes = await this.page.locator('tbody input[type="checkbox"]:checked').count();
            console.log('After deselect all - checked boxes:', deselectedBoxes);

            const deselectBadge = await this.page.locator('text=/data dipilih/i').count();
            console.log('After deselect badge:', deselectBadge);

            const test05Pass = deselectedBoxes === 0 && deselectBadge === 0;
            console.log('TEST 05:', test05Pass ? 'PASSED' : 'FAILED');

            // ============================================================
            // TEST 06: Import/Export buttons visible
            // ============================================================
            console.log('\nTEST 06: Import/Export buttons');
            console.log('========================================');

            const importBtn = await this.page.locator('button:has-text("Import")').count();
            const exportBtn = await this.page.locator('button:has-text("Export")').count();
            console.log('Import button:', importBtn > 0);
            console.log('Export button:', exportBtn > 0);

            console.log('TEST 06:', (importBtn > 0 && exportBtn > 0) ? 'PASSED' : 'FAILED');

            console.log('\n========================================');
            console.log('ALL TESTS COMPLETED');
            console.log('========================================\n');

        } catch (error) {
            console.error('[FATAL ERROR]', error.message);
        } finally {
            if (this.browser) await this.browser.close();
        }
    }
}

new InsentifCheckboxTest().runTests();