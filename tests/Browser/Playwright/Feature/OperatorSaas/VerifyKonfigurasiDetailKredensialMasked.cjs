// Deep verify: detail modal masking behavior for kredensial type.
// User feedback: "modal detail ketika dibuka tipenya kredensial kok langsung
// tampil? harusnya ke mask dulu. cek juga yg operator perusahaan"
//
// This test verifies BOTH /operator-saas/konfigurasi AND
// /operator-perusahaan/konfigurasi-perusahaan
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');


const BASE = require('../../support/baseUrl.cjs');
const RESULT_DIR = path.join(__dirname, '..', 'result', 'OperatorSaas', 'KonfigurasiDetailKredensialMasked');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });


const SCENARIOS = [
    {
        name: 'Operator SaaS',
        loginUrl: `${BASE}/login-operator-saas`,
        email: 'admin@demo.test',
        configUrl: `${BASE}/operator-saas/konfigurasi?per_page=10`,
        createUrl: `${BASE}/operator-saas/konfigurasi`,
        token: 'indigo',
    },
    {
        name: 'Operator Perusahaan',
        loginUrl: `${BASE}/login-perusahaan`,
        email: 'admin@netsejahtera.com',
        configUrl: `${BASE}/operator-perusahaan/konfigurasi-perusahaan?per_page=10`,
        createUrl: `${BASE}/operator-perusahaan/konfigurasi-perusahaan`,
        token: 'sky',
    },
];

let totalPass = 0;
let totalFail = 0;
const allFailures = [];

function check(scenario, name, condition, detail = '') {
    if (condition) {
        totalPass++;
        console.log(`    ✓ ${name}${detail ? ' — ' + detail : ''}`);
    } else {
        totalFail++;
        allFailures.push(`[${scenario.name}] ${name}`);
        console.log(`    ✗ ${name}${detail ? ' — ' + detail : ''}`);
    }
}

async function shot(page, name) {
    await page.screenshot({ path: path.join(RESULT_DIR, name), fullPage: false });
    console.log(`      → ${name}`);
}

