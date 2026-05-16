# Halaman Admin Role Perusahaan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/admin-role-perusahaan`

## Fungsi
Halaman untuk **melihat pemasangan role ke admin perusahaan** di perusahaan sendiri.
Menampilkan admin perusahaan mana saja yang sudah dipasangkan role dan role apa yang mereka miliki.

## Fitur
- **Tabel daftar pemasangan** — menampilkan admin perusahaan dan role yang sudah dipasangkan

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `admin-role-perusahaan-op.list` | Melihat tabel pemasangan dan sidebar menu |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/admin-role-perusahaan` | — | `auth:admin-company` | `admin-role-perusahaan-op.list` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/AdminRolePerusahaan.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/AdminRolePerusahaanViewTest.php` | Various | Browser view test |
