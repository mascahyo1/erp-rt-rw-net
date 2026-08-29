# AGENTS.md

Compact guide for AI coding agents working in **ERP RT/RW Net** — multi-tenant ISP management app (tagihan, pembayaran, pelanggan, karyawan, perusahaan). Stack: Laravel 13 / PHP 8.3 / Vue 3.5 / InertiaJS v2 / Vite 8 / Tailwind 4.

For exhaustive context, read in this order before doing non-trivial work:

1. `STANDARDS.md` — **v4 Single Source of Truth** (§1 kontrak build-time, §7 headed E2E per-langkah, §8 DoD) + `workflow.md` ringkas
2. `CLAUDE.md` — stack, commands, gotchas (verbose, slightly stale)
3. `dokumentasi/CONVENTIONS.md` — **Hybrid Inertia + AJAX** form rules (mandatory before writing forms)
4. `DOCS.md` — demo login credentials
5. `dokumentasi/operator-perusahaan/` — per-feature docs

---

## Workflow (non-negotiable)

Teliti workflow — agents in this repo have burned cycles by rushing:

1. **Confirm requirements** until ≥95% confident. Ask with concrete options, not open-ended.
2. **Write a todo list and wait for user sign-off** before coding.
3. **One file at a time** — after each file change: take a screenshot (headed browser), verify, re-read the diff for typos/side effects, then move on.
4. **No silent iteration on visual issues** — describe current vs expected in text (row-by-row, positions, colors) before asking for fixes.
5. **Respond in Indonesian**, concise, with `path:line` when referencing code.

---

## Test runners — use the right one (STANDARDS.md §7)

This repo has THREE test paths; don't confuse them — **STANDARDS §7.1 adalah sumber tunggal**:

| Path | Engine | Use for | Notes |
|---|---|---|---|
| `tests/Feature/**/*.php`, `tests/Unit/` | **PHPUnit** (via `php artisan test`) | Backend unit/feature, auth, RBAC, controllers | Default. `phpunit.xml` points at DB `erp_rt_rw_net` (NOT `_tmp`). Via `.\parallel-test.ps1`. **Jangan** untuk verify UI. |
| `tests/Browser/Playwright/Feature/**/*.cjs` | **Playwright (Node, CommonJS)** | **PRIMARY — UI/E2E, visual, CRUD** | **Always `headless: false` + `slowMo:350` for debug** (`PLAYWRIGHT_HEADLESS=true` untuk CI). Template `DeepVerifyKonfigurasiSaaS.cjs`, per-langkah `assert+screenshot+network`. `chromedriver.exe` in root. |
| `tests/Browser/deprecatedoldFeature/**/*.php` | **Laravel Dusk** | **Legacy** — jangan buat baru | Scaffolding + `parallel-dusk.ps1` exist. New E2E → Playwright `.cjs`. `.kilo/skills/new-feature-test.md` **outdated**. |

Helpers:
- `tests/Browser/Playwright/support/PlaywrightHelper.cjs` — headed launch, login (`.fa-building`), screenshot, console capture, `assertNoConsoleErrors()`
- `tests/Browser/Playwright/support/baseUrl.cjs` — baseUrl helper (env → .env → default)
- `tests/Browser/Playwright/support/DeepVerifyTemplate.cjs` — template 15 langkah deep verify
- `scripts/check-testing-standards.cjs` — enforce STANDARDS §1+§7 (0 errors = boleh testing)
- `parallel-dusk.ps1` — multi-worker Dusk + CSV report
- `parallel-test.ps1` — `php artisan test --parallel`

Single test runs:
```bash
# Playwright — single (headed)
node tests/Browser/Playwright/Feature/OperatorPerusahaan/CheckPdfLogoHeaded.cjs
node tests/Browser/Playwright/Feature/OperatorSaas/DeepVerifyKonfigurasiSaaS.cjs

# PHPUnit — single
php artisan test --filter=CustomerTest
# atau parallel:
.\parallel-test.ps1 -MaxWorkers 8

# Standards check
node scripts/check-testing-standards.cjs
```

Playwright convention: log in via the `.fa-building` button on the login page → pick company from dropdown. Demo users are seeded by `DemoSeeder` (see `DOCS.md`). Deep verify checklist: lihat `STANDARDS.md §7.3`.

---

## Hybrid Inertia + AJAX (read CONVENTIONS.md first)

| Use case | Protocol |
|---|---|
| Navigation, sidebar, breadcrumbs, redirect after create | **Inertia** — `Inertia::render()` + `<Link>` |
| CRUD form submit in modal/inline, no navigation | **Pure AJAX** — `fetch()` + `response()->json()` |

Three rules agents break repeatedly:

1. **`useForm().put()/patch()/delete()` with file upload is BROKEN** — PHP doesn't parse multipart for non-POST methods, so `$request->all()` is empty. Use `fetch()` + `FormData` directly, or POST + `_method: PUT` transform.
2. **AJAX controllers must `use Illuminate\Http\JsonResponse` explicitly** — inside `App\Http\Controllers\OperatorPerusahaan`, unqualified `JsonResponse` resolves to the controller's namespace → `TypeError`.
3. **AJAX routes** live under `/api/...` inside the portal group (e.g. `/operator-perusahaan/api/...`).

Composable: `resources/js/Composables/useAjaxForm.js` — use it.

---

## Critical gotchas (already debugged — don't re-discover)

- **DomPDF does NOT support CSS Flexbox.** Use `<table>`/`<tr>`/`<td>`, `display: table/table-cell`, `float`, or `inline-block`. Affects `TagihanController::buildInvoiceHtml()`, `PembayaranController::downloadPdf()` (via `resources/views/pdf/payment-receipt.blade.php`), and PhpWord HTML exports.
- **File proxy route requires auth** (`auth:admin-company,admin-saas,employee,customer`) — DomPDF server-side fetches to it 302 to login. Solution: embed logos as base64 data URI via `Company::getLogoDataUri('logo', 'minio')`.
- **Vue runtime cache**: after changing computed attributes in models or Vue components, **`npm run build`** (HMR alone is not enough). Then verify in headed browser.
- **CSRF for AJAX** is already in `<meta name="csrf-token">` in `resources/views/app.blade.php` — just read it from `document.querySelector(...)`.
- **Inertia 303 redirects**: `back()->with()` from controllers → 302/303; Inertia client follows. Use `onSuccess` to update local state.
- **Route not picked up** → `php artisan route:clear && php artisan config:clear`.
- **`.npmrc` has `ignore-scripts=true`** — `npm install` skips postinstall scripts (intentional; do not remove).

---

## Data layer conventions

Models in `app/Models/` use three traits — apply them to new models:

- `HasUuidV7` — UUID v7 primary keys (timestamp-ordered). **Never** auto-increment.
- `HasSoftDelete` — `trashed()`, `withTrashed()`.
- `HasBlameable` — auto-fills `created_by`, `updated_by`, `deleted_by`, `restored_by`.

DB:
- Dev DB: `erp_rt_rw_net_tmp` (per `.env.example`).
- PHPUnit DB: `erp_rt_rw_net` (per `phpunit.xml`, hardcoded with `force="true"`) — make sure both exist.
- Full reset (DESTRUCTIVE): `php artisan migrate:fresh --seed` → runs `DatabaseSeeder` → `DemoSeeder` + `PermissionSeeder`.
- Storage: MinIO via `Storage::disk('minio')` (private). Browser access goes through `file.proxy` route (auth-gated, see gotcha above).

---

## Frontend dev

- `npm run dev` runs Vite at `http://localhost:5173` with HMR.
- `php artisan serve` for Laravel at `:8000`. Run in a second terminal.
- After Vue/Tailwind changes: `npm run build` to refresh the build that browsers/tests use (Dusk uses `public/build/`, not Vite).
- Tailwind 4 — no `tailwind.config.js`, config is in `resources/css/`.
- Login pages: click `.fa-building` icon → pick company from dropdown.

---

## Skills & skills to load

- `.claude/skills/crud` — CRUD generation/modification
- `.claude/skills/rbac` — permission/role multi-tenant
- `.claude/skills/excel-export` — Excel import/export
- `.claude/skills/laravel-export` — long-running Laravel jobs
- `.kilo/skills/crud-table-standard` — table/CRUD UI standards

If the user's request matches a skill, load it before writing code.

---

## Coding conventions (this repo's defaults)

- One function = one responsibility. Delete unused code, don't comment it out.
- Eager-load relations (`with()`) or use computed attributes — no N+1.
- Validate every request with `$request->validate([...])`.
- Never hardcode credentials.
- Branch: `feature/{name}` or `fix/{short-desc}`. Commit: `feat(scope): summary`.
