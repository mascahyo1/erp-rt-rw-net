# STANDARDS.md — v4 Single Source of Truth — ERP RT/RW Net

> **Sumber tunggal standar build-time & testing.** File ini melebur `dokumentasi/workflow coding dan test.md` v3 (legacy) + semua memory/AGENTS/CONVENTIONS yang tersebar. Jika ada konflik, **STANDARDS.md menang**.

Baca juga: `workflow.md` (ringkas 1 halaman), `dokumentasi/CONVENTIONS.md` (Hybrid Inertia+AJAX detail), `AGENTS.md` & `.kilo/rules.md` (workflow harian).

---

## §1 Kontrak Build-Time (Lifecycle / State / Selector Solid)

Kontrak ini **wajib lolos sebelum testing boleh dimulai**. Tidak ada pengecualian.

### 1.1 Lifecycle Solid
- Setiap halaman CRUD/Vue **wajib** punya siklus hidup jelas: `mounted → fetch → render → interaksi → submit → feedback → cleanup`.
- Tidak boleh ada race: fetch sebelum mount selesai, atau submit sebelum validasi.
- Async flow pakai `await` + `try/catch`, bukan callback bertumpuk. Loading state `ref(false)` di-toggle di `finally`.
- `npm run build` wajib setelah ubah Vue/model computed — HMR tidak cukup (cache Vite). Test baca `public/build/`.

### 1.2 State Solid
- Satu sumber kebenaran per fitur: `ref`/`reactive` di Vue, `props` dari Inertia.
- Jangan duplikasi state di 2 tempat (props + local ref yang tidak sinkron).
- Setelah AJAX sukses: update local state via `Object.assign(props.xxx, json.data)` atau re-fetch, jangan reload halaman.
- Form state pakai `useAjaxForm.js` atau `FormData` manual — dilarang `useForm().put()` + `forceFormData:true` untuk file upload (PHP multipart bug).

### 1.3 Selector Solid (anti-fragile)
- Utamakan `data-testid` untuk selector test. Fallback: `role`, `placeholder`, `text` yang stabil.
- Dilarang selector rapuh: `nth-child`, XPath panjang, class Tailwind yang bisa berubah.
- Memory: `robust-selectors-data-testid` — tambahkan `data-testid="btn-tambah"`, `data-testid="input-nama"` di setiap elemen interaktif baru.
- Pastikan selector ada di DOM **sebelum** interaksi: `waitForSelector` / `waitForTimeout(500)` setelah navigasi/modal open.

### 1.4 Enforcement
- Sebelum `headed E2E` (§7), jalankan `scripts/check-testing-standards.cjs` — harus 0 violation untuk kontrak ini.

---

## §2 Arsitektur Hybrid Inertia + AJAX

| Use case | Protokol | Aturan |
|----------|----------|--------|
| Navigasi (sidebar, breadcrumb, redirect) | **Inertia** | `Inertia::render()` + `<Link>` |
| CRUD form submit (modal/inline, no nav) | **Pure AJAX** | `fetch()` + `response()->json()` |

**3 aturan yang sering dilanggar:**
1. `useForm().put()/patch()/delete()` + file upload = **BROKEN** (PHP tidak parse multipart untuk non-POST). Pakai `fetch` + `FormData` atau `POST` + `_method: PUT`.
2. Controller AJAX wajib `use Illuminate\Http\JsonResponse` eksplisit — unqualified resolves ke namespace salah → `TypeError`.
3. Route AJAX di bawah `/api/...` di dalam portal group (`/operator-perusahaan/api/...`).

Composable: `resources/js/Composables/useAjaxForm.js`.

Decision tree & contoh lengkap: lihat `dokumentasi/CONVENTIONS.md` §1-§4.

---

## §3 Stack & Commands

**Stack:** Laravel 13 + PHP 8.3 | Vue 3.5 + Inertia v2 | Vite 8 + Tailwind 4 | Flowbite 4 + FontAwesome 7 | Reverb | MinIO | PhpSpreadsheet 5 + PhpWord 1 + DomPDF 3

```bash
# Dev (2 terminal)
npm run dev              # Vite HMR :5173
php artisan serve        # Laravel :8000 (atau Laragon: erp-rt-rw-net.test)

# Build (WAJIB setelah Vue/Tailwind/model computed berubah)
npm run build

# DB reset (DESTRUCTIVE)
php artisan migrate:fresh --seed   # via DemoSeeder + PermissionSeeder

# Cache clear (jika route baru tidak kepickup)
php artisan route:clear; php artisan config:clear

# PDF verify
pdftotext path/to/file.pdf -

# Tinker
php artisan tinker
```

