// Comprehensive visual + responsive + dark/light test for admin-role pages
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

class AdminRolePagesInspect {
    constructor() {
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.outDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'AdminRolePages', 'Inspect');
        this.testResults = { passed: 0, failed: 0, errors: [] };
        this.downloadsDir = path.join(__dirname, '..', '..', '..', '..', 'storage', 'app', 'temp', 'admin-role-downloads');
    }

    async shot(page, name) {
        if (!fs.existsSync(this.outDir)) fs.mkdirSync(this.outDir, { recursive: true });
        const f = path.join(this.outDir, name + '.png');
        await page.screenshot({ path: f, fullPage: false });
        return f;
    }

    log(name, ok, note = '') {
        this.testResults[ok ? 'passed' : 'failed']++;
        console.log(`  ${ok ? '✓' : '✗'} ${name}${note ? ' — ' + note : ''}`);
        if (!ok) this.testResults.errors.push(`${name}: ${note}`);
    }

    async login(page) {
        await page.goto(`${this.baseUrl}/login-perusahaan`, { waitUntil: 'domcontentloaded' });
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1500);
        const companyBtn = page.locator('button:has(.fa-building)').first();
        if (await companyBtn.count() > 0) {
            await companyBtn.click();
            await page.waitForTimeout(2000);
            const search = page.locator('input[placeholder*="Cari perusahaan"]').first();
            await search.fill('Digital Media');
            await page.waitForTimeout(2000);
            await page.locator('text=CV Digital Media Nusantara').first().click();
            await page.waitForTimeout(2000);
        }
        await page.fill('input[type="email"]', 'admin@digitalmedia.id');
        await page.fill('input[type="password"]', 'password123');
        await page.waitForTimeout(500);
        await page.click('button[type="submit"]');
        await page.waitForTimeout(8000);
        const url = page.url();
        console.log(`    [login] URL after submit: ${url}`);
        if (url.includes('login')) {
            console.log(`    [login] STILL ON LOGIN — retrying submit`);
            await page.waitForTimeout(2000);
            await page.click('button[type="submit"]');
            await page.waitForTimeout(5000);
        }
    }

    async clearDownloads() {
        if (fs.existsSync(this.downloadsDir)) {
            for (const f of fs.readdirSync(this.downloadsDir)) {
                try { fs.unlinkSync(path.join(this.downloadsDir, f)); } catch {}
            }
        } else {
            fs.mkdirSync(this.downloadsDir, { recursive: true });
        }
    }

    async inspectPage(page, pageName, url) {
        console.log(`\n=== ${pageName} (${url}) ===`);
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1500);
        await this.shot(page, `${pageName}-01-list`);

        // Verify action buttons
        const tambahBtn = await page.$('button:has-text("Tambah")');
        const importBtn = await page.$('button:has-text("Import")');
        const exportBtn = await page.$('button:has-text("Export")');
        const templateLink = await page.$('a:has-text("Template")');
        this.log(`[${pageName}] Tombol Tambah terlihat`, tambahBtn !== null);
        this.log(`[${pageName}] Tombol Import terlihat`, importBtn !== null);
        this.log(`[${pageName}] Tombol Export terlihat`, exportBtn !== null);
        this.log(`[${pageName}] Link Template terlihat`, templateLink !== null);

        // Open Tambah modal
        if (tambahBtn) {
            await tambahBtn.click();
            await page.waitForTimeout(1200);
            await this.shot(page, `${pageName}-02-modal-tambah`);
            const close = page.locator('button:has-text("Batal")').first();
            if (await close.count() > 0) await close.click();
            await page.waitForTimeout(500);
        }

        // Open Detail modal
        const detailBtn = await page.$('button[title="Detail"]');
        if (detailBtn) {
            await detailBtn.click();
            await page.waitForTimeout(1200);
            await this.shot(page, `${pageName}-03-modal-detail`);
            const close = page.locator('button:has-text("Tutup")').first();
            if (await close.count() > 0) await close.click();
            await page.waitForTimeout(500);
        }

        // Open Edit modal
        const editBtn = await page.$('button[title="Edit"]');
        if (editBtn) {
            await editBtn.click();
            await page.waitForTimeout(1200);
            await this.shot(page, `${pageName}-04-modal-edit`);
            const close = page.locator('button:has-text("Batal")').first();
            if (await close.count() > 0) await close.click();
            await page.waitForTimeout(500);
        }

        // Open Import modal
        if (importBtn) {
            await importBtn.click();
            await page.waitForTimeout(1200);
            await this.shot(page, `${pageName}-05-modal-import`);
            const close = page.locator('button:has-text("Batal")').first();
            if (await close.count() > 0) await close.click();
            await page.waitForTimeout(500);
        }

        // Download template
        if (templateLink) {
            const [download] = await Promise.all([
                page.waitForEvent('download', { timeout: 10000 }).catch(() => null),
                templateLink.click({ force: true })
            ]);
            this.log(`[${pageName}] Download template berhasil`, download !== null, download ? download.suggestedFilename() : 'no event');
        }

        // Export all
        if (exportBtn) {
            const [download] = await Promise.all([
                page.waitForEvent('download', { timeout: 10000 }).catch(() => null),
                exportBtn.click({ force: true })
            ]);
            this.log(`[${pageName}] Export semua berhasil`, download !== null, download ? download.suggestedFilename() : 'no event');
        }
    }

    async run() {
        const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });

        const viewports = [
            { name: 'mobile', width: 375, height: 812 },
            { name: 'tablet', width: 768, height: 1024 },
            { name: 'desktop', width: 1366, height: 850 },
        ];
        const themes = ['light', 'dark'];

        for (const vp of viewports) {
            for (const theme of themes) {
                console.log(`\n########## ${vp.name.toUpperCase()} (${vp.width}x${vp.height}) — ${theme.toUpperCase()} ##########`);
                const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height } });
                const page = await ctx.newPage();
                await page.emulateMedia({ colorScheme: theme });
                try {
                    await this.clearDownloads();
                    await this.login(page);

                    const tag = `${vp.name}-${theme}`;
                    await this.inspectPage(page, `perusahaan-${tag}`, `${this.baseUrl}/operator-perusahaan/admin-role-perusahaan`);
                    await this.inspectPage(page, `webkaryawan-${tag}`, `${this.baseUrl}/operator-perusahaan/admin-role-web-karyawan`);
                } catch (e) {
                    console.log(`[FATAL ${vp.name} ${theme}] ${e.message}`);
                } finally {
                    await ctx.close();
                }
            }
        }

        await browser.close();

        console.log(`\n========== FINAL SUMMARY ==========`);
        console.log(`Passed: ${this.testResults.passed} | Failed: ${this.testResults.failed}`);
        this.testResults.errors.forEach(e => console.log(`  ✗ ${e}`));
        process.exit(this.testResults.failed > 0 ? 1 : 0);
    }
}

new AdminRolePagesInspect().run();
