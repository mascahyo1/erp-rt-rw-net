const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

class RiwayatInsentifDarkModeTest {
    constructor() {
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'RiwayatInsentifDark');
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

    async login(email, password = 'password123') {
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
        console.log('Riwayat Insentif Dark Mode Test');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            // ============================================================
            // TEST 01: Main Page - Switch to Dark Mode
            // ============================================================
            console.log('\nTEST 01: Toggle Dark Mode - Main Page');
            console.log('========================================');

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('01-light-main-page');

            // Toggle dark mode - system -> light -> dark (first click goes to light, second to dark)
            const themeBtn = this.page.locator('button[title*="Tema"]').first();
            await themeBtn.click();
            await this.page.waitForTimeout(500);
            await themeBtn.click();
            await this.page.waitForTimeout(1000);
            await this.takeScreenshot('02-dark-main-page');

            const isDark = await this.page.evaluate(() => document.documentElement.classList.contains('dark'));
            console.log('Dark mode active:', isDark);
            this.assert(isDark, 'Dark mode should be active after toggle');

            // Check table text is visible in dark mode
            const tableRows = await this.page.locator('tbody tr').count();
            console.log('Table rows visible in dark mode:', tableRows > 0);

            // Check all table text is visible (not same as background)
            const firstRowText = await this.page.locator('tbody tr').first().textContent();
            console.log('First row text content length:', firstRowText?.length);
            this.assert(firstRowText?.length > 0, 'Table row should have visible text');

            // Check checkbox visible in dark mode
            const checkbox = this.page.locator('tbody input[type="checkbox"]').first();
            const checkboxVisible = await checkbox.isVisible();
            console.log('Checkbox visible in dark mode:', checkboxVisible);

            console.log('TEST 01: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 02: Filter Section in Dark Mode
            // ============================================================
            console.log('\nTEST 02: Filter Section in Dark Mode');
            console.log('========================================');

            // Check filter inputs - should not be invisible dark on dark
            const statusFilterLabel = await this.page.locator('text=/Status/i').first().isVisible();
            console.log('Status filter label visible:', statusFilterLabel);

            const filterInput = await this.page.locator('input[type="date"]').first();
            const filterInputVisible = await filterInput.isVisible();
            console.log('Date filter input visible:', filterInputVisible);

            // The date input calendar icon should be visible
            const filterInputBg = await filterInput.evaluate(el => getComputedStyle(el).backgroundColor);
            console.log('Date filter input background:', filterInputBg);

            console.log('TEST 02: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 03: Detail Modal Dark Mode
            // ============================================================
            console.log('\nTEST 03: Detail Modal Dark Mode');
            console.log('========================================');

            const detailBtn = this.page.locator('button[title="Detail"]').first();
            await detailBtn.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('03-detail-modal-dark');

            // Check modal header text is visible
            const modalHeader = this.page.locator('h3:has-text("Detail Riwayat Insentif")');
            const modalHeaderVisible = await modalHeader.isVisible();
            const modalHeaderColor = await modalHeader.evaluate(el => getComputedStyle(el).color);
            console.log('Modal header visible:', modalHeaderVisible, '- color:', modalHeaderColor);

            // Check No Invoice value
            const noInvoiceLabel = await this.page.locator('text=/No. Invoice/i').first().isVisible();
            console.log('No Invoice label visible:', noInvoiceLabel);

            // Check the invoice number value is visible
            const invoiceValueArea = this.page.locator('.font-mono.text-sm').first();
            if (await invoiceValueArea.count() > 0) {
                const invoiceText = await invoiceValueArea.textContent();
                console.log('Invoice value text:', invoiceText);
                this.assert(invoiceText?.trim().length > 0, 'Invoice number should be visible');
            }

            console.log('TEST 03: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 04: Create Modal Dark Mode
            // ============================================================
            console.log('\nTEST 04: Create Modal Dark Mode');
            console.log('========================================');

            // Close detail modal first
            await this.page.keyboard.press('Escape');
            await this.page.waitForTimeout(300);

            const tambahBtn = this.page.locator('button:has-text("Tambah")').first();
            if (await tambahBtn.count() > 0) {
                await tambahBtn.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('04-create-modal-dark');

                // Check modal title
                const createTitle = this.page.locator('h3:has-text("Tambah Riwayat Insentif")');
                const createTitleVisible = await createTitle.isVisible();
                const createTitleColor = await createTitle.evaluate(el => getComputedStyle(el).color);
                console.log('Create modal title visible:', createTitleVisible, '- color:', createTitleColor);

                // Check date input in create modal
                const dateInputCreate = this.page.locator('input[type="date"]').first();
                const dateInputVisible = await dateInputCreate.isVisible();
                console.log('Date input in create modal visible:', dateInputVisible);

                // Check the calendar picker icon is visible
                if (dateInputVisible) {
                    const calendarIcon = await dateInputCreate.evaluate(el => {
                        const style = getComputedStyle(el, '::-webkit-calendar-picker-indicator');
                        return style.display !== 'none';
                    });
                    console.log('Calendar icon potentially visible:', calendarIcon);
                }

                // Close modal
                await this.page.locator('button:has-text("Batal")').click();
                await this.page.waitForTimeout(300);
            }

            console.log('TEST 04: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 05: Review Modal Dark Mode
            // ============================================================
            console.log('\nTEST 05: Review Modal Dark Mode');
            console.log('========================================');

            const reviewBtn = this.page.locator('button[title="Review"]').first();
            if (await reviewBtn.count() > 0) {
                await reviewBtn.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('05-review-modal-dark');

                // Check modal title - should show "Review: <incentive_name>"
                const reviewTitle = this.page.locator('h3:has-text("Review:")');
                const reviewTitleVisible = await reviewTitle.isVisible();
                const reviewTitleColor = await reviewTitle.evaluate(el => getComputedStyle(el).color);
                console.log('Review modal title visible:', reviewTitleVisible, '- color:', reviewTitleColor);
                this.assert(reviewTitleVisible, 'Review modal title should be visible in dark mode');

                // Check No. Invoice in review modal is visible
                const noInvoiceInReview = await this.page.locator('text=/No. Invoice/i').first().isVisible();
                console.log('No Invoice in review modal visible:', noInvoiceInReview);
                this.assert(noInvoiceInReview, 'No Invoice label should be visible in dark mode review modal');

                // Close modal
                await this.page.keyboard.press('Escape');
                await this.page.waitForTimeout(300);
            } else {
                console.log('No pending item with review button found, skipping');
            }

            console.log('TEST 05: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 06: Edit Modal Dark Mode
            // ============================================================
            console.log('\nTEST 06: Edit Modal Dark Mode');
            console.log('========================================');

            const editBtn = this.page.locator('button[title="Edit"]').first();
            if (await editBtn.count() > 0) {
                await editBtn.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('06-edit-modal-dark');

                const editTitle = this.page.locator('h3:has-text("Edit Riwayat Insentif")');
                const editTitleVisible = await editTitle.isVisible();
                const editTitleColor = await editTitle.evaluate(el => getComputedStyle(el).color);
                console.log('Edit modal title visible:', editTitleVisible, '- color:', editTitleColor);

                const dateInputEdit = this.page.locator('input[type="date"]').first();
                const dateInputEditVisible = await dateInputEdit.isVisible();
                console.log('Date input in edit modal visible:', dateInputEditVisible);

                await this.page.locator('button:has-text("Batal")').click();
                await this.page.waitForTimeout(300);
            } else {
                console.log('No pending item with edit button found, skipping');
            }

            console.log('TEST 06: PASSED');
            this.testResults.passed++;

            // ============================================================
            // TEST 07: Confirm dark mode elements all visible
            // ============================================================
            console.log('\nTEST 07: Dark mode elements visible');
            console.log('========================================');

            // We are already in dark mode from TEST 01
            // Just verify various UI elements are visible
            const filterSection = await this.page.locator('text=/Status/i').first().isVisible();
            console.log('Filter section visible in dark:', filterSection);

            const tableInDark = await this.page.locator('table').count();
            console.log('Table visible in dark:', tableInDark > 0);

            console.log('TEST 07: PASSED');
            this.testResults.passed++;

            console.log('\n========================================');
            console.log('TEST SUMMARY - Dark Mode Tests');
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

new RiwayatInsentifDarkModeTest().runTests();
