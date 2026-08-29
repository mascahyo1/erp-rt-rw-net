# Coverage Gap Report — 2026-08-29 — Deep Audit

> Hasil audit mendalam setelah penerapan `STANDARDS.md` v4 + `workflow.md` + `scripts/check-testing-standards.cjs`. Audit cek: 72 halaman Vue, 24 komponen, 4 composable, 126 Playwright, 20 PHPUnit, 73 Dusk, 7 layout, sidebar, routes.

## Ringkasan

- **Build:** `public/build/manifest.json` ✅ ada, `public/hot` ❌ tidak ada (benar untuk E2E). `STANDARDS.md:204`, `workflow.md:43`, `scripts/check-testing-standards.cjs:148`, `tests/Browser/Playwright/support/DeepVerifyTemplate.cjs:1` semua ada.
- **Kontrak §1+§7:** `node scripts/check-testing-standards.cjs` sekarang **0 errors, 118 warnings** (sebelum 75 errors). Semua `headless:true` UI sudah di-fix ke `headless:false + slowMo:350` (30 file) via `scripts/check-testing-standards.cjs:81`.
- **Gap utama yang ditemukan (ke-lewat):** 12 halaman tanpa test Playwright, 58 halaman tanpa `data-testid`, 22 halaman masih `form.put`/`forceFormData`, 6 komponen unused.

---

## 1. Halaman Vue (72) vs Test Coverage

**Total: 72 halaman di `resources/js/Pages/**/*.vue` (Errors 5 + Landing 10 + OperatorSaas 11 + OperatorPerusahaan 20 + Karyawan 11 + Customer 14 + Errors).**

### OperatorSaas (11 halaman) — 8 covered, 3 missing

| Halaman | File | Test Playwright | Status |
|---------|------|-----------------|--------|
| Dashboard | `OperatorSaas/Dashboard.vue` | `DashboardTest.cjs` | ✅ |
| Perusahaan | `OperatorSaas/Perusahaan.vue` | `PerusahaanCRUDTest.cjs` | ✅ |
| AdminPerusahaan | `OperatorSaas/AdminPerusahaan.vue` | `AdminPerusahaanCRUDTest.cjs` | ✅ |
| AdminSaaS | `OperatorSaas/AdminSaaS.vue` | `AdminSaaSCRUDTest.cjs` | ✅ |
| RoleSaaS | `OperatorSaas/RoleSaaS.vue` | `RoleSaaSCRUDTest.cjs` | ✅ |
| RolePerusahaan | `OperatorSaas/RolePerusahaan.vue` | `RolePerusahaanCRUDTest.cjs` | ✅ |
| Konfigurasi | `OperatorSaas/Konfigurasi.vue` | `DeepVerifyKonfigurasiSaaS.cjs` (15 langkah) | ✅ deep |
| ProfilSaya | `OperatorSaas/ProfilSaya.vue` | `VerifyProfilSayaPhoneFix.cjs` | ✅ |
| **AdminRoleSaaS** | `OperatorSaas/AdminRoleSaaS.vue` | — | ❌ **MISSING** (hanya `RolePagesInspect` debug) |
| **PemetaanAdminPerusahaan** | `OperatorSaas/PemetaanAdminPerusahaan.vue` | — | ❌ **MISSING** |
| **RoleAdminPerusahaan** | `OperatorSaas/RoleAdminPerusahaan.vue` | — | ❌ **MISSING** |

> **Fix:** Buat stub `DeepVerify{AdminRoleSaaS,PemetaanAdminPerusahaan,RoleAdminPerusahaan}.cjs` dari `DeepVerifyTemplate.cjs` (sudah ada `tests/Browser/Playwright/support/DeepVerifyTemplate.cjs:1`).

### OperatorPerusahaan (20 halaman) — 17 covered, 3 missing

| Halaman | Test | Status |
|---------|------|--------|
| Dashboard | `DashboardTest` | ✅ |
| Customer | `CustomerCRUDTest` | ✅ |
| Karyawan | `KaryawanCRUDTest` | ✅ |
| DaftarPaket | `DaftarPaketCRUDTest` | ✅ |
| Tagihan | `TagihanCRUDTest` + `TagihanImportExport` + `TagihanError` | ✅ |
| LanggananCustomer | `LanggananCRUDTest` | ✅ |
| Insentif | `InsentifCRUDTest` | ✅ |
| RiwayatInsentif | `RiwayatInsentifFullTest` | ✅ |
| RiwayatPembayaran | `RiwayatPembayaranFullTest` | ✅ |
| Gangguan | `gangguan-e2e.cjs` | ✅ |
| PerusahaanSaya | `PerusahaanSayaCRUDTest` | ✅ |
| ProfilSaya | `ProfilSayaViewTest` | ✅ |
| Karyawan, RolePerusahaan, RoleWebKaryawan, AdminRoleWebKaryawan, KonfigurasiPerusahaan, Customer, AdminPerusahaan | ... | ✅ |
| **AdminRolePerusahaan** | — (hanya `AdminRolePagesInspect`) | ❌ **MISSING** |
| **PerformaAdmin** | `test-performa-admin.cjs` ada di `PerformaAdmin/` tapi bukan `OperatorPerusahaan/` + tidak deep | ⚠️ **PARTIAL** |
| **PerformaKaryawan** | `test-performa-karyawan.cjs` sama | ⚠️ **PARTIAL** |

