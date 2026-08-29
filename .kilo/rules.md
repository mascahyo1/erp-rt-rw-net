# Environment Rules — ERP RT/RW Net

> Sinkron dengan `STANDARDS.md` v4 §7 & `workflow.md`. Prioritas testing: **Playwright primary**, PHPUnit backend, Dusk legacy.

## Frontend dev server
- `npm run dev` is ALWAYS running (Vite HMR at `http://[::1]:5173` / `localhost:5173`). User refreshes manually.
- **After any Vue/Tailwind/model computed change:** `npm run build` **wajib** — HMR cache tidak cukup, Playwright/Dusk baca `public/build/`.

## Test runners — pakai yang benar (STANDARDS §7.1)

| Runner | Path | Engine | Kapan pakai |
|--------|------|--------|-------------|
| **Playwright (PRIMARY)** | `tests/Browser/Playwright/Feature/**/*.cjs` | Playwright Node `.cjs` | Semua UI/E2E, visual, CRUD — `headless:false`, `slowMo:300-500`, per-langkah assert+screenshot+network. Lihat `tests/Browser/Playwright/README.md` & `STANDARDS.md §7`. |
| PHPUnit Feature/Unit | `tests/Feature/**/*.php`, `tests/Unit/` | PHPUnit (`php artisan test`) | Backend murni: auth, RBAC, controller JSON. Via `.\parallel-test.ps1`. **Jangan** untuk verify UI (memory: `testing-prioritas-playwright`). |
| Dusk (LEGACY) | `tests/Browser/deprecatedoldFeature/**/*.php` (old) + `tests/Browser/Feature/**/*.php` (jika ada) | Laravel Dusk | Jangan buat baru. New E2E → Playwright `.cjs`. Butuh `public/hot` rename + `chromedriver.exe`. |

## Playwright (primary) — aturan wajib (STANDARDS §7.2-7.6)

- **Template:** `tests/Browser/Playwright/Feature/OperatorSaas/DeepVerifyKonfigurasiSaaS.cjs` (15 langkah). Copy untuk fitur baru, jangan `QuickVerify*`.
- **Headed:** `chromium.launch({ headless:false, slowMo:350 })` — `headless:true` hanya untuk CI (`PLAYWRIGHT_HEADLESS=true` env).
- **Per langkah:** `check/assert` + `shot/screenshot` + `waitForResponse` untuk submit + `pageerror`/`console.error` capture. Screenshot ke `tests/Browser/Playwright/Feature/result/{Portal}/{Fitur}/` (gitignored).
- **Data unik:** suffix `Date.now()` / nanoid, cleanup soft-delete.
- **Selector:** `data-testid` dulu, jangan `nth-child`/XPath.
- **Runner:** `node tests/Browser/Playwright/Feature/OperatorPerusahaan/CheckPdfLogoHeaded.cjs` — single. Parallel belum ada runner khusus (jalankan file satu-satu atau via `runner.cjs`).

## Dusk browser tests (legacy)

- Requires production build (`public/build`) — Chrome sandbox tidak bisa reach Vite dev server.
- When running Dusk: temporarily rename `public/hot` → `public/hot.bak`, run dusk, then restore. Build artifacts may be stale if recently changed.
- Screenshots per-guard `tests/Browser/screenshots/operator-saas/login/` (gitignored).
- Video: `tests/Browser/Support/VideoRecorder.php` + `BrowserVideoRecorder.php` — auto via `parallel-dusk.ps1` (FFmpeg→MP4, fallback HTML). Lihat `dokumentasi/testing/dusk-running.md`.
- **No migration** — `DatabaseMigrations` removed. Seed once: `php artisan setup --demo` atau `migrate:fresh --seed`.
- Run: `.\parallel-dusk.ps1` (4 workers) / `.\parallel-dusk.ps1 -MaxWorkers 8 -Folders "OperatorSaas,Karyawan"` — `public/hot` auto-disabled/restored.

### Parallel Dusk details
- Files split round-robin across workers, same DB `erp_rt_rw_net_tmp`, factory unique data.
- Output: `tests/Browser/dusk-output/worker-*.log` + `worker-*.xml` + `dusk-report-*.csv`.

## PHPUnit feature tests

- Can run anytime without special setup (but need DB `erp_rt_rw_net` — lihat `phpunit.xml` force). `TestCase.php` bypass CSRF via `withoutMiddleware([PreventRequestForgery::class])`.
- **Always use** `.\parallel-test.ps1` for full run — jangan `php artisan test` langsung (lambat ~68s vs ~15s @8 workers).
- `parallel-test.ps1 -MaxWorkers 8` — each worker subset `*Test.php` round-robin → `tests/Browser/dusk-output/ftest-*.log` + `ftest-report-*.csv`.

## Test structure (current)

```
tests/
├── Browser/
│   ├── Playwright/
│   │   ├── Feature/
│   │   │   ├── OperatorSaas/{LoginTest,PerusahaanCRUDTest,DeepVerifyKonfigurasiSaaS}.cjs
│   │   │   ├── OperatorPerusahaan/{DashboardTest,CustomerCRUDTest,PerusahaanSayaCRUDTest}.cjs
│   │   │   ├── Karyawan/{DashboardTest,TagihanViewTest}.cjs
│   │   │   └── Pelanggan/{DashboardTest,TagihanSayaViewTest}.cjs
│   │   ├── result/               ← headed screenshots (gitignored)
│   │   ├── videos/               ← Playwright video (gitignored)
│   │   └── support/{baseUrl.cjs,PlaywrightHelper.cjs}
│   ├── deprecatedoldFeature/     ← Dusk legacy (jangan hapus)
│   ├── Support/{VideoRecorder,BrowserVideoRecorder}.php
│   └── dusk-output/              ← parallel logs + csv (gitignored)
├── Feature/
│   ├── Auth/{AdminSaas,AdminCompany,Customer,Employee}AuthenticationTest.php
│   ├── OperatorSaas/{Perusahaan,AdminSaas,RoleSaas}Test.php
│   └── OperatorPerusahaan/{Customer,Karyawan,PerusahaanSaya}Test.php
└── Unit/
```

## Standards enforcement

- `STANDARDS.md` §1,§7,§8 adalah kontrak build-time — jalankan `node scripts/check-testing-standards.cjs` sebelum push. 0 violation = boleh testing.
- `workflow.md` = ringkas 1 halaman.
- Memory: `teliti-workflow` (todo+sign-off+1 file+screenshot), `testing-with-headed-browser`, `deep-verify-no-quick`, `testing-prioritas-playwright`.

## Parallel runners quick ref

```powershell
# PHPUnit
.\parallel-test.ps1
.\parallel-test.ps1 -MaxWorkers 8

# Dusk
.\parallel-dusk.ps1
.\parallel-dusk.ps1 -MaxWorkers 8 -Folders "OperatorPerusahaan,Pelanggan"

# Playwright (single, headed)
node tests/Browser/Playwright/Feature/OperatorSaas/DeepVerifyKonfigurasiSaaS.cjs
node tests/Browser/Playwright/Feature/OperatorPerusahaan/PerusahaanSayaCRUDTest.cjs
```
