# Perusahaan Saya
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/perusahaan-saya`

## Fungsi
Halaman untuk **melihat dan mengedit informasi perusahaan sendiri** — nama, alamat, kontak, dan status perusahaan yang dikelola oleh admin perusahaan.
Visible di sidebar dan dropdown navbar **hanya jika user punya izin `perusahaan-saya.detail`**.

## Fitur
- **Tampilan detail perusahaan** — menampilkan logo (light + dark), nama, email, alamat, telepon, dan status perusahaan
- **Edit perusahaan** — formulir edit + replace logo (tombol Edit hanya muncul jika user punya izin `perusahaan-saya.edit`)
- **Logo Perusahaan** (Light & Dark) — admin perusahaan bisa upload logo light + logo dark untuk perusahaan sendiri
- **Dark/Light mode** — mendukung tema gelap/terang dengan logo yang sesuai per mode
- **Responsive** — tampilan menyesuaikan mobile, tablet, desktop

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Detail** | `perusahaan-saya.detail` | Melihat informasi + logo perusahaan sendiri |
| **Edit Perusahaan** | `perusahaan-saya.edit` | Mengubah data + replace logo perusahaan |

## Field Logo (di halaman ini)
- `logo` (Mode Terang) — logo untuk background terang
- `logo_dark` (Mode Gelap) — logo untuk background gelap

**Format:** JPG/PNG/WebP/SVG, maks 2MB. Gambar raster otomatis dikompres ke WebP, SVG disimpan apa adanya.

**Kompresi:** Otomatis ditangani oleh `FileUploadService::processLogo()` → `processImage()`.
- **JPG/PNG/WebP** → dikompres ke **WebP**, di-resize ke `default_upload_max_width_and_height_image` (default **1920×1920 px**, aspect ratio dijaga)
- **SVG** → disimpan apa adanya (sudah vector-based)
- **PDF** → tidak diterima untuk logo (format file tidak relevan)

**Path penyimpanan:** `companies/logos/photos/{uuid7}.webp` di MinIO (disk `minio`).
**Batas ukuran upload:** `default_upload_max_file_size_in_kb` (default **2048 KB / 2 MB**), di-enforce oleh Laravel `max:2048`.

## Catatan RBAC
- Halaman "Perusahaan Saya" visible di sidebar dan dropdown navbar **hanya jika user punya permission `perusahaan-saya.detail`**
- Tombol "Edit Perusahaan" hanya muncul jika user punya permission `perusahaan-saya.edit`
- Semua admin perusahaan bisa melihat halaman ini, hanya yang punya permission edit yang bisa mengubah data
- Permission `perusahaan-saya.list` **tidak digunakan** (dihilangkan)

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/perusahaan-saya` | perusahaan-saya.index | `auth:admin-company` | `perusahaan-saya.detail` |
| PUT | `/operator-perusahaan/perusahaan-saya/{company}` | perusahaan-saya.update | `auth:admin-company` | `perusahaan-saya.edit` |

> ⚠️ **Catatan penting — Inertia form submission untuk endpoint ini:**
> Form edit di Vue pakai `form.put(url, { forceFormData: true })` untuk kirim logo. Inertia v2 + PHP punya **pre-existing bug** dimana multipart/form-data body **tidak ter-parse** oleh PHP untuk method PUT/PATCH/DELETE (PHP hanya auto-parse untuk POST). Akibatnya `$request->all()` di controller kosong.
>
> **Pattern yang benar (Laravel-native, tanpa workaround):**
> ```js
> form.post(url, {
>   forceFormData: true,
>   transform: (data) => ({ ...data, _method: 'PUT' }),  // _method override → route matching jadi PUT
> });
> ```
> Ini mengirim POST (PHP bisa parse body ✅) + `_method=PUT` di body → Laravel Symfony Request deteksi → route cocok dengan PUT → controller `update()` jalan. `$request->method()` return "PUT" karena override.
>
> Index route di-enforce dengan middleware `permission:perusahaan-saya.detail`. Update route menggunakan `permission:perusahaan-saya.edit`. Permission `perusahaan-saya.list` **tidak dipakai**.

