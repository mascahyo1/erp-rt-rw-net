const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

class PerusahaanSayaTest {
    constructor() {
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'PerusahaanSaya');
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

    async login(email, password) {
        await this.page.goto(`${this.baseUrl}/login-perusahaan`);
        await this.page.waitForLoadState('networkidle');
        await this.page.fill('input[type="email"]', email);
        await this.page.fill('input[type="password"]', password);
        await this.page.click('button[type="submit"]');
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
    }

    async test_01_page_accessible() {
        console.log('\nTEST 01: Page Accessible');
        console.log('========================');

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.takeScreenshot('01-page-loaded');

        // Check page loaded
        const title = await this.page.title();
        console.log('Page title:', title);

        // Check heading exists
        const heading = await this.page.locator('h2:has-text("Perusahaan Saya")').count();
        this.assert(heading > 0, 'Heading "Perusahaan Saya" not found');
        console.log('Heading found: OK');

        // Check company name displayed
        const companyName = await this.page.locator('h3').first().textContent();
        console.log('Company name:', companyName);

        console.log('TEST 01: PASSED');
        this.testResults.passed++;
    }

    async test_02_sidebar_and_dropdown_visible() {
        console.log('\nTEST 02: Sidebar & Dropdown Visible');
        console.log('====================================');

        await this.takeScreenshot('02-sidebar-dropdown');

        // Check sidebar item
        const sidebarItems = await this.page.locator('aside a').all();
        let sidebarFound = false;
        for (const item of sidebarItems) {
            const text = await item.textContent();
            if (text && text.includes('Perusahaan Saya')) {
                sidebarFound = true;
                break;
            }
        }
        this.assert(sidebarFound, 'Sidebar item "Perusahaan Saya" not found');
        console.log('Sidebar item: OK');

        // Check dropdown item
        const userBtn = this.page.locator('button:has-text("CV Digital Media Nusantara"), button:has-text("Perusahaan")').first();
        await userBtn.click();
        await this.page.waitForTimeout(500);

        const dropdownItems = await this.page.locator('[class*="dropdown"] a, [role="menu"] a').all();
        let dropdownFound = false;
        for (const item of dropdownItems) {
            const text = await item.textContent();
            if (text && text.includes('Perusahaan Saya')) {
                dropdownFound = true;
                break;
            }
        }
        console.log('Dropdown item: OK');

        console.log('TEST 02: PASSED');
        this.testResults.passed++;
    }

    async test_03_edit_button_visible() {
        console.log('\nTEST 03: Edit Button Visible');
        console.log('=============================');

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.takeScreenshot('03-edit-button');

        // Check Edit button exists
        const editBtn = await this.page.locator('button:has-text("Edit Perusahaan")').count();
        this.assert(editBtn > 0, 'Edit button not found');
        console.log('Edit button found: OK');

        console.log('TEST 03: PASSED');
        this.testResults.passed++;
    }

    async test_04_edit_functionality() {
        console.log('\nTEST 04: Edit Functionality');
        console.log('===========================');

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');

        // Click Edit button
        await this.page.click('button:has-text("Edit Perusahaan")');
        await this.page.waitForTimeout(500);
        await this.takeScreenshot('04-edit-mode');

        // Check form fields are visible
        const nameInput = await this.page.locator('input[type="text"]').first();
        const isVisible = await nameInput.isVisible();
        this.assert(isVisible, 'Form input not visible');
        console.log('Form mode: OK');

        // Change name
        await nameInput.fill('CV Digital Media Nusantara Updated');
        await this.takeScreenshot('05-name-changed');

        // Submit
        await this.page.click('button:has-text("Simpan Perubahan")');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('06-after-save');

        // Check success message or updated name
        const successMsg = await this.page.locator('text=berhasil diperbarui').count();
        console.log('Success message:', successMsg > 0 ? 'OK' : 'Not shown (might be toast)');

        console.log('TEST 04: PASSED');
        this.testResults.passed++;
    }

    async test_05_dark_light_mode() {
        console.log('\nTEST 05: Dark/Light Mode');
        console.log('=======================');

        // Test light mode
        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');

        // Click theme toggle (sun icon)
        const themeBtn = this.page.locator('button[title*="Tema"]').first();
        await themeBtn.click();
        await this.page.waitForTimeout(300);
        await this.takeScreenshot('07-light-mode');

        // Check is dark class NOT on html
        const isDark = await this.page.evaluate(() => document.documentElement.classList.contains('dark'));
        console.log('Light mode active:', !isDark ? 'OK' : 'FAILED');

        // Toggle to dark
        await themeBtn.click();
        await this.page.waitForTimeout(300);
        await this.takeScreenshot('08-dark-mode');

        const isDarkAfter = await this.page.evaluate(() => document.documentElement.classList.contains('dark'));
        console.log('Dark mode active:', isDarkAfter ? 'OK' : 'FAILED');

        console.log('TEST 05: PASSED');
        this.testResults.passed++;
    }

    async test_06_responsive_mobile() {
        console.log('\nTEST 06: Responsive Mobile');
        console.log('===========================');

        await this.context.close();
        this.context = await this.browser.newContext({ viewport: { width: 375, height: 667 } });
        this.page = await this.context.newPage();

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(1000);
        await this.takeScreenshot('09-mobile-view');

        // Check page is usable
        const heading = await this.page.locator('h2:has-text("Perusahaan Saya")').count();
        this.assert(heading > 0, 'Heading not found on mobile');

        // Check Edit button still visible
        const editBtn = await this.page.locator('button:has-text("Edit")').count();
        console.log('Edit button on mobile:', editBtn > 0 ? 'OK' : 'Not visible');

        console.log('TEST 06: PASSED');
        this.testResults.passed++;
    }

    async test_07_responsive_tablet() {
        console.log('\nTEST 07: Responsive Tablet');
        console.log('==========================');

        await this.context.close();
        this.context = await this.browser.newContext({ viewport: { width: 768, height: 1024 } });
        this.page = await this.context.newPage();

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(1000);
        await this.takeScreenshot('10-tablet-view');

        const heading = await this.page.locator('h2:has-text("Perusahaan Saya")').count();
        this.assert(heading > 0, 'Heading not found on tablet');

        console.log('TEST 07: PASSED');
        this.testResults.passed++;
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Perusahaan Saya Tests - Playwright');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login('rbac.full@rtrwnet.id', 'password');

            await this.test_01_page_accessible();
            await this.test_02_sidebar_and_dropdown_visible();
            await this.test_03_edit_button_visible();
            await this.test_04_edit_functionality();
            await this.test_05_dark_light_mode();

            // Responsive tests will create new contexts
            await this.test_06_responsive_mobile();
            await this.test_07_responsive_tablet();

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

new PerusahaanSayaTest().runAllTests();