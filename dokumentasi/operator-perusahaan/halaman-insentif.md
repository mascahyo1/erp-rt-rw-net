# Halaman Insentif
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/insentif`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/insentif` | — | `auth:admin-company` | `insentif.list` |
| POST | `/operator-perusahaan/insentif` | — | `auth:admin-company` | `insentif.create` |
| PUT | `/operator-perusahaan/insentif/{id}` | — | `auth:admin-company` | `insentif.edit` |
| DELETE | `/operator-perusahaan/insentif/{id}` | — | `auth:admin-company` | `insentif.delete` |
| PATCH | `/operator-perusahaan/insentif/{id}/restore` | — | `auth:admin-company` | `insentif.restore` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/OperatorPerusahaan/Insentif.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/InsentifPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/OperatorPerusahaan/InsentifViewTest.php` | Various | Browser view test |
