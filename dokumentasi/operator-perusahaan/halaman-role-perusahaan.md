# Halaman Role Perusahaan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/role-perusahaan`

## Fungsi
Halaman untuk **melihat role (peran)** yang tersedia untuk admin perusahaan di perusahaan ini.
Role menentukan hak akses apa saja yang dimiliki admin perusahaan terhadap menu dan fitur di portal perusahaan.

## Fitur
- **Tabel daftar role** — menampilkan semua role yang tersedia dengan nama dan status

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `role-perusahaan-op.list` | Melihat daftar role dan sidebar menu |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/role-perusahaan` | — | `auth:admin-company` | `role-perusahaan-op.list` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/RolePerusahaan.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/RolePerusahaanViewTest.php` | Various | Browser view test |
