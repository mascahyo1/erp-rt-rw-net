# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Tech Stack

- **Backend:** Laravel 13 + PHP 8.3
- **Frontend:** Vue 3.5 + InertiaJS v2
- **Build:** Vite 8 + TailwindCSS 4
- **UI:** Flowbite 4 + FontAwesome 7
- **Spreadsheet/Doc:** PhpSpreadsheet 5 + PhpWord 1
- **PDF:** DomPDF 3 (**JANGAN pakai CSS Flexbox — tidak support; pakai `<table>` atau `display: table`/`table-cell`**)
- **Image:** Intervention/Image 4
- **Realtime:** Reverb (Laravel Echo)
- **Storage:** MinIO via `Storage::disk('minio')` (private visibility, diakses via `file.proxy` route for browser)

## Commands

```bash
# Build assets (REQUIRED after any Vue/Tailwind change)
npm run build

# Dev server (jalanin di 2 terminal terpisah)
npm run dev              # Terminal 1: Vite hot-reload di :5173
php artisan serve        # Terminal 2: Laravel di :8000

# Reset DB + re-seed (DESTRUCTIVE — wipes all data)
php artisan migrate:fresh --seed

# Clear route cache (jika route baru gak ke-pickup)
php artisan route:clear
php artisan config:clear

# Run Playwright E2E test (single file)
node tests/Browser/Playwright/Feature/OperatorPerusahaan/CheckPdfLogoHeaded.cjs

# Convert PDF ke text untuk verify content
pdftotext path/to/file.pdf -

# Tinker (Laravel REPL)
php artisan tinker
```

## Test Fixtures (default setelah `migrate:fresh --seed`)

| User | Email | Password | Portal | Catatan |
|------|-------|----------|--------|---------|
| Super Admin | `superadmin@demo.test` | `password123` | operator-saas | Semua permission (40) |
| Admin Net Sejahtera | `admin@netsejahtera.com` | `password123` | operator-perusahaan | Semua (101) — dipakai di test terbaru |
| Admin Digital Media | `admin@digitalmedia.id` | (lihat DB) | operator-perusahaan | (lihat DB) |
| RBAC Full | `rbac.full@rtrwnet.id` | `password` | operator-perusahaan | Semua + rbac khusus |

**Companies** (PT/CV/UD tenant): `PT Net Sejahtera Abadi`, `CV Angkasa Netindo`, `PT Jaringan Prima`, `UD Net Mandiri Global`.

**Test data pattern**: selalu buat data baru per test run (timestamp/nanoid suffix), cleanup setelah selesai.

## Database & Migration Pattern

- **Primary keys**: UUID v7 (timestamp-ordered) via `HasUuidV7` trait — JANGAN pakai auto-increment
- **Soft delete**: `HasSoftDelete` trait (`trashed()`, `withTrashed()`)
- **Blameable**: `HasBlameable` trait (auto-fill created_by, updated_by, deleted_by, restored_by)
- **Re-seed**: `php artisan migrate:fresh --seed` (DESTRUCTIVE)

## Testing — lihat `STANDARDS.md §7` (Single Source)

**Tool primary:** Playwright (NodeJS) — `tests/Browser/Playwright/Feature/**/*.cjs`
**Secondary:** PHPUnit `tests/Feature/**/*.php` untuk backend murni (via `.\parallel-test.ps1`)
**Legacy:** Laravel Dusk `tests/Browser/deprecatedoldFeature/**/*.php` — jangan buat baru (STANDARDS §7.1)

- Test files: `tests/Browser/Playwright/Feature/{portal}/{Resource}Test.cjs`
- All test files use CommonJS (`.cjs`), NOT ESM
- **WAJIB pakai `headless: false` + `slowMo:350`** untuk debug visual (lihat [memory](.claude/projects/c--laragon-www-erp-rt-rw-net/memory/testing-with-headed-browser.md) & `STANDARDS.md §7.1`). CI: `PLAYWRIGHT_HEADLESS=true`.
- Per-langkah wajib `assert` + `screenshot` + `network` (`waitForResponse`) + `video` jika kritis — lihat `STANDARDS.md §7.2` & template `DeepVerifyKonfigurasiSaaS.cjs`
- Deep verify checklist 13 poin — lihat `STANDARDS.md §7.3`, jangan `QuickVerify*`
- Enforce: `node scripts/check-testing-standards.cjs` (0 errors = boleh testing)
- Login flow: klik button `.fa-building` di halaman login, pilih company dari dropdown

## Architecture: Hybrid Inertia + AJAX

**WAJIB baca** [dokumentasi/CONVENTIONS.md](dokumentasi/CONVENTIONS.md) sebelum menulis form baru.

| Use case | Protokol | Aturan |
|----------|----------|-------|
| Navigasi (sidebar, breadcrumb, redirect) | **Inertia** | `Inertia::render()` + `<Link>` |
| CRUD form submit (modal/inline, no navigation) | **Pure AJAX** | `fetch()` + JSON `response()->json()` |

**AJAX route convention** (untuk pisah dari Inertia):
- Route prefix `api` di dalam portal group (contoh: `/operator-perusahaan/api/...`)
- Controller return `Illuminate\Http\JsonResponse` (import eksplisit, jangan unqualified `JsonResponse` — clash dengan namespace controller)
- **POST** method (bukan PUT/PATCH) untuk form submit supaya PHP parse multipart dengan benar

**Composable reusable** untuk AJAX form submit: [`resources/js/Composables/useAjaxForm.js`](resources/js/Composables/useAjaxForm.js)

## Code Map

```
app/
  Http/Controllers/
    Controller.php                  (base)
    CompanyController.php            (SaaS CRUD perusahaan)
    OperatorPerusahaan/
      PerusahaanSayaController.php  (current admin's own company edit)
      TagihanController.php         (invoice CRUD + PDF)
      PembayaranController.php      (payment receipt + PDF)
  Http/Middleware/
    HandleInertiaRequests.php       (extends Inertia\Middleware)
    CheckPermission.php              (RBAC, alias 'permission')
    EnsureUserIsActive.php           (alias 'ensure.user.active')
  Models/                            (UUID v7 PK, soft delete, blameable)
    Company.php                      (has logo, logo_dark + getLogoDataUri())
    CustInternetInvc.php             (tagihan; has computed total_paid/remaining/payment_status_label)
    CustInternetPayment.php          (pembayaran; status enum: pending/paid/rejected)
    CompanyConfig.php                (legacy config, prefer Company fields)
  Services/
    FileUploadService.php             (auto-compress JPG/PNG/WebP→WebP, skip SVG)
database/seeders/
  DemoSeeder.php                     (run via `migrate:fresh --seed`)
  DatabaseSeeder.php                 (calls DemoSeeder + PermissionSeeder)
routes/web/
  operator-perusahaan.php            (operator-perusahaan group routes)
  operator-saas.php                  (operator-saas group routes)
  customer.php, karyawan.php, landing-page.php
  web.php                            (file-proxy + requires route files)
dokumentasi/
  CONVENTIONS.md                     (Wajib baca sebelum nulis form)
  operator-perusahaan/operator-saas/ (per-halaman docs)
  testing/
tests/Browser/Playwright/Feature/   (E2E tests, .cjs only)
```

## Key Gotchas (sudah pernah di-debug, jangan ulangi)

1. **DomPDF tidak support CSS Flexbox** — pakai `<table>`, `display: table/table-cell`, atau `float`. (Lihat [memory](.claude/projects/c--laragon-www-erp-rt-rw-net/memory/dompdf-no-flex.md))
2. **PHP tidak parse `multipart/form-data` untuk method non-POST** — pakai `form.post(url, { transform: data => ({...data, _method: 'PUT'}) })`, atau controller endpoint AJAX POST
3. **Controller `use Illuminate\Http\JsonResponse` secara eksplisit** — kalau di folder `App\Http\Controllers\OperatorPerusahaan`, unqualified `JsonResponse` resolves ke namespace yg salah dan TypeError
4. **File proxy route butuh auth** (`auth:admin-company,admin-saas,employee,customer`) — DomPDF server-side fetch ke proxy akan 302 redirect ke login. Solusi: embed logo sebagai base64 data URI via `Company::getLogoDataUri('logo', 'minio')`
5. **CSRF token harus di meta tag** untuk AJAX: `<meta name="csrf-token" content="{{ csrf_token() }}">` di `resources/views/app.blade.php` (sudah ada)
6. **Vue form `useForm().put()` dengan `forceFormData: true` di form dengan file upload** — broken (bug). Pakai `fetch()` + FormData atau `form.post()` + `transform` dengan `_method: 'PUT'`
7. **AI tidak bisa lihat gambar** — kalau user screenshot masalah visual, minta deskripsi teks (row-by-row, posisi kiri/kanan/tengah, warna). (Lihat [memory](.claude/projects/c--laragon-www-erp-rt-rw-net/memory/cant-see-images.md))
8. **Vue runtime cache**: setelah ubah computed attributes di model atau Vue component, **wajib `npm run build`** atau HMR cache gak ke-update. Test ulang di headed browser untuk verify
9. **Inertia 303 redirect**: `back()->with()` dari controller return 302/303 — Inertia client follow redirect + GET page. Pakai `onSuccess` di Vue form untuk update local state

## Debugging

- Backend: `\Log::info()` di titik kritis. Untuk PDF: `pdftotext` untuk extract text dan verify content
- Frontend: `console.log()` di script Vue, atau screenshot via `page.screenshot({ path })`
- Login redirect loop atau 401: cek session cookie di Playwright browser context
- Route baru gak aktif: `php artisan route:clear` lalu `php artisan route:list | grep <nama>`

## Coding Standards

- **Jawab dalam bahasa Indonesia**, singkat, langsung ke poin
- **Sertakan path file + nomor baris** saat merujuk kode
- **Hapus kode yang tidak dipakai** (jangan dikomen)
- **Hindari N+1**: pakai `with()` eager loading atau computed attribute (lihat `CustInternetInvc::getTotalPaidAttribute`)
- **Validasi semua input** user dengan Laravel Request validation
- **Jangan hardcode credentials** di code
- Satu fungsi = satu tanggung jawab
- **Branch naming** (kalau ditanya): `feature/{nama-fitur}` atau `fix/{deskripsi-singkat}`
- **Commit message**: prefix dengan scope, mis. `feat(tagihan): tambah kolom sisa pembayaran`

## Skills (`.claude/skills/`)

- `crud` — untuk generate/modify CRUD
- `excel-export` — Excel import/export
- `laravel-export` — long-running Laravel jobs
- `rbac` — permission/role multi-tenant

Cek skill yang relevan sebelum nulis feature baru.
