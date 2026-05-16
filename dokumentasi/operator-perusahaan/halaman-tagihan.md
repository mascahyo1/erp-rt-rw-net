# Halaman Tagihan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/tagihan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/tagihan` | — | `auth:admin-company` | `tagihan.list` |
| POST | `/operator-perusahaan/tagihan` | — | `auth:admin-company` | `tagihan.create` |
| PUT | `/operator-perusahaan/tagihan/{id}` | — | `auth:admin-company` | `tagihan.edit` |
| DELETE | `/operator-perusahaan/tagihan/{id}` | — | `auth:admin-company` | `tagihan.delete` |
| PATCH | `/operator-perusahaan/tagihan/{id}/restore` | — | `auth:admin-company` | `tagihan.restore` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/OperatorPerusahaan/Tagihan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/TagihanPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/OperatorPerusahaan/TagihanViewTest.php` | Various | Browser view test |