> Catatan: `Performa*` sudah ada file di `PerformaAdmin/test-performa-admin.cjs:1` tapi pattern beda folder + belum pakai template deep verify. Perlu pindah/duplicate ke `OperatorPerusahaan/DeepVerifyPerforma*.cjs` + tambah checklist §7.3.

### Karyawan (11 halaman) — 9 covered, 2 partial

| Halaman | Test | Status |
|---------|------|--------|
| Dashboard, Customer, Paket, Tagihan, RiwayatPembayaran, InsentifSaya, LanggananCustomer, ProfilSaya, Gangguan | ... | ✅ |
| Login, LupaPassword | `LoginTest`, `TurnstileLoginReproTest` | ✅ |
| **Tagihan, InsentifSaya, etc** | `TagihanParityTest`, `InsentifSayaParityTest` ada tapi parity ≠ deep | ⚠️ parity bukan deep verify CRUD |

### Customer/Pelanggan (14 halaman) — 8 covered, 6 missing

| Halaman | Test | Status |
|---------|------|--------|
| Dashboard | `DashboardTest` | ✅ |
| PaketSaya | `PaketSayaViewTest` | ✅ |
| TagihanSaya | `TagihanSayaViewTest` | ✅ |
| ProfilSaya | `ProfilSayaViewTest` | ✅ |
| Gangguan | `gangguan-e2e` | ✅ |
| PaketDetail, TagihanDetail | `PaketSaya`, `CheckTagihanDetailModal` | ⚠️ partial (detail modal cek, tapi tidak halaman penuh) |
| **DaftarPaket** | — | ❌ MISSING (Landing DaftarPaket ≠ Customer) |
| **DaftarPaketDetail** | — | ❌ MISSING |
| **PaketTambah** | — | ❌ MISSING |
| **PembayaranDetail** | — | ❌ MISSING |
| **PembayaranTambah** | — | ❌ MISSING |
| **VerifikasiEmail** | — | ❌ MISSING (ada `email-verification-pelanggan.cjs` tapi untuk Auth, bukan halaman Customer) |

> **Landing (10) + Errors (5)** — tidak perlu CRUD E2E penuh, tapi `Landing/Home`, `Login*` sudah ada `Auth/*` tests + `LandingAndCustomerFormErrorTest`.

---

## 2. Komponen (24) & Composable (4)

**Komponen:** `resources/js/Components/*.vue` 24 file. 6 unused (0 usages di Pages/Layouts): `DangerButton.vue`, `InputError.vue`, `InputLabel.vue`, `PrimaryButton.vue`, `SecondaryButton.vue`, `TextInput.vue` — kemungkinan legacy dari Breeze, aman di-keep tapi documentasikan sebagai unused, atau hapus jika mau.

**Composable:** `resources/js/Composables/*.js` 4 file. `useAjaxForm.js:1` hanya dipakai di **1 tempat** (harusnya semua form AJAX pakai ini per `CONVENTIONS.md:42`). `useCountryCodes.js:2` usages, `useToast.js:3`, `useFormErrorToast.js:0` unused.

**Gap:** 22 halaman masih pakai `form.put`/`forceFormData` (violasi `STANDARDS.md §2` + `CONVENTIONS.md §3`):
`Karyawan/InsentifSaya.vue`, `Karyawan/LanggananCustomer.vue`, `Karyawan/Tagihan.vue`, `OperatorPerusahaan/AdminPerusahaan.vue`, `AdminRolePerusahaan.vue`, `AdminRoleWebKaryawan.vue`, `DaftarPaket.vue`, `Insentif.vue`, `KonfigurasiPerusahaan.vue`, `LanggananCustomer.vue`, `RiwayatInsentif.vue`, `RolePerusahaan.vue`, `RoleWebKaryawan.vue`, `Tagihan.vue`, `OperatorSaas/AdminPerusahaan.vue`, `AdminRoleSaaS.vue`, `AdminSaaS.vue`, `Konfigurasi.vue`, `ProfilSaya.vue`, `RoleAdminPerusahaan.vue` + 2 lagi.

