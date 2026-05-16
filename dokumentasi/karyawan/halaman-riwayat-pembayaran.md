# Halaman Riwayat Pembayaran
> Portal: Karyawan | URL: `/karyawan/riwayat-pembayaran`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/riwayat-pembayaran` | — | `auth:employee` | `karyawan-riwayat-pembayaran.list` |
| POST | `/karyawan/riwayat-pembayaran` | — | `auth:employee` | `karyawan-riwayat-pembayaran.create` |
| PUT | `/karyawan/riwayat-pembayaran/{id}` | — | `auth:employee` | `karyawan-riwayat-pembayaran.edit` |
| DELETE | `/karyawan/riwayat-pembayaran/{id}` | — | `auth:employee` | `karyawan-riwayat-pembayaran.delete` |
| PATCH | `/karyawan/riwayat-pembayaran/{id}/restore` | — | `auth:employee` | `karyawan-riwayat-pembayaran.restore` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/Karyawan/RiwayatPembayaran.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/RiwayatPembayaranPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/Karyawan/RiwayatPembayaranViewTest.php` | Various | Browser view test |
