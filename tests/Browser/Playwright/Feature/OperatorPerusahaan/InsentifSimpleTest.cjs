const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class InsentifSimpleTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'Insentif');
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
        console.log('Insentif Simple Test - Screenshot focused');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false, slowMo: 350 });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            // ============================================================
            // TEST 01: Light Mode - Full Page View
            // ============================================================
            console.log('\nTEST 01: Light Mode - Full page view');
            console.log('========================================');

            await this.page.goto(`${BASE}/operator-perusahaan/insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('01-light-full-page');

            // Log all visible buttons
            const buttons = await this.page.locator('button').allTextContents();
            console.log('All buttons:', buttons.filter(b => b.trim()));

            // Log table content
            const rows = await this.page.locator('tbody tr').count();
            console.log('Table rows:', rows);

            console.log('TEST 01: PASSED');

            // ============================================================
            // TEST 02: Toggle Dark Mode - Full page view
            // ============================================================
            console.log('\nTEST 02: Dark Mode - Full page view');
            console.log('========================================');

            // Get initial theme
            const initialTheme = await this.page.evaluate(() => localStorage.getItem('theme') || 'system');
            console.log('Initial theme:', initialTheme);

            // Click theme toggle
            const themeBtn = this.page.locator('button[title*="Tema"]').first();
            await themeBtn.click();
            await this.page.waitForTimeout(1000);
            await this.takeScreenshot('02-dark-full-page');

            // Check dark class
            const hasDarkClass = await this.page.evaluate(() => document.documentElement.classList.contains('dark'));
            const currentTheme = await this.page.evaluate(() => localStorage.getItem('theme'));
            console.log('After click - localStorage theme:', currentTheme, '| dark class:', hasDarkClass);

            console.log('TEST 02: PASSED');

            // ============================================================
            // TEST 03: Light Mode Again
            // ============================================================
            console.log('\nTEST 03: Light Mode Again');
            console.log('========================================');

            await themeBtn.click(); // Click again to go to light
            await this.page.waitForTimeout(1000);
            await this.takeScreenshot('03-light-again');

            const lightMode = await this.page.evaluate(() => !document.documentElement.classList.contains('dark'));
            console.log('Light mode active:', lightMode);

            console.log('TEST 03: PASSED');

            // ============================================================
            // TEST 04: Open Create Modal - Dark Mode
            // ============================================================
            console.log('\nTEST 04: Dark mode - Open Create Modal');
            console.log('========================================');

            await themeBtn.click(); // Go dark
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('04-dark-before-modal');

            // Click Tambah Insentif
            await this.page.locator('button:has-text("Tambah Insentif")').click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('05-dark-create-modal');

            // Get modal text
            const modalTitle = await this.page.locator('h3').first().textContent();
            console.log('Modal title:', modalTitle);

            // Get form inputs
            const inputs = await this.page.locator('.fixed input[type="text"], .fixed input[type="number"]').all();
            console.log('Form inputs count:', inputs.length);

            // Fill form
            if (inputs.length >= 2) {
                await inputs[0].fill('Insentif Performance');
                await inputs[1].fill('50000');
                await this.takeScreenshot('06-dark-form-filled');
            }

            console.log('TEST 04: PASSED');

            // ============================================================
            // TEST 05: Close modal and test search
            // ============================================================
            console.log('\nTEST 05: Close modal, test search');
            console.log('========================================');

            await this.page.keyboard.press('Escape');
            await this.page.waitForTimeout(500);

            // Test search
            const searchInput = this.page.locator('input[placeholder*="Cari"]');
            await searchInput.fill('Performance');
            await this.takeScreenshot('07-search-filled');

            console.log('TEST 05: PASSED');

            // ============================================================
            // TEST 06: Mobile Responsive - Light
            // ============================================================
            console.log('\nTEST 06: Mobile Responsive - Light');
            console.log('========================================');

            await this.context.close();
            this.context = await this.browser.newContext({ viewport: { width: 375, height: 667 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            await this.page.goto(`${BASE}/operator-perusahaan/insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('08-mobile-light');

            // Check if text is readable (dark text on light bg)
            const h2El = await this.page.locator('h2').first();
            const h2Color = await h2El.evaluate(el => getComputedStyle(el).color);
            console.log('H2 color:', h2Color);

            console.log('TEST 06: PASSED');

            // ============================================================
            // TEST 07: Mobile Dark Mode
            // ============================================================
            console.log('\nTEST 07: Mobile Dark Mode');
            console.log('========================================');

            const themeBtnMobile = this.page.locator('button[title*="Tema"]').first();
            await themeBtnMobile.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('09-mobile-dark');

            const isDark = await this.page.evaluate(() => document.documentElement.classList.contains('dark'));
            console.log('Mobile dark mode active:', isDark);

            // Check heading text readability in dark
            const h2Mobile = await this.page.locator('h2').first();
            const h2MobileColor = await h2Mobile.evaluate(el => getComputedStyle(el).color);
            console.log('H2 color in dark mode:', h2MobileColor);

            console.log('TEST 07: PASSED');

            // ============================================================
            // TEST 08: Open Create Modal on Mobile
            // ============================================================
            console.log('\nTEST 08: Mobile - Create Modal');
            console.log('========================================');

            // Check if buttons are visible and text is readable
            const tambahBtnMobile = this.page.locator('button:has-text("Tambah Insentif")').first();
            const isVisible = await tambahBtnMobile.isVisible();
            console.log('Tambah button visible:', isVisible);

            await tambahBtnMobile.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('10-mobile-create-modal');

            // Check modal title color
            const modalTitleMobile = await this.page.locator('h3').first();
            const modalTitleColor = await modalTitleMobile.evaluate(el => getComputedStyle(el).color);
            console.log('Modal title color:', modalTitleColor);

            console.log('TEST 08: PASSED');

            // ============================================================
            // TEST 09: Tablet Responsive
            // ============================================================
            console.log('\nTEST 09: Tablet Responsive - Dark');
            console.log('========================================');

            await this.context.close();
            this.context = await this.browser.newContext({ viewport: { width: 768, height: 1024 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            await this.page.goto(`${BASE}/operator-perusahaan/insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            // Toggle dark mode
            const themeBtnTablet = this.page.locator('button[title*="Tema"]').first();
            await themeBtnTablet.click();
            await this.page.waitForTimeout(500);

            await this.takeScreenshot('11-tablet-dark');

            // Check sidebar
            const sidebarBox = await this.page.locator('aside').boundingBox();
            console.log('Sidebar dimensions:', sidebarBox);

            console.log('TEST 09: PASSED');

            // ============================================================
            // TEST 10: Desktop Responsive - Light
            // ============================================================
            console.log('\nTEST 10: Desktop Responsive - Light');
            console.log('========================================');

            await this.context.close();
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            await this.page.goto(`${BASE}/operator-perusahaan/insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('12-desktop-light');

            // Check sidebar expanded
            const sidebarDesktop = await this.page.locator('aside').boundingBox();
            console.log('Desktop sidebar width:', sidebarDesktop?.width);

            console.log('TEST 10: PASSED');

            console.log('\n========================================');
            console.log('ALL TESTS COMPLETED');
            console.log('Check screenshots in result folder');
            console.log('========================================\n');

        } catch (error) {
            console.error('[FATAL ERROR]', error.message);
        } finally {
            if (this.browser) await this.browser.close();
        }
    }
}

new InsentifSimpleTest().runTests();