# Halaman Tagihan
> Portal: Karyawan | URL: `/karyawan/tagihan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/tagihan` | — | `auth:employee` | `karyawan-tagihan.list` |
| POST | `/karyawan/tagihan` | — | `auth:employee` | `karyawan-tagihan.create` |
| PUT | `/karyawan/tagihan/{id}` | — | `auth:employee` | `karyawan-tagihan.edit` |
| DELETE | `/karyawan/tagihan/{id}` | — | `auth:employee` | `karyawan-tagihan.delete` |
| PATCH | `/karyawan/tagihan/{id}/restore` | — | `auth:employee` | `karyawan-tagihan.restore` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/Karyawan/Tagihan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/TagihanPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/Karyawan/TagihanViewTest.php` | Various | Browser view test |
