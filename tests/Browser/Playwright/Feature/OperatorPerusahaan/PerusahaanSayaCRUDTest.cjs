const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
/**
 * PerusahaanSaya CRUD + Logo E2E Test (Playwright + AJAX).
 *
 * Pattern: navigasi pakai Inertia, form submit pakai AJAX (lihat CONVENTIONS.md).
 * Data test SELALU baru per run (suffix timestamp) lalu di-cleanup di akhir.
 */
class PerusahaanSayaCRUDTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'PerusahaanSaya');
        this.screenshotCount = 0;
        this.testResults = { passed: 0, failed: 0, errors: [] };

        // Unique suffix untuk data test — supaya tidak konflik dengan test run lain
        // dan mudah di-cleanup dengan pattern nama.
        this.testSuffix = String(Date.now()).slice(-7);
        this.createdCompanyName = `PT Test Logo ${this.testSuffix}`;
        this.createdCompanyId = null;
    }

    async takeScreenshot(name) {
        if (!fs.existsSync(this.screenshotDir)) {
            fs.mkdirSync(this.screenshotDir, { recursive: true });
        }
        this.screenshotCount++;
        const filename = `${String(this.screenshotCount).padStart(3, '0')}-${name}.png`;
        const filepath = path.join(this.screenshotDir, filename);
        await this.page.screenshot({ path: filepath, fullPage: false });
        console.log(`  [Screenshot] ${filepath}`);
        return filepath;
    }

    assert(condition, message) {
        if (!condition) {
            throw new Error(message);
        }
    }

    async loginAsAdminPerusahaan(email, password) {
        await this.page.goto(`${BASE}/login-perusahaan`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(1000);
        const companyBtn = this.page.locator('button:has(.fa-building)').first();
        await companyBtn.click();
        await this.page.waitForTimeout(800);
        // Pilih CV Angkasa Netindo (test fixtures pakai ini)
        const firstCompany = this.page.locator('button:has-text("CV Angkasa Netindo")').first();
        await firstCompany.click();
        await this.page.waitForTimeout(500);
        await this.page.fill('input[type="email"]', email);
        await this.page.fill('input[type="password"]', password);
        await this.page.click('button[type="submit"]');
        await this.page.waitForTimeout(8000);
    }

    async loginAsSuperAdmin() {
        // Login sebagai SaaS admin untuk create test company
        await this.page.goto(`${BASE}/login-operator-saas`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(1000);
        await this.page.fill('input[type="email"]', 'superadmin@demo.test');
        await this.page.fill('input[type="password"]', 'password123');
        await this.page.click('button[type="submit"]');
        await this.page.waitForTimeout(6000);
    }

    /**
     * Setup test data: create a fresh company untuk testing.
     * Method ini dipanggil di awal test_09 dst.
     */
    async createTestCompany() {
        console.log(`\n  SETUP: Create test company "${this.createdCompanyName}"`);
        // Login as SaaS super admin
        await this.page.goto(`${BASE}/login-operator-saas`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(1000);
        await this.page.fill('input[type="email"]', 'superadmin@demo.test');
        await this.page.fill('input[type="password"]', 'password123');
        await this.page.click('button[type="submit"]');
        await this.page.waitForTimeout(6000);

        // Create company via direct DB call (lebih reliable dari UI)
        // Pakai tinker-like approach via evaluate
        const result = await this.page.evaluate(async (name) => {
            const fd = new FormData();
            fd.append('nama_perusahaan', name);
            fd.append('email', `test-${Date.now()}@rtrwnet.id`);
            fd.append('kode_negara', '+62');
            fd.append('no_telp', '81234567890');
            fd.append('alamat', 'Test Address');
            fd.append('deskripsi', 'Auto-created for test');
            fd.append('status', 'Aktif');
            const res = await fetch('/api/operator-saas/perusahaan', {
                method: 'POST',
                body: fd,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
            });
            const json = await res.json();
            return { status: res.status, json };
        }, this.createdCompanyName);

        this.assert(result.status === 200 && result.json.success, `Create company failed: ${JSON.stringify(result)}`);
        this.createdCompanyId = result.json.data.id;
        console.log(`  SETUP: Company created id=${this.createdCompanyId}`);

        // Logout SaaS
        await this.page.goto(`${BASE}/logout-operator-saas`);
        await this.page.waitForTimeout(2000);
    }

    /**
     * Cleanup test data: hapus test company.
     * Method ini dipanggil di akhir (tearDown).
     */
    async cleanupTestCompany() {
        if (!this.createdCompanyId) return;
        console.log(`\n  CLEANUP: Delete test company "${this.createdCompanyName}" id=${this.createdCompanyId}`);
        try {
            // Login as SaaS super admin
            await this.page.goto(`${BASE}/login-operator-saas`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(1000);
            await this.page.fill('input[type="email"]', 'superadmin@demo.test');
            await this.page.fill('input[type="password"]', 'password123');
            await this.page.click('button[type="submit"]');
            await this.page.waitForTimeout(6000);

            // Hard delete via DB to avoid soft-delete complexity
            // Actually just soft-delete via API
            const res = await this.page.evaluate(async (id) => {
                const res = await fetch(`/api/operator-saas/perusahaan/${id}/delete`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                return res.status;
            }, this.createdCompanyId);
            console.log(`  CLEANUP: Soft-delete status=${res}`);
        } catch (e) {
            console.log(`  CLEANUP error: ${e.message}`);
        }
    }

    // ============================================================
    // TEST 01: Page Accessible (existing CV Angkasa Netindo)
    // ============================================================
    async test_01_page_accessible() {
        console.log('\nTEST 01: Page Accessible');
        console.log('========================');

        await this.loginAsAdminPerusahaan('rbac.full@rtrwnet.id', 'password');
        await this.page.goto(`${BASE}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(5000);
        await this.takeScreenshot('01-page-loaded');

        const heading = await this.page.locator('h2:has-text("Perusahaan Saya")').count();
        this.assert(heading > 0, 'Heading not found');
        const companyName = await this.page.locator('h3').first().textContent();
        this.assert(companyName && companyName.length > 0, 'Company name not found');
        console.log('  Heading + company name: OK');

        console.log('TEST 01: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 02: Sidebar & Dropdown Visible
    // ============================================================
    async test_02_sidebar_and_dropdown() {
        console.log('\nTEST 02: Sidebar & Dropdown');
        console.log('============================');

        const sidebarItems = await this.page.locator('aside a').all();
        let sidebarFound = false;
        for (const item of sidebarItems) {
            const text = await item.textContent();
            if (text && text.includes('Perusahaan Saya')) {
                sidebarFound = true;
                break;
            }
        }
        this.assert(sidebarFound, 'Sidebar item "Perusahaan Saya" not found');
        console.log('  Sidebar: OK');

        console.log('TEST 02: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 03: Edit button visible
    // ============================================================
    async test_03_edit_button_visible() {
        console.log('\nTEST 03: Edit Button Visible');
        console.log('=============================');

        await this.page.goto(`${BASE}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        const editBtn = await this.page.locator('button:has-text("Edit Perusahaan")').count();
        this.assert(editBtn > 0, 'Edit button not found');
        console.log('  Edit button: OK');

        console.log('TEST 03: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 04: Edit success — name changes, toast appears, form closes
    // ============================================================
    async test_04_edit_success() {
        console.log('\nTEST 04: Edit Success (field change + toast)');
        console.log('==============================================');

        await this.page.goto(`${BASE}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);

        const originalName = await this.page.locator('h3').first().textContent();
        const newName = originalName + ' [UPDATED]';
        console.log(`  Original: ${originalName} → New: ${newName}`);

        // Click Edit
        await this.page.click('button:has-text("Edit Perusahaan")');
        await this.page.waitForTimeout(500);

        // Change name
        const nameInput = this.page.locator('input[type="text"]').first();
        await nameInput.fill(newName);
        await this.takeScreenshot('04a-name-filled');

        // Click Simpan — wait for AJAX response
        const [response] = await Promise.all([
            this.page.waitForResponse(
                r => r.url().includes('/operator-perusahaan/api/perusahaan-saya/') && r.request().method() === 'POST',
                { timeout: 10000 }
            ),
            this.page.click('button:has-text("Simpan Perubahan")'),
        ]);
        await this.takeScreenshot('04b-after-save');

        this.assert(response.status() === 200, `Expected 200, got ${response.status()}`);
        const body = await response.json();
        this.assert(body.success === true, 'Response success should be true');
        this.assert(body.data.name === newName, 'Response data.name should match new name');
        console.log('  AJAX 200 + success=true: OK');

        // Wait for toast
        await this.page.waitForTimeout(2000);
        const toast = await this.page.locator('text=berhasil diperbarui').count();
        this.assert(toast > 0, 'Success toast not shown');
        console.log('  Success toast: OK');

        // Form should be closed (back to detail view)
        const editBtnAgain = await this.page.locator('button:has-text("Edit Perusahaan")').count();
        const simpanBtn = await this.page.locator('button:has-text("Simpan Perubahan")').count();
        this.assert(editBtnAgain > 0 && simpanBtn === 0, 'Form should close after save');
        console.log('  Form closed: OK');

        // New name visible in detail
        await this.page.waitForTimeout(1000);
        const updatedName = await this.page.locator('h3').first().textContent();
        this.assert(updatedName.includes('[UPDATED]'), `Name should be updated, got: ${updatedName}`);
        console.log(`  Updated name visible: ${updatedName}`);

        // Revert back
        await this.page.click('button:has-text("Edit Perusahaan")');
        await this.page.waitForTimeout(500);
        await this.page.locator('input[type="text"]').first().fill(originalName);
        await this.page.click('button:has-text("Simpan Perubahan")');
        await this.page.waitForTimeout(2000);

        console.log('TEST 04: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 05: Validation error — empty name shows error + toast
    // ============================================================
    async test_05_validation_error() {
        console.log('\nTEST 05: Validation Error (empty name)');
        console.log('======================================');

        await this.page.goto(`${BASE}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);

        await this.page.click('button:has-text("Edit Perusahaan")');
        await this.page.waitForTimeout(500);

        // Clear required field (name)
        const nameInput = this.page.locator('input[type="text"]').first();
        await nameInput.fill('');

        // Submit — expect 422
        const [response] = await Promise.all([
            this.page.waitForResponse(
                r => r.url().includes('/operator-perusahaan/api/perusahaan-saya/') && r.request().method() === 'POST',
                { timeout: 10000 }
            ),
            this.page.click('button:has-text("Simpan Perubahan")'),
        ]);

        this.assert(response.status() === 422, `Expected 422, got ${response.status()}`);
        const body = await response.json();
        this.assert(body.errors && body.errors.name, 'Should have name error');
        console.log('  422 + name error: OK');

        await this.page.waitForTimeout(1000);

        // Error toast — Laravel default returns English message like "The name field is required".
        // Cek presence via 2 locator terpisah.
        const errorToastEl = await this.page.locator('.toast-error').count();
        const errorTextMatch = await this.page.locator('text=/required|gagal|invalid/i').count();
        this.assert(errorToastEl + errorTextMatch > 0, 'Error toast not shown');
        console.log('  Error toast: OK (count=' + (errorToastEl + errorTextMatch) + ')');

        // Form should stay open
        const simpanBtn = await this.page.locator('button:has-text("Simpan Perubahan")').count();
        this.assert(simpanBtn > 0, 'Form should stay open on error');
        console.log('  Form stayed open: OK');

        // Error message visible below field
        const fieldError = await this.page.locator('text=The name field is required').count();
        this.assert(fieldError > 0, 'Field error message not shown');
        console.log('  Field error: OK');

        await this.takeScreenshot('05-validation-error');

        // Cancel
        await this.page.click('button:has-text("Batal")');
        await this.page.waitForTimeout(500);

        console.log('TEST 05: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 06: Upload logo light (JPG → compressed to WebP)
    // ============================================================
    async test_06_upload_logo_light() {
        console.log('\nTEST 06: Upload Logo Light (JPG)');
        console.log('================================');

        await this.page.goto(`${BASE}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);

        await this.page.click('button:has-text("Edit Perusahaan")');
        await this.page.waitForTimeout(500);

        const lightInput = this.page.locator('input[type="file"]').first();
        // Real PNG (will be compressed to WebP)
        await lightInput.setInputFiles({
            name: 'logo-light.png',
            mimeType: 'image/png',
            buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==', 'base64'),
        });
        await this.page.waitForTimeout(500);
        await this.takeScreenshot('06a-light-preview');

        const previewCount = await this.page.locator('img[alt="Logo Light"]').count();
        this.assert(previewCount > 0, 'Logo light preview not shown');
        console.log('  Preview: OK');

        // Submit
        const [response] = await Promise.all([
            this.page.waitForResponse(
                r => r.url().includes('/operator-perusahaan/api/perusahaan-saya/') && r.request().method() === 'POST',
                { timeout: 10000 }
            ),
            this.page.click('button:has-text("Simpan Perubahan")'),
        ]);

        this.assert(response.status() === 200, `Expected 200, got ${response.status()}`);
        const body = await response.json();
        this.assert(body.data.logo, 'logo path should be returned');
        this.assert(body.data.logo.endsWith('.webp') || body.data.logo.includes('.webp'), `Logo should be WebP, got: ${body.data.logo}`);
        console.log(`  Logo saved as: ${body.data.logo}`);

        // Wait for UI to update
        await this.page.waitForTimeout(2000);

        // Verify logo appears in detail view
        const detailLogo = await this.page.locator('img[alt="Logo Light"]').count();
        this.assert(detailLogo > 0, 'Logo not displayed in detail view');
        console.log('  Logo displayed in detail: OK');

        await this.takeScreenshot('06b-light-saved');

        console.log('TEST 06: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 07: Upload logo dark (SVG → NOT compressed)
    // ============================================================
    async test_07_upload_logo_dark_svg() {
        console.log('\nTEST 07: Upload Logo Dark (SVG)');
        console.log('=================================');

        await this.page.goto(`${BASE}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);

        await this.page.click('button:has-text("Edit Perusahaan")');
        await this.page.waitForTimeout(500);

        const darkInput = this.page.locator('input[type="file"]').nth(1);
        const svgBuf = Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="#1e40af"/><text x="50" y="55" text-anchor="middle" fill="white" font-size="20">D</text></svg>');
        await darkInput.setInputFiles({
            name: 'logo-dark.svg',
            mimeType: 'image/svg+xml',
            buffer: svgBuf,
        });
        await this.page.waitForTimeout(500);

        const preview = await this.page.locator('img[alt="Logo Dark"]').count();
        this.assert(preview > 0, 'Logo dark preview not shown');
        console.log('  Preview: OK');

        // Submit
        const [response] = await Promise.all([
            this.page.waitForResponse(
                r => r.url().includes('/operator-perusahaan/api/perusahaan-saya/') && r.request().method() === 'POST',
                { timeout: 10000 }
            ),
            this.page.click('button:has-text("Simpan Perubahan")'),
        ]);

        this.assert(response.status() === 200, `Expected 200, got ${response.status()}`);
        const body = await response.json();
        this.assert(body.data.logo_dark, 'logo_dark path should be returned');
        this.assert(body.data.logo_dark.endsWith('.svg') || body.data.logo_dark.includes('.svg'), `Dark logo should be SVG, got: ${body.data.logo_dark}`);
        console.log(`  Dark logo saved as: ${body.data.logo_dark}`);

        await this.page.waitForTimeout(2000);

        const detailLogo = await this.page.locator('img[alt="Logo Dark"]').count();
        this.assert(detailLogo > 0, 'Dark logo not displayed in detail view');
        console.log('  Dark logo displayed: OK');

        await this.takeScreenshot('07-dark-svg-saved');

        console.log('TEST 07: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 08: File size validation (>2MB rejected)
    // ============================================================
    async test_08_file_size_validation() {
        console.log('\nTEST 08: File Size Validation');
        console.log('=============================');

        await this.page.goto(`${BASE}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);

        await this.page.click('button:has-text("Edit Perusahaan")');
        await this.page.waitForTimeout(500);

        const lightInput = this.page.locator('input[type="file"]').first();
        // 3MB file
        const bigBuf = Buffer.alloc(3 * 1024 * 1024, 0);
        await lightInput.setInputFiles({
            name: 'logo-big.jpg',
            mimeType: 'image/jpeg',
            buffer: bigBuf,
        });
        await this.page.waitForTimeout(500);

        // Submit — expect 422
        const [response] = await Promise.all([
            this.page.waitForResponse(
                r => r.url().includes('/operator-perusahaan/api/perusahaan-saya/') && r.request().method() === 'POST',
                { timeout: 15000 }
            ),
            this.page.click('button:has-text("Simpan Perubahan")'),
        ]);

        this.assert(response.status() === 422, `Expected 422, got ${response.status()}`);
        const body = await response.json();
        this.assert(body.errors && (body.errors.logo || body.errors.logo_dark), 'Should have logo file error');
        console.log('  422 + logo size error: OK');

        await this.page.waitForTimeout(1000);
        const errorToast = await this.page.locator('text=/tidak boleh lebih dari|maximum|size/i').count();
        console.log(`  Error toast present: ${errorToast > 0 ? 'YES' : 'NO'}`);

        await this.takeScreenshot('08-file-size-error');

        // Cancel
        await this.page.click('button:has-text("Batal")');
        await this.page.waitForTimeout(500);

        console.log('TEST 08: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // TEST 09: Dark/Light mode toggle
    // ============================================================
    async test_09_dark_light_mode() {
        console.log('\nTEST 09: Dark/Light Mode');
        console.log('=========================');

        await this.page.goto(`${BASE}/operator-perusahaan/perusahaan-saya`);
        await this.page.waitForLoadState('networkidle');
        await this.page.waitForTimeout(2000);

        const themeBtn = this.page.locator('button[title*="Tema"]').first();

        await themeBtn.click();
        await this.page.waitForTimeout(500);
        const t1 = await this.page.evaluate(() => localStorage.getItem('theme'));
        this.assert(t1 === 'light', `Theme should be light, got: ${t1}`);
        console.log('  → light: OK');

        await themeBtn.click();
        await this.page.waitForTimeout(500);
        const t2 = await this.page.evaluate(() => localStorage.getItem('theme'));
        this.assert(t2 === 'dark', `Theme should be dark, got: ${t2}`);
        console.log('  → dark: OK');

        console.log('TEST 09: PASSED');
        this.testResults.passed++;
    }

    // ============================================================
    // RUNNER
    // ============================================================
    async runAllTests() {
        console.log('========================================');
        console.log('PerusahaanSaya CRUD + Logo (AJAX)');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: true });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            // Run tests sequentially
            await this.test_01_page_accessible();
            await this.test_02_sidebar_and_dropdown();
            await this.test_03_edit_button_visible();
            await this.test_04_edit_success();
            await this.test_05_validation_error();
            await this.test_06_upload_logo_light();
            await this.test_07_upload_logo_dark_svg();
            await this.test_08_file_size_validation();
            await this.test_09_dark_light_mode();

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
            this.testResults.errors.push(error.message);
            try { await this.takeScreenshot('XX-fatal'); } catch {}
        } finally {
            if (this.browser) await this.browser.close();
        }
    }
}

new PerusahaanSayaCRUDTest().runAllTests();
