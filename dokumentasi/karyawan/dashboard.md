# Dashboard
> Portal: Karyawan | URL: `/karyawan/dashboard`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/dashboard` | `employee.dashboard` | `auth:employee` | — |

## Controller
Closure (inline route) — loads stats: customer_ditagih, tagihan_bulan_ini, pembayaran_collection

## View
`resources/js/Pages/Karyawan/Dashboard.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/DashboardTest.php` | Various | Browser test with screenshot |
