/**
 * Verifikasi throttle 5/menit di 4 portal login.
 *
 * CATATAN: Laravel ThrottleRequests default cache key = sha1(domain + IP).
 * Artinya SEMUA routes yang pakai middleware `throttle:5,1` SHARE counter
 * per IP (bukan per route). Ini best practice: 1 attacker = 1 IP = 1 counter
 * untuk semua endpoint login.
 *
 * Test flow:
 * 1. Flush cache (counter fresh)
 * 2. Attempt 1-5 ke operator-saas: 422 (auth failed)
 * 3. Attempt 6 ke operator-saas: 429 (throttled)
 * 4. Attempt 1 ke perusahaan (counter shared): langsung 429
 * 5. Verify semua 4 routes throttled (semua pakai middleware throttle:5,1)
 */
const { chromium } = require('playwright');
const { execSync } = require('child_process');

const BASE = 'http://erp-rt-rw-net.test';

const portals = [
    { name: 'operator-saas', url: '/login-operator-saas', email: 'superadmin@demo.test' },
    { name: 'perusahaan', url: '/login-perusahaan', email: 'admin@netsejahtera.com' },
    { name: 'pelanggan', url: '/login-pelanggan', email: 'sugeng@gmail.com' },
    { name: 'karyawan', url: '/login-karyawan', email: 'ahmad@netsejahtera.com' },
];

function flushCache() {
    try {
        execSync('cd /c/laragon/www/erp-rt-rw-net && php artisan cache:clear', { stdio: 'pipe' });
        execSync('sleep 1');
    } catch (e) { /* ignore */ }
}

(async () => {
    const browser = await chromium.launch({ headless: false, slowMo: 100 });
    const results = { total: 0, passed: 0, failed: 0 };
    const assert = (name, cond, info) => {
        results.total++;
        cond ? results.passed++ : results.failed++;
        console.log(`  ${cond ? '✓' : '✗'} ${name}${info ? ' — ' + info : ''}`);
    };

    try {
        const ctx = await browser.newContext();

        // === PHASE 1: Fresh counter, 5 attempts ke operator-saas OK ===
        console.log(`\n=== Phase 1: counter fresh, ${portals[0].name} ===`);
        flushCache();
        for (let i = 1; i <= 5; i++) {
            const res = await ctx.request.post(`${BASE}${portals[0].url}`, {
                headers: { 'Accept': 'application/json', 'X-Inertia': 'true' },
                form: { email: portals[0].email, password: `wrong-${i}` },
            });
            assert(`[${portals[0].name}] attempt ${i} → 422 (auth failed)`, res.status() === 422, `got=${res.status()}`);
        }

        // Attempt 6: HARUS 429
        const res6 = await ctx.request.post(`${BASE}${portals[0].url}`, {
            headers: { 'Accept': 'application/json', 'X-Inertia': 'true' },
            form: { email: portals[0].email, password: 'wrong-6' },
        });
        assert(`[${portals[0].name}] attempt 6 → 429 (throttled)`, res6.status() === 429, `got=${res6.status()}`);

        // Attempt 7: tetap 429
        const res7 = await ctx.request.post(`${BASE}${portals[0].url}`, {
            headers: { 'Accept': 'application/json', 'X-Inertia': 'true' },
            form: { email: portals[0].email, password: 'wrong-7' },
        });
        assert(`[${portals[0].name}] attempt 7 → masih 429`, res7.status() === 429, `got=${res7.status()}`);

        // === PHASE 2: Counter shared — attempt ke portal lain LANGSUNG 429 ===
        console.log(`\n=== Phase 2: counter shared (Laravel default behavior) ===`);
        for (const portal of portals.slice(1)) {
            const res = await ctx.request.post(`${BASE}${portal.url}`, {
                headers: { 'Accept': 'application/json', 'X-Inertia': 'true' },
                form: { email: portal.email, password: 'wrong' },
            });
            assert(`[${portal.name}] attempt 1 (counter shared) → langsung 429`, res.status() === 429, `got=${res.status()}`);
        }

        // === PHASE 3: Verify throttle applied di SEMUA 4 routes (independent fresh counter per route, per IP) ===
        // Pakai context browser baru (IP effectively different) + cache flush
        console.log(`\n=== Phase 3: verify middleware applied di SEMUA 4 routes ===`);
        for (const portal of portals) {
            console.log(`\n  --- ${portal.name} ---`);
            flushCache();
            const ctx2 = await browser.newContext();
            for (let i = 1; i <= 5; i++) {
                const res = await ctx2.request.post(`${BASE}${portal.url}`, {
                    headers: { 'Accept': 'application/json', 'X-Inertia': 'true' },
                    form: { email: portal.email, password: `wrong-${i}` },
                });
                assert(`[${portal.name}] fresh attempt ${i} → 422`, res.status() === 422, `got=${res.status()}`);
            }
            const res6 = await ctx2.request.post(`${BASE}${portal.url}`, {
                headers: { 'Accept': 'application/json', 'X-Inertia': 'true' },
                form: { email: portal.email, password: 'wrong-6' },
            });
            assert(`[${portal.name}] fresh attempt 6 → 429 (middleware works di route ini)`, res6.status() === 429, `got=${res6.status()}`);
            await ctx2.close();
        }
    } catch (e) {
        console.log('  ✗ FATAL:', e.message);
        results.failed++;
    } finally {
        console.log(`\nResult: ${results.passed}/${results.total} pass`);
        await browser.close();
        process.exit(results.failed > 0 ? 1 : 0);
    }
})();
