const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class DaftarPaketResponsiveTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'DaftarPaket', 'TestResponsive');
        this.screenshotCount = 0;
        this.testResults = { passed: 0, failed: 0, errors: [] };

        // Viewport sizes to test
        this.viewports = [
            { name: 'Mobile (375x667)', width: 375, height: 667 },
            { name: 'Mobile Landscape (812x375)', width: 812, height: 375 },
            { name: 'Tablet (768x1024)', width: 768, height: 1024 },
            { name: 'Laptop (1366x768)', width: 1366, height: 768 },
            { name: 'Desktop (1920x1080)', width: 1920, height: 1080 },
        ];

        // Color schemes to test
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
        console.log('Daftar Paket - Responsive & Theme Tests');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false, slowMo: 350 });

            // Login first
            this.context = await this.browser.newContext({
                viewport: { width: 1920, height: 1080 }
            });
            this.page = await this.context.newPage();

            await this.loginAsAdminPerusahaan('rbac.full@rtrwnet.id', 'password');

            // Test each viewport + color scheme combination
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

    async setColorScheme(scheme) {
        // Force color scheme via Emulator
        const emulation = this.context._emulators || [];
        // Use page.emulate to set color scheme
        await this.page.emulateMedia({
            colorScheme: scheme
        });
    }

    async testViewport(viewport, colorScheme) {
        const testName = `${colorScheme.toUpperCase()} - ${viewport.name}`;
        console.log(`[TEST] ${testName}`);

        try {
            // Set viewport
            await this.page.setViewportSize({ width: viewport.width, height: viewport.height });

            // Set color scheme
            await this.page.emulateMedia({ colorScheme: colorScheme });

            // Navigate to page
            await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket?per_page=25`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);

            const filename = `${colorScheme}-${viewport.width}x${viewport.height}`.replace(/[^a-z0-9\-]/gi, '_');
            await this.takeScreenshot(filename);

            // Check for console errors
            const errors = [];
            this.page.on('console', msg => {
                if (msg.type() === 'error') errors.push(msg.text());
            });

            // Check UI elements are visible
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

        // Check header
        total++;
        const header = await this.page.$('h2');
        if (header && await header.isVisible()) visible++;

        // Check table
        total++;
        const table = await this.page.$('table');
        if (table && await table.isVisible()) visible++;

        // Check buttons (Tambah, Import, Export)
        const buttons = [
            'button:has-text("Tambah Paket")',
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
        const search = await this.page.$('input[placeholder="Cari..."]');
        if (search && await search.isVisible()) visible++;

        // Check filter selects
        const selects = await this.page.$$('select');
        let selectsVisible = 0;
        for (const sel of selects) {
            if (await sel.isVisible()) selectsVisible++;
        }
        if (selectsVisible > 0) {
            visible++; // Count as one check
            total++;
        } else {
            total++;
        }

        return { visible, total };
    }
}

const test = new DaftarPaketResponsiveTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});