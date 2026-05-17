# Halaman Role Web Karyawan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/role-web-karyawan`

## Fungsi
Halaman untuk **melihat role (peran)** yang tersedia untuk karyawan di portal web karyawan.
Role menentukan hak akses apa saja yang dimiliki karyawan terhadap menu dan fitur di portal karyawan.

## Fitur
- **Tabel daftar role** — menampilkan semua role karyawan dengan nama dan status

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `role-web-karyawan.list` | Melihat daftar role karyawan dan sidebar menu |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/role-web-karyawan` | — | `auth:admin-company` | `role-web-karyawan.list` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/RoleWebKaryawan.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Role` | `roles` | Model utama — role dengan scope `web-karyawan` |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_140234_create_roles_table` | `roles` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/RoleWebKaryawanViewTest.php` | Various | Browser view test |
