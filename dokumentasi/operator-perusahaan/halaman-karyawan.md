# Halaman Karyawan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/karyawan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/karyawan` | `operator-perusahaan.karyawan.index` | `auth:admin-company` | `karyawan.list` |
| POST | `/operator-perusahaan/karyawan` | `operator-perusahaan.karyawan.store` | `auth:admin-company` | `karyawan.create` |
| PUT | `/operator-perusahaan/karyawan/{employee}` | `operator-perusahaan.karyawan.update` | `auth:admin-company` | `karyawan.edit` |
| POST | `/operator-perusahaan/karyawan/bulk-status` | `operator-perusahaan.karyawan.bulkStatus` | `auth:admin-company` | `karyawan.edit` |
| DELETE | `/operator-perusahaan/karyawan/{employee}` | `operator-perusahaan.karyawan.destroy` | `auth:admin-company` | `karyawan.delete` |
| POST | `/operator-perusahaan/karyawan/bulk-delete` | `operator-perusahaan.karyawan.bulkDelete` | `auth:admin-company` | `karyawan.delete` |
| PATCH | `/operator-perusahaan/karyawan/{id}/restore` | `operator-perusahaan.karyawan.restore` | `auth:admin-company` | `karyawan.restore` |

## Controller
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@index`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@store`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@update`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@bulkToggleStatus`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@destroy`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@bulkDelete`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@restore`

## View
`resources/js/Pages/OperatorPerusahaan/Karyawan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorPerusahaan/KaryawanTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorPerusahaan/KaryawanCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorPerusahaan/KaryawanPermissionTest.php` | Various | Browser permission test |
