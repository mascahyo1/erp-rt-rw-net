
const BASE = require('../../support/baseUrl.cjs');
const PROJECT_BASH = path.resolve(__dirname, '..', '..', '..', '..').replace(/\\/g, '/');
/**
 * Verifikasi integrasi Cloudflare Turnstile di 4 portal login — validation phase.
 *
 * Cloudflare testing keys (saat ini .env pakai always-pass):
 *   - TURNSTILE_SITE_KEY=1x00000000000000000000AA → widget auto-solve
 *   - TURNSTILE_SECRET_KEY=1x0000000000000000000000000000000AA → server verify selalu success
 *
 * Test case: Submit TANPA cf-turnstile-response → 422 (required rule gagal)
 * Dijalankan di 4 portal (operator-saas, perusahaan, pelanggan, karyawan).
 *
 * CATATAN throttle: middleware 5/menit share counter per IP. Total 4
 * attempts (1 per portal) = di bawah limit. AMAN.
 *
 * Happy path (kredensial valid + valid captcha → 302) TIDAK di-test di sini
 * karena Inertia POST butuh X-XSRF-TOKEN cookie + X-CSRF-TOKEN header
 * yang sulit di-set via page.request.post() tanpa browser context. Test
 * happy path lebih cocok via headed browser (lihat TurnstileVisualTest.cjs).
 */
const { chromium } = require('playwright');
const { execSync } = require('child_process');


const portals = [
    { name: 'operator-saas', url: '/login-operator-saas' },
    { name: 'perusahaan',    url: '/login-perusahaan' },
    { name: 'pelanggan',     url: '/login-pelanggan' },
    { name: 'karyawan',      url: '/login-karyawan' },
];

function flushCache() {
    try {
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
        // === PHASE: Submit TANPA cf-turnstile-response → 422 (required rule) ===
        console.log(`\n=== Phase: 4 portal × submit tanpa captcha (expected 422) ===`);
        flushCache();
        const ctx = await browser.newContext();
        for (const portal of portals) {
            const res = await ctx.request.post(`${BASE}${portal.url}`, {
                headers: { 'Accept': 'application/json', 'X-Inertia': 'true' },
                form: { email: 'test@example.com', password: 'dummy' },
            });
            assert(
                `[${portal.name}] tanpa captcha → 422 (cf-turnstile-response required)`,
                res.status() === 422,
                `got=${res.status()}`
            );
        }
        await ctx.close();

    } catch (e) {
        console.log('  ✗ FATAL:', e.message, e.stack);
        results.failed++;
    } finally {
        console.log(`\nResult: ${results.passed}/${results.total} pass`);
        await browser.close();
        process.exit(results.failed > 0 ? 1 : 0);
    }
})();