Demo users & DB: lihat `DOCS.md` dan `CLAUDE.md § Test Fixtures`.

---

## §4 Struktur Project (Code Map)

```
app/Http/Controllers/{OperatorPerusahaan,OperatorSaas,Karyawan,Customer}/*.php
app/Models/*.php  (HasUuidV7, HasSoftDelete, HasBlameable)
resources/js/Pages/{OperatorSaas,OperatorPerusahaan,Karyawan,Pelanggan}/*.vue
resources/js/Composables/useAjaxForm.js
routes/web/{operator-perusahaan,operator-saas,karyawan,customer,landing-page}.php
dokumentasi/{CONVENTIONS.md, DEPLOY.md, testing/{dusk-running,playwright-recording}.md}
tests/
  Feature/{Auth,OperatorSaas,OperatorPerusahaan}/*.php  → PHPUnit (parallel-test.ps1)
  Browser/Playwright/Feature/{OperatorSaas,OperatorPerusahaan,Karyawan,Pelanggan}/**/*.cjs → Playwright (primary)
  Browser/deprecatedoldFeature/**/*.php                  → Dusk legacy (parallel-dusk.ps1)
```

---

## §5 Data Layer

- **PK:** UUID v7 via `HasUuidV7` — jangan auto-increment.
- **Soft delete:** `HasSoftDelete` (`trashed()`, `withTrashed()`).
- **Blameable:** `HasBlameable` (created_by, updated_by, deleted_by, restored_by).
- **DB:** Dev `erp_rt_rw_net_tmp` (`.env.example`), PHPUnit `erp_rt_rw_net` (`phpunit.xml` force), Dusk `erp_rt_rw_net_tmp` (`phpunit.dusk.xml`). Pastikan keduanya ada.
- **Storage:** `Storage::disk('minio')` private, akses via `file.proxy` (auth-gated).

---

## §6 Critical Gotchas (sudah di-debug — jangan ulangi)

1. **DomPDF tidak support Flexbox** — pakai `<table>` / `display:table`.
2. **PHP multipart PUT bug** — lihat §2.
3. **JsonResponse namespace clash** — lihat §2.
4. **File proxy butuh auth** — DomPDF fetch 302; embed base64 via `Company::getLogoDataUri()`.
5. **CSRF meta** sudah di `resources/views/app.blade.php`.
6. **Vue runtime cache** — `npm run build` wajib.
7. **Inertia 303 redirect** — pakai `onSuccess` bukan `back()->with()` untuk AJAX.
8. **AI tidak bisa lihat gambar** — minta deskripsi teks row-by-row sebelum iterate visual.
9. **keydown preventDefault unconditional** blocks input — pakai `type()` bukan `fill()` untuk verify jika ada handler global.
10. **.npmrc ignore-scripts=true** — jangan hapus.

---

## §7 Headed E2E per-Langkah Assert + Screenshot / Network / Video

Ini adalah **standar testing utama** (menggantikan smoke/quick). Berlaku untuk **semua** portal & semua fitur baru/modifikasi.

### 7.1 Engine Prioritas

| Runner | Engine | Kapan pakai | Catatan |
|--------|--------|-------------|---------|
| `tests/Browser/Playwright/Feature/**/*.cjs` | **Playwright (Node, CommonJS)** | **UTAMA — semua UI/E2E, visual, CRUD** | `headless:false`, `slowMo:300-500`, `.cjs` only. Lihat `tests/Browser/Playwright/README.md`. |
| `tests/Feature/**/*.php` | PHPUnit (`php artisan test`) | Backend unit/feature murni, auth, RBAC, controller JSON | Via `parallel-test.ps1`, **jangan** untuk verify UI. CLAUDE.md: deprecated untuk UI. |
| `tests/Browser/deprecatedoldFeature/**/*.php` | Dusk (PHP) | **Legacy** — jangan buat baru. New E2E → Playwright `.cjs` | Butuh `public/hot` rename, `chromedriver.exe`. |

