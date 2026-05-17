# Halaman Konfigurasi Perusahaan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/konfigurasi-perusahaan`

## Fungsi
Halaman untuk **melihat konfigurasi perusahaan** yang diatur oleh operator SaaS.
Konfigurasi ini mencakup pengaturan yang berlaku untuk perusahaan, seperti kontak dan informasi umum.

## Fitur
- **Tampilan konfigurasi** — menampilkan pengaturan yang berlaku untuk perusahaan

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Konfigurasi** | `konfigurasi-perusahaan.list` | Melihat konfigurasi perusahaan dan sidebar menu |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/konfigurasi-perusahaan` | — | `auth:admin-company` | `konfigurasi-perusahaan.list` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/KonfigurasiPerusahaan.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\SaasConfig` | `saas_configs` | Model utama — konfigurasi global dari operator SaaS |
| `App\Models\CompanyConfig` | `company_configs` | Konfigurasi spesifik perusahaan |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_224754_create_saas_configs_table` | `saas_configs` |
| `2026_05_11_224942_create_company_configs_table` | `company_configs` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/KonfigurasiPerusahaanViewTest.php` | Various | Browser view test |
