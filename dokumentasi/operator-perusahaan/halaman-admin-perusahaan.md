# Halaman Admin Perusahaan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/admin-perusahaan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/admin-perusahaan` | `operator-perusahaan.admin-perusahaan.index` | `auth:admin-company` | `admin-perusahaan.list` |
| POST | `/operator-perusahaan/admin-perusahaan` | `operator-perusahaan.admin-perusahaan.store` | `auth:admin-company` | `admin-perusahaan.create` |
| PUT | `/operator-perusahaan/admin-perusahaan/{adminCompany}` | `operator-perusahaan.admin-perusahaan.update` | `auth:admin-company` | `admin-perusahaan.edit` |
| POST | `/operator-perusahaan/admin-perusahaan/bulk-status` | `operator-perusahaan.admin-perusahaan.bulkStatus` | `auth:admin-company` | `admin-perusahaan.edit` |
| DELETE | `/operator-perusahaan/admin-perusahaan/{adminCompany}` | `operator-perusahaan.admin-perusahaan.destroy` | `auth:admin-company` | `admin-perusahaan.delete` |
| POST | `/operator-perusahaan/admin-perusahaan/bulk-delete` | `operator-perusahaan.admin-perusahaan.bulkDelete` | `auth:admin-company` | `admin-perusahaan.delete` |
| PATCH | `/operator-perusahaan/admin-perusahaan/{id}/restore` | `operator-perusahaan.admin-perusahaan.restore` | `auth:admin-company` | `admin-perusahaan.restore` |

## Controller
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@index`
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@store`
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@update`
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@bulkToggleStatus`
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@destroy`
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@bulkDelete`
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@restore`

## View
`resources/js/Pages/OperatorPerusahaan/AdminPerusahaan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorPerusahaan/AdminPerusahaanTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorPerusahaan/AdminPerusahaanCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorPerusahaan/AdminPerusahaanPermissionTest.php` | Various | Browser permission test |