**Aturan keras (memory):**
- `testing-prioritas-playwright` — habis fix apapun, verify pakai Playwright headed. Jangan `php artisan test` untuk regression UI.
- `testing-with-headed-browser` — selalu `headless:false` untuk debug visual; `headless:true` hanya untuk CI.
- `deep-verify-no-quick` — tidak ada "quick verify". Pakai `DeepVerify*.cjs` atau `Verify{Scenario}.cjs`. Cakupan menyeluruh.
- `teliti-workflow` — confirm → todo + sign-off → 1 file + screenshot/verify/re-read → lanjut.

### 7.2 Kontrak per-Langkah

Setiap langkah test **WAJIB** punya 3 hal:

```js
// 1. Assert — gagal = fail, bukan silent
check('Tombol Tambah visible', hasTambah);
assert(response.status() === 200, `Expected 200 got ${response.status()}`);

// 2. Screenshot — bukti visual per step, simpan ke result/{Portal}/{Fitur}/
await shot(page, '03-create-modal-default-text.png');
await takeScreenshot('04a-name-filled');

// 3. Network/Video — untuk form submit & aliran kritis
const [response] = await Promise.all([
  page.waitForResponse(r => r.url().includes('/api/') && r.request().method() === 'POST', { timeout: 10000 }),
  page.click('button:has-text("Simpan")'),
]);
assert(response.status() === 200 && body.success === true);
page.on('pageerror', e => consoleErrors.push(e.message)); // + console.error capture
// Video: Playwright `recordVideo` atau Dusk `VideoRecorder` (lihat §7.5)
```

**Template lengkap:** `tests/Browser/Playwright/Feature/OperatorSaas/DeepVerifyKonfigurasiSaaS.cjs` (15 langkah: login → list → CRUD all types → filter → search → sort → bulk → dark → responsive). Untuk CRUD baru, copy template ini.

### 7.3 Cakupan Deep Verify (checklist)

Setiap fitur CRUD wajib diverifikasi:
- [ ] Login + permission gate (guest redirect, role-based visibility)
- [ ] List rendering: header, badge, pagination, `per_page`, search, filter (status/type/trash), multi-sort
- [ ] CRUD modal: create (all field types), edit, delete confirm, detail + edit button
- [ ] Validasi: 422 + error toast + field error + form stay open
- [ ] File upload: type validation, size validation, preview, compress (WebP), SVG not compressed
- [ ] Soft-delete: filter Terhapus, restore, bulk delete/status
- [ ] Import/Export: modal, template download, file validation
- [ ] Dark mode + responsive (390/768/1280)
- [ ] No JS errors (`pageerror` + `console.error` = 0)

### 7.4 Selector & Data Unik

- Data test **selalu baru per run** (`Date.now()` / nanoid suffix), cleanup setelah selesai. Jangan pakai data statis yang bisa konflik parallel.
- Screenshot path: `tests/Browser/Playwright/Feature/result/{Portal}/{Fitur}/{scenario}/` atau `tests/Browser/Playwright/result/{Portal}/{Fitur}/` — konsisten, sudah di `.gitignore`.

### 7.5 Video / Trace

- Playwright: `chromium.launch({headless:false, slowMo:350})` + `context: {recordVideo:{dir:'tests/Browser/Playwright/videos'}}` jika perlu. Repo sudah ada `tests/Browser/Playwright/videos` di gitignore.
- Dusk: `VideoRecorder` / `BrowserVideoRecorder` di `tests/Browser/Support/` — auto via `parallel-dusk.ps1` (FFmpeg → MP4, fallback HTML). Lihat `dokumentasi/testing/dusk-running.md` & `playwright-recording.md`.

### 7.6 Commands

```bash
# Playwright single file (headed)
node tests/Browser/Playwright/Feature/OperatorPerusahaan/CheckPdfLogoHeaded.cjs
node tests/Browser/Playwright/Feature/OperatorSaas/DeepVerifyKonfigurasiSaaS.cjs

# Semua login
node tests/Browser/Playwright/Feature/OperatorSaas/LoginTest.cjs
node tests/Browser/Playwright/Feature/OperatorPerusahaan/LoginTest.cjs
node tests/Browser/Playwright/Feature/Karyawan/LoginTest.cjs
node tests/Browser/Playwright/Feature/Pelanggan/LoginTest.cjs

# PHPUnit (hanya backend murni, jangan untuk UI)
.\parallel-test.ps1              # 4 workers default, ~15s vs 68s single
.\parallel-test.ps1 -MaxWorkers 8
php artisan test --filter=CustomerTest  # single file debug

# Dusk (legacy, butuh build)
php artisan migrate:fresh --seed
npm run build
# parallel:
.\parallel-dusk.ps1
.\parallel-dusk.ps1 -MaxWorkers 8 -Folders "OperatorSaas,Karyawan"
# single:
Move-Item public\hot public\hot.bak -Force; php artisan dusk --filter="test_01_page_renders" tests/Browser/Feature/OperatorPerusahaan/DaftarPaketCRUDTest.php; Move-Item public\hot.bak public\hot -Force
```

---

## §8 Definisi Selesai (DoD) — kapan fitur boleh di-merge

Fitur **belum selesai** jika salah satu belum centang:

- [ ] **§1 Lifecycle/State/Selector solid** lolos (check script 0 violation).
- [ ] **§2 Hybrid Inertia+AJAX** dipatuhi (route `/api`, `JsonResponse` eksplisit, tidak pakai `form.put`+file).
- [ ] **`npm run build` sukses** tanpa error, `public/build` fresh.
- [ ] **§7 Deep Verify headed E2E** pass 100% (semua langkah assert+screenshot+network, no JS errors). Screenshot di-review, video jika ada.
- [ ] **PHPUnit Feature** (jika ada logika baru) pass via `parallel-test.ps1` — minimal auth/RBAC/controller test.
- [ ] **Validasi 3 lapis** konsisten (FE hint + `required` + BE `validate` + pesan Indonesia).
- [ ] **Dark mode & responsive** verified (screenshot 390/768/1280).
- [ ] **Data unik & cleanup** — test buat data baru per run, cleanup soft-delete.
- [ ] **Tidak ada kode mati** — hapus, jangan komen. Satu fungsi = satu tanggung jawab.
- [ ] **Docs & report updated** — `briefing/report/{progress,daily,weekly}` + `dokumentasi/{portal}/` jika fitur baru.
- [ ] **Commit + push** — `push-after-commit` (backup cloud).

Jika ragu, tanya user dengan opsi konkret sampai ≥95% confident (lihat §9).

---

## §9 Workflow Teliti (non-negotiable)

1. **Konfirmasi requirement** sampai ≥95% confident. Tanya dengan opsi konkret, bukan open-ended.
2. **Tulis todo list & tunggu sign-off user** sebelum coding.
3. **Satu file pada satu waktu** — setelah edit 1 file: screenshot headed browser → verify sendiri → re-read diff (typo/side effects) → baru lanjut.
4. **Visual issue? Deskripsi text dulu** — row-by-row, posisi, warna, sebelum minta fix. Jangan silent iteration.
5. **Respons Indonesia, singkat, dengan `path:line`** saat rujuk kode.

Pemicu tugas baru: acknowledge 1-2 kalimat → `todowrite` → tanya verifikasi todo → baru coding setelah konfirmasi. Lihat `.kilo/AGENT-WORKFLOW.md`.

---

## §10 Coding Standards

- Jawab Indonesia, singkat, `path:line`.
- Hapus kode tidak dipakai.
- Hindari N+1 — `with()` atau computed attribute.
- Validasi semua input (`$request->validate`).
- Jangan hardcode credentials.
- Satu fungsi = satu tanggung jawab.
- Branch `feature/{nama}` atau `fix/{deskripsi}`, commit `feat(scope): summary`.
- Skills: `.claude/skills/{crud,excel-export,laravel-export,rbac}` dan `.kilo/skills/crud-table-standard`.

---

## §11 Lampiran: File Acuan

- `dokumentasi/CONVENTIONS.md` — Hybrid Inertia+AJAX (wajib sebelum tulis form)
- `dokumentasi/testing/{dusk-running,playwright-recording}.md` — runner detail
- `tests/Browser/Playwright/README.md` — Playwright struktur & konversi Dusk→Playwright
- `.kilo/skills/crud-table-standard/SKILL.md` — standar UI tabel CRUD
- `.kilo/rules.md` & `.kilo/AGENT-WORKFLOW.md` — workflow harian
- `DOCS.md` — kredensial demo (jangan commit production creds)
- Memory: `.claude/projects/.../memory/{teliti-workflow,testing-prioritas-playwright,testing-with-headed-browser,deep-verify-no-quick}.md`

---

*Generated: 2026-08-29 — v4 Single Source. Legacy v3 history: `git log -- dokumentasi/"workflow coding dan test.md"`.*
