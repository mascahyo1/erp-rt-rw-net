# Halaman Konfigurasi Perusahaan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/konfigurasi-perusahaan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/konfigurasi-perusahaan` | — | `auth:admin-company` | `konfigurasi-perusahaan.list` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/OperatorPerusahaan/KonfigurasiPerusahaan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/KonfigurasiPerusahaanViewTest.php` | Various | Browser view test |
