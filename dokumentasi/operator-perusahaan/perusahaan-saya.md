# Perusahaan Saya
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/perusahaan-saya`

## Fungsi
Halaman untuk **melihat dan mengedit informasi perusahaan sendiri** — nama, alamat, kontak, dan status perusahaan yang dikelola oleh admin perusahaan.
Visible di sidebar dan dropdown navbar **hanya jika user punya izin `perusahaan-saya.detail`**.

## Fitur
- **Tampilan detail perusahaan** — menampilkan nama, email, alamat, telepon, dan status perusahaan
- **Edit perusahaan** — formulir edit dengan validasi (tombol Edit hanya muncul jika user punya izin `perusahaan-saya.edit`)
- **Dark/Light mode** — mendukung tema gelap/terang
- **Responsive** — tampilan menyesuaikan mobile, tablet, desktop

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Detail** | `perusahaan-saya.detail` | Melihat informasi perusahaan sendiri |
| **Edit Perusahaan** | `perusahaan-saya.edit` | Mengubah data perusahaan |

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

### Controller
`App\Http\Controllers\OperatorPerusahaan\PerusahaanSayaController`

### View
`resources/js/Pages/OperatorPerusahaan/PerusahaanSaya.vue`

### Layout
`resources/js/Layouts/OperatorPerusahaanLayout.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Company` | `companies` | Model utama — data perusahaan (diambil via `auth()->user()->company_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_135231_create_companies_table` | `companies` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/PerusahaanSayaCRUDTest.cjs` | test_01 – test_08 | Playwright E2E test |