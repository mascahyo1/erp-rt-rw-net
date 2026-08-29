// Quick visual inspection of role pages - dark mode + modals + permission checklist
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class RolePagesInspect {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.outDir = path.join(__dirname, '..', 'result', '_INSPECT');
        this.screenshotCount = 0;
    }
    async shot(page, name) {
        if (!fs.existsSync(this.outDir)) fs.mkdirSync(this.outDir, { recursive: true });
        this.screenshotCount++;
        const f = path.join(this.outDir, `${String(this.screenshotCount).padStart(2,'0')}-${name}.png`);
        await page.screenshot({ path: f, fullPage: false });
        console.log(`  [SS] ${f}`);
        return f;
    }

    async loginAndGo(page, email, password, companyName, targetUrl) {
        // Use operator-saas login (no company selector, no 2-step)
        const isSaas = targetUrl.includes('/operator-saas/');
        if (isSaas) {
            await page.goto(`${BASE}/login-operator-saas`);
            await page.waitForLoadState('networkidle');
            await page.fill('input[type="email"]', email);
            await page.fill('input[type="password"]', password);
            await page.click('button[type="submit"]');
            await page.waitForTimeout(5000);
        } else {
            await page.goto(`${BASE}/login-perusahaan`);
            await page.waitForLoadState('networkidle');
            const companyBtn = page.locator('button:has(.fa-building)').first();
            if (await companyBtn.count() > 0) {
                await companyBtn.click();
                await page.waitForTimeout(700);
                const companySearch = page.locator('input[placeholder*="Cari perusahaan"]').first();
                await companySearch.fill(companyName);
                await page.waitForTimeout(800);
                await page.locator(`text=${companyName}`).first().click();
                await page.waitForTimeout(500);
            }
            await page.fill('input[type="email"]', email);
            await page.fill('input[type="password"]', password);
            await page.click('button[type="submit"]');
            await page.waitForTimeout(4000);
        }
        await page.goto(targetUrl);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
    }

    async inspectModal(page, name, action) {
        // action = 'create' | 'edit' | 'detail'
        if (action === 'create') {
            const btn = await page.$('button:has-text("Tambah")');
            if (btn) await btn.click();
        } else if (action === 'edit') {
            const btn = await page.$('button[title="Edit"]');
            if (btn) await btn.click();
        } else {
            const btn = await page.$('button[title="Detail"]');
            if (btn) await btn.click();
        }
        await page.waitForTimeout(1500);
        await this.shot(page, `${name}-${action}-modal`);
        // close
        const close = page.locator('button:has-text("Batal"), button:has-text("Tutup")').first();
        if (await close.count() > 0) await close.click();
        await page.waitForTimeout(500);
    }

    async run() {
        const browser = await chromium.launch({ slowMo: 350, headless: false, args: ['--no-sandbox'] });
        const ctx = await browser.newContext({ viewport: { width: 1366, height: 850 } });
        const page = await ctx.newPage();

        // Force dark mode via media emulation
        await page.emulateMedia({ colorScheme: 'dark' });

        // ---- OPERATOR SAAS ROLE-SAAS ----
        console.log('\n=== /operator-saas/role-saas ===');
        await this.loginAndGo(page, 'superadmin@demo.test', 'password123', '', `${BASE}/operator-saas/role-saas`);
        await this.shot(page, 'saas-role-list');
        await this.inspectModal(page, 'saas-role', 'create');
        await this.inspectModal(page, 'saas-role', 'edit');
        await this.inspectModal(page, 'saas-role', 'detail');

        // ---- OPERATOR PERUSAHAAN ROLE-PERUSAHAAN ----
        console.log('\n=== /operator-perusahaan/role-perusahaan ===');
        await this.loginAndGo(page, 'admin@digitalmedia.id', 'password123', 'CV Digital Media Nusantara', `${BASE}/operator-perusahaan/role-perusahaan`);
        await this.shot(page, 'perusahaan-role-list');
        await this.inspectModal(page, 'perusahaan-role', 'create');
        await this.inspectModal(page, 'perusahaan-role', 'edit');
        await this.inspectModal(page, 'perusahaan-role', 'detail');

        // ---- OPERATOR PERUSAHAAN ROLE-WEB-KARYAWAN ----
        console.log('\n=== /operator-perusahaan/role-web-karyawan ===');
        await page.goto(`${BASE}/operator-perusahaan/role-web-karyawan`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);
        await this.shot(page, 'webkaryawan-role-list');
        await this.inspectModal(page, 'webkaryawan-role', 'create');
        await this.inspectModal(page, 'webkaryawan-role', 'edit');
        await this.inspectModal(page, 'webkaryawan-role', 'detail');

        await browser.close();
        console.log(`\nDone. ${this.screenshotCount} screenshots saved to ${this.outDir}`);
    }
}

new RolePagesInspect().run();
