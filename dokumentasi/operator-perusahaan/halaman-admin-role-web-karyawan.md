# Halaman Admin Role Web Karyawan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/admin-role-web-karyawan`

## Fungsi
Halaman untuk **melihat pemasangan role ke karyawan** di perusahaan sendiri.
Menampilkan karyawan mana saja yang sudah dipasangkan role dan role apa yang mereka miliki.

## Fitur
- **Tabel daftar pemasangan** — menampilkan karyawan dan role yang sudah dipasangkan

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `admin-role-web-karyawan.list` | Melihat tabel pemasangan dan sidebar menu |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/admin-role-web-karyawan` | — | `auth:admin-company` | `admin-role-web-karyawan.list` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/AdminRoleWebKaryawan.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/AdminRoleWebKaryawanViewTest.php` | Various | Browser view test |
