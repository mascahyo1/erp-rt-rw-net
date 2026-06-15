/**
 * Helper untuk generate test user (active atau inactive) untuk E2E test.
 *
 * Pakai:
 *   const { createInactiveUser, deleteUser } = require('../../support/testUsers.cjs');
 *   const user = await createInactiveUser('customer', companyId);
 *   // ...run test...
 *   await deleteUser('customer', user.id);
 *
 * Returned user shape:
 *   { id, email, password, modelName, is_active: false }
 *
 * Menggunakan PHP untuk bypass UUID v7 + bcrypt + HasUuidV7 trait.
 */

const path = require('path');
const fs = require('fs');
const { execSync } = require('child_process');

// Path dinamis ke project root (parent dari support/).
// support/ → tests/Browser/Playwright/ → tests/Browser/ → tests/ → project root.
// Naik 4 level.
const PROJECT_ROOT = path.resolve(__dirname, '..', '..', '..', '..');

const BOOTSTRAP = `<?php
require '${PROJECT_ROOT.replace(/\\/g, '\\\\')}\\\\vendor\\\\autoload.php';
$app = require '${PROJECT_ROOT.replace(/\\/g, '\\\\')}\\\\bootstrap\\\\app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
`;

const tmpScript = path.join(PROJECT_ROOT, '.claude', 'tmp_test_users.php');

function phpExec(code) {
    fs.writeFileSync(tmpScript, BOOTSTRAP + code);
    try {
        return execSync(`php "${tmpScript}"`, { cwd: PROJECT_ROOT }).toString().trim();
    } catch (e) {
        const stdout = e.stdout ? e.stdout.toString().trim() : '';
        if (stdout) return stdout;
        throw e;
    }
}

function cleanup() {
    try { fs.unlinkSync(tmpScript); } catch (e) { /* ignore */ }
}

const modelClass = {
    customer: 'App\\Models\\Customer',
    karyawan: 'App\\Models\\Employee',
    admin_company: 'App\\Models\\AdminCompany',
    admin_saas: 'App\\Models\\AdminSaas',
};

/**
 * Create inactive user untuk testing.
 * @param {'customer'|'karyawan'|'admin_company'|'admin_saas'} type
 * @param {string} [companyId] - required for customer/karyawan/admin_company
 * @param {string} [extraFields] - PHP assoc array string, optional
 * @returns {{id: string, email: string, password: string, type: string, is_active: false}}
 */
function createInactiveUser(type, companyId, extraFields = '') {
    if (!modelClass[type]) {
        throw new Error(`Unknown user type: ${type}`);
    }
    if (type !== 'admin_saas' && !companyId) {
        throw new Error(`companyId required for type=${type}`);
    }
    const ts = Date.now();
    const email = `inactive+${type}+${ts}@test.local`;
    const password = 'password123';
    const cls = modelClass[type];
    const companyField = type === 'admin_saas' ? '' : `'company_id' => '${companyId}',`;
    const code = `
        $u = ${cls}::create(array_merge([
            'name' => 'Inactive Test ${type} ${ts}',
            'email' => '${email}',
            'phone_country_code' => '+62',
            'phone_number' => '8${String(ts).slice(-9)}',
            'password' => bcrypt('${password}'),
            'is_active' => false,
            ${companyField}
        ], ${extraFields || '[]'}));
        echo $u->id . '|' . $u->email;
    `;
    const result = phpExec(code);
    const [id, resultEmail] = result.split('|');
    return { id, email: resultEmail, password, type, is_active: false };
}

function deleteUser(type, id) {
    const cls = modelClass[type];
    if (!cls) throw new Error(`Unknown user type: ${type}`);
    phpExec(`${cls}::where('id', '${id}')->forceDelete(); echo 'OK';`);
}

module.exports = { createInactiveUser, deleteUser, cleanup };
