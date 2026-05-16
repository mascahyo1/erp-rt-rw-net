# Daftar Paket
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/daftar-paket`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/daftar-paket` | — | `auth:admin-company` | `paket.list` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/OperatorPerusahaan/DaftarPaket.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/DaftarPaketViewTest.php` | Various | Browser view test |
