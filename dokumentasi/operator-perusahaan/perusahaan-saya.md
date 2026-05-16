# Perusahaan Saya
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/perusahaan-saya`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/perusahaan-saya` | — | `auth:admin-company` | `perusahaan-saya.list` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/OperatorPerusahaan/PerusahaanSaya.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/PerusahaanSayaViewTest.php` | Various | Browser view test |