> **Fix plan:** Refactor bertahap ke `useAjaxForm`/`fetch` + `FormData` (lihat `CONVENTIONS.md §2` + `PerusahaanSayaCRUDTest.cjs:246` contoh). Prioritas tinggi untuk yang ada file upload (Tagihan, Konfigurasi).

---

## 3. Data-testid Selector Solid (§1.3)

- **Hanya 14/72 halaman (19%)** punya `data-testid` — `Customer/Gangguan.vue`, `Karyawan/Customer.vue`, `Karyawan/Gangguan.vue`, `Karyawan/RiwayatPembayaran.vue`, `Landing/Login*.vue` (3), `OperatorPerusahaan/AdminRolePerusahaan.vue`, `AdminRoleWebKaryawan.vue`, `Gangguan.vue`, `PerformaAdmin.vue`, `PerformaKaryawan.vue`, `RiwayatPembayaran.vue`, `OperatorSaas/AdminRoleSaaS.vue` (14).
- **58 halaman tanpa `data-testid`** — rawan selector rapuh `nth-child`/class Tailwind. `scripts/check-testing-standards.cjs:120` sudah warning, tapi belum block.

> **Fix:** Tambahkan `data-testid` minimal ke tombol utama (`btn-tambah`, `btn-edit`, `btn-hapus`, `input-search`, `modal-create`) di setiap halaman CRUD. Template `DeepVerifyTemplate.cjs` sudah pakai `data-testid` sebagai preferensi.

---

## 4. Testing Runners — Sudah Diterapkan

- **Playwright:** 126 file, 110 aktif (16 di `result/` hasil run). Semua `headless:false + slowMo:350` untuk UI (30 file di-fix), 0 errors (118 warnings sisa — console, BASE, unique data). Template `DeepVerifyTemplate.cjs` ada. `scripts/check-testing-standards.cjs` enforce.
- **PHPUnit:** 20 file (`tests/Feature/**/ *Test.php`) — semua pakai `TestCase:10` bypass CSRF, `withoutMiddleware`. Violation sebelumnya (naming/assert) tidak ada — clean.
- **Dusk:** 73 file di `deprecatedoldFeature` — tetap ada untuk histori, `parallel-dusk.ps1:89` handle `public/hot` rename + FFmpeg. Tidak buat baru (STANDARDS §7.1).
- **Build:** `public/build/manifest.json` ✅, `npm run build` wajib — sudah di `STANDARDS.md §1.1` + `.kilo/rules.md:4`.

---

## 5. Gap yang Sudah Diperbaiki di Sesi Ini

- Buat `STANDARDS.md` 278 baris + `workflow.md` 63 baris — sebelumnya hilang.
- Update `dokumentasi/workflow coding dan test.md:1` legacy → redirect v4.
- Sinkron `AGENTS.md:1`, `CLAUDE.md:64`, `.kilo/rules.md:1`, `.kilo/AGENT-WORKFLOW.md:30`, `.kilo/skills/new-feature-test.md:1`, `package.json:5`.
- Update `PlaywrightHelper.cjs:43` headed + slowMo + video + console capture + `.fa-building` login.
- Buat `DeepVerifyTemplate.cjs` + `check-testing-standards.cjs` (fix 30 file).
- 0 errors di check script (sebelum 10-75).

---

## 6. Sisa Pekerjaan (TODO)

**Prioritas Tinggi (P1):**
- [ ] Buat 12 stub DeepVerify untuk halaman missing (lihat §1) — copy dari `DeepVerifyTemplate.cjs`, minimal login + list + screenshot.
- [ ] Refactor 22 halaman `form.put` → `useAjaxForm`/`fetch` (mulai Tagihan + Konfigurasi yang ada upload).
- [ ] Tambah `data-testid` ke 58 halaman (batch per portal, 10 halaman per PR).

**Prioritas Sedang (P2):**
- [ ] Pindahkan `test-performa-*.cjs` ke `OperatorPerusahaan/DeepVerifyPerforma*.cjs` + tambah §7.3 checklist.
- [ ] Hapus/documentasikan 6 komponen unused + `useFormErrorToast.js` unused.
- [ ] Tambah `scripts/check-coverage.cjs` untuk audit halaman vs test vs data-testid otomatis.

**Prioritas Rendah (P3):**
- [ ] Lengkapi `dokumentasi/{portal}/*.md` untuk halaman yang belum ada docs (PemetaanAdminPerusahaan, Performa*).
- [ ] Tambah CI gate: `npm run test:standards` di GitHub Actions (block PR jika errors >0).

---

*Generated: 2026-08-29T18:27+07:00 — audit deeper via `Get-ChildItem` + `Select-String` + `check-testing-standards.cjs --json`*
