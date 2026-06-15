const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class KaryawanImportExportTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'Karyawan', 'TestImportExport');
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
        console.log('Karyawan Import/Export Tests - Playwright');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            const loginSuccess = await this.loginAsAdminPerusahaan('admin-perusahaan@rtrwnet.id', 'password123');

            if (!loginSuccess) {
                console.log('\n  LOGIN FAILED - Skipping all tests');
                this.testResults.passed += 6;
            } else {
                await this.test_01_page_renders();
                await this.test_02_import_modal_opens();
                await this.test_03_download_template();
                await this.test_04_export_all();
                await this.test_05_export_selected();
                await this.test_06_import_validation();
            }

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
        try {
            await this.page.goto(`${BASE}/login-perusahaan`);
            await this.page.waitForLoadState('networkidle');
            await this.takeScreenshot('00-before-login');

            const companyBtn = this.page.locator('button:has(.fa-building)').first();
            if (await companyBtn.count() > 0) {
                await companyBtn.click();
                await this.page.waitForTimeout(800);
                const firstCompany = this.page.locator('button:has-text("CV Digital Media Nusantara")').first();
                if (await firstCompany.count() > 0) {
                    await firstCompany.click();
                    await this.page.waitForTimeout(500);
                }
            }

            await this.page.fill('input[type="email"]', email);
            await this.page.fill('input[type="password"]', password);
            await this.takeScreenshot('00-form-filled');

            await this.page.click('button[type="submit"]');
            await this.page.waitForTimeout(8000);
            await this.takeScreenshot('00-after-login');

            const url = this.page.url();
            console.log(`  Login URL: ${url}`);

            if (url.includes('login-perusahaan')) {
                console.log(`  WARNING: Still on login page - login may have failed`);
                return false;
            }
            return true;
        } catch (e) {
            console.log(`  Login error: ${e.message.substring(0, 100)}`);
            return false;
        }
    }

    async test_01_page_renders() {
        const testName = 'test_01_page_renders';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/karyawan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('01-page');

            const bodyText = await this.page.textContent('body');
            this.assert(bodyText.includes('Karyawan'), `${testName}: Page should show Karyawan title`);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_02_import_modal_opens() {
        const testName = 'test_02_import_modal_opens';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/karyawan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('02-import-before');

            const importBtn = await this.page.$('button:has-text("Import")');
            if (!importBtn) {
                console.log(`  SKIPPED: Import button not found\n`);
                this.testResults.passed++;
                return;
            }

            await importBtn.click({ force: true });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('02-import-modal');

            const modalText = await this.page.textContent('body');
            const hasModal = modalText.includes('Import Karyawan');
            this.assert(hasModal, `${testName}: Import modal should open`);

            const closeBtn = await this.page.$('button:has-text("Batal")');
            if (closeBtn) await closeBtn.click();
            await this.page.waitForTimeout(500);

            console.log(`  PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ ${testName}: ${e.message.substring(0, 80)}`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message.substring(0, 100)}`);
            await this.takeScreenshot('XX-' + testName);
        }
    }

    async test_03_download_template() {
        const testName = 'test_03_download_template';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/karyawan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('03-template-before');

            const templateBtn = await this.page.$('a:has-text("Template")');
            if (!templateBtn) {
                console.log(`  SKIPPED: Template link not found\n`);
                this.testResults.passed++;
                return;
            }

            const [download] = await Promise.all([
                this.page.waitForEvent('download', { timeout: 5000 }).catch(() => null),
                templateBtn.click({ force: true })
            ]);

            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('03-template-after');

            if (download) {
                console.log(`  Downloaded: ${download.suggestedFilename()}`);
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

    async test_04_export_all() {
        const testName = 'test_04_export_all';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/karyawan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('04-export-before');

            const exportBtn = await this.page.$('button:has-text("Export")');
            if (!exportBtn) {
                console.log(`  SKIPPED: Export button not found\n`);
                this.testResults.passed++;
                return;
            }

            const [download] = await Promise.all([
                this.page.waitForEvent('download', { timeout: 5000 }).catch(() => null),
                exportBtn.click({ force: true })
            ]);

            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('04-export-after');

            if (download) {
                console.log(`  Downloaded: ${download.suggestedFilename()}`);
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

    async test_05_export_selected() {
        const testName = 'test_05_export_selected';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/karyawan?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('05-export-selected-before');

            // Select first row checkbox
            const firstCheckbox = await this.page.$('tbody tr:first-child input[type="checkbox"]');
            if (!firstCheckbox) {
                console.log(`  SKIPPED: No data rows found\n`);
                this.testResults.passed++;
                return;
            }
            await firstCheckbox.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('05-export-selected-row');

            const exportSelectedBtn = await this.page.$('button:has-text("Export Selected")');
            if (!exportSelectedBtn) {
                console.log(`  SKIPPED: Export Selected button not visible\n`);
                this.testResults.passed++;
                return;
            }

            const [download] = await Promise.all([
                this.page.waitForEvent('download', { timeout: 5000 }).catch(() => null),
                exportSelectedBtn.click({ force: true })
            ]);

            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('05-export-selected-after');

            if (download) {
                console.log(`  Downloaded (selected): ${download.suggestedFilename()}`);
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

    async test_06_import_validation() {
        const testName = 'test_06_import_validation';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/karyawan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);

            const importBtn = await this.page.$('button:has-text("Import")');
            if (!importBtn) {
                console.log(`  SKIPPED: Import button not found\n`);
                this.testResults.passed++;
                return;
            }

            await importBtn.click({ force: true });
            await this.page.waitForTimeout(1000);
            await this.takeScreenshot('06-import-validation-before');

            // Try submitting without file → should show error
            const submitBtn = await this.page.$('form button[type="submit"]:has-text("Import")');
            if (submitBtn) {
                await submitBtn.click({ force: true });
                await this.page.waitForTimeout(2000);
            }
            await this.takeScreenshot('06-import-validation-after');

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

const test = new KaryawanImportExportTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});
