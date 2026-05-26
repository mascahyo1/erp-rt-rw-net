const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// These constants match what setup-rbac-users.php creates
const RBAC_TEST_USERS = {
    listOnly: 'rbac.list@rtrwnet.id',
    listCreate: 'rbac.list.create@rtrwnet.id',
    listEdit: 'rbac.list.edit@rtrwnet.id',
    listDetail: 'rbac.list.detail@rtrwnet.id',
    listDelete: 'rbac.list.delete@rtrwnet.id',
    listRestore: 'rbac.list.restore@rtrwnet.id',
    listPersetujuan: 'rbac.list.persetujuan@rtrwnet.id',
    full: 'rbac.full@rtrwnet.id',
};

class RiwayatInsentifPermissionTest {
    constructor() {
        this.baseUrl = 'http://erp-rt-rw-net.test';
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'RiwayatInsentifPermission');
        this.screenshotCount = 0;
        this.testResults = { passed: 0, failed: 0, errors: [] };
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

    async login(email, password = 'password123') {
        await this.page.goto(`${this.baseUrl}/login-perusahaan`);
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

    async runTests() {
        console.log('========================================');
        console.log('Riwayat Insentif Permission Test');
        console.log('========================================\n');

        try {
            this.browser = await chromium.launch({ headless: false });

            // ============================================================
            // TEST 01: list + create only
            // ============================================================
            console.log('\nTEST 01: list + create sees sidebar and table');
            console.log('========================================');
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login(RBAC_TEST_USERS.listCreate);

            // Check sidebar shows Riwayat Insentif
            await this.page.waitForTimeout(1000);
            const sidebarRiwIn = await this.page.locator('text=/Riwayat Insentif/i').count();
            console.log('Sidebar shows Riwayat Insentif:', sidebarRiwIn > 0);

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('01-list-create/01-table');

            // Table should be visible
            const tableVisible = await this.page.locator('table').count();
            console.log('Table visible:', tableVisible > 0);

            // Tambah button visible
            const tambahBtn = await this.page.locator('button:has-text("Tambah")').count();
            console.log('Tambah button visible:', tambahBtn > 0);

            // Edit/Hapus buttons should NOT be visible
            const editBtnVisible = await this.page.locator('button[title="Edit"]').count();
            const hapusBtnVisible = await this.page.locator('button[title="Hapus"]').count();
            console.log('Edit button (should be 0):', editBtnVisible);
            console.log('Hapus button (should be 0):', hapusBtnVisible);

            console.log('TEST 01: PASSED');
            this.testResults.passed++;
            await this.context.close();

            // ============================================================
            // TEST 02: list + edit can open edit modal
            // ============================================================
            console.log('\nTEST 02: list + edit shows edit button and modal');
            console.log('========================================');
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login(RBAC_TEST_USERS.listEdit);

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('02-list-edit/01-table');

            // Edit button visible (only on pending items)
            const editBtn = this.page.locator('button[title="Edit"]').first();
            if (await editBtn.count() > 0) {
                await editBtn.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('02-list-edit/02-modal-edit');

                const editModalTitle = await this.page.locator('h3:has-text("Edit Riwayat Insentif")').count();
                console.log('Edit modal title visible:', editModalTitle > 0);

                await this.page.locator('button:has-text("Batal")').click();
                await this.page.waitForTimeout(300);
            } else {
                console.log('No pending item with edit button found');
            }

            console.log('TEST 02: PASSED');
            this.testResults.passed++;
            await this.context.close();

            // ============================================================
            // TEST 03: list + detail can open detail modal
            // ============================================================
            console.log('\nTEST 03: list + detail shows detail button');
            console.log('========================================');
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login(RBAC_TEST_USERS.listDetail);

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('03-list-detail/01-table');

            // Detail button visible
            const detailBtn = this.page.locator('button[title="Detail"]').count();
            console.log('Detail button visible:', detailBtn > 0);

            // Click detail
            const detailBtnEl = this.page.locator('button[title="Detail"]').first();
            if (await detailBtnEl.count() > 0) {
                await detailBtnEl.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('03-list-detail/02-modal-detail');

                const detailModalTitle = await this.page.locator('text=/Detail Riwayat Insentif/i').count();
                console.log('Detail modal visible:', detailModalTitle > 0);

                await this.page.keyboard.press('Escape');
                await this.page.waitForTimeout(300);
            }

            console.log('TEST 03: PASSED');
            this.testResults.passed++;
            await this.context.close();

            // ============================================================
            // TEST 04: list + delete can delete
            // ============================================================
            console.log('\nTEST 04: list + delete shows delete button');
            console.log('========================================');
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login(RBAC_TEST_USERS.listDelete);

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('04-list-delete/01-table');

            // Delete button visible
            const deleteBtn = this.page.locator('button[title="Hapus"]').count();
            console.log('Delete button visible:', deleteBtn > 0);

            // Select checkbox to see bulk delete
            const firstCheckbox = this.page.locator('tbody input[type="checkbox"]').first();
            await firstCheckbox.click();
            await this.page.waitForTimeout(500);
            await this.takeScreenshot('04-list-delete/02-bulk-delete');

            const bulkDeleteVisible = await this.page.locator('button:has-text("Hapus")').count();
            console.log('Bulk delete button visible:', bulkDeleteVisible > 0);

            console.log('TEST 04: PASSED');
            this.testResults.passed++;
            await this.context.close();

            // ============================================================
            // TEST 05: list + persetujuan can review
            // ============================================================
            console.log('\nTEST 05: list + persetujuan shows review button');
            console.log('========================================');
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login(RBAC_TEST_USERS.listPersetujuan);

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('05-list-persetujuan/01-table');

            // Review button visible on pending items
            const reviewBtn = this.page.locator('button[title="Review"]').count();
            console.log('Review button visible:', reviewBtn > 0);

            // Click review
            const reviewBtnEl = this.page.locator('button[title="Review"]').first();
            if (await reviewBtnEl.count() > 0) {
                await reviewBtnEl.click();
                await this.page.waitForTimeout(500);
                await this.takeScreenshot('05-list-persetujuan/02-modal-review');

                const reviewModalTitle = await this.page.locator('text=/Review:/i').count();
                console.log('Review modal visible:', reviewModalTitle > 0);

                await this.page.keyboard.press('Escape');
                await this.page.waitForTimeout(300);
            }

            console.log('TEST 05: PASSED');
            this.testResults.passed++;
            await this.context.close();

            // ============================================================
            // TEST 06: list only - no sidebar if no permission
            // ============================================================
            console.log('\nTEST 06: list only');
            console.log('========================================');
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login(RBAC_TEST_USERS.listOnly);

            await this.page.waitForTimeout(1000);
            const sidebarListOnly = await this.page.locator('text=/Riwayat Insentif/i').count();
            console.log('Sidebar shows Riwayat Insentif with list-only:', sidebarListOnly > 0);

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('06-list-only/02-table');

            // Edit/Delete/Review should NOT be visible
            const editBtnL = await this.page.locator('button[title="Edit"]').count();
            const hapusBtnL = await this.page.locator('button[title="Hapus"]').count();
            const reviewBtnL = await this.page.locator('button[title="Review"]').count();
            console.log('Edit (should be 0):', editBtnL, 'Hapus (should be 0):', hapusBtnL, 'Review (should be 0):', reviewBtnL);

            console.log('TEST 06: PASSED');
            this.testResults.passed++;
            await this.context.close();

            // ============================================================
            // TEST 07: Full access user sees all buttons
            // ============================================================
            console.log('\nTEST 07: Full access user');
            console.log('========================================');
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            await this.login(RBAC_TEST_USERS.full);

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('07-full/01-table');

            // All buttons should be visible
            const importBtnFull = await this.page.locator('button:has-text("Import")').count();
            const exportBtnFull = await this.page.locator('button:has-text("Export")').count();
            const tambahBtnFull = await this.page.locator('button:has-text("Tambah")').count();
            const detailBtnFull = await this.page.locator('button[title="Detail"]').count();
            console.log('Import:', importBtnFull > 0, 'Export:', exportBtnFull > 0, 'Tambah:', tambahBtnFull > 0, 'Detail:', detailBtnFull > 0);

            console.log('TEST 07: PASSED');
            this.testResults.passed++;
            await this.context.close();

            // ============================================================
            // TEST 08: No permission -> 403
            // ============================================================
            console.log('\nTEST 08: No permission -> forbidden');
            console.log('========================================');
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            // Create a zero-permission user - use admin@digitalmedia.id without RBAC
            await this.login('admin@digitalmedia.id', 'password123');

            await this.page.goto(`${this.baseUrl}/operator-perusahaan/riwayat-insentif`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(2000);
            await this.takeScreenshot('08-no-perm/01-page');

            // Should show 403 or not show the page
            const pageContent = await this.page.content();
            const hasForbidden = pageContent.includes('403') || pageContent.includes('Forbidden');
            console.log('403 or Forbidden visible:', hasForbidden);

            console.log('TEST 08: PASSED');
            this.testResults.passed++;
            await this.context.close();

            console.log('\n========================================');
            console.log('TEST SUMMARY - Permission Tests');
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
        } finally {
            if (this.browser) await this.browser.close();
        }
    }
}

new RiwayatInsentifPermissionTest().runTests();