async function ensureKredensialRecord(page, scenario) {
    const key = `test.detail.kred.${Date.now()}`;
    const secret = `sk_test_${Date.now()}_secretvalue`;

    console.log(`  [Setup] Create kredensial record '${key}' via Tambah modal`);
    await page.goto(scenario.createUrl, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    await page.locator('button:has-text("Tambah")').first().click();
    await page.waitForTimeout(800);

    await page.locator('form input[placeholder*="Contoh:"]').fill(key);

    const typeSelect = page.locator('form select').filter({ has: page.locator('option[value="kredensial"]') });
    await typeSelect.selectOption('kredensial');
    await page.waitForTimeout(400);

    const kredInput = page.locator('form input[placeholder*="API key"]');
    await kredInput.fill(secret);
    await page.waitForTimeout(300);

    await page.locator('button[type="submit"]:has-text("Simpan")').click();
    await page.waitForTimeout(3000);

    const modalGone = await page.evaluate(() => !document.body.textContent.includes('Tambah Konfigurasi'));
    check(scenario, 'Tambah kredensial record berhasil (modal closed)', modalGone);

    return { key, secret };
}

async function findRowByKey(page, key) {
    const searchInput = page.locator('input[placeholder*="Cari"]').first();
    await searchInput.fill(key);
    await page.locator('button[title="Cari"]').first().click();
    await page.waitForTimeout(2000);
    return (await page.locator('table tbody tr').count()) > 0;
}

async function runScenario(browser, scenario) {
    console.log(`\n${'═'.repeat(70)}`);
    console.log(`  ${scenario.name}`);
    console.log(`${'═'.repeat(70)}`);

    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const page = await ctx.newPage();
    const consoleErrors = [];
    page.on('pageerror', e => consoleErrors.push('pageerror: ' + e.message));
    page.on('console', m => { if (m.type() === 'error') consoleErrors.push('console.error: ' + m.text()); });

    // Login
    await page.goto(scenario.loginUrl, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.fill('input[type="email"]', scenario.email);
    await page.fill('input[type="password"]', 'password123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(5000);

    const { key, secret } = await ensureKredensialRecord(page, scenario);

    // ─────────────────────────────────────────────────────────────────
    // Test 1: Table row masking behavior
    // ─────────────────────────────────────────────────────────────────
    console.log(`  [Test 1] Table row masking`);
    await page.goto(scenario.configUrl, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);
    await findRowByKey(page, key);
    await shot(page, `${scenario.name.replace(/ /g, '_')}-01-table-search.png`);

    // Get value cell specifically (LAST code.font-mono in row, not the key)
    const tableInitial = await page.evaluate((k) => {
        const rows = document.querySelectorAll('table tbody tr');
        for (const r of rows) {
            if (r.textContent.includes(k)) {
                const codes = r.querySelectorAll('code.font-mono');
                // First code = key, second code = value (if kred, has bullets)
                const valueCode = codes[1];
                const eyeBtn = r.querySelector('button[title*="Tampilkan"], button[title*="Sembunyikan"]');
                return {
                    hasEyeBtn: !!eyeBtn,
                    valueText: valueCode?.textContent.trim() || null,
                    hasBullets: valueCode?.textContent.includes('••••') || false,
                };
            }
        }
        return null;
    }, key);
    check(scenario, 'Table: row punya eye button', tableInitial?.hasEyeBtn);
    check(scenario, 'Table: value MASKED (bullets)', tableInitial?.hasBullets && !tableInitial?.valueText?.startsWith('sk_test_'),
        `valueText: "${tableInitial?.valueText?.slice(0, 30)}"`);
    await shot(page, `${scenario.name.replace(/ /g, '_')}-02-table-masked.png`);

    // Click eye in table to reveal
    const eyeInTable = page.locator('table tbody tr').filter({ hasText: key }).locator('button[title*="Tampilkan"]');
    await eyeInTable.click();
    await page.waitForTimeout(500);
    const tableRevealed = await page.evaluate((k) => {
        const rows = document.querySelectorAll('table tbody tr');
        for (const r of rows) {
            if (r.textContent.includes(k)) {
                const codes = r.querySelectorAll('code.font-mono');
                return codes[1]?.textContent.trim() || null;
            }
        }
        return null;
    }, key);
    check(scenario, 'Table: click eye → value terlihat (revealed)', tableRevealed === secret,
        `actual: "${tableRevealed?.slice(0, 30)}..."`);
    await shot(page, `${scenario.name.replace(/ /g, '_')}-03-table-revealed.png`);

    // Toggle back to mask
    const eyeHideInTable = page.locator('table tbody tr').filter({ hasText: key }).locator('button[title*="Sembunyikan"]');
    await eyeHideInTable.click();
    await page.waitForTimeout(500);

    // ─────────────────────────────────────────────────────────────────
    // Test 2: Detail modal masking behavior (THE BUG FIX)
    // ─────────────────────────────────────────────────────────────────
    console.log(`  [Test 2] Detail modal masking (bug fix)`);
    // Click detail button
    const detailBtn = page.locator('table tbody tr').filter({ hasText: key }).locator('button[title="Detail"]');
    await detailBtn.click();
    await page.waitForTimeout(800);
    await shot(page, `${scenario.name.replace(/ /g, '_')}-04-detail-default.png`);

    // Scope to detail modal — find eye button INSIDE the modal
    const detailState1 = await page.evaluate(() => {
        // Detail modal is the one with title "Detail Konfigurasi"
        const allButtons = Array.from(document.querySelectorAll('button'));
        // The detail modal eye button is the one inside a div that has label "Value"
        // We need to find the modal panel that has "Detail Konfigurasi" text
        const modals = Array.from(document.querySelectorAll('.fixed.inset-0'));
        let detailModal = null;
        for (const m of modals) {
            if (m.textContent.includes('Detail Konfigurasi')) {
                detailModal = m;
                break;
            }
        }
        if (!detailModal) return { found: false };
        const eyeBtn = detailModal.querySelector('button[title*="Tampilkan"], button[title*="Sembunyikan"]');
        // Find pre inside detail modal (value pre)
        const pre = detailModal.querySelector('pre');
        // Find bullets div
        const allDivs = Array.from(detailModal.querySelectorAll('div'));
        const bulletsDiv = allDivs.find(d => d.textContent.includes('••••') && d.classList.contains('select-none'));
        return {
            found: true,
            hasEyeBtn: !!eyeBtn,
            eyeTitle: eyeBtn?.getAttribute('title') || null,
            eyeIconClass: eyeBtn?.querySelector('i')?.className || null,
            preText: pre?.textContent.trim() || null,
            hasPreWithSecret: pre?.textContent.includes('sk_test_') || false,
            hasBullets: !!bulletsDiv,
        };
    });
    check(scenario, 'Detail modal: terbuka', detailState1.found);
    check(scenario, 'Detail modal: punya eye button (untuk kredensial)', detailState1.hasEyeBtn);
    check(scenario, 'Detail modal: TIDAK menampilkan value pre by default (masked)',
        !detailState1.hasPreWithSecret, `preText: "${detailState1.preText?.slice(0, 30)}"`);
    check(scenario, 'Detail modal: menampilkan bullets (••••) by default', detailState1.hasBullets);
    check(scenario, 'Detail modal: eye title = "Tampilkan value" (value hidden)',
        detailState1.eyeTitle === 'Tampilkan value', `actual: "${detailState1.eyeTitle}"`);
    check(scenario, 'Detail modal: eye icon = fa-eye (bukan fa-eye-slash)',
        detailState1.eyeIconClass?.includes('fa-eye') && !detailState1.eyeIconClass?.includes('fa-eye-slash'),
        `icon: ${detailState1.eyeIconClass}`);

    // Click eye IN DETAIL MODAL (scope to modal)
    const detailModalEyeBtn = page.evaluate(() => {
        const modals = Array.from(document.querySelectorAll('.fixed.inset-0'));
        for (const m of modals) {
            if (m.textContent.includes('Detail Konfigurasi')) {
                const btn = m.querySelector('button[title*="Tampilkan"]');
                if (btn) {
                    btn.setAttribute('data-test-eye', 'detail-modal');
                    return true;
                }
            }
        }
        return false;
    });
    check(scenario, 'Detail modal: eye button located', detailModalEyeBtn);

    await page.locator('button[data-test-eye="detail-modal"]').click();
    await page.waitForTimeout(500);
    const detailState2 = await page.evaluate(() => {
        const modals = Array.from(document.querySelectorAll('.fixed.inset-0'));
        for (const m of modals) {
            if (m.textContent.includes('Detail Konfigurasi')) {
                const eyeBtn = m.querySelector('button[title*="Tampilkan"], button[title*="Sembunyikan"]');
                const pre = m.querySelector('pre');
                return {
                    eyeTitle: eyeBtn?.getAttribute('title') || null,
                    preText: pre?.textContent.trim() || null,
                };
            }
        }
        return null;
    });
    check(scenario, 'Detail modal click eye: pre menampilkan value',
        detailState2?.preText?.includes('sk_test_'),
        `preText: "${detailState2?.preText?.slice(0, 30)}..."`);
    check(scenario, 'Detail modal click eye: value content match secret',
        detailState2?.preText === secret);
    check(scenario, 'Detail modal click eye: title berubah ke "Sembunyikan value"',
        detailState2?.eyeTitle === 'Sembunyikan value');
    await shot(page, `${scenario.name.replace(/ /g, '_')}-05-detail-revealed.png`);

    // Click eye lagi to mask
    await page.locator('button[data-test-eye="detail-modal"]').click();
    await page.waitForTimeout(500);
    const detailState3 = await page.evaluate(() => {
        const modals = Array.from(document.querySelectorAll('.fixed.inset-0'));
        for (const m of modals) {
            if (m.textContent.includes('Detail Konfigurasi')) {
                const eyeBtn = m.querySelector('button[title*="Tampilkan"], button[title*="Sembunyikan"]');
                const pre = m.querySelector('pre');
                const bulletsDiv = Array.from(m.querySelectorAll('div')).find(d => d.textContent.includes('••••') && d.classList.contains('select-none'));
                return {
                    eyeTitle: eyeBtn?.getAttribute('title') || null,
                    hasPreWithSecret: pre?.textContent.includes('sk_test_') || false,
                    hasBullets: !!bulletsDiv,
                };
            }
        }
        return null;
    });
    check(scenario, 'Detail modal click eye lagi: value ke-mask (no pre dengan secret)',
        !detailState3?.hasPreWithSecret);
    check(scenario, 'Detail modal click eye lagi: bullets muncul kembali',
        detailState3?.hasBullets);
    check(scenario, 'Detail modal click eye lagi: title balik ke "Tampilkan value"',
        detailState3?.eyeTitle === 'Tampilkan value');

    // Close detail modal
    await page.locator('.fixed.inset-0 button:has(.fa-times)').first().click();
    await page.waitForTimeout(500);

    // ─────────────────────────────────────────────────────────────────
    // Test 3: Non-kred detail modal (regression check)
    // ─────────────────────────────────────────────────────────────────
    console.log(`  [Test 3] Regression: non-kred detail modal`);
    // Clear search
    const clearBtn = page.locator('button[title="Clear"]');
    if (await clearBtn.count() > 0) {
        await clearBtn.click();
        await page.waitForTimeout(1500);
    }
    // Go to boolean filter to get a non-kred row
    await page.locator('button').filter({ hasText: /^Boolean$/ }).first().click().catch(() => {});
    await page.waitForTimeout(1500);
    const boolRow = page.locator('table tbody tr').filter({ hasNotText: 'Tidak ada data' }).first();
    if (await boolRow.count() > 0) {
        await boolRow.locator('button[title="Detail"]').click();
        await page.waitForTimeout(800);
        const nonKredDetail = await page.evaluate(() => {
            const modals = Array.from(document.querySelectorAll('.fixed.inset-0'));
            for (const m of modals) {
                if (m.textContent.includes('Detail Konfigurasi')) {
                    const eyeBtn = m.querySelector('button[title*="Tampilkan"], button[title*="Sembunyikan"]');
                    const pre = m.querySelector('pre');
                    return {
                        hasEyeBtn: !!eyeBtn,
                        hasPre: !!pre,
                    };
                }
            }
            return null;
        });
        check(scenario, 'Non-kred detail modal: TIDAK ada eye button', !nonKredDetail?.hasEyeBtn);
        check(scenario, 'Non-kred detail modal: value pre visible langsung', nonKredDetail?.hasPre);
        await shot(page, `${scenario.name.replace(/ /g, '_')}-06-detail-nonkred.png`);
        // Close
        await page.locator('.fixed.inset-0 button:has(.fa-times)').first().click();
        await page.waitForTimeout(500);
    }

    // ─────────────────────────────────────────────────────────────────
    // Cleanup
    // ─────────────────────────────────────────────────────────────────
    console.log(`  [Cleanup] Delete the kred record`);
    await page.goto(scenario.configUrl, { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await findRowByKey(page, key);
    const deleteBtn = page.locator('table tbody tr').filter({ hasText: key }).locator('button[title="Hapus"]');
    if (await deleteBtn.count() > 0) {
        await deleteBtn.click();
        await page.waitForTimeout(800);
        await page.locator('button:has-text("Hapus")').last().click();
        await page.waitForTimeout(2000);
    }

    check(scenario, 'No JS errors selama scenario', consoleErrors.length === 0, consoleErrors.join('; '));
    await page.waitForTimeout(1000);
    await ctx.close();
}

async function main() {
    const browser = await chromium.launch({ headless: false, slowMo: 400 });

    for (const scenario of SCENARIOS) {
        try {
            await runScenario(browser, scenario);
        } catch (e) {
            totalFail++;
            allFailures.push(`[${scenario.name}] FATAL: ${e.message}`);
            console.log(`  ✗ FATAL: ${e.message}`);
        }
    }

    console.log(`\n${'═'.repeat(70)}`);
    console.log(`  DEEP VERIFY SUMMARY (Detail Modal Kredensial Masking)`);
    console.log(`${'═'.repeat(70)}`);
    console.log(`  Pass: ${totalPass}`);
    console.log(`  Fail: ${totalFail}`);
    if (allFailures.length > 0) {
        console.log('\n  Failures:');
        allFailures.forEach(f => console.log(`    - ${f}`));
    }
    console.log(`${'═'.repeat(70)}\n`);

    await browser.close();
    process.exit(totalFail > 0 ? 1 : 0);
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
