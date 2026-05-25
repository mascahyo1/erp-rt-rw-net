const { chromium } = require('playwright');

class TagihanDarkModeTest {
    constructor() {
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.browser = null;
        this.page = null;
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

    async runTest() {
        console.log('========================================');
        console.log('Tagihan Dark Mode Date Input Test');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false });
            const context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            const errors = [];
            this.page.on('console', msg => {
                if (msg.type() === 'error') errors.push(msg.text());
            });
            this.page.on('pageerror', err => errors.push(err.message));

            console.log('Navigating to Tagihan page...');
            await this.page.goto(`${this.baseUrl}/operator-perusahaan/tagihan`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            // Toggle dark mode
            const themeBtn = this.page.locator('button[title*="Tema"]').first();
            await themeBtn.click();
            await this.page.waitForTimeout(1000);

            console.log('Dark mode active:', await this.page.evaluate(() => document.documentElement.classList.contains('dark')));
            console.log('Theme localStorage:', await this.page.evaluate(() => localStorage.getItem('theme')));

            // Take screenshot of date inputs in dark mode
            await this.page.screenshot({ path: 'tests/Browser/Playwright/Feature/result/OperatorPerusahaan/TagihanDarkMode/01-dark-mode-date-inputs.png', fullPage: false });
            console.log('Screenshot saved');

            // Check date input icons
            const dateInputs = await this.page.locator('input[type="date"]').all();
            console.log('Date inputs count:', dateInputs.length);

            // Check computed color of date input calendar icon
            if (dateInputs.length > 0) {
                const iconColor = await this.page.evaluate(() => {
                    const input = document.querySelector('input[type="date"]');
                    if (input) {
                        const style = window.getComputedStyle(input, '::-webkit-calendar-picker-indicator');
                        return style.color;
                    }
                    return null;
                });
                console.log('Calendar icon color:', iconColor);
            }

            console.log('\n--- Console Errors ---');
            if (errors.length === 0) {
                console.log('No console errors detected!');
            } else {
                errors.forEach((e, i) => console.log(`${i + 1}. ${e}`));
            }

            console.log('\n========================================');
            console.log('TEST COMPLETED');
            console.log('========================================\n');

        } catch (error) {
            console.error('[FATAL ERROR]', error.message);
        } finally {
            if (this.browser) await this.browser.close();
        }
    }
}

new TagihanDarkModeTest().runTest();