#!/usr/bin/env node
/**
 * check-testing-standards.cjs — Enforce STANDARDS.md §1 + §7 ke semua testing
 *
 * Kontrak:
 *  §1 Lifecycle/State/Selector solid — selector harus data-testid/role, tidak ada nth-child rapuh, lifecycle solid (build check)
 *  §7 Headed E2E per-langkah — setiap Playwright test harus punya assert + screenshot + network (untuk submit) + headed false + slowMo
 *
 * Cara pakai:
 *   node scripts/check-testing-standards.cjs              # audit semua
 *   node scripts/check-testing-standards.cjs --fix        # auto-fix headless:true → false + tambah slowMo
 *   node scripts/check-testing-standards.cjs --json       # output JSON untuk CI
 *
 * Exit code: 0 = 0 error, 1 = ada violation
 */
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const PLAYWRIGHT_DIR = path.join(ROOT, 'tests', 'Browser', 'Playwright', 'Feature');
const FEATURE_DIR = path.join(ROOT, 'tests', 'Feature');
const DUSK_DIR = path.join(ROOT, 'tests', 'Browser', 'deprecatedoldFeature');

const args = process.argv.slice(2);
const FIX = args.includes('--fix');
const JSON_OUT = args.includes('--json');

let errors = [];
let warnings = [];
let fixes = [];

function walk(dir, ext, out = []) {
  if (!fs.existsSync(dir)) return out;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) walk(p, ext, out);
    else if (e.name.endsWith(ext)) out.push(p);
  }
  return out;
}

function rel(p) { return path.relative(ROOT, p).replace(/\\/g, '/'); }

// ──────────────────────────────────────────────────────────────────────────
// 1. Playwright audit — STANDARDS §7
// ──────────────────────────────────────────────────────────────────────────
const cjsFiles = walk(PLAYWRIGHT_DIR, '.cjs');

