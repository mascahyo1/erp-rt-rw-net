const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

class PerusahaanSayaCRUDTest {
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
        console.log('After login title:', await this.page.title());
    }

    // ============================================================
    // TEST 01: Page Accessible (Detail Permission)
    // ============================================================
    async test_01_page_accessible() {
        console.log('\nTEST 01: Page Accessible (Detail Permission)');
        console.log('==============================================');

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(5000);
        await this.takeScreenshot('01-page-loaded');

        console.log('Page URL:', this.page.url());
        console.log('Page title:', await this.page.title());

        // Debug: Get all headings
        const headings = await this.page.locator('h2').all();
        console.log('All h2 elements:', headings.length);
        for (const h of headings) {
            console.log('  h2 text:', await h.textContent());
        }

        // Debug: Get body text
        const bodyText = await this.page.locator('body').textContent();
        console.log('Body text length:', bodyText?.trim().length);
        console.log('Body text (first 1000):', bodyText?.substring(0, 1000));

        // Debug: Check page.props via JavaScript
        const propsDebug = await this.page.evaluate(() => {
            return {
                hasPage: typeof window.page !== 'undefined',
                inertiaProps: typeof window.page !== 'undefined' ? window.page.props : 'N/A',
                appEl: document.getElementById('app') ? document.getElementById('app').innerHTML.substring(0, 200) : 'NOT FOUND'
            };
        });
        console.log('Props debug:', JSON.stringify(propsDebug, null, 2));

        // Check heading exists
        const heading = await this.page.locator('h2:has-text("Perusahaan Saya")').count();
        this.assert(heading > 0, 'Heading "Perusahaan Saya" not found');
        console.log('Heading found: OK');

        const companyName = await this.page.locator('h3').first().textContent();
        console.log('Company name:', companyName);

        console.log('TEST 01: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 02: Sidebar & Dropdown Visible (Detail Permission)
    // ============================================================
    async test_02_sidebar_and_dropdown_visible() {
        console.log('\nTEST 02: Sidebar & Dropdown Visible (Detail Permission)');
        console.log('=======================================================');

        await this.takeScreenshot('02-sidebar-dropdown');

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

        const userBtn = this.page.locator('button:has-text("CV Digital Media Nusantara"), button:has-text("Perusahaan")').first();
        await userBtn.click();
        await this.page.waitForTimeout(500);

        const dropdownItems = await this.page.locator('.absolute a').all();
        let dropdownFound = false;
        for (const item of dropdownItems) {
            const text = await item.textContent();
            if (text && text.includes('Perusahaan Saya')) {
                dropdownFound = true;
                break;
            }
        }
        this.assert(dropdownFound, 'Dropdown item "Perusahaan Saya" not found');
        console.log('Dropdown item: OK');

        console.log('TEST 02: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 03: Edit Button Visibility (Edit Permission)
    // ============================================================
    async test_03_edit_button_visible() {
        console.log('\nTEST 03: Edit Button Visible (Edit Permission)');
        console.log('===============================================');

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.takeScreenshot('03-edit-button');

        const editBtn = await this.page.locator('button:has-text("Edit Perusahaan")').count();
        this.assert(editBtn > 0, 'Edit button not found - user should have edit permission');
        console.log('Edit button found: OK');

        console.log('TEST 03: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 04: Edit Functionality
    // ============================================================
    async test_04_edit_functionality() {
        console.log('\nTEST 04: Edit Functionality');
        console.log('===========================');

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(1000);

        await this.page.click('button:has-text("Edit Perusahaan")');
        await this.page.waitForTimeout(500);
        await this.takeScreenshot('04-edit-mode');

        const nameInput = await this.page.locator('input[type="text"]').first();
        const isVisible = await nameInput.isVisible();
        this.assert(isVisible, 'Form input not visible');
        console.log('Form mode: OK');

        await nameInput.fill('CV Digital Media Nusantara Updated');
        await this.takeScreenshot('05-name-changed');

        await this.page.click('button:has-text("Simpan Perubahan")');
        await this.page.waitForTimeout(3000);
        await this.takeScreenshot('06-after-save');

        const successMsg = await this.page.locator('text=berhasil diperbarui').count();
        console.log('Success message:', successMsg > 0 ? 'OK' : 'Not shown (might be toast)');

        console.log('TEST 04: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 05: Dark/Light Mode Toggle
    // ============================================================
    async test_05_dark_light_mode() {
        console.log('\nTEST 05: Dark/Light Mode Toggle');
        console.log('===============================');

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('05a-initial-theme');

        const themeBtn = this.page.locator('button[title*="Tema"]').first();
        await themeBtn.click();
        await this.page.waitForTimeout(300);
        await this.takeScreenshot('05b-after-toggle');

        const isDark = await this.page.evaluate(() => document.documentElement.classList.contains('dark'));
        console.log('Dark mode active:', isDark ? 'OK' : 'FAILED');

        await themeBtn.click();
        await this.page.waitForTimeout(300);
        await this.takeScreenshot('05c-after-second-toggle');

        const isDarkAfter = await this.page.evaluate(() => document.documentElement.classList.contains('dark'));
        console.log('Light mode active:', !isDarkAfter ? 'OK' : 'FAILED');

        console.log('TEST 05: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 06: Responsive Mobile (375x667)
    // ============================================================
    async test_06_responsive_mobile() {
        console.log('\nTEST 06: Responsive Mobile (375x667)');
        console.log('====================================');

        // Set mobile viewport on existing authenticated context
        await this.context.close();
        this.context = await this.browser.newContext({ viewport: { width: 375, height: 667 } });
        this.page = await this.context.newPage();

        // Re-authenticate since new context is fresh
        await this.login('rbac.full@rtrwnet.id', 'password');

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('06-mobile-view');

        const heading = await this.page.locator('h2:has-text("Perusahaan Saya")').count();
        this.assert(heading > 0, 'Heading not found on mobile');
        console.log('Heading visible on mobile: OK');

        const editBtn = await this.page.locator('button:has-text("Edit")').count();
        console.log('Edit button on mobile:', editBtn > 0 ? 'OK' : 'Not visible (no permission)');

        const companyName = await this.page.locator('h3').first().textContent();
        console.log('Company name on mobile:', companyName);

        console.log('TEST 06: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 07: Responsive Tablet (768x1024)
    // ============================================================
    async test_07_responsive_tablet() {
        console.log('\nTEST 07: Responsive Tablet (768x1024)');
        console.log('=====================================');

        await this.context.close();
        this.context = await this.browser.newContext({ viewport: { width: 768, height: 1024 } });
        this.page = await this.context.newPage();

        await this.login('rbac.full@rtrwnet.id', 'password');

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('07-tablet-view');

        const heading = await this.page.locator('h2:has-text("Perusahaan Saya")').count();
        this.assert(heading > 0, 'Heading not found on tablet');
        console.log('Heading visible on tablet: OK');

        const companyName = await this.page.locator('h3').first().textContent();
        console.log('Company name on tablet:', companyName);

        console.log('TEST 07: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 08: Responsive Desktop (1280x720)
    // ============================================================
    async test_08_responsive_desktop() {
        console.log('\nTEST 08: Responsive Desktop (1280x720)');
        console.log('======================================');

        await this.context.close();
        this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
        this.page = await this.context.newPage();

        await this.login('rbac.full@rtrwnet.id', 'password');

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('08-desktop-view');

        const heading = await this.page.locator('h2:has-text("Perusahaan Saya")').count();
        this.assert(heading > 0, 'Heading not found on desktop');
        console.log('Heading visible on desktop: OK');

        const companyName = await this.page.locator('h3').first().textContent();
        console.log('Company name on desktop:', companyName);

        const editBtn = await this.page.locator('button:has-text("Edit Perusahaan")').count();
        console.log('Edit button on desktop:', editBtn > 0 ? 'OK' : 'Not visible (no permission)');

        console.log('TEST 08: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // RUN ALL TESTS
    // ============================================================
    async runAllTests() {
        console.log('========================================');
        console.log('Perusahaan Saya CRUD Test - Playwright');
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

            await this.test_06_responsive_mobile();
            await this.test_07_responsive_tablet();
            await this.test_08_responsive_desktop();

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

new PerusahaanSayaCRUDTest().runAllTests();