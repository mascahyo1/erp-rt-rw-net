# Halaman Riwayat Pembayaran
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/riwayat-pembayaran`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/riwayat-pembayaran` | — | `auth:admin-company` | `riwayat-pembayaran.list` |
| POST | `/operator-perusahaan/riwayat-pembayaran` | — | `auth:admin-company` | `riwayat-pembayaran.create` |
| PUT | `/operator-perusahaan/riwayat-pembayaran/{id}` | — | `auth:admin-company` | `riwayat-pembayaran.edit` |
| DELETE | `/operator-perusahaan/riwayat-pembayaran/{id}` | — | `auth:admin-company` | `riwayat-pembayaran.delete` |
| PATCH | `/operator-perusahaan/riwayat-pembayaran/{id}/restore` | — | `auth:admin-company` | `riwayat-pembayaran.restore` |
| POST | `/operator-perusahaan/riwayat-pembayaran/{id}/approve` | — | `auth:admin-company` | `riwayat-pembayaran.persetujuan` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/OperatorPerusahaan/RiwayatPembayaran.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/RiwayatPembayaranPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/OperatorPerusahaan/RiwayatPembayaranViewTest.php` | Various | Browser view test |
