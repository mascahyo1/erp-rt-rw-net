# Perusahaan Saya
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/perusahaan-saya`

## Fungsi
Halaman untuk **melihat informasi perusahaan sendiri** — nama, alamat, kontak, dan status perusahaan yang dikelola oleh admin perusahaan.
Ini adalah halaman profil perusahaan, bukan untuk mengedit (edit perusahaan dilakukan oleh operator SaaS).

## Fitur
- **Tampilan detail perusahaan** — menampilkan nama, email, alamat, telepon, dan status perusahaan

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Detail** | `perusahaan-saya.list` | Melihat informasi perusahaan sendiri |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/perusahaan-saya` | — | `auth:admin-company` | `perusahaan-saya.list` |

### Controller
Closure (inline route)

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
| `tests/Browser/Feature/OperatorPerusahaan/PerusahaanSayaViewTest.php` | Various | Browser view test |
