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
| POST (PUT) | `/operator-perusahaan/perusahaan-saya/{company}` | perusahaan-saya.update | `auth:admin-company` | `perusahaan-saya.edit` |

> Form edit menggunakan POST + `_method=PUT` untuk support file upload logo (multipart/form-data).

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
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/PerusahaanSayaCRUDTest.cjs` | test_01 – test_08 | Playwright E2E test |