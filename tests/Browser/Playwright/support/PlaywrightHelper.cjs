const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// Baca .env sederhana (cukup untuk PLAYWRIGHT_BASE_URL)
// Tidak dependensi dotenv — test harus ringan.
function readEnvVar(key) {
    try {
        const envPath = path.join(__dirname, '..', '..', '..', '..', '.env');
        if (!fs.existsSync(envPath)) return null;
        const content = fs.readFileSync(envPath, 'utf8');
        const lines = content.split(/\r?\n/);
        for (const line of lines) {
            const trimmed = line.trim();
            if (trimmed.startsWith('#') || !trimmed.includes('=')) continue;
            const [k, ...vParts] = trimmed.split('=');
            if (k.trim() !== key) continue;
            let v = vParts.join('=').trim();
            // Strip quotes (single or double)
            v = v.replace(/^["']|["']$/g, '');
            return v || null;
        }
    } catch (e) {}
    return null;
}

const DEFAULT_BASE_URL = 'http://erp-rt-rw-net.test';

class PlaywrightHelper {
    constructor(baseUrl) {
        // Prioritas: constructor arg > env var > default
        this.baseUrl = baseUrl
            || process.env.PLAYWRIGHT_BASE_URL
            || readEnvVar('PLAYWRIGHT_BASE_URL')
            || DEFAULT_BASE_URL;
        this.browser = null;
        this.context = null;
        this.page = null;
        this.tempDir = path.join(__dirname, '..', 'result');
        this.screenshotCount = 0;
    }

    async launch(opts = {}) {
        // STANDARDS §7.1 — headed untuk debug, CI bisa gate via PLAYWRIGHT_HEADLESS=true
        const headlessEnv = process.env.PLAYWRIGHT_HEADLESS === 'true';
        const headless = opts.headless ?? (headlessEnv ? true : false);
        const slowMo = opts.slowMo ?? (headless ? 0 : 350);
        this.browser = await chromium.launch({
            headless,
            slowMo,
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });
        const recordVideo = opts.recordVideo ?? (process.env.PLAYWRIGHT_VIDEO === 'true');
        this.context = await this.browser.newContext({
            viewport: { width: opts.width || 1280, height: opts.height || 720 },
            ignoreHTTPSErrors: true,
            ...(recordVideo ? { recordVideo: { dir: path.join(__dirname, '..', 'videos'), size: { width: 1280, height: 720 } } } : {}),
        });
        this.page = await this.context.newPage();
        // STANDARDS §7.2 — capture console errors untuk deep verify
        this.consoleErrors = [];
        this.page.on('pageerror', e => this.consoleErrors.push('pageerror: ' + e.message));
        this.page.on('console', m => { if (m.type() === 'error') this.consoleErrors.push('console.error: ' + m.text()); });

        if (!opts.skipGoto) {
            await this.page.goto(this.baseUrl);
            await this.page.waitForLoadState('networkidle');
        }
    }

    getConsoleErrors() { return this.consoleErrors || []; }
    assertNoConsoleErrors() {
        if (this.consoleErrors && this.consoleErrors.length > 0) {
            throw new Error('JS errors detected: ' + this.consoleErrors.join('; '));
        }
    }

    async close() {
        if (this.browser) {
            await this.browser.close();
        }
    }

    async loginAsAdminPerusahaan(email, password) {
        // STANDARDS: login flow via .fa-building button + company dropdown (CLAUDE.md)
        await this.page.goto(`${this.baseUrl}/login-perusahaan`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(800);
        // Jika ada tombol pilih perusahaan (.fa-building), klik dulu
        const buildingBtn = this.page.locator('button:has(.fa-building)').first();
        if (await buildingBtn.count() > 0 && await buildingBtn.isVisible().catch(() => false)) {
            await buildingBtn.click();
            await this.page.waitForTimeout(800);
            // Pilih perusahaan pertama yang tersedia
            const firstCompany = this.page.locator('button').filter({ hasText: /PT |CV |UD / }).first();
            if (await firstCompany.count() > 0) {
                await firstCompany.click();
                await this.page.waitForTimeout(500);
            }
        }
        await this.page.fill('input[type="email"]', email);
        await this.page.fill('input[type="password"]', password);
        await this.page.click('button[type="submit"]');
        await this.page.waitForTimeout(3000);
    }

    async loginAsAdminSaaS(email, password) {
        await this.page.goto(`${this.baseUrl}/login-operator-saas`);
        await this.page.waitForLoadState('networkidle');

        await this.page.fill('input[type="email"]', email);
        await this.page.fill('input[type="password"]', password);
        await this.page.click('button[type="submit"]');
        await this.page.waitForLoadState('networkidle');
    }

    async loginAsKaryawan(email, password) {
        await this.page.goto(`${this.baseUrl}/login-karyawan`);
        await this.page.waitForLoadState('networkidle');

        await this.page.fill('input[type="email"]', email);
        await this.page.fill('input[type="password"]', password);
        await this.page.click('button[type="submit"]');
        await this.page.waitForLoadState('networkidle');
    }

    async loginAsPelanggan(email, password) {
        await this.page.goto(`${this.baseUrl}/login-pelanggan`);
        await this.page.waitForLoadState('networkidle');

        await this.page.fill('input[type="email"]', email);
        await this.page.fill('input[type="password"]', password);
        await this.page.click('button[type="submit"]');
        await this.page.waitForLoadState('networkidle');
    }

    async screenshot(customPath = null) {
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const screenshotDir = path.join(this.tempDir, customPath || 'screenshots');

        if (!fs.existsSync(screenshotDir)) {
            fs.mkdirSync(screenshotDir, { recursive: true });
        }

        this.screenshotCount++;
        const filename = `${String(this.screenshotCount).padStart(3, '0')}-${timestamp}.png`;
        const filepath = path.join(screenshotDir, filename);

        await this.page.screenshot({ path: filepath, fullPage: false });
        console.log(`[Screenshot] ${filepath}`);
        return filepath;
    }

    async screenshotFullPage(customPath = null) {
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const screenshotDir = path.join(this.tempDir, customPath || 'screenshots');

        if (!fs.existsSync(screenshotDir)) {
            fs.mkdirSync(screenshotDir, { recursive: true });
        }

        this.screenshotCount++;
        const filename = `${String(this.screenshotCount).padStart(3, '0')}-${timestamp}.png`;
        const filepath = path.join(screenshotDir, filename);

        await this.page.screenshot({ path: filepath, fullPage: true });
        console.log(`[Screenshot] ${filepath}`);
        return filepath;
    }

    async waitForSelector(selector, timeout = 5000) {
        await this.page.waitForSelector(selector, { timeout, state: 'visible' });
    }

    async waitForText(text, timeout = 5000) {
        await this.page.waitForFunction(
            (searchText) => document.body.innerText.includes(searchText),
            text,
            { timeout }
        );
    }

    async click(selector) {
        await this.waitForSelector(selector);
        await this.page.click(selector);
    }

    async fill(selector, value) {
        await this.waitForSelector(selector);
        await this.page.fill(selector, value);
    }

    async selectOption(selector, value) {
        await this.waitForSelector(selector);
        await this.page.selectOption(selector, value);
    }

    async pressEnter(selector) {
        await this.page.focus(selector);
        await this.page.press(selector, 'Enter');
    }

    async pause(ms) {
        await this.page.waitForTimeout(ms);
    }

    getCurrentUrl() {
        return this.page.url();
    }

    async isVisible(selector) {
        const el = await this.page.$(selector);
        return el ? await el.isVisible() : false;
    }

    async isChecked(selector) {
        const el = await this.page.$(selector);
        return el ? await el.isChecked() : false;
    }

    async getText(selector) {
        const el = await this.page.$(selector);
        return el ? await el.innerText() : null;
    }

    async getValue(selector) {
        const el = await this.page.$(selector);
        return el ? await el.inputValue() : null;
    }

    async getAttribute(selector, attribute) {
        const el = await this.page.$(selector);
        return el ? await el.getAttribute(attribute) : null;
    }

    async getAllAttributes(selector, attribute) {
        const elements = await this.page.$$(selector);
        const results = [];
        for (const el of elements) {
            results.push(await el.getAttribute(attribute));
        }
        return results;
    }

    async searchInput(text) {
        await this.fill('input[placeholder="Cari..."]', text);
        await this.pressEnter('input[placeholder="Cari..."]');
        await this.pause(1500);
    }

    async clickButtonWithTitle(title) {
        const selector = `button[title="${title}"]`;
        await this.click(selector);
    }

    async clickButtonWithText(text) {
        const selector = `button:has-text("${text}")`;
        await this.click(selector);
    }

    async getTableRows() {
        return await this.page.$$('tbody tr');
    }

    async getTableRowCount() {
        const rows = await this.getTableRows();
        return rows.length;
    }

    async getColumnValue(rowIndex, colIndex) {
        const rows = await this.getTableRows();
        if (rows[rowIndex]) {
            const cells = await rows[rowIndex].$$('td');
            if (cells[colIndex]) {
                return await cells[colIndex].innerText();
            }
        }
        return null;
    }

    async getTableHeaders() {
        const headers = await this.page.$$('thead th');
        const result = [];
        for (const h of headers) {
            result.push(await h.innerText());
        }
        return result;
    }

    async clickTableHeader(columnName) {
        const headers = await this.page.$$('thead th');
        for (const h of headers) {
            const text = await h.innerText();
            if (text.includes(columnName)) {
                await h.click();
                await this.pause(1500);
                return;
            }
        }
    }

    async clickCheckbox(rowIndex) {
        const rows = await this.getTableRows();
        if (rows[rowIndex]) {
            const checkbox = await rows[rowIndex].$('input[type="checkbox"]');
            if (checkbox) {
                await checkbox.check();
            }
        }
    }

    async checkAllCheckboxes() {
        const checkboxes = await this.page.$$('thead input[type="checkbox"]');
        if (checkboxes.length > 0) {
            await checkboxes[0].check();
        }
    }

    async getAlertText() {
        await this.pause(500);
        const alert = await this.page.$('.bg-red-100, .bg-emerald-100, .bg-amber-100');
        if (alert) {
            return await alert.innerText();
        }
        return null;
    }

    async waitForModal(title = null) {
        if (title) {
            await this.waitForText(title);
        } else {
            await this.pause(500);
        }
    }

    async closeModal() {
        const closeButtons = await this.page.$$('button:has-text("Tutup"), button:has-text("Batal"), button[class*="bg-gray"]');
        for (const btn of closeButtons) {
            const isVisible = await btn.isVisible();
            if (isVisible) {
                await btn.click();
                await this.pause(500);
                return;
            }
        }
    }

    async confirmDelete() {
        const deleteButton = await this.page.$('button:has-text("Hapus"), button.bg-red-600');
        if (deleteButton) {
            await deleteButton.click();
            await this.pause(1500);
        }
    }

    async getPaginationInfo() {
        const paginationText = await this.getText('.flex.items-center.gap-2.text-sm.text-gray-600');
        return paginationText;
    }

    async clickPage(pageNum) {
        const pageButtons = await this.page.$$('button:has-text("' + pageNum + '")');
        for (const btn of pageButtons) {
            const isVisible = await btn.isVisible();
            if (isVisible) {
                await btn.click();
                await this.pause(1500);
                return;
            }
        }
    }

    async changePerPage(value) {
        const select = await this.page.$('.flex.items-center.gap-2.text-sm select, select[class*="per_page"]');
        if (select) {
            await select.selectOption(value.toString());
            await this.pause(1500);
        }
    }

    async getCurrentPageFromUrl() {
        const url = this.getCurrentUrl();
        const match = url.match(/[?&]page=(\d+)/);
        return match ? parseInt(match[1]) : 1;
    }

    async getSelectedFiltersCount() {
        const resetButton = await this.page.$('button:has-text("Reset filter")');
        return resetButton ? 1 : 0;
    }

    async resetFilters() {
        const resetButton = await this.page.$('button:has-text("Reset filter")');
        if (resetButton) {
            await resetButton.click();
            await this.pause(1000);
        }
    }

    async applyFilter() {
        const filterButton = await this.page.$('button:has-text("Filter")');
        if (filterButton) {
            await filterButton.click();
            await this.pause(1500);
        }
    }

    async selectStatusFilter(value) {
        const select = await this.page.$('select:has(option[value="' + value + '"])');
        if (select) {
            await select.selectOption(value);
        }
    }

    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    async consoleLogCapture() {
        const logs = [];
        this.page.on('console', msg => {
            if (msg.type() === 'error') {
                logs.push(`[ERROR] ${msg.text()}`);
            }
        });
        return logs;
    }
}

module.exports = PlaywrightHelper;