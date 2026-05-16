# Dashboard
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/dashboard`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/dashboard` | `operator-perusahaan.dashboard` | `auth:admin-company` | — |

## Controller
Closure (inline route) — loads stats: total_customer, customer_aktif, karyawan_aktif, langganan_aktif, tagihan_bulan_ini

## View
`resources/js/Pages/OperatorPerusahaan/Dashboard.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/DashboardViewTest.php` | Various | Browser view test |