for (const f of cjsFiles) {
  const c = fs.readFileSync(f, 'utf8');
  const r = rel(f);

  // Skip result/ helper subfolders yang bukan test utama
  if (r.includes('/result/') || r.includes('/Feature/result/') || r.includes('/screenshots/')) continue;
  if (r.includes('support/') || r.includes('runner.cjs') || r.includes('setup-')) continue;

  const hasHeadlessFalse = /headless:\s*false/.test(c);
  const hasHeadlessTrue = /headless:\s*true/.test(c);
  const hasHeadless = /headless:\s*(true|false)/.test(c);
  const hasSlowMo = /slowMo:\s*\d+/.test(c);
  const hasAssert = /\b(assert|check\(|expect\(|toBeVisible|toContainText)\b/.test(c);
  const hasShot = /\b(screenshot|shot\(|takeScreenshot|helper\.screenshot)\b/.test(c);
  const hasBase = /\b(BASE|baseUrl|baseURL|PLAYWRIGHT_BASE_URL)\b/.test(c);
  const hasNetwork = /\b(waitForResponse|waitForRequest|waitFor.*Response)\b/.test(c);
  const hasConsoleCapture = /\b(pageerror|console\.error|consoleErrors)\b/.test(c);
  const usesDataTestId = /data-testid|getByTestId/.test(c);
  const usesFragile = /nth-child|:nth\(|:first-child.*:last/.test(c) && !/data-testid/.test(c);
  const hasUniqueData = /Date\.now\(\)|nanoid|testSuffix|timestamp/.test(c);

  const isUI = /page\.goto|page\.locator|page\.screenshot|shot\(|takeScreenshot/.test(c);
  const isAPIOnly = /ctx\.request|request\.post|request\.get/.test(c) && !isUI;
  // §7.1 Headed — hanya untuk UI. API-only boleh headless:true
  if (hasHeadlessTrue && !/process\.env.*HEADLESS|HEADLESS.*process\.env/.test(c)) {
    if (isUI) {
      errors.push({ file: r, rule: '§7.1 headless:false', msg: 'UI test pakai headless:true tanpa env gate — ganti ke headless:false + slowMo (atau gate via PLAYWRIGHT_HEADLESS env).' });
    } else if (isAPIOnly) {
      warnings.push({ file: r, rule: '§7.1 headless', msg: 'API-only test pakai headless:true — ok, tapi pertimbangkan gate via PLAYWRIGHT_HEADLESS env.' });
    } else {
      warnings.push({ file: r, rule: '§7.1 headless', msg: 'Pakai headless:true tanpa env gate — untuk UI ganti ke false+slowMo, untuk API ok.' });
    }
    if (FIX && isUI) {
      let nc = c.replace(/headless:\s*true/g, 'headless: false');
      // normalisasi slowMo:0 → 350
      nc = nc.replace(/slowMo:\s*0/g, 'slowMo: 350');
      if (!/slowMo:\s*\d+/.test(nc)) {
        nc = nc.replace(/chromium\.launch\(\{/, 'chromium.launch({ slowMo: 350,');
        nc = nc.replace(/chromium\.launch\(\{\s*headless:\s*false/, 'chromium.launch({ headless: false, slowMo: 350');
      }
      fs.writeFileSync(f, nc, 'utf8');
      fixes.push(r + ' → headless:true→false + slowMo:350');
    }
  }
  if (!hasHeadless) {
    warnings.push({ file: r, rule: '§7.1 headed', msg: 'Tidak ada headless config — default Playwright adalah headless:true, eksplisit set headless:false + slowMo:350.' });
  }
  if (hasHeadlessFalse && !hasSlowMo) {
    warnings.push({ file: r, rule: '§7.1 slowMo', msg: 'headless:false tanpa slowMo — tambah slowMo: 300-500 agar animasi terlihat.' });
    if (FIX) {
      let nc = c.replace(/headless:\s*false/, 'headless: false, slowMo: 350');
      fs.writeFileSync(f, nc, 'utf8');
      fixes.push(r + ' → tambah slowMo:350');
    }
  }

  // assert/screenshot hanya wajib untuk UI deep verify (CRUD/DeepVerify/View). Debug/Inspect/API exempt → warning
  const isDeepVerify = /DeepVerify|CRUDTest|ViewTest|PermissionTest|ImportExport/.test(r);
  const hasAssertLoose = /\b(assert|check\(|expect\(|toBeVisible|toContainText|toHaveCount|assert\(|Passed|passed\+\+|results\.passed)\b/.test(c) || /throw new Error/.test(c);
  const hasShotLoose = /\b(screenshot|shot\(|takeScreenshot|helper\.screenshot|screenshots)\b/.test(c);
  if (!hasAssertLoose) {
    if (isDeepVerify) errors.push({ file: r, rule: '§7.2 assert', msg: 'Tidak ada assert/check/expect — setiap langkah wajib assert (gagal = fail).' });
    else warnings.push({ file: r, rule: '§7.2 assert', msg: 'Tidak ada assert — untuk deep verify wajib, untuk debug/inspect/api warning saja.' });
  }
  if (!hasShotLoose) {
    if (isDeepVerify && isUI) errors.push({ file: r, rule: '§7.2 screenshot', msg: 'Tidak ada screenshot/shot — setiap langkah wajib screenshot sebagai bukti visual.' });
    else if (isUI) warnings.push({ file: r, rule: '§7.2 screenshot', msg: 'Tidak ada screenshot — UI test sebaiknya punya screenshot per langkah.' });
  }
  if (!hasBase) warnings.push({ file: r, rule: '§7.1 BASE', msg: 'Tidak pakai BASE/baseUrl helper — rawan hardcode URL.' });
  if (!hasConsoleCapture) warnings.push({ file: r, rule: '§7.2 console', msg: 'Tidak capture pageerror/console.error — deep verify wajib cek no JS errors.' });
  if (!hasUniqueData && /CRUD|create|Create/.test(r)) warnings.push({ file: r, rule: '§7.4 unique data', msg: 'Test CRUD tanpa Date.now/nanoid — data harus unik per run + cleanup.' });
  if (usesFragile) warnings.push({ file: r, rule: '§1.3 selector solid', msg: 'Pakai selector rapuh (nth-child) tanpa data-testid — ganti ke data-testid/role.' });
  // File upload test should have network check
  if (/Upload|upload|File/.test(c) && !hasNetwork) warnings.push({ file: r, rule: '§7.2 network', msg: 'Ada file upload tapi tanpa waitForResponse — wajib intercept network untuk verify 200/422.' });

  // CommonJS check
  if (f.endsWith('.cjs') && /import\s+.*from/.test(c) && !/require\(/.test(c)) {
    warnings.push({ file: r, rule: '§7.1 CJS', msg: 'Pakai ESM import di .cjs — harus CommonJS require().' });
  }
}

// ──────────────────────────────────────────────────────────────────────────
// 2. PHPUnit audit — §7.1 (hanya backend)
// ──────────────────────────────────────────────────────────────────────────
const phpFiles = walk(FEATURE_DIR, '.php');
for (const f of phpFiles) {
  const c = fs.readFileSync(f, 'utf8');
  const r = rel(f);
  if (!/class\s+\w+Test/.test(c)) continue;
  if (!/function\s+test_/.test(c)) warnings.push({ file: r, rule: 'PHPUnit naming', msg: 'Tidak ada method test_* — pakai test_ prefix.' });
  if (!/assert/.test(c)) warnings.push({ file: r, rule: 'PHPUnit assert', msg: 'Tidak ada assert — test tanpa assertion = risky.' });
  // Check CSRF bypass via TestCase (expected)
  // Check Inertia assert for page tests
  if (/->get\(/.test(c) && !/assertInertia|assertOk|assertRedirect/.test(c)) warnings.push({ file: r, rule: 'PHPUnit Inertia', msg: 'GET tanpa assertInertia/assertOk — tambah assertion.' });
}

// ──────────────────────────────────────────────────────────────────────────
// 3. Build check — §1.1
// ──────────────────────────────────────────────────────────────────────────
const hotExists = fs.existsSync(path.join(ROOT, 'public', 'hot'));
const buildExists = fs.existsSync(path.join(ROOT, 'public', 'build', 'manifest.json')) || fs.existsSync(path.join(ROOT, 'public', 'build', '.vite', 'manifest.json'));
if (hotExists && buildExists) {
  warnings.push({ file: 'public/hot + public/build', rule: '§1.1 build', msg: 'public/hot ada (dev) tapi public/build juga ada — untuk Dusk/Playwright pastikan npm run build fresh sebelum headed test.' });
}
if (!buildExists) {
  warnings.push({ file: 'public/build', rule: '§1.1 build', msg: 'public/build tidak ada — jalankan npm run build sebelum E2E.' });
}

// ──────────────────────────────────────────────────────────────────────────
// 4. Legacy doc check
// ──────────────────────────────────────────────────────────────────────────
const standardsExists = fs.existsSync(path.join(ROOT, 'STANDARDS.md'));
const workflowExists = fs.existsSync(path.join(ROOT, 'workflow.md'));
if (!standardsExists) errors.push({ file: 'STANDARDS.md', rule: '§1 contract', msg: 'STANDARDS.md tidak ada — single source of truth hilang.' });
if (!workflowExists) errors.push({ file: 'workflow.md', rule: '§1 contract', msg: 'workflow.md tidak ada — ringkas 1 halaman hilang.' });

// ──────────────────────────────────────────────────────────────────────────
// 5. Output
// ──────────────────────────────────────────────────────────────────────────
if (JSON_OUT) {
  console.log(JSON.stringify({ errors, warnings, fixes, summary: { files: cjsFiles.length, phpFiles: phpFiles.length, errors: errors.length, warnings: warnings.length } }, null, 2));
  process.exit(errors.length > 0 ? 1 : 0);
}

console.log('═══════════════════════════════════════════════════════════════');
console.log('  check-testing-standards.cjs — STANDARDS.md §1 + §7');
console.log('═══════════════════════════════════════════════════════════════\n');

console.log(`Scanned: ${cjsFiles.length} Playwright .cjs, ${phpFiles.length} PHP Feature tests`);
console.log(`Fix mode: ${FIX ? 'ON (auto-fix applied)' : 'OFF (use --fix to auto-fix headless)'}\n`);

if (errors.length) {
  console.log(`❌ ERRORS (${errors.length}) — wajib fix sebelum testing:`);
  for (const e of errors) console.log(`  [${e.rule}] ${e.file}\n    → ${e.msg}`);
  console.log('');
}
if (warnings.length) {
  console.log(`⚠️  WARNINGS (${warnings.length}) — sebaiknya fix:`);
  for (const w of warnings) console.log(`  [${w.rule}] ${w.file}\n    → ${w.msg}`);
  console.log('');
}
if (fixes.length) {
  console.log(`🔧 FIXES APPLIED (${fixes.length}):`);
  for (const f of fixes) console.log(`  - ${f}`);
  console.log('');
}

console.log('───────────────────────────────────────────────────────────────');
if (errors.length === 0 && warnings.length === 0) console.log('✅ 0 errors, 0 warnings — semua testing sudah patuh STANDARDS.md §1+§7');
else if (errors.length === 0) console.log(`✅ 0 errors, ${warnings.length} warnings — boleh testing, tapi perbaiki warnings.`);
else console.log(`❌ ${errors.length} errors — belum boleh testing, fix dulu (kontrak §1).`);

console.log('───────────────────────────────────────────────────────────────');
console.log('\nDetail: STANDARDS.md §1 (lifecycle/state/selector) + §7 (headed per-langkah) + §8 DoD');
console.log('Template: tests/Browser/Playwright/Feature/OperatorSaas/DeepVerifyKonfigurasiSaaS.cjs\n');

process.exit(errors.length > 0 ? 1 : 0);
