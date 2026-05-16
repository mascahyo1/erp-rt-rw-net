# Halaman Langganan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/langganan-customer`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/langganan-customer` | — | `auth:admin-company` | `langganan.list` |
| POST | `/operator-perusahaan/langganan-customer` | — | `auth:admin-company` | `langganan.create` |
| PUT | `/operator-perusahaan/langganan-customer/{id}` | — | `auth:admin-company` | `langganan.edit` |
| DELETE | `/operator-perusahaan/langganan-customer/{id}` | — | `auth:admin-company` | `langganan.delete` |
| PATCH | `/operator-perusahaan/langganan-customer/{id}/restore` | — | `auth:admin-company` | `langganan.restore` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/OperatorPerusahaan/LanggananCustomer.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/LanggananPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/OperatorPerusahaan/LanggananCustomerViewTest.php` | Various | Browser view test |
