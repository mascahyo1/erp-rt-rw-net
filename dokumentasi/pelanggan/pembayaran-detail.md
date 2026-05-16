# Pembayaran Detail
> Portal: Pelanggan | URL: `/customer/riwayat-pembayaran/detail`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/customer/riwayat-pembayaran/detail` | — | `auth:customer` | — |

## Controller
Closure (inline route)

## View
`resources/js/Pages/Customer/PembayaranDetail.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/PembayaranDetailViewTest.php` | Various | Browser view test |
