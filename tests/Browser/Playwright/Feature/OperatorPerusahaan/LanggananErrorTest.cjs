
const BASE = require('../../support/baseUrl.cjs');
const { chromium } = require('playwright');

class LanggananErrorTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.browser = null;
        this.page = null;
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

    async runTest() {
        console.log('========================================');
        console.log('LanggananCustomer Page Error Test');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false, slowMo: 350 });
            const context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await context.newPage();

            await this.login('admin@digitalmedia.id', 'password123');

            const errors = [];
            this.page.on('console', msg => {
                if (msg.type() === 'error') errors.push(msg.text());
            });
            this.page.on('pageerror', err => errors.push(err.message));

            console.log('Navigating to LanggananCustomer page...');
            await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(3000);

            console.log('Current URL:', this.page.url());
            const title = await this.page.title();
            console.log('Page title:', title);

            const h2Count = await this.page.locator('h2').count();
            console.log('H2 count:', h2Count);
            if (h2Count > 0) {
                const h2Text = await this.page.locator('h2').first().textContent();
                console.log('H2 text:', h2Text);
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

new LanggananErrorTest().runTest();