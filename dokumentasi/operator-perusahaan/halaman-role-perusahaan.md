# Halaman Role Perusahaan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/role-perusahaan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/role-perusahaan` | — | `auth:admin-company` | `role-perusahaan-op.list` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/OperatorPerusahaan/RolePerusahaan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/RolePerusahaanViewTest.php` | Various | Browser view test |
