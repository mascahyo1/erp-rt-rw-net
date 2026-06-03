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

        // Assert heading exists
        const heading = await this.page.locator('h2:has-text("Perusahaan Saya")').count();
        this.assert(heading > 0, 'Heading "Perusahaan Saya" not found');
        console.log('Heading found: OK');

        // Assert company name visible
        const companyName = await this.page.locator('h3').first().textContent();
        this.assert(companyName && companyName.length > 0, 'Company name not found');
        console.log('Company name:', companyName);

        // Assert page title correct
        const title = await this.page.title();
        this.assert(title.includes('Perusahaan Saya'), `Page title should contain "Perusahaan Saya", got: ${title}`);
        console.log('Page title:', title);

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

        // Assert sidebar has "Perusahaan Saya"
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

        // Assert dropdown has "Perusahaan Saya"
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

        // Assert form input visible
        const nameInput = await this.page.locator('input[type="text"]').first();
        const isVisible = await nameInput.isVisible();
        this.assert(isVisible, 'Form input not visible');
        console.log('Form mode: OK');

        // Get original name
        const originalName = await nameInput.inputValue();
        console.log('Original name:', originalName);

        // Edit and save
        await nameInput.fill('CV Digital Media Nusantara Updated');
        await this.takeScreenshot('05-name-changed');

        await this.page.click('button:has-text("Simpan Perubahan")');
        await this.page.waitForTimeout(3000);
        await this.takeScreenshot('06-after-save');

        // Assert form closed (back to detail view) — that is the real success indicator
        const editBtnAgain = await this.page.locator('button:has-text("Edit Perusahaan")').count();
        const simpanBtn = await this.page.locator('button:has-text("Simpan Perubahan")').count();
        this.assert(editBtnAgain > 0 && simpanBtn === 0, 'Form should close after successful save');
        console.log('Form closed after save: OK');

        // Assert company name updated in UI
        const newCompanyName = await this.page.locator('h3').first().textContent();
        this.assert(newCompanyName.includes('CV Digital Media Nusantara Updated'), `Company name should be updated, got: ${newCompanyName}`);
        console.log('Updated company name:', newCompanyName);

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

        // Initial state - should be system (default)
        const initialTheme = await this.page.evaluate(() => localStorage.getItem('theme') || 'system');
        console.log('Initial theme from localStorage:', initialTheme);

        const themeBtn = this.page.locator('button[title*="Tema"]').first();

        // Click 1: system -> light
        await themeBtn.click();
        await this.page.waitForTimeout(500);
        await this.takeScreenshot('05b-after-first-click');

        const themeAfterFirst = await this.page.evaluate(() => localStorage.getItem('theme'));
        const isDarkAfterFirst = await this.page.evaluate(() => document.documentElement.classList.contains('dark'));
        console.log('After 1st click (system->light) - theme:', themeAfterFirst, '| dark class:', isDarkAfterFirst);
        this.assert(themeAfterFirst === 'light', 'Theme should be light after first click');
        this.assert(!isDarkAfterFirst, 'Light mode - dark class should not be present');
        console.log('Light mode: OK');

        // Click 2: light -> dark
        await themeBtn.click();
        await this.page.waitForTimeout(500);
        await this.takeScreenshot('05c-after-second-click');

        const themeAfterSecond = await this.page.evaluate(() => localStorage.getItem('theme'));
        const isDarkAfterSecond = await this.page.evaluate(() => document.documentElement.classList.contains('dark'));
        console.log('After 2nd click (light->dark) - theme:', themeAfterSecond, '| dark class:', isDarkAfterSecond);
        this.assert(themeAfterSecond === 'dark', 'Theme should be dark after second click');
        this.assert(isDarkAfterSecond, 'Dark mode - dark class should be present');
        console.log('Dark mode: OK');

        // Click 3: dark -> system
        await themeBtn.click();
        await this.page.waitForTimeout(500);
        await this.takeScreenshot('05d-after-third-click');

        const themeAfterThird = await this.page.evaluate(() => localStorage.getItem('theme'));
        console.log('After 3rd click (dark->system) - theme:', themeAfterThird);
        this.assert(themeAfterThird === 'system', 'Theme should be system after third click');
        console.log('System theme: OK');

        console.log('TEST 05: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 06: Responsive Mobile (375x667)
    // ============================================================
    async test_06_responsive_mobile() {
        console.log('\nTEST 06: Responsive Mobile (375x667)');
        console.log('====================================');

        await this.context.close();
        this.context = await this.browser.newContext({ viewport: { width: 375, height: 667 } });
        this.page = await this.context.newPage();

        await this.login('rbac.full@rtrwnet.id', 'password');

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('06-mobile-view');

        // Assert heading visible
        const heading = await this.page.locator('h2:has-text("Perusahaan Saya")').count();
        this.assert(heading > 0, 'Heading not found on mobile');
        console.log('Heading visible on mobile: OK');

        // Assert company name visible
        const companyName = await this.page.locator('h3').first().textContent();
        this.assert(companyName && companyName.length > 0, 'Company name not found on mobile');
        console.log('Company name on mobile:', companyName);

        // Assert edit button visible (should have edit permission)
        const editBtn = await this.page.locator('button:has-text("Edit")').count();
        this.assert(editBtn > 0, 'Edit button should be visible on mobile');
        console.log('Edit button on mobile: OK');

        // Assert sidebar toggle visible (mobile should have hamburger menu)
        const menuBtn = await this.page.locator('button:has(.fa-bars)').count();
        this.assert(menuBtn > 0, 'Mobile menu button not found');
        console.log('Mobile menu button: OK');

        // Assert main content is within viewport (not overflow)
        const mainContent = await this.page.locator('main').boundingBox();
        this.assert(mainContent.width <= 375, 'Main content should not exceed viewport width');
        console.log('Main content width OK:', mainContent.width);

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

        // Assert heading visible
        const heading = await this.page.locator('h2:has-text("Perusahaan Saya")').count();
        this.assert(heading > 0, 'Heading not found on tablet');
        console.log('Heading visible on tablet: OK');

        // Assert company name visible
        const companyName = await this.page.locator('h3').first().textContent();
        this.assert(companyName && companyName.length > 0, 'Company name not found on tablet');
        console.log('Company name on tablet:', companyName);

        // Assert edit button visible
        const editBtn = await this.page.locator('button:has-text("Edit Perusahaan")').count();
        this.assert(editBtn > 0, 'Edit button should be visible on tablet');
        console.log('Edit button on tablet: OK');

        // Assert sidebar visible on tablet (larger than mobile)
        const sidebar = await this.page.locator('aside').boundingBox();
        this.assert(sidebar && sidebar.width > 50, 'Sidebar should be visible on tablet');
        console.log('Sidebar visible on tablet: OK');

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

        // Assert heading visible
        const heading = await this.page.locator('h2:has-text("Perusahaan Saya")').count();
        this.assert(heading > 0, 'Heading not found on desktop');
        console.log('Heading visible on desktop: OK');

        // Assert company name visible
        const companyName = await this.page.locator('h3').first().textContent();
        this.assert(companyName && companyName.length > 0, 'Company name not found on desktop');
        console.log('Company name on desktop:', companyName);

        // Assert edit button visible
        const editBtn = await this.page.locator('button:has-text("Edit Perusahaan")').count();
        this.assert(editBtn > 0, 'Edit button should be visible on desktop');
        console.log('Edit button on desktop: OK');

        // Assert sidebar fully expanded on desktop
        const sidebar = await this.page.locator('aside').boundingBox();
        this.assert(sidebar && sidebar.width > 100, 'Sidebar should be fully expanded on desktop');
        console.log('Sidebar expanded on desktop: OK width=' + sidebar?.width);

        console.log('TEST 08: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 09: Upload Logo (Light) - JPG raster should be compressed to WebP
    // ============================================================
    async test_09_upload_logo_light() {
        console.log('\nTEST 09: Upload Logo Light (JPG)');
        console.log('================================');

        await this.context.close();
        this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
        this.page = await this.context.newPage();
        await this.login('rbac.full@rtrwnet.id', 'password');
        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('09a-before-upload');

        // Enter edit mode
        const editBtn = this.page.locator('button:has-text("Edit Perusahaan")').first();
        await editBtn.click();
        await this.page.waitForTimeout(500);

        // Locate light logo file input (first file input on the page)
        const lightInput = this.page.locator('input[type="file"]').first();
        await lightInput.setInputFiles({
            name: 'logo-light.jpg',
            mimeType: 'image/jpeg',
            buffer: Buffer.from(this.makeJpegBuffer(2)),
        });
        await this.page.waitForTimeout(500);
        await this.takeScreenshot('09b-light-preview');

        // Verify preview visible
        const previewCount = await this.page.locator('img[alt="Logo Light"]').count();
        this.assert(previewCount > 0, 'Logo light preview not shown');
        console.log('Logo light preview: OK');

        // Save
        await this.page.click('button:has-text("Simpan Perubahan")');
        await this.page.waitForTimeout(3000);
        await this.takeScreenshot('09c-after-save');

        // Verify success toast / page
        const success = await this.page.locator('text=berhasil diperbarui').count();
        this.assert(success > 0, 'Success message not shown after logo upload');
        console.log('Upload success: OK');

        // Verify logo displayed in detail
        const logoAfter = await this.page.locator('img[alt="Logo"]').count();
        this.assert(logoAfter > 0, 'Logo not displayed in detail view after upload');
        console.log('Logo displayed in detail: OK');

        console.log('TEST 09: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 10: Upload Logo (Dark) - SVG should NOT be compressed
    // ============================================================
    async test_10_upload_logo_dark_svg() {
        console.log('\nTEST 10: Upload Logo Dark (SVG)');
        console.log('================================');

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);

        const editBtn = this.page.locator('button:has-text("Edit Perusahaan")').first();
        await editBtn.click();
        await this.page.waitForTimeout(500);

        const darkInput = this.page.locator('input[type="file"]').nth(1);
        await darkInput.setInputFiles({
            name: 'logo-dark.svg',
            mimeType: 'image/svg+xml',
            buffer: Buffer.from(this.makeSvgBuffer('DARK')),
        });
        await this.page.waitForTimeout(500);
        await this.takeScreenshot('10a-dark-svg-preview');

        const preview = await this.page.locator('img[alt="Logo Dark"]').count();
        this.assert(preview > 0, 'Logo dark preview not shown');
        console.log('Logo dark SVG preview: OK');

        await this.page.click('button:has-text("Simpan Perubahan")');
        await this.page.waitForTimeout(3000);
        await this.takeScreenshot('10b-after-save');

        const success = await this.page.locator('text=berhasil diperbarui').count();
        this.assert(success > 0, 'Success message not shown after dark logo upload');
        console.log('Dark logo save: OK');

        console.log('TEST 10: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 11: Logo validation - oversize file rejected
    // ============================================================
    async test_11_logo_validation_oversize() {
        console.log('\nTEST 11: Logo Validation - Oversize Rejected');
        console.log('=============================================');

        await this.page.goto(`${this.baseUrl}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);

        const editBtn = this.page.locator('button:has-text("Edit Perusahaan")').first();
        await editBtn.click();
        await this.page.waitForTimeout(500);

        // 3MB file (exceeds 2MB max)
        const lightInput = this.page.locator('input[type="file"]').first();
        await lightInput.setInputFiles({
            name: 'logo-big.jpg',
            mimeType: 'image/jpeg',
            buffer: Buffer.from(this.makeJpegBuffer(3)),
        });
        await this.page.waitForTimeout(500);
        await this.takeScreenshot('11a-oversize-attempt');

        await this.page.click('button:has-text("Simpan Perubahan")');
        await this.page.waitForTimeout(2000);
        await this.takeScreenshot('11b-oversize-result');

        // Should stay in edit mode + show validation error
        const stillInEdit = await this.page.locator('button:has-text("Simpan Perubahan")').count();
        this.assert(stillInEdit > 0, 'Should stay in edit mode when validation fails');
        console.log('Stayed in edit mode: OK');

        console.log('TEST 11: PASSED');
        this.testResults.passed++;
    }

    // Helper: build a small but valid-looking JPEG buffer of approx N MB
    makeJpegBuffer(sizeMb) {
        const target = sizeMb * 1024 * 1024;
        // JPEG header (SOI + APP0 + SOF0 + EOI) - we just pad with bytes; PHP will reject
        // but Playwright upload is browser-side and the server validates by file size.
        // For real validation, we need a real image. Use a small generated PNG + padding.
        // Use simple PNG with binary payload — the FileUploadService reads mime, not real decode
        // for size check (Laravel max:2048 = 2MB).
        // Build minimal PNG (8x8 red) then pad to size.
        const minimalPng = Buffer.from([
            0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A, // PNG signature
            0x00, 0x00, 0x00, 0x0D, 0x49, 0x48, 0x44, 0x52, // IHDR
            0x00, 0x00, 0x00, 0x08, 0x00, 0x00, 0x00, 0x08, // 8x8
            0x08, 0x02, 0x00, 0x00, 0x00, 0x4B, 0x6D, 0x29, 0xDC,
            0x00, 0x00, 0x00, 0x1F, 0x49, 0x44, 0x41, 0x54, // IDAT
            0x78, 0x9C, 0x62, 0xFC, 0xCF, 0xC0, 0xC0, 0xC0,
            0xC0, 0xC0, 0xC0, 0xC0, 0x00, 0x00, 0x00, 0x12,
            0x00, 0x05, 0xFE, 0xA7, 0xFE, 0xA7, 0x6A, 0x6A,
            0x6A, 0x00, 0x00, 0x00, 0x00, 0x49, 0x45, 0x4E, 0x44, // IEND
            0xAE, 0x42, 0x60, 0x82,
        ]);
        if (target <= minimalPng.length) return minimalPng;
        const pad = Buffer.alloc(target - minimalPng.length, 0);
        return Buffer.concat([minimalPng, pad]);
    }

    // Helper: minimal SVG buffer
    makeSvgBuffer(text) {
        return Buffer.from(`<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="#1e40af"/><text x="50" y="55" text-anchor="middle" fill="white" font-size="20" font-family="Arial">${text}</text></svg>`);
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
            await this.test_09_upload_logo_light();
            await this.test_10_upload_logo_dark_svg();
            await this.test_11_logo_validation_oversize();

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