/**
 * REPRO: "The cf-turnstile-response field is required." di /login-karyawan
 * Skenario user: clear cache + incognito (first load, cold cache) + kredensial benar.
 * LOG: semua langkah FE (console page + marker), BE (semua request+response),
 * screenshot tiap langkah, video mp4.
 */
const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

const BASE = 'http://erp-rt-rw-net.test';
const OUT = path.join(__dirname, '..', 'result', 'TurnstileLoginRepro');
const SC = path.join(OUT, 'screenshots');
const VID = path.join(OUT, 'videos');
for (const d of [OUT, SC, VID]) if (!fs.existsSync(d)) fs.mkdirSync(d, { recursive: true });
const LOG = path.join(OUT, 'log.txt');
fs.writeFileSync(LOG, '');
const log = (s) => { const line = `[${new Date().toISOString()}] ${s}`; console.log(line); fs.appendFileSync(LOG, line + '\n'); };
const snap = async (p, n) => { const f = path.join(SC, n + '.png'); await p.screenshot({ path: f, fullPage: false }); log('  [snap] ' + f); };

async function pageMarker(page, label) {
  await page.evaluate((l) => console.log('[TEST-MARKER] ' + l), label);
}

(async () => {
  log('=== START: Turnstile login-karyawan repro (cold/incognito first load) ===');
  const browser = await chromium.launch({ headless: false, slowMo: 250 });
  // context baru = fresh storage (setara incognito, tanpa cache browser)
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, recordVideo: { dir: VID, size: { width: 1440, height: 900 } } });
  const page = await ctx.newPage();

  // FE logging: semua tipe console
  page.on('console', m => log(`  [FE-${m.type()}] ${m.text().slice(0, 220)}`));
  page.on('pageerror', e => log('  [FE-pageerror] ' + e.message.slice(0, 300)));
  page.on('requestfailed', r => log(`  [BE-failed] ${r.method()} ${r.url()}`));
  // BE logging: request + response
  page.on('request', r => { if (r.url().includes('login-karyawan') || r.url().includes('turnstile') || r.url().includes('challenges')) log(`  [BE-req] ${r.method()} ${r.url().replace(BASE, '').slice(0, 110)}`); });
  page.on('response', async r => {
    if (r.url().includes('login-karyawan')) {
      let body = '';
      try { body = (await r.text()).slice(0, 300); } catch (e) {}
      log(`  [BE-res ${r.status()}] ${r.request().method()} ${r.url().replace(BASE, '')}${r.status() >= 400 ? ' BODY: ' + body : ''}`);
    }
  });

  let videoPath = null;
  try {
    // STEP 1: first load (cold)
    log('\n--- STEP 1: goto /login-karyawan (first load, fresh context) ---');
    await pageMarker(page, 'before-goto');
    await page.goto(BASE + '/login-karyawan', { waitUntil: 'load' });
    await pageMarker(page, 'after-goto');
    await page.waitForTimeout(3000);
    await snap(page, '01-login-karyawan-first-load');

    // STEP 2: state audit
    log('\n--- STEP 2: audit state widget & callback ---');
    const state = await page.evaluate(() => ({
      turnstileLoaded: !!window.turnstile,
      onTurnstileSuccessDefined: typeof window.onTurnstileSuccess === 'function',
      onTurnstileExpiredDefined: typeof window.onTurnstileExpired === 'function',
      widgetCount: document.querySelectorAll('.cf-turnstile').length,
      widgetHasIframe: !!document.querySelector('.cf-turnstile iframe'),
      widgetMarkedRendered: document.querySelector('.cf-turnstile')?.hasAttribute('data-ts-rendered'),
    }));
    log('  state: ' + JSON.stringify(state));
    await pageMarker(page, 'state-audit: ' + JSON.stringify(state));

    // STEP 3: isi kredensial + pilih perusahaan
    log('\n--- STEP 3: isi kredensial + pilih perusahaan JMP ---');
    await page.fill('input[type="email"]', 'karyawan1@jmp.test');
    await page.fill('input[type="password"]', 'password');
    await pageMarker(page, 'credentials-filled');
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
    await pageMarker(page, 'company-selected');
    await snap(page, '02-form-filled');

    // STEP 4: cek state tombol SEBELUM turnstile solved (harus disabled)
    log('\n--- STEP 4: tombol submit sebelum captcha solved ---');
    const btnBefore = await page.evaluate(() => {
      const b = document.querySelector('button[type="submit"]');
      return { disabled: b?.disabled, text: b?.textContent.trim() };
    });
    log('  button before solve: ' + JSON.stringify(btnBefore));
    await pageMarker(page, 'button-before-solve: ' + JSON.stringify(btnBefore));

    // STEP 5: tunggu turnstile auto-solve (test key) lalu cek tombol enabled + token masuk
    log('\n--- STEP 5: tunggu widget auto-solve (max 30s) ---');
    let solved = false;
    for (let i = 0; i < 30; i++) {
      await page.waitForTimeout(1000);
      const s = await page.evaluate(() => ({
        iframe: !!document.querySelector('.cf-turnstile iframe'),
        btnEnabled: !document.querySelector('button[type="submit"]')?.disabled,
        tokenSet: (() => { try { return document.querySelector('.cf-turnstile input[name="cf-turnstile-response"]')?.value?.length > 0 || false; } catch (e) { return false; } })(),
      }));
      if (s.btnEnabled) { solved = true; log(`  [t+${i + 1}s] solved: ${JSON.stringify(s)}`); break; }
      if (i % 5 === 0) log(`  [t+${i + 1}s] ${JSON.stringify(s)}`);
    }
    if (!solved) {
      log('  ✗ Turnstile TIDAK solve dalam 30s — screenshot untuk diagnosa');
      await snap(page, '03-turnstile-not-solved');
      throw new Error('Turnstile not solved in 30s');
    }
    await snap(page, '03-turnstile-solved');

    // STEP 6: submit (token sudah masuk — tidak boleh ada error cf-turnstile-response)
    log('\n--- STEP 6: submit form ---');
    await pageMarker(page, 'before-submit');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(7000);
    log('  URL setelah submit: ' + page.url());
    await snap(page, '04-after-submit');

    const urlOk = page.url().includes('/karyawan/dashboard');
    log(urlOk ? '  ✓ LOGIN SUKSES tanpa error cf-turnstile-response' : '  ✗ LOGIN GAGAL — masih di: ' + page.url());

    // STEP 7: uji halaman lupa password (smoke + turnstile gate)
    log('\n--- STEP 7: smoke /lupa-password-karyawan (first load) ---');
    const ctx2 = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page2 = await ctx2.newPage();
    const errs2 = [];
    page2.on('pageerror', e => errs2.push(e.message));
    await page2.goto(BASE + '/lupa-password-karyawan', { waitUntil: 'load' });
    await page2.waitForTimeout(3500);
    await snap(page2, '05-lupa-password');
    const lpState = await page2.evaluate(() => ({
      onTurnstileSuccessDefined: typeof window.onTurnstileSuccess === 'function',
      btnDisabled: document.querySelector('button[type="submit"]')?.disabled,
      hasSummary: !!document.querySelector('[data-testid="form-error-summary"], .bg-red-50'),
    }));
    log('  lupa-password state: ' + JSON.stringify(lpState));
    log('  pageerror: ' + errs2.length);

    log('\n=== RESULT: ' + (urlOk ? 'PASS' : 'FAIL') + ' ===');
  } catch (e) {
    log('✗ EXCEPTION: ' + e.message);
    await snap(page, '99-exception');
  } finally {
    const vid = page.video();
    if (vid) log('  Video: ' + await vid.path());
    await browser.close();
    process.exit(0);
  }
})();
