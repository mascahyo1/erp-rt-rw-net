# Halaman Customer
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/customer`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/customer` | `operator-perusahaan.customer.index` | `auth:admin-company` | `customer.list` |
| POST | `/operator-perusahaan/customer` | `operator-perusahaan.customer.store` | `auth:admin-company` | `customer.create` |
| PUT | `/operator-perusahaan/customer/{customer}` | `operator-perusahaan.customer.update` | `auth:admin-company` | `customer.edit` |
| POST | `/operator-perusahaan/customer/bulk-status` | `operator-perusahaan.customer.bulkStatus` | `auth:admin-company` | `customer.edit` |
| DELETE | `/operator-perusahaan/customer/{customer}` | `operator-perusahaan.customer.destroy` | `auth:admin-company` | `customer.delete` |
| POST | `/operator-perusahaan/customer/bulk-delete` | `operator-perusahaan.customer.bulkDelete` | `auth:admin-company` | `customer.delete` |
| PATCH | `/operator-perusahaan/customer/{id}/restore` | `operator-perusahaan.customer.restore` | `auth:admin-company` | `customer.restore` |

## Controller
`App\Http\Controllers\OperatorPerusahaan\CustomerController@index`
`App\Http\Controllers\OperatorPerusahaan\CustomerController@store`
`App\Http\Controllers\OperatorPerusahaan\CustomerController@update`
`App\Http\Controllers\OperatorPerusahaan\CustomerController@bulkToggleStatus`
`App\Http\Controllers\OperatorPerusahaan\CustomerController@destroy`
`App\Http\Controllers\OperatorPerusahaan\CustomerController@bulkDelete`
`App\Http\Controllers\OperatorPerusahaan\CustomerController@restore`

## View
`resources/js/Pages/OperatorPerusahaan/Customer.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorPerusahaan/CustomerTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorPerusahaan/CustomerCRUDTest.php` | Various | Browser CRUD test with screenshot |
