# Dashboard
> Portal: Operator SaaS | URL: `/operator-saas/dashboard`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/dashboard` | `operator-saas.dashboard` | `auth:web`, `ensure.user.active:web` | — |

## Controller
Closure (inline route) — loads stats: perusahaan_aktif, admin_perusahaan_aktif, admin_saas, pelanggan_aktif, karyawan_aktif, langganan_aktif

## View
`resources/js/Pages/OperatorSaas/Dashboard.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorSaas/DashboardViewTest.php` | Various | Browser view test |
