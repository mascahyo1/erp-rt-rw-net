# Halaman Customer
> Portal: Karyawan | URL: `/karyawan/customer`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/customer` | — | `auth:employee` | `karyawan-customer.list` |
| POST | `/karyawan/customer` | — | `auth:employee` | `karyawan-customer.create` |
| PUT | `/karyawan/customer/{id}` | — | `auth:employee` | `karyawan-customer.edit` |
| DELETE | `/karyawan/customer/{id}` | — | `auth:employee` | `karyawan-customer.delete` |
| PATCH | `/karyawan/customer/{id}/restore` | — | `auth:employee` | `karyawan-customer.restore` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/Karyawan/Customer.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/CustomerPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/Karyawan/CustomerViewTest.php` | Various | Browser view test |
