# Perusahaan Saya
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/perusahaan-saya`

## Fungsi
Halaman untuk **melihat dan mengedit informasi perusahaan sendiri** — nama, alamat, kontak, dan status perusahaan yang dikelola oleh admin perusahaan.
Selalu visible di sidebar dan dropdown navbar untuk semua admin perusahaan.

## Fitur
- **Tampilan detail perusahaan** — menampilkan nama, email, alamat, telepon, dan status perusahaan
- **Edit perusahaan** — formulir edit dengan validasi (tombol Edit hanya muncul jika user punya izin `perusahaan-saya.edit`)
- **Dark/Light mode** — mendukung tema gelap/terang
- **Responsive** — tampilan menyesuaikan mobile, tablet, desktop

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Detail** | Tidak ada (selalu accessible) | Melihat informasi perusahaan sendiri |
| **Edit Perusahaan** | `perusahaan-saya.edit` | Mengubah data perusahaan |

## Catatan RBAC
- Halaman "Perusahaan Saya" **selalu visible** di sidebar dan dropdown navbar — tidak perlu permission `perusahaan-saya.list`
- Tombol "Edit Perusahaan" hanya muncul jika user punya permission `perusahaan-saya.edit`
- Semua admin perusahaan bisa melihat halaman ini, hanya yang punya permission edit yang bisa mengubah data

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/perusahaan-saya` | perusahaan-saya.index | `auth:admin-company` | Tidak ada (selalu accessible) |
| PUT | `/operator-perusahaan/perusahaan-saya/{company}` | perusahaan-saya.update | `auth:admin-company` | `perusahaan-saya.edit` |

### Controller
`App\Http\Controllers\OperatorPerusahaan\PerusahaanSayaController`

### View
`resources/js/Pages/OperatorPerusahaan/PerusahaanSaya.vue`

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
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/PerusahaanSayaCRUDTest.cjs` | Various | Playwright E2E test |
