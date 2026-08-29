const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class CustomerResponsiveTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'Customer', 'TestResponsive');
        this.screenshotCount = 0;
        this.testResults = { passed: 0, failed: 0, errors: [] };

        this.viewports = [
            { name: 'Mobile (375x667)', width: 375, height: 667 },
            { name: 'Mobile Landscape (812x375)', width: 812, height: 375 },
            { name: 'Tablet (768x1024)', width: 768, height: 1024 },
            { name: 'Laptop (1366x768)', width: 1366, height: 768 },
            { name: 'Desktop (1920x1080)', width: 1920, height: 1080 },
        ];

        this.colorSchemes = ['light', 'dark'];
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

    async runAllTests() {
        console.log('========================================');
        console.log('Customer - Responsive & Theme Tests');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false, slowMo: 350 });

            this.context = await this.browser.newContext({
                viewport: { width: 1920, height: 1080 }
            });
            this.page = await this.context.newPage();

            await this.loginAsAdminPerusahaan('rbac.full@rtrwnet.id', 'password');

            for (const scheme of this.colorSchemes) {
                console.log(`\n=== COLOR SCHEME: ${scheme.toUpperCase()} ===\n`);

                for (const vp of this.viewports) {
                    await this.testViewport(vp, scheme);
                }
            }

            console.log('\n========================================');
            console.log('TEST SUMMARY');
            console.log('========================================');
            console.log(`Passed: ${this.testResults.passed}`);
            console.log(`Failed: ${this.testResults.failed}`);
            console.log('========================================\n');

        } catch (error) {
            console.error('[FATAL ERROR]', error.message);
        } finally {
            if (this.browser) await this.browser.close();
        }
    }

    async loginAsAdminPerusahaan(email, password) {
        await this.page.goto(`${BASE}/login-perusahaan`);
        await this.page.waitForLoadState('networkidle');

        await this.page.fill('input[type="email"]', email);
        await this.page.fill('input[type="password"]', password);

        await this.page.click('button[type="submit"]');
        await this.page.waitForTimeout(8000);

        console.log(`  Logged in as: ${email}`);
    }

    async testViewport(viewport, colorScheme) {
        const testName = `${colorScheme.toUpperCase()} - ${viewport.name}`;
        console.log(`[TEST] ${testName}`);

        try {
            await this.page.setViewportSize({ width: viewport.width, height: viewport.height });
            await this.page.emulateMedia({ colorScheme: colorScheme });

            await this.page.goto(`${BASE}/operator-perusahaan/customer?per_page=25`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            const filename = `${colorScheme}-${viewport.width}x${viewport.height}`.replace(/[^a-z0-9\-]/gi, '_');
            await this.takeScreenshot(filename);

            const errors = [];
            this.page.on('console', msg => {
                if (msg.type() === 'error') errors.push(msg.text());
            });

            const checks = await this.performUIChecks();
            console.log(`  Viewport: ${viewport.width}x${viewport.height}`);
            console.log(`  UI Elements: ${checks.visible}/${checks.total} visible`);
            if (errors.length > 0) {
                console.log(`  Console errors: ${errors.length}`);
            }

            this.testResults.passed++;
            console.log(`  PASSED\n`);

        } catch (error) {
            console.log(`  FAILED: ${error.message}\n`);
            this.testResults.failed++;
        }
    }

    async performUIChecks() {
        let visible = 0;
        let total = 0;

        // Check page title
        total++;
        const title = await this.page.$('h2');
        if (title && await title.isVisible()) visible++;

        // Check table
        total++;
        const table = await this.page.$('table');
        if (table && await table.isVisible()) visible++;

        // Check buttons (Tambah Customer, Import, Export)
        const buttons = [
            'button:has-text("Tambah Customer")',
            'button:has-text("Import")',
            'button:has-text("Export")'
        ];

        for (const selector of buttons) {
            total++;
            const btn = await this.page.$(selector);
            if (btn && await btn.isVisible()) visible++;
        }

        // Check search input
        total++;
        const search = await this.page.$('input[placeholder="Cari customer..."]');
        if (search && await search.isVisible()) visible++;

        // Check filter selects
        const selects = await this.page.$$('select');
        let selectsVisible = 0;
        for (const sel of selects) {
            if (await sel.isVisible()) selectsVisible++;
        }
        if (selectsVisible > 0) {
            visible++;
            total++;
        } else {
            total++;
        }

        // Check pagination
        total++;
        const pagination = await this.page.$('.flex.items-center.gap-1 button');
        if (pagination && await pagination.isVisible()) visible++;

        // Check data rows
        total++;
        const rows = await this.page.$$('tbody tr');
        if (rows.length > 0) visible++;

        return { visible, total };
    }
}

const test = new CustomerResponsiveTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});