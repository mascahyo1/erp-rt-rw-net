const { chromium } = require('playwright');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
class DaftarPaketRBACTest {
    constructor() {
        // baseUrl di-migrate ke BASE const (di-inject di bawah)
        this.browser = null;
        this.context = null;
        this.page = null;
        this.screenshotDir = path.join(__dirname, '..', 'result', 'OperatorPerusahaan', 'DaftarPaket', 'TestRBAC');
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
        await this.page.screenshot({ path: filepath });
        console.log(`  [Screenshot] ${filename}`);
        return filepath;
    }

    assert(condition, message) {
        if (!condition) {
            throw new Error(message);
        }
    }

    async runArtisan(command) {
        try {
            const result = execSync(`php artisan ${command}`, {
                cwd: path.resolve(__dirname, '..', '..', '..', '..'),
                encoding: 'utf8',
                timeout: 30000
            });
            return result.trim();
        } catch (e) {
            return e.output?.toString() || e.message;
        }
    }

    async setupUsersWithPermissions() {
        console.log('  Setting up users with specific permissions...');

        // Get company ID first
        const companyId = await this.runArtisan('tinker --execute="echo App\\\\Models\\\\Company::first()->id ?? 1;"');

        // Create user with ALL paket permissions
        await this.runArtisan(`tinker --execute="
            \\$role = App\\\\Models\\\\Role::firstOrCreate(
                ['name' => 'Test Full Paket Access'],
                ['scope' => 'admin_perusahaan', 'is_active' => 1, 'display_order' => 1]
            );

            // Get paket permissions
            \\$perms = App\\\\Models\\\\Permission::whereIn('name', [
                'paket.list', 'paket.create', 'paket.edit', 'paket.delete', 'paket.export', 'paket.import'
            ])->pluck('id');

            foreach(\\$perms as \\$permId) {
                App\\\\Models\\\\Role::find(\\$role->id)->permissions()->syncWithoutDetaching([\\$permId]);
            }

            \\$user = App\\\\Models\\\\AdminCompany::firstOrCreate(
                ['email' => 'test.fullaccess@rtrwnet.id'],
                [
                    'name' => 'Test Full Access',
                    'password' => bcrypt('password'),
                    'company_id' => ${companyId},
                    'is_active' => 1
                ]
            );

            App\\\\Models\\\\AdminCompany::find(\$user->id)->roles()->sync([\\$role->id]);
            echo 'Full access user: test.fullaccess@rtrwnet.id / password' . PHP_EOL;
        "`);

        // Create user with ONLY list permission
        await this.runArtisan(`tinker --execute="
            \\$role = App\\\\Models\\\\Role::firstOrCreate(
                ['name' => 'Test List Only'],
                ['scope' => 'admin_perusahaan', 'is_active' => 1, 'display_order' => 2]
            );

            \\$perm = App\\\\Models\\\\Permission::where('name', 'paket.list')->first();
            if (\\$perm) {
                App\\\\Models\\\\Role::find(\$role->id)->permissions()->sync([\\$perm->id]);
            }

            \\$user = App\\\\Models\\\\AdminCompany::firstOrCreate(
                ['email' => 'test.listonly@rtrwnet.id'],
                [
                    'name' => 'Test List Only',
                    'password' => bcrypt('password'),
                    'company_id' => ${companyId},
                    'is_active' => 1
                ]
            );

            App\\\\Models\\\\AdminCompany::find(\$user->id)->roles()->sync([\\$role->id]);
            echo 'List only user: test.listonly@rtrwnet.id / password' . PHP_EOL;
        "`);

        // Create user with NO permissions at all
        await this.runArtisan(`tinker --execute="
            \\$role = App\\\\Models\\\\Role::firstOrCreate(
                ['name' => 'Test No Paket Access'],
                ['scope' => 'admin_perusahaan', 'is_active' => 1, 'display_order' => 3]
            );

            \\$user = App\\\\Models\\\\AdminCompany::firstOrCreate(
                ['email' => 'test.nopermission@rtrwnet.id'],
                [
                    'name' => 'Test No Permission',
                    'password' => bcrypt('password'),
                    'company_id' => ${companyId},
                    'is_active' => 1
                ]
            );

            App\\\\Models\\\\AdminCompany::find(\$user->id)->roles()->sync([\\$role->id]);
            echo 'No permission user: test.nopermission@rtrwnet.id / password' . PHP_EOL;
        "`);

        console.log('  Users setup complete');
    }

    async login(email, password) {
        // Create fresh context for each user
        this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
        this.page = await this.context.newPage();

        await this.page.goto(`${BASE}/login-perusahaan`);
        await this.page.waitForLoadState('networkidle');
        await this.takeScreenshot(`login-${email.replace('@rtrwnet.id','')}`);

        await this.page.fill('input[type="email"]', email);
        await this.page.fill('input[type="password"]', password);
        await this.page.click('button[type="submit"]');
        await this.page.waitForTimeout(8000);

        const url = this.page.url();
        console.log(`  Logged in as ${email}, URL: ${url}`);
        await this.takeScreenshot(`after-login-${email.replace('@rtrwnet.id','')}`);
        return url;
    }

    async runAllTests() {
        console.log('========================================');
        console.log('Daftar Paket RBAC Tests - Granular Permission Testing');
        console.log('========================================\n');

        try {
            // Setup users with specific permissions
            await this.setupUsersWithPermissions();

            this.browser = await chromium.launch({ headless: false });
            this.context = await this.browser.newContext({ viewport: { width: 1280, height: 720 } });
            this.page = await this.context.newPage();

            // Test 1: User with ALL permissions
            await this.testUserWithAllPermissions();

            // Test 2: User with LIST only
            await this.testUserWithListOnly();

            // Test 3: User with NO permissions
            await this.testUserWithNoPermissions();

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

    async testUserWithAllPermissions() {
        const testName = 'User with ALL permissions';
        console.log(`\n[TEST] ${testName}`);

        try {
            await this.login('rbac.full@rtrwnet.id', 'password');
            await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('all-perms-page');

            const pageText = await this.page.textContent('body');
            const hasTable = await this.page.$('table') !== null;
            const hasTambahButton = pageText.includes('Tambah Paket');
            const hasExportButton = pageText.includes('Export');
            const hasImportButton = pageText.includes('Import');

            console.log(`  Has table: ${hasTable}`);
            console.log(`  Has Tambah button: ${hasTambahButton}`);
            console.log(`  Has Export button: ${hasExportButton}`);
            console.log(`  Has Import button: ${hasImportButton}`);

            this.assert(hasTable, 'User with all permissions should see table');
            this.assert(hasTambahButton, 'User with all permissions should see Tambah Paket button');
            this.assert(hasExportButton, 'User with all permissions should see Export button');
            this.assert(hasImportButton, 'User with all permissions should see Import button');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ FAILED: ${e.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message}`);
            await this.takeScreenshot(`XX-${testName.replace(/\s/g, '-')}`);
        }
    }

    async testUserWithListOnly() {
        const testName = 'User with LIST only permission';
        console.log(`\n[TEST] ${testName}`);

        try {
            await this.login('rbac.list@rtrwnet.id', 'password');
            await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('list-only-page');

            const pageText = await this.page.textContent('body');
            const hasTable = await this.page.$('table') !== null;
            const hasTambahButton = pageText.includes('Tambah Paket');
            const hasExportButton = pageText.includes('Export');
            const hasImportButton = pageText.includes('Import');

            console.log(`  Has table: ${hasTable}`);
            console.log(`  Has Tambah button: ${hasTambahButton}`);
            console.log(`  Has Export button: ${hasExportButton}`);
            console.log(`  Has Import button: ${hasImportButton}`);

            this.assert(hasTable, 'User with list permission should see table');
            this.assert(!hasTambahButton, 'User with list-only should NOT see Tambah Paket button');
            this.assert(!hasExportButton, 'User with list-only should NOT see Export button');
            this.assert(!hasImportButton, 'User with list-only should NOT see Import button');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ FAILED: ${e.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message}`);
            await this.takeScreenshot(`XX-${testName.replace(/\s/g, '-')}`);
        }
    }

    async testUserWithNoPermissions() {
        const testName = 'User with NO permissions';
        console.log(`\n[TEST] ${testName}`);

        try {
            await this.login('rbac.no@rtrwnet.id', 'password');
            await this.page.goto(`${BASE}/operator-perusahaan/daftar-paket`);
            await this.page.waitForLoadState('networkidle');
            await this.page.waitForTimeout(3000);
            await this.takeScreenshot('no-perms-page');

            const url = this.page.url();
            const pageText = await this.page.textContent('body');

            console.log(`  URL: ${url}`);
            console.log(`  Page text: ${pageText.substring(0, 100)}...`);

            // User without permissions should see 403 or be redirected
            const has403 = pageText.includes('403') || url.includes('403');
            const hasForbidden = pageText.includes('Forbidden') || pageText.includes('Unauthorized');

            this.assert(has403 || hasForbidden || url.includes('403'), 'User without permissions should see 403 or be blocked');

            console.log(`  ✓ PASSED\n`);
            this.testResults.passed++;
        } catch (e) {
            console.log(`  ✗ FAILED: ${e.message}\n`);
            this.testResults.failed++;
            this.testResults.errors.push(`${testName}: ${e.message}`);
            await this.takeScreenshot(`XX-${testName.replace(/\s/g, '-')}`);
        }
    }
}

const test = new DaftarPaketRBACTest();
test.runAllTests().then(() => {
    process.exit(test.testResults.failed > 0 ? 1 : 0);
});