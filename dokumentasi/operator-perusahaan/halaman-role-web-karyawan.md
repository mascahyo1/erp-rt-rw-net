# Halaman Role Web Karyawan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/role-web-karyawan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/role-web-karyawan` | — | `auth:admin-company` | `role-web-karyawan.list` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/OperatorPerusahaan/RoleWebKaryawan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/RoleWebKaryawanViewTest.php` | Various | Browser view test |
