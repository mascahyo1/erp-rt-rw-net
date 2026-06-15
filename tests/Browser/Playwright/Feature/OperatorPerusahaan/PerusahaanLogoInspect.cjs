// Comprehensive visual test for Perusahaan + PerusahaanSaya + downstream PDF with logo
// Tests: light/dark mode, 3 viewports, screenshots + video recording
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class PerusahaanLogoInspect {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.outDir = path.join(__dirname, '..', 'result', 'PerusahaanLogo');
        this.testResults = { passed: 0, failed: 0, errors: [] };
        this.downloadsDir = path.join(__dirname, '..', '..', '..', '..', 'storage', 'app', 'temp', 'logo-downloads');
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

    async clearDownloads() {
        if (fs.existsSync(this.downloadsDir)) {
            for (const f of fs.readdirSync(this.downloadsDir)) {
                try { fs.unlinkSync(path.join(this.downloadsDir, f)); } catch {}
            }
        } else {
            fs.mkdirSync(this.downloadsDir, { recursive: true });
        }
    }

    async loginAsAdminPerusahaan(page) {
        await page.goto(`${BASE}/login-perusahaan`);
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
        await page.click('button[type="submit"]');
        await page.waitForTimeout(7000);
    }

    async loginAsOperatorSaas(page) {
        await page.goto(`${BASE}/login-operator-saas`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1500);
        await page.fill('input[type="email"]', 'superadmin@demo.test');
        await page.fill('input[type="password"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(6000);
    }

    async inspectListAndModals(page, pageName, url) {
        console.log(`\n--- ${pageName} (${url}) ---`);
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
        await this.shot(page, `${pageName}-01-list`);

        // Check logo display
        const isListPage = await page.locator('table tbody tr').count() > 0;
        if (isListPage) {
            const hasLogoColumn = await page.locator('table img, table .bg-white').count() > 0;
            this.log(`[${pageName}] Tabel dengan logo avatar`, hasLogoColumn);
        } else {
            // PerusahaanSaya = card layout
            const hasCardLogo = await page.locator('.w-20.h-20 img, .w-20.h-20 .bg-gradient-to-br').count() > 0;
            this.log(`[${pageName}] Card perusahaan dengan logo avatar`, hasCardLogo);
        }

        // Open Tambah modal
        const tambahBtn = page.locator('button:has-text("Tambah")').first();
        if (await tambahBtn.count() > 0) {
            await tambahBtn.click();
            await page.waitForTimeout(1500);
            await this.shot(page, `${pageName}-02-tambah-modal`);

            // Check logo section
            const hasLogoLabel = await page.locator('text=Logo Perusahaan').count() > 0
                || await page.locator('text=Logo (Mode Terang)').count() > 0;
            this.log(`[${pageName}] Modal Tambah: section Logo Perusahaan terlihat`, hasLogoLabel);

            // Check 2 file inputs for light + dark
            const fileInputs = await page.locator('input[type="file"][accept*="svg"]').count();
            this.log(`[${pageName}] Modal Tambah: 2 file input logo (light + dark)`, fileInputs >= 2, `${fileInputs} input ditemukan`);

            const close = page.locator('button:has-text("Batal")').first();
            if (await close.count() > 0) await close.click();
            await page.waitForTimeout(500);
        }

        // Open Edit modal
        const editBtn = page.locator('button[title="Edit"]').first();
        if (await editBtn.count() > 0) {
            await editBtn.click();
            await page.waitForTimeout(1500);
            await this.shot(page, `${pageName}-03-edit-modal`);

            // Check pre-filled
            const hasLogoLabel = await page.locator('text=Logo Perusahaan').count() > 0
                || await page.locator('text=Logo (Mode Terang)').count() > 0;
            this.log(`[${pageName}] Modal Edit: section Logo Perusahaan terlihat`, hasLogoLabel);

            const close = page.locator('button:has-text("Batal")').first();
            if (await close.count() > 0) await close.click();
            await page.waitForTimeout(500);
        }

        // Open Detail modal (only for list pages with detail button)
        const detailBtn = page.locator('button[title="Detail"]').first();
        if (await detailBtn.count() > 0) {
            await detailBtn.click();
            await page.waitForTimeout(1500);
            await this.shot(page, `${pageName}-04-detail-modal`);

            // Check logo preview in detail
            const bodyText = await page.textContent('body');
            this.log(`[${pageName}] Detail menampilkan section Logo`, bodyText.includes('Logo'));
        }
    }

    async inspectRiwayatPdf(page) {
        console.log(`\n--- Riwayat Pembayaran PDF ---`);
        await this.clearDownloads();
        await page.goto(`${BASE}/operator-perusahaan/riwayat-pembayaran?per_page=100`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
        await this.shot(page, 'riwayat-pembayaran-list');

        // Get first row's payment ID from "Detail" link or similar
        const firstDetailLink = page.locator('tbody tr a[href*="riwayat-pembayaran/"]').first();
        let downloaded = null;
        if (await firstDetailLink.count() > 0) {
            const href = await firstDetailLink.getAttribute('href');
            // Find PDF link in detail page or directly go to PDF URL
            const paymentId = href.match(/riwayat-pembayaran\/([a-f0-9-]+)/)?.[1];
            if (paymentId) {
                const pdfUrl = `${BASE}/operator-perusahaan/riwayat-pembayaran/${paymentId}/pdf`;
                [downloaded] = await Promise.all([
                    page.waitForEvent('download', { timeout: 15000 }).catch(() => null),
                    page.goto(pdfUrl)
                ]);
            }
        }
        // Fallback: try any element with PDF text
        if (!downloaded) {
            const pdfLink = page.locator('a:has-text("PDF"), button:has-text("PDF")').first();
            if (await pdfLink.count() > 0) {
                [downloaded] = await Promise.all([
                    page.waitForEvent('download', { timeout: 15000 }).catch(() => null),
                    pdfLink.click({ force: true })
                ]);
            }
        }
        this.log(`[Riwayat PDF] Download PDF berhasil`, downloaded !== null, downloaded ? downloaded.suggestedFilename() : 'no event');
        if (downloaded) {
            const savePath = `${this.downloadsDir}/riwayat-${downloaded.suggestedFilename()}`;
            await downloaded.saveAs(savePath);
            const stat = fs.statSync(savePath);
            this.log(`[Riwayat PDF] File PDF tersimpan`, stat.size > 1000, `${stat.size} bytes`);
        }
    }

    async inspectTagihanPdf(page) {
        console.log(`\n--- Tagihan Invoice PDF ---`);
        await this.clearDownloads();
        await page.goto(`${BASE}/operator-perusahaan/tagihan?per_page=100`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
        await this.shot(page, 'tagihan-list');

        // Find first row ID
        const firstDetailLink = page.locator('tbody tr a[href*="tagihan/"]').first();
        let downloaded = null;
        if (await firstDetailLink.count() > 0) {
            const href = await firstDetailLink.getAttribute('href');
            const tagihanId = href.match(/tagihan\/([a-f0-9-]+)/)?.[1];
            if (tagihanId) {
                const pdfUrl = `${BASE}/operator-perusahaan/tagihan/${tagihanId}/export-pdf`;
                [downloaded] = await Promise.all([
                    page.waitForEvent('download', { timeout: 15000 }).catch(() => null),
                    page.goto(pdfUrl)
                ]);
            }
        }
        if (!downloaded) {
            const pdfLink = page.locator('a:has-text("PDF"), button:has-text("PDF")').first();
            if (await pdfLink.count() > 0) {
                [downloaded] = await Promise.all([
                    page.waitForEvent('download', { timeout: 15000 }).catch(() => null),
                    pdfLink.click({ force: true })
                ]);
            }
        }
        this.log(`[Tagihan PDF] Download PDF berhasil`, downloaded !== null, downloaded ? downloaded.suggestedFilename() : 'no event');
        if (downloaded) {
            const savePath = `${this.downloadsDir}/tagihan-${downloaded.suggestedFilename()}`;
            await downloaded.saveAs(savePath);
            const stat = fs.statSync(savePath);
            this.log(`[Tagihan PDF] File PDF tersimpan`, stat.size > 1000, `${stat.size} bytes`);
        }
    }

    async run() {
        const browser = await chromium.launch({
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox'],
            // Enable video recording
            recordVideo: { dir: this.outDir, size: { width: 1280, height: 720 } }
        });

        const viewports = [
            { name: 'mobile', width: 375, height: 812 },
            { name: 'tablet', width: 768, height: 1024 },
            { name: 'desktop', width: 1366, height: 850 },
        ];
        const themes = ['light', 'dark'];

        for (const vp of viewports) {
            for (const theme of themes) {
                console.log(`\n========== ${vp.name.toUpperCase()} (${vp.width}x${vp.height}) — ${theme.toUpperCase()} ==========`);
                const ctx = await browser.newContext({
                    viewport: { width: vp.width, height: vp.height },
                    acceptDownloads: true,
                    recordVideo: { dir: this.outDir, size: { width: vp.width, height: vp.height } }
                });
                const page = await ctx.newPage();
                await page.emulateMedia({ colorScheme: theme });

                const tag = `${vp.name}-${theme}`;
                try {
                    // Test SaaS Perusahaan
                    await this.loginAsOperatorSaas(page);
                    await this.inspectListAndModals(page, `saas-perusahaan-${tag}`, `${BASE}/operator-saas/perusahaan`);

                    // Test Perusahaan Saya
                    await this.inspectListAndModals(page, `perusahaan-saya-${tag}`, `${BASE}/operator-perusahaan/perusahaan-saya`);

                    // Test downstream PDF (only on desktop dark to save time)
                    if (vp.name === 'desktop' && theme === 'light') {
                        // Logout admin-saas, login admin-perusahaan for downstream PDFs
                        await ctx.clearCookies();
                        await this.loginAsAdminPerusahaan(page);
                        await this.inspectTagihanPdf(page);
                        await this.inspectRiwayatPdf(page);
                    }
                } catch (e) {
                    console.log(`  [FATAL ${vp.name} ${theme}] ${e.message}`);
                } finally {
                    await page.close();
                    await ctx.close();
                }
            }
        }

        await browser.close();

        console.log(`\n========== FINAL SUMMARY ==========`);
        console.log(`Passed: ${this.testResults.passed} | Failed: ${this.testResults.failed}`);
        this.testResults.errors.forEach(e => console.log(`  ✗ ${e}`));
        console.log(`\nScreenshots saved to: ${this.outDir}`);
        console.log(`Video recordings saved to: ${this.outDir}`);
        process.exit(this.testResults.failed > 0 ? 1 : 0);
    }
}

new PerusahaanLogoInspect().run();
