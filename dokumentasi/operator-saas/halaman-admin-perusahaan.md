# Halaman Admin Perusahaan
> Portal: Operator SaaS | URL: `/operator-saas/admin-perusahaan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/admin-perusahaan` | `operator-saas.admin-perusahaan.index` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.list` |
| POST | `/operator-saas/admin-perusahaan` | `operator-saas.admin-perusahaan.store` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.create` |
| PUT | `/operator-saas/admin-perusahaan/{adminCompany}` | `operator-saas.admin-perusahaan.update` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.edit` |
| POST | `/operator-saas/admin-perusahaan/bulk-status` | `operator-saas.admin-perusahaan.bulk-status` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.edit` |
| DELETE | `/operator-saas/admin-perusahaan/{adminCompany}` | `operator-saas.admin-perusahaan.destroy` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.delete` |
| POST | `/operator-saas/admin-perusahaan/bulk-delete` | `operator-saas.admin-perusahaan.bulk-delete` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.delete` |
| POST | `/operator-saas/admin-perusahaan/{id}/restore` | `operator-saas.admin-perusahaan.restore` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.restore` |

## Controller
`App\Http\Controllers\AdminCompanyController@index`
`App\Http\Controllers\AdminCompanyController@store`
`App\Http\Controllers\AdminCompanyController@update`
`App\Http\Controllers\AdminCompanyController@bulkToggleStatus`
`App\Http\Controllers\AdminCompanyController@destroy`
`App\Http\Controllers\AdminCompanyController@bulkDelete`
`App\Http\Controllers\AdminCompanyController@restore`

## View
`resources/js/Pages/OperatorSaas/AdminPerusahaan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/AdminCompanyTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/AdminPerusahaanCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorSaas/AdminPerusahaanPermissionTest.php` | Various | Browser permission test |
