/**
 * DeepVerifyTemplate.cjs — Template STANDARDS §7 Deep Verify
 *
 * Copy file ini untuk fitur baru: cp DeepVerifyTemplate.cjs ../Feature/{Portal}/DeepVerify{NamaFitur}.cjs
 * Ganti TODO, ikuti 15 langkah checklist STANDARDS §7.3
 *
 * Cara pakai:
 *   const BASE = require('../../support/baseUrl.cjs');
 *   const { chromium } = require('playwright');
 *   // atau via helper:
 *   const PlaywrightHelper = require('../../support/PlaywrightHelper.cjs');
 *
 * Aturan STANDARDS §7.2 per langkah:
 *   1. assert/check — gagal = throw
 *   2. screenshot — bukti visual (shot/page.screenshot)
 *   3. network — waitForResponse untuk submit
 *   4. consoleErrors — pastikan 0
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const BASE = require('./baseUrl.cjs');

const RESULT_DIR = path.join(__dirname, '..', 'Feature', 'result', 'Portal', 'NamaFiturDeepVerify');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

let pass = 0, fail = 0;
const failures = [];
function check(name, cond, detail = '') {
  if (cond) { pass++; console.log(`  ✓ ${name}${detail ? ' — ' + detail : ''}`); }
  else { fail++; failures.push(name); console.log(`  ✗ ${name}${detail ? ' — ' + detail : ''}`); }
}
async function shot(page, name) {
  await page.screenshot({ path: path.join(RESULT_DIR, name), fullPage: false });
  console.log(`  → screenshot: ${name}`);
}

async function main() {
  // STANDARDS §7.1 — headed + slowMo, CI gate via env
  const headless = process.env.PLAYWRIGHT_HEADLESS === 'true';
  const browser = await chromium.launch({ headless, slowMo: headless ? 0 : 350 });
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await ctx.newPage();

  const consoleErrors = [];
  page.on('pageerror', e => consoleErrors.push('pageerror: ' + e.message));
  page.on('console', m => { if (m.type() === 'error') consoleErrors.push('console.error: ' + m.text()); });

  // ── 1. Login + permission gate ────────────────────────────────
  console.log('\n[1] Login');
  await page.goto(`${BASE}/login-perusahaan`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1000);
  // TODO: pilih perusahaan via .fa-building jika diperlukan
  await page.fill('input[type="email"]', 'admin@netsejahtera.com');
  await page.fill('input[type="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(6000);
  check('Login berhasil', page.url().includes('/operator-perusahaan') || page.url().includes('/dashboard'));
  await shot(page, '01-login.png');

  // ── 2. Navigasi ke halaman ───────────────────────────────────
  console.log('\n[2] Navigasi');
  await page.goto(`${BASE}/operator-perusahaan/TODO`, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1500);
  await shot(page, '02-list-light.png');
  check('H2 title', (await page.locator('h2').first().textContent())?.trim().length > 0);
  check('No JS errors saat load', consoleErrors.length === 0, consoleErrors.join('; '));

  // ── 3. CRUD Create modal ─────────────────────────────────────
  console.log('\n[3] Create modal');
  await page.locator('button:has-text("Tambah")').first().click();
  await page.waitForTimeout(800);
  await shot(page, '03-create-modal.png');
  // TODO: picks type, fill, waitForResponse, assert 200, screenshot
  await page.locator('button .fa-times').first().click();
  await page.waitForTimeout(500);

  // ── 4. Filter ────────────────────────────────────────────────
  // TODO: filter Status/Type/Trash, assert row badge, screenshot

  // ── 5. Search ────────────────────────────────────────────────
  // TODO: fill Cari + Enter, assert row, clear, screenshot

  // ── 6. Sort ──────────────────────────────────────────────────
  // TODO: click th, assert, screenshot

  // ── 7. Pagination ────────────────────────────────────────────
  // TODO: select per_page 25, assert max 25 rows, screenshot

  // ── 8. Bulk select ───────────────────────────────────────────
  // TODO: check first checkbox, assert bulk bar, screenshot

  // ── 9. Edit modal ────────────────────────────────────────────
  // TODO: click Edit, assert pre-filled, edit + waitForResponse 200, screenshot

  // ──10. Detail modal ──────────────────────────────────────────
  // TODO: click Detail, assert content, screenshot

  // ──11. Delete / Soft-delete ─────────────────────────────────
  // TODO: delete + 200, filter Terhapus, restore, screenshot

  // ──12. Import/Export ────────────────────────────────────────
  // TODO: Import modal, template link, screenshot

  // ──13. Dark mode ────────────────────────────────────────────
  console.log('\n[13] Dark mode');
  await page.evaluate(() => document.documentElement.classList.add('dark'));
  await page.waitForTimeout(500);
  await shot(page, '13-dark.png');
  await page.evaluate(() => document.documentElement.classList.remove('dark'));

  // ──14. Responsive ───────────────────────────────────────────
  console.log('\n[14] Responsive');
  await page.setViewportSize({ width: 390, height: 800 }); await page.waitForTimeout(500); await shot(page, '14-mobile.png');
  await page.setViewportSize({ width: 768, height: 1024 }); await page.waitForTimeout(500); await shot(page, '14-tablet.png');
  await page.setViewportSize({ width: 1280, height: 800 });

  // ── Final ───────────────────────────────────────────────────
  check('No JS errors selama semua test', consoleErrors.length === 0, consoleErrors.join('; '));
  console.log(`\n${'═'.repeat(60)}\nSUMMARY: ${pass} pass, ${fail} fail\n${'═'.repeat(60)}\n`);
  if (failures.length) failures.forEach(f => console.log('  - ' + f));
  await browser.close();
  process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });
