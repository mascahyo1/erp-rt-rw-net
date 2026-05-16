# Dashboard
> Portal: Pelanggan | URL: `/customer/dashboard`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/customer/dashboard` | `customer.dashboard` | `auth:customer` | — |

## Controller
Closure (inline route) — loads stats: paket_aktif, tagihan_bulan_ini, riwayat_pembayaran

## View
`resources/js/Pages/Customer/Dashboard.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/DashboardTest.php` | Various | Browser test with screenshot |