### Known Pre-existing Issues (belum fix, bukan dari fitur logo)

| # | Issue | Dampak | Affected |
|---|-------|--------|----------|
| 1 | PHP tidak parse `multipart/form-data` body untuk method PUT/PATCH/DELETE | `$request->all()` kosong saat form pakai `form.put()` + `forceFormData: true` → validasi gagal "field is required" untuk semua field | Semua form edit dengan upload file (bukan hanya logo) |
| 2 | Inertia Laravel v2 middleware tidak set header `X-Inertia: true` di response redirect | `onSuccess` callback tidak dipanggil setelah save → form tidak auto-close, toast tidak auto-fire | Semua form yang `back()->with()` setelah PUT/PATCH/DELETE |
| 3 | `form._method = 'PUT'` di Inertia Vue3 **tidak** otomatis masuk body (bukan Inertia convention) | Set property `_method` saja tidak cukup; perlu `transform: (data) => ({ ...data, _method: 'PUT' })` | Semua form yang coba pakai Laravel `_method` override via Inertia form helper |

**Fix yang direkomendasikan (belum dilakukan, butuh diskusi):**
1. Untuk #1 & #3: pilih antara (a) ubah semua form jadi `form.post()` + `transform: data => ({...data, _method: 'PUT'})`, (b) tambahkan middleware parsing multipart untuk PUT/PATCH/DELETE, (c) ubah route jadi POST-only + method override
2. Untuk #2: pilih antara (a) patch Inertia Laravel v2 di `vendor/` (tidak ideal), (b) bikin wrapper middleware di project untuk handle X-Inertia header, (c) downgrade ke Inertia v1.x, (d) rewrite form submit jadi pure AJAX (lihat catatan arsitektur di bawah)
3. Test Playwright `PerusahaanSayaCRUDTest.test_09/10/11` (logo upload) saat ini akan fail karena bug #1

### Catatan Arsitektur (untuk diskusi)

Bug-bug di atas adalah gejala dari **incompatibility pattern**, bukan dari fitur logo. Pattern yang dipakai (`form.put()` + file upload di Inertia v2) adalah anti-pattern karena:
- HTML form tradisional cuma support GET/POST — makanya Laravel pakai `_method` override
- Inertia v2 mewarisi keterbatasan itu + punya quirk tambahan soal response header
- Untuk CRUD edit (yang **tidak** pindah halaman setelah save), pure AJAX + JSON response lebih sesuai daripada Inertia form submission

Lihat juga dokumentasi test [PerusahaanSayaCRUDTest](../testing) untuk detail test apa saja yang saat ini pass/fail.

### Controller
`App\Http\Controllers\OperatorPerusahaan\PerusahaanSayaController`

### View
`resources/js/Pages/OperatorPerusahaan/PerusahaanSaya.vue`

### Layout
`resources/js/Layouts/OperatorPerusahaanLayout.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Company` | `companies` | Model utama — data perusahaan + kolom `logo` & `logo_dark` (diambil via `auth()->user()->company_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_135231_create_companies_table` | `companies` |
| `2026_06_02_100000_add_logo_to_companies_table` | `companies` (tambah `logo` + `logo_dark`) |

### Accessor
- `Company::logo_url` → URL untuk logo light (via `file.proxy` route)
- `Company::logo_dark_url` → URL untuk logo dark (via `file.proxy` route)

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/PerusahaanSayaCRUDTest.cjs` | test_01 – test_08 | Playwright E2E test (page, edit, dark mode, responsive) |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/PerusahaanSayaCRUDTest.cjs` | test_09 | Upload logo light (JPG raster → dikompres ke WebP) |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/PerusahaanSayaCRUDTest.cjs` | test_10 | Upload logo dark (SVG → disimpan apa adanya) |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/PerusahaanSayaCRUDTest.cjs` | test_11 | Validasi upload — file >2MB ditolak |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/PdfDownloadLogoTest.cjs` | test_01 – test_02 | Tagihan PDF endpoint + download (sumber logo via `CompanyConfig::getLogo`) |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/PdfDownloadLogoTest.cjs` | test_03 – test_04 | Riwayat Pembayaran PDF endpoint + download |