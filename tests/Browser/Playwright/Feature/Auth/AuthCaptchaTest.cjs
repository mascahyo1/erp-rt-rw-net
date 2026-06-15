
const BASE = require('../../support/baseUrl.cjs');
const PROJECT_BASH = path.resolve(__dirname, '..', '..', '..', '..').replace(/\\/g, '/');
/**
 * Verifikasi Turnstile + throttle di endpoint REGISTER + LUPA PASSWORD.
 *
 * Endpoint yang dites (semua 4 portal):
 *   - POST /daftar-pelanggan  (register)
 *   - POST /lupa-password-{portal}  (forgot, kirim link)
 *   - POST /lupa-password-{portal}/reset  (reset, update password)
 *
 * Test case: Submit TANPA cf-turnstile-response → 422 (required rule)
 * TIDAK test happy path (butuh CSRF token dari browser session penuh).
 *
 * Total 9 endpoint × 1 attempt = 9 attempts, di bawah shared 5/menit limit
 * PER IP per phase. Phase dipisah dengan flush cache + context baru.
 *
 * CATATAN: throttle counter di-share per IP. Jadi setelah 5 attempts di
 * phase manapun, attempt ke-6+ kena 429. Solusi: flush cache + context
 * baru di setiap phase (cache:clear reset counter).
 */
const { chromium } = require('playwright');
const { execSync } = require('child_process');


const forgotEndpoints = [
    { name: 'operator-saas', url: '/lupa-password-operator-saas' },
    { name: 'perusahaan',    url: '/lupa-password-perusahaan' },
    { name: 'pelanggan',     url: '/lupa-password-pelanggan' },
    { name: 'karyawan',      url: '/lupa-password-karyawan' },
];

function flushCache() {
    try {
        // cache:clear + hapus isi folder cache (rate limiter pakai file driver)
        execSync(`rm -rf ${PROJECT_BASH}/storage/framework/cache/data/*`, { stdio: 'pipe' });
        execSync(`cd ${PROJECT_BASH} && php artisan cache:clear`, { stdio: 'pipe' });
        execSync('sleep 1');
    } catch (e) { /* ignore */ }
}

(async () => {
    const browser = await chromium.launch({ headless: true, slowMo: 0 });
    const results = { total: 0, passed: 0, failed: 0 };
    const assert = (name, cond, info) => {
        results.total++;
        cond ? results.passed++ : results.failed++;
        console.log(`  ${cond ? '✓' : '✗'} ${name}${info ? ' — ' + info : ''}`);
    };

    try {
        // === PHASE 1: Customer register (1 endpoint) ===
        console.log(`\n=== Phase 1: register (expected 422) ===`);
        flushCache();
        const ctx1 = await browser.newContext();
        {
            const res = await ctx1.request.post(`${BASE}/daftar-pelanggan`, {
                headers: { 'Accept': 'application/json', 'X-Inertia': 'true' },
                form: { name: 'Test User', email: 'test@test.com', phone: '08123456789', password: 'Password123!', password_confirmation: 'Password123!', company_id: 'dummy' },
            });
            assert('[register] tanpa captcha → 422 (cf-turnstile-response required)', res.status() === 422, `got=${res.status()}`);
        }
        await ctx1.close();

        // === PHASE 2: Forgot password 4 portal (4 endpoints) ===
        console.log(`\n=== Phase 2: forgot password (expected 422) ===`);
        flushCache();
        const ctx2 = await browser.newContext();
        for (const ep of forgotEndpoints) {
            const formData = { email: 'test@example.com', 'cf-turnstile-response': '' };
            const res = await ctx2.request.post(`${BASE}${ep.url}`, {
                headers: { 'Accept': 'application/json', 'X-Inertia': 'true' },
                form: formData,
            });
            assert(`[forgot ${ep.name}] tanpa captcha → 422`, res.status() === 422, `got=${res.status()}`);
        }
        await ctx2.close();

        // === PHASE 3: Reset password 4 portal (4 endpoints) ===
        console.log(`\n=== Phase 3: reset password (expected 422) ===`);
        flushCache();
        const ctx3 = await browser.newContext();
        for (const ep of forgotEndpoints) {
            const formData = { token: 'fake-token', email: 'test@example.com', password: 'Password123!', password_confirmation: 'Password123!', 'cf-turnstile-response': '' };
            const res = await ctx3.request.post(`${BASE}${ep.url}/reset`, {
                headers: { 'Accept': 'application/json', 'X-Inertia': 'true' },
                form: formData,
            });
            assert(`[reset ${ep.name}] tanpa captcha → 422`, res.status() === 422, `got=${res.status()}`);
        }
        await ctx3.close();

        // === PHASE 4: Throttle 5/menit (verifikasi middleware applied) ===
        // Pakai 1 endpoint (operator-saas forgot), 5 attempts OK, 6th → 429
        console.log(`\n=== Phase 4: throttle 5/menit (expected 5×422 + 1×429) ===`);
        flushCache();
        const ctx4 = await browser.newContext();
        for (let i = 1; i <= 5; i++) {
            const res = await ctx4.request.post(`${BASE}/lupa-password-operator-saas`, {
                headers: { 'Accept': 'application/json', 'X-Inertia': 'true' },
                form: { email: 'test@example.com', 'cf-turnstile-response': '' },
            });
            assert(`[forgot operator-saas] throttle attempt ${i} → 422`, res.status() === 422, `got=${res.status()}`);
        }
        const res6 = await ctx4.request.post(`${BASE}/lupa-password-operator-saas`, {
            headers: { 'Accept': 'application/json', 'X-Inertia': 'true' },
            form: { email: 'test@example.com', 'cf-turnstile-response': '' },
        });
        assert(`[forgot operator-saas] throttle attempt 6 → 429`, res6.status() === 429, `got=${res6.status()}`);
        await ctx4.close();

    } catch (e) {
        console.log('  ✗ FATAL:', e.message, e.stack);
        results.failed++;
    } finally {
        console.log(`\nResult: ${results.passed}/${results.total} pass`);
        await browser.close();
        process.exit(results.failed > 0 ? 1 : 0);
    }
})();
