const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class LanggananResponsiveTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'Langganan', 'TestResponsive');
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
        console.log('Langganan - Responsive & Theme Tests');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false });

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
        await this.page.goto(`${BASE}/login-perusahaan`);
        await this.page.waitForLoadState('networkidle');

        await this.page.fill('input[type="email"]', email);
        await this.page.fill('input[type="password"]', password);

        await this.page.click('button[type="submit"]');
        await this.page.waitForTimeout(8000);
    }

    async setColorScheme(scheme) {
        const script = scheme === 'dark'
            ? "document.documentElement.classList.add('dark')"
            : "document.documentElement.classList.remove('dark')";
        await this.page.evaluate(script);
    }

    async testViewport(viewport, colorScheme) {
        const testName = `responsive_${viewport.name.replace(/[^a-zA-Z0-9]/g, '_')}_${colorScheme}`;
        console.log(`[TEST] ${testName} - ${viewport.name} (${colorScheme})`);

        try {
            await this.context.newPage();
            this.page = (await this.context.pages())[0] || this.page;

            await this.page.setViewportSize({ width: viewport.width, height: viewport.height });
            await this.setColorScheme(colorScheme);

            const response = await this.page.goto(`${BASE}/operator-perusahaan/langganan-customer`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot(`${viewport.name.replace(/[^a-zA-Z0-9]/g, '_')}_${colorScheme}`);

            const url = this.page.url();
            const status = response ? response.status() : 'unknown';

            console.log(`  Viewport: ${viewport.width}x${viewport.height}`);
            console.log(`  Color scheme: ${colorScheme}`);
            console.log(`  URL: ${url}`);
            console.log(`  Status: ${status}`);

            this.assert(!url.includes('403'), `${testName}: Access denied`);
            this.assert(status !== 403, `${testName}: HTTP 403`);

            const pageText = await this.page.textContent('body');
            const hasLangganan = pageText.includes('Langganan') || pageText.includes('langganan');
            console.log(`  Has langganan content: ${hasLangganan}`);

            // Check table visibility
            const table = await this.page.$('table');
            console.log(`  Table visible: ${table !== null}`);

            // Check modal responsive (open create modal)
            const tambahBtn = await this.page.$('button:has-text("Tambah Langganan")');
            if (tambahBtn) {
                await tambahBtn.click({ force: true });
                await this.page.waitForTimeout(1000);
                await this.takeScreenshot(`${viewport.name.replace(/[^a-zA-Z0-9]/g, '_')}_${colorScheme}_modal`);

                const modal = await this.page.$('.fixed.inset-0');
                console.log(`  Modal opens: ${modal !== null}`);
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

    assert(condition, message) {
        if (!condition) {
            throw new Error(message);
        }
    }
}

const test = new LanggananResponsiveTest();
test.runAllTests().catch(console.error);