# Halaman Riwayat Insentif
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/riwayat-insentif`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/riwayat-insentif` | — | `auth:admin-company` | `riwayat-insentif.list` |
| POST | `/operator-perusahaan/riwayat-insentif` | — | `auth:admin-company` | `riwayat-insentif.create` |
| PUT | `/operator-perusahaan/riwayat-insentif/{id}` | — | `auth:admin-company` | `riwayat-insentif.edit` |
| DELETE | `/operator-perusahaan/riwayat-insentif/{id}` | — | `auth:admin-company` | `riwayat-insentif.delete` |
| PATCH | `/operator-perusahaan/riwayat-insentif/{id}/restore` | — | `auth:admin-company` | `riwayat-insentif.restore` |
| POST | `/operator-perusahaan/riwayat-insentif/{id}/approve` | — | `auth:admin-company` | `riwayat-insentif.persetujuan` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/OperatorPerusahaan/RiwayatInsentif.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/RiwayatInsentifPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/OperatorPerusahaan/RiwayatInsentifViewTest.php` | Various | Browser view test |
