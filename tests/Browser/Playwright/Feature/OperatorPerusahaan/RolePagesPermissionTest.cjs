// Visual + functional test for all 3 role pages - dark mode + permission checklist
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

class RolePagesPermissionTest {
    constructor() {
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.outDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'RolePages', 'PermissionChecklist');
        this.testResults = { passed: 0, failed: 0, errors: [] };
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

    async loginOperatorPerusahaan(page) {
        await page.goto(`${this.baseUrl}/login-perusahaan`);
        await page.waitForLoadState('networkidle');
        const companyBtn = page.locator('button:has(.fa-building)').first();
        if (await companyBtn.count() > 0) {
            await companyBtn.click();
            await page.waitForTimeout(700);
            const search = page.locator('input[placeholder*="Cari perusahaan"]').first();
            await search.fill('Digital Media');
            await page.waitForTimeout(800);
            await page.locator('text=CV Digital Media Nusantara').first().click();
            await page.waitForTimeout(500);
        }
        await page.fill('input[type="email"]', 'admin@digitalmedia.id');
        await page.fill('input[type="password"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(4000);
    }

    async loginOperatorSaas(page) {
        await page.goto(`${this.baseUrl}/login-operator-saas`);
        await page.waitForLoadState('networkidle');
        await page.fill('input[type="email"]', 'superadmin@demo.test');
        await page.fill('input[type="password"]', 'password123');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(5000);
    }

    async testPage(page, pageName, url) {
        console.log(`\n=== ${pageName} (${url}) ===`);
        await page.goto(url);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1500);
        await this.shot(page, `${pageName}-01-list`);

        // 1) Open Create modal
        const tambahBtn = page.locator('button:has-text("Tambah")').first();
        await tambahBtn.click();
        await page.waitForTimeout(1200);
        await this.shot(page, `${pageName}-02-create-modal`);

        // Assert: permission section visible
        const permSearch = page.locator('input[placeholder*="Cari permission"]');
        this.log(`[${pageName}] Search input terlihat di modal Tambah`, await permSearch.count() > 0);

        // Assert: at least one group badge "X/Y" visible
        const groupBadges = await page.locator('span:has-text("/")').allTextContents();
        this.log(`[${pageName}] Badge "X/Y" per group terlihat`, groupBadges.length > 0, `${groupBadges.length} badges: ${groupBadges.slice(0, 3).join(', ')}`);

        // Assert: counter "X / Y dipilih"
        const counter = await page.locator('text=/\\d+\\s*\\/\\s*\\d+\\s* dipilih/').first();
        this.log(`[${pageName}] Counter "X / Y dipilih" terlihat`, await counter.count() > 0);

        // Assert: "Pilih Semua" button visible
        const pilihSemua = page.locator('button:has-text("Pilih Semua")');
        this.log(`[${pageName}] Tombol "Pilih Semua" terlihat`, await pilihSemua.count() > 0);

        // Test: type in search
        if (await permSearch.count() > 0) {
            await permSearch.fill('tagihan');
            await page.waitForTimeout(800);
            await this.shot(page, `${pageName}-03-create-search`);
            // Check filter reduced groups
            const filteredBadges = await page.locator('span:has-text("/")').allTextContents();
            this.log(`[${pageName}] Search filter bekerja (groups berkurang)`, filteredBadges.length < groupBadges.length, `${groupBadges.length} → ${filteredBadges.length}`);

            // Clear search
            await permSearch.fill('');
            await page.waitForTimeout(500);
        }

        // Test: click "Pilih Semua" - should select all visible
        if (await pilihSemua.count() > 0) {
            await pilihSemua.click();
            await page.waitForTimeout(500);
            await this.shot(page, `${pageName}-04-create-all-selected`);
            // Click "Bersihkan" to deselect
            const bersihkan = page.locator('button:has-text("Bersihkan")');
            this.log(`[${pageName}] Tombol berubah jadi "Bersihkan" setelah Pilih Semua`, await bersihkan.count() > 0);
            if (await bersihkan.count() > 0) {
                await bersihkan.click();
                await page.waitForTimeout(500);
            }
        }

        // Close modal
        const closeBtn = page.locator('button:has-text("Batal")').first();
        if (await closeBtn.count() > 0) await closeBtn.click();
        await page.waitForTimeout(500);

        // 2) Open Edit modal — pre-filled with existing role's permissions
        const editBtn = page.locator('button[title="Edit"]').first();
        if (await editBtn.count() > 0) {
            await editBtn.click();
            await page.waitForTimeout(1200);
            await this.shot(page, `${pageName}-05-edit-modal`);

            const editCounter = await page.locator('text=/\\d+\\s*\\/\\s*\\d+\\s* dipilih/').first();
            const editCounterText = await editCounter.count() > 0 ? await editCounter.textContent() : '';
            // Extract the "X" from "X / Y dipilih" and check it's > 0
            const m = editCounterText ? editCounterText.match(/(\d+)\s*\/\s*(\d+)\s* dipilih/) : null;
            const selected = m ? parseInt(m[1], 10) : 0;
            this.log(`[${pageName}] Edit modal pre-filled dengan permission existing`, selected > 0, `counter: ${editCounterText}`);

            // Close
            const cancelEdit = page.locator('button:has-text("Batal")').first();
            if (await cancelEdit.count() > 0) await cancelEdit.click();
            await page.waitForTimeout(500);
        }

        // 3) Open Detail modal — show grouped permissions
        const detailBtn = page.locator('button[title="Detail"]').first();
        if (await detailBtn.count() > 0) {
            await detailBtn.click();
            await page.waitForTimeout(1200);
            await this.shot(page, `${pageName}-06-detail-modal`);

            // Check permission grouped (Permission (N) + module names visible)
            const detailBody = await page.textContent('body');
            const hasPermissionHeader = detailBody.includes('Permission (');
            this.log(`[${pageName}] Detail modal menampilkan "Permission (N)" header`, hasPermissionHeader);

            // Close
            const closeDetail = page.locator('button:has-text("Tutup")').first();
            if (await closeDetail.count() > 0) await closeDetail.click();
            await page.waitForTimeout(500);
        }
    }

    async run() {
        const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
        const ctx = await browser.newContext({ viewport: { width: 1366, height: 850 } });
        const page = await ctx.newPage();

        // Force dark mode
        await page.emulateMedia({ colorScheme: 'dark' });

        try {
            // Test 1: Role Perusahaan
            await this.loginOperatorPerusahaan(page);
            await this.testPage(page, 'role-perusahaan', `${this.baseUrl}/operator-perusahaan/role-perusahaan`);

            // Test 2: Role Web Karyawan
            await this.testPage(page, 'role-web-karyawan', `${this.baseUrl}/operator-perusahaan/role-web-karyawan`);

            // Logout & re-login as operator saas
            await page.context().clearCookies();
            await this.loginOperatorSaas(page);
            await this.testPage(page, 'role-saas', `${this.baseUrl}/operator-saas/role-saas`);
        } catch (e) {
            console.log(`\n[FATAL] ${e.message}`);
            await this.shot(page, 'fatal');
        } finally {
            await browser.close();
        }

        console.log(`\n=== SUMMARY ===`);
        console.log(`Passed: ${this.testResults.passed} | Failed: ${this.testResults.failed}`);
        this.testResults.errors.forEach(e => console.log(`  ✗ ${e}`));
        process.exit(this.testResults.failed > 0 ? 1 : 0);
    }
}

new RolePagesPermissionTest().run();
