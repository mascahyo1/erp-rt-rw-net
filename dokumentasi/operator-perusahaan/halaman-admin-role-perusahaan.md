# Halaman Admin Role Perusahaan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/admin-role-perusahaan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/admin-role-perusahaan` | — | `auth:admin-company` | `admin-role-perusahaan-op.list` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/OperatorPerusahaan/AdminRolePerusahaan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/AdminRolePerusahaanViewTest.php` | Various | Browser view test |
