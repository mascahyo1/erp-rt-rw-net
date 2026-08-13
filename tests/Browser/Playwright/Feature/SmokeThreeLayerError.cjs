/**
 * Smoke test: halaman hasil 3-lapis error — pastikan render tanpa FE error.
 */
const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE = 'http://erp-rt-rw-net.test';
const OUT = path.join(__dirname, '..', 'result', 'SmokeThreeLayer');
const LOG = path.join(OUT, 'log.txt');
if (!fs.existsSync(OUT)) fs.mkdirSync(OUT, { recursive: true });
fs.writeFileSync(LOG, '');
const log = (s) => { console.log(s); fs.appendFileSync(LOG, s + '\n'); };

async function loginPerusahaan(page) {
  await page.goto(BASE + '/login-perusahaan', { waitUntil: 'load' });
  await page.waitForTimeout(2500);
  await page.fill('input[type="email"]', 'jamalulinsan1997@gmail.com');
  await page.fill('input[type="password"]', 'P@ssw0rd!2026');
  await page.waitForTimeout(1000);
  const hasPicker = await page.evaluate(() => !!Array.from(document.querySelectorAll('button[type="button"]')).find(x => x.textContent.includes('Cari perusahaan')));
  if (hasPicker) {
    await page.evaluate(() => Array.from(document.querySelectorAll('button[type="button"]')).find(x => x.textContent.includes('Cari perusahaan'))?.click());
    await page.waitForTimeout(2000);
    await page.fill('input[placeholder*="Cari perusahaan"]', 'JMP');
    await page.waitForTimeout(2500);
    const item = page.locator('[data-testid^="company-item-"]').first();
    if (await item.count() > 0) await item.click();
    await page.waitForTimeout(800);
  }
  await page.waitForTimeout(3000);
  await page.click('form button[type="submit"]');
  await page.waitForTimeout(6000);
}

(async () => {
  const browser = await chromium.launch({ headless: false, slowMo: 80 });
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await ctx.newPage();
  const feErrors = [];
  page.on('pageerror', e => { feErrors.push(e.message); log('  [pageerror] ' + e.message.slice(0, 200)); });

  const pagesPerusahaan = [
    '/operator-perusahaan/customer',
    '/operator-perusahaan/karyawan',
    '/operator-perusahaan/daftar-paket',
    '/operator-perusahaan/role-perusahaan',
    '/operator-perusahaan/role-web-karyawan',
    '/operator-perusahaan/admin-role-web-karyawan',
    '/operator-perusahaan/tagihan',
    '/operator-perusahaan/langganan-customer',
    '/operator-perusahaan/insentif',
    '/operator-perusahaan/riwayat-pembayaran',
    '/operator-perusahaan/riwayat-insentif',
    '/operator-perusahaan/gangguan',
    '/operator-perusahaan/admin-perusahaan',
    '/operator-perusahaan/konfigurasi-perusahaan',
  ];

  try {
    log('=== LOGIN PERUSAHAAN ===');
    await loginPerusahaan(page);
    log('  URL: ' + page.url());
    for (const p of pagesPerusahaan) {
      const before = feErrors.length;
      await page.goto(BASE + p, { waitUntil: 'load' });
      await page.waitForTimeout(2500);
      const newErr = feErrors.slice(before);
      log(`${newErr.length === 0 ? 'OK' : 'ERROR'} ${p}${newErr.length ? ' -> ' + newErr[0].slice(0, 120) : ''}`);
    }

    log('\n=== LOGIN KARYAWAN ===');
    await page.goto(BASE + '/login-karyawan', { waitUntil: 'load' });
    await page.waitForTimeout(2500);
    await page.fill('input[type="email"]', 'karyawan1@jmp.test');
    await page.fill('input[type="password"]', 'password');
    await page.waitForTimeout(1000);
    const hasPicker = await page.evaluate(() => !!Array.from(document.querySelectorAll('button[type="button"]')).find(x => x.textContent.includes('Cari perusahaan')));
    if (hasPicker) {
      await page.evaluate(() => Array.from(document.querySelectorAll('button[type="button"]')).find(x => x.textContent.includes('Cari perusahaan'))?.click());
      await page.waitForTimeout(2000);
      await page.fill('input[placeholder*="Cari perusahaan"]', 'JMP');
      await page.waitForTimeout(2500);
      const item = page.locator('[data-testid^="company-item-"]').first();
      if (await item.count() > 0) await item.click();
      await page.waitForTimeout(800);
    }
    await page.waitForTimeout(3000);
    await page.click('form button[type="submit"]');
    await page.waitForTimeout(6000);
    log('  URL: ' + page.url());

    const pagesKaryawan = [
      '/karyawan/tagihan',
      '/karyawan/insentif-saya',
      '/karyawan/langganan-customer',
      '/karyawan/riwayat-pembayaran',
      '/karyawan/gangguan',
    ];
    for (const p of pagesKaryawan) {
      const before = feErrors.length;
      await page.goto(BASE + p, { waitUntil: 'load' });
      await page.waitForTimeout(2500);
      const newErr = feErrors.slice(before);
      log(`${newErr.length === 0 ? 'OK' : 'ERROR'} ${p}${newErr.length ? ' -> ' + newErr[0].slice(0, 120) : ''}`);
    }

    log('\n=== TOTAL FE ERRORS: ' + feErrors.length + ' ===');
  } catch (e) {
    log('✗ EXCEPTION: ' + e.message);
  } finally {
    await browser.close();
    process.exit(0);
  }
})();
