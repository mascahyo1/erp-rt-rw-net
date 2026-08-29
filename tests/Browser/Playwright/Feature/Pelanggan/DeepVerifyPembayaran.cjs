/**
 * DeepVerifyTemplate.cjs â€” Template STANDARDS Â§7 Deep Verify
 *
 * Copy file ini untuk fitur baru: cp DeepVerifyTemplate.cjs ../Feature/{Feature}/DeepVerify{NamaFitur}.cjs
 * Ganti Riwayat Pembayaran, ikuti 15 langkah checklist STANDARDS Â§7.3
 *
 * Cara pakai:
 *   const BASE = require('../../support/baseUrl.cjs');
 *   const { chromium } = require('playwright');
 *   // atau via helper:
 *   const PlaywrightHelper = require('../../support/PlaywrightHelper.cjs');
 *
 * Aturan STANDARDS Â§7.2 per langkah:
 *   1. assert/check â€” gagal = throw
 *   2. screenshot â€” bukti visual (shot/page.screenshot)
 *   3. network â€” waitForResponse untuk submit
 *   4. consoleErrors â€” pastikan 0
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const BASE = require('./baseUrl.cjs');

const RESULT_DIR = path.join(__dirname, '..', 'Feature', 'result', 'Feature', 'DeepVerifyPembayaran');
if (!fs.existsSync(RESULT_DIR)) fs.mkdirSync(RESULT_DIR, { recursive: true });

let pass = 0, fail = 0;
const failures = [];
function check(name, cond, detail = '') {
  if (cond) { pass++; console.log(`  âœ“ ${name}${detail ? ' â€” ' + detail : ''}`); }
  else { fail++; failures.push(name); console.log(`  âœ— ${name}${detail ? ' â€” ' + detail : ''}`); }
}
async function shot(page, name) {
  await page.screenshot({ path: path.join(RESULT_DIR, name), fullPage: false });
  console.log(`  â†’ screenshot: ${name}`);
}

async function main() {
  // STANDARDS Â§7.1 â€” headed + slowMo, CI gate via env
  const headless = process.env.PLAYWRIGHT_HEADLESS === 'true';
  const browser = await chromium.launch({ headless, slowMo: headless ? 0 : 350 });
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
  const page = await ctx.newPage();

  const consoleErrors = [];
  page.on('pageerror', e => consoleErrors.push('pageerror: ' + e.message));
  page.on('console', m => { if (m.type() === 'error') consoleErrors.push('console.error: ' + m.text()); });

  // â”€â”€ 1. Login + permission gate â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  console.log('\n[1] Login');
  await page.goto(`${BASE}/login-pelanggan`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1000);
  // Riwayat Pembayaran: pilih perusahaan via .fa-building jika diperlukan
  await page.fill('input[type="email"]', 'sugeng@gmail.com');
  await page.fill('input[type="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(6000);
  check('Login berhasil', page.url().includes('/operator-perusahaan') || page.url().includes('/dashboard'));
  await shot(page, '01-login.png');

  // â”€â”€ 2. Navigasi ke halaman â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  console.log('\n[2] Navigasi');
  await page.goto(`${BASE}/customer/riwayat-pembayaran`, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1500);
  await shot(page, '02-list-light.png');
  check('H2 title', (await page.locator('h2').first().textContent())?.trim().length > 0);
  check('No JS errors saat load', consoleErrors.length === 0, consoleErrors.join('; '));

  // â”€â”€ 3. CRUD Create modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  console.log('\n[3] Create modal');
  await page.locator('button:has-text("Tambah")').first().click();
  await page.waitForTimeout(800);
  await shot(page, '03-create-modal.png');
  // Riwayat Pembayaran: picks type, fill, waitForResponse, assert 200, screenshot
  await page.locator('button .fa-times').first().click();
  await page.waitForTimeout(500);

  // â”€â”€ 4. Filter â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  // Riwayat Pembayaran: filter Status/Type/Trash, assert row badge, screenshot

  // â”€â”€ 5. Search â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  // Riwayat Pembayaran: fill Cari + Enter, assert row, clear, screenshot

  // â”€â”€ 6. Sort â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  // Riwayat Pembayaran: click th, assert, screenshot

  // â”€â”€ 7. Pagination â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  // Riwayat Pembayaran: select per_page 25, assert max 25 rows, screenshot

  // â”€â”€ 8. Bulk select â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  // Riwayat Pembayaran: check first checkbox, assert bulk bar, screenshot

  // â”€â”€ 9. Edit modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  // Riwayat Pembayaran: click Edit, assert pre-filled, edit + waitForResponse 200, screenshot

  // â”€â”€10. Detail modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  // Riwayat Pembayaran: click Detail, assert content, screenshot

  // â”€â”€11. Delete / Soft-delete â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  // Riwayat Pembayaran: delete + 200, filter Terhapus, restore, screenshot

  // â”€â”€12. Import/Export â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  // Riwayat Pembayaran: Import modal, template link, screenshot

  // â”€â”€13. Dark mode â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  console.log('\n[13] Dark mode');
  await page.evaluate(() => document.documentElement.classList.add('dark'));
  await page.waitForTimeout(500);
  await shot(page, '13-dark.png');
  await page.evaluate(() => document.documentElement.classList.remove('dark'));

  // â”€â”€14. Responsive â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  console.log('\n[14] Responsive');
  await page.setViewportSize({ width: 390, height: 800 }); await page.waitForTimeout(500); await shot(page, '14-mobile.png');
  await page.setViewportSize({ width: 768, height: 1024 }); await page.waitForTimeout(500); await shot(page, '14-tablet.png');
  await page.setViewportSize({ width: 1280, height: 800 });

  // â”€â”€ Final â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
  check('No JS errors selama semua test', consoleErrors.length === 0, consoleErrors.join('; '));
  console.log(`\n${'â•'.repeat(60)}\nSUMMARY: ${pass} pass, ${fail} fail\n${'â•'.repeat(60)}\n`);
  if (failures.length) failures.forEach(f => console.log('  - ' + f));
  await browser.close();
  process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error('FATAL:', e); process.exit(1); });

