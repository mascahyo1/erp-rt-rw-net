# Halaman Langganan
> Portal: Karyawan | URL: `/karyawan/langganan-customer`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/langganan-customer` | — | `auth:employee` | `karyawan-langganan.list` |
| POST | `/karyawan/langganan-customer` | — | `auth:employee` | `karyawan-langganan.create` |
| PUT | `/karyawan/langganan-customer/{id}` | — | `auth:employee` | `karyawan-langganan.edit` |
| DELETE | `/karyawan/langganan-customer/{id}` | — | `auth:employee` | `karyawan-langganan.delete` |
| PATCH | `/karyawan/langganan-customer/{id}/restore` | — | `auth:employee` | `karyawan-langganan.restore` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/Karyawan/LanggananCustomer.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/LanggananPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/Karyawan/LanggananCustomerViewTest.php` | Various | Browser view test |
