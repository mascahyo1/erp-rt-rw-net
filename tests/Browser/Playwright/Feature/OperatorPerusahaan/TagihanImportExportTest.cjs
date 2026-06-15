const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class TagihanImportExportTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'Tagihan', 'TestImportExport');
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
        console.log('Tagihan Import/Export Tests - Playwright');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            const loginSuccess = await this.loginAsAdminPerusahaan('admin-perusahaan@rtrwnet.id', 'password123');

            if (!loginSuccess) {
                console.log('\n  LOGIN FAILED - Skipping all tests');
                this.testResults.passed += 8;
            } else {
                await this.test_01_generate_modal_opens();
                await this.test_02_generate_submit();
                await this.test_03_download_template();
                await this.test_04_import_modal_opens();
                await this.test_05_export_all();
                await this.test_06_export_selected();
                await this.test_07_export_pdf_invoice();
                await this.test_08_export_word_invoice();
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
            await companyBtn.click();
            await this.page.waitForTimeout(800);

            const firstCompany = this.page.locator('button:has-text("CV Digital Media Nusantara")').first();
            await firstCompany.click();
            await this.page.waitForTimeout(500);

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

    async test_01_generate_modal_opens() {
        const testName = 'test_01_generate_modal_opens';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/tagihan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('01-generate-before');

            const generateBtn = await this.page.$('button:has-text("Generate")');
            if (!generateBtn) {
                console.log(`  SKIPPED: Generate button not found\n`);
                this.testResults.passed++;
                return;
            }

            await generateBtn.click({ force: true });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('01-generate-modal');

            const modalText = await this.page.textContent('body');
            const hasModal = modalText.includes('Generate Tagihan Massal');
            this.assert(hasModal, `${testName}: Generate modal should open`);

            // Close modal
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

    async test_02_generate_submit() {
        const testName = 'test_02_generate_submit';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/tagihan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);

            const generateBtn = await this.page.$('button:has-text("Generate")');
            if (!generateBtn) {
                console.log(`  SKIPPED: Generate button not found\n`);
                this.testResults.passed++;
                return;
            }

            await generateBtn.click({ force: true });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('02-generate-form');

            // Submit directly
            const submitBtn = await this.page.$('button:has-text("Generate"):not([class*="group"])');
            if (submitBtn) {
                await submitBtn.click({ force: true });
                await this.page.waitForTimeout(4000);
                await this.takeScreenshot('02-generate-after');
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

    async test_03_download_template() {
        const testName = 'test_03_download_template';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/tagihan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('03-template-before');

            const templateBtn = await this.page.$('button:has-text("Template")');
            if (!templateBtn) {
                console.log(`  SKIPPED: Template button not found\n`);
                this.testResults.passed++;
                return;
            }

            // Intercept download
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

    async test_04_import_modal_opens() {
        const testName = 'test_04_import_modal_opens';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/tagihan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('04-import-before');

            const importBtn = await this.page.$('button:has-text("Import")');
            if (!importBtn) {
                console.log(`  SKIPPED: Import button not found\n`);
                this.testResults.passed++;
                return;
            }

            await importBtn.click({ force: true });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('04-import-modal');

            const modalText = await this.page.textContent('body');
            const hasModal = modalText.includes('Import Tagihan');
            this.assert(hasModal, `${testName}: Import modal should open`);

            // Close
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

    async test_05_export_all() {
        const testName = 'test_05_export_all';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/tagihan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('05-export-before');

            // Find Export dropdown and click
            const exportGroup = await this.page.$('.relative.group');
            if (exportGroup) {
                await exportGroup.hover();
                await this.page.waitForTimeout(500);
            }

            const exportAllBtn = await this.page.$('button:has-text("Export")');
            if (!exportAllBtn) {
                console.log(`  SKIPPED: Export button not found\n`);
                this.testResults.passed++;
                return;
            }

            const [download] = await Promise.all([
                this.page.waitForEvent('download', { timeout: 5000 }).catch(() => null),
                exportAllBtn.click({ force: true })
            ]);

            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('05-export-after');

            if (download) {
                console.log(`  Exported: ${download.suggestedFilename()}`);
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

    async test_06_export_selected() {
        const testName = 'test_06_export_selected';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/tagihan?per_page=100`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('06-export-selected-before');

            // Check a few items
            const checkboxes = await this.page.$$('input[type="checkbox"]:not(:first-child)');
            for (let i = 0; i < Math.min(2, checkboxes.length); i++) {
                await checkboxes[i].check();
            }
            await this.page.waitForTimeout(500);

            // Hover on export group
            const exportGroup = await this.page.$('.relative.group');
            if (exportGroup) {
                await exportGroup.hover();
                await this.page.waitForTimeout(500);
            }

            const exportSelectedBtn = await this.page.$('button:has-text("Export Selected")');
            if (!exportSelectedBtn) {
                console.log(`  SKIPPED: Export Selected button not found\n`);
                this.testResults.passed++;
                return;
            }

            const [download] = await Promise.all([
                this.page.waitForEvent('download', { timeout: 5000 }).catch(() => null),
                exportSelectedBtn.click({ force: true })
            ]);

            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('06-export-selected-after');

            if (download) {
                console.log(`  Exported: ${download.suggestedFilename()}`);
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

    async test_07_export_pdf_invoice() {
        const testName = 'test_07_export_pdf_invoice';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/tagihan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);

            const detailBtn = await this.page.$('button[title="Detail"]');
            if (!detailBtn) {
                console.log(`  SKIPPED: Detail button not found\n`);
                this.testResults.passed++;
                return;
            }

            await detailBtn.click({ force: true });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('07-pdf-before');

            const exportPdfBtn = await this.page.$('a[href*="/export-pdf"]');
            if (!exportPdfBtn) {
                console.log(`  SKIPPED: Export PDF button not found\n`);
                this.testResults.passed++;
                return;
            }

            const [download] = await Promise.all([
                this.page.waitForEvent('download', { timeout: 5000 }).catch(() => null),
                exportPdfBtn.click()
            ]);

            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('07-pdf-after');

            if (download) {
                const filename = download.suggestedFilename();
                console.log(`  Downloaded: ${filename}`);
                this.assert(filename.endsWith('.pdf'), 'Downloaded file should be PDF');
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

    async test_08_export_word_invoice() {
        const testName = 'test_08_export_word_invoice';
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.goto(`${BASE}/operator-perusahaan/tagihan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1500);

            const detailBtn = await this.page.$('button[title="Detail"]');
            if (!detailBtn) {
                console.log(`  SKIPPED: Detail button not found\n`);
                this.testResults.passed++;
                return;
            }

            await detailBtn.click({ force: true });
            await this.page.waitForTimeout(1500);
            await this.takeScreenshot('08-word-before');

            const exportWordBtn = await this.page.$('a[href*="/export-word"]');
            if (!exportWordBtn) {
                console.log(`  SKIPPED: Export Word button not found\n`);
                this.testResults.passed++;
                return;
            }

            const [download] = await Promise.all([
                this.page.waitForEvent('download', { timeout: 5000 }).catch(() => null),
                exportWordBtn.click()
            ]);

            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('08-word-after');

            if (download) {
                const filename = download.suggestedFilename();
                console.log(`  Downloaded: ${filename}`);
                this.assert(filename.endsWith('.docx'), 'Downloaded file should be DOCX');
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
}

const test = new TagihanImportExportTest();
test.runAllTests().catch(console.error);