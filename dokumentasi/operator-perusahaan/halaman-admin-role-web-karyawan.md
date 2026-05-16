# Halaman Admin Role Web Karyawan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/admin-role-web-karyawan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/admin-role-web-karyawan` | — | `auth:admin-company` | `admin-role-web-karyawan.list` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/OperatorPerusahaan/AdminRoleWebKaryawan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/AdminRoleWebKaryawanViewTest.php` | Various | Browser view test |
