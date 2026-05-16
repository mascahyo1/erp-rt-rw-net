# Halaman Perusahaan
> Portal: Operator SaaS | URL: `/operator-saas/perusahaan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/perusahaan` | `operator-saas.perusahaan.index` | `auth:web`, `ensure.user.active:web` | `perusahaan.list` |
| GET | `/operator-saas/perusahaan/select-search` | `operator-saas.perusahaan.select-search` | — | — |
| POST | `/operator-saas/perusahaan` | `operator-saas.perusahaan.store` | `auth:web`, `ensure.user.active:web` | `perusahaan.create` |
| PUT | `/operator-saas/perusahaan/{company}` | `operator-saas.perusahaan.update` | `auth:web`, `ensure.user.active:web` | `perusahaan.edit` |
| POST | `/operator-saas/perusahaan/bulk-status` | `operator-saas.perusahaan.bulk-status` | `auth:web`, `ensure.user.active:web` | `perusahaan.edit` |
| DELETE | `/operator-saas/perusahaan/{company}` | `operator-saas.perusahaan.destroy` | `auth:web`, `ensure.user.active:web` | `perusahaan.delete` |
| POST | `/operator-saas/perusahaan/bulk-delete` | `operator-saas.perusahaan.bulk-delete` | `auth:web`, `ensure.user.active:web` | `perusahaan.delete` |
| POST | `/operator-saas/perusahaan/{id}/restore` | `operator-saas.perusahaan.restore` | `auth:web`, `ensure.user.active:web` | `perusahaan.restore` |

## Controller
`App\Http\Controllers\CompanyController@index`
`App\Http\Controllers\CompanyController@selectSearch`
`App\Http\Controllers\CompanyController@store`
`App\Http\Controllers\CompanyController@update`
`App\Http\Controllers\CompanyController@bulkToggleStatus`
`App\Http\Controllers\CompanyController@destroy`
`App\Http\Controllers\CompanyController@bulkDelete`
`App\Http\Controllers\CompanyController@restore`

## View
`resources/js/Pages/OperatorSaas/Perusahaan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/PerusahaanTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/PerusahaanCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorSaas/PerusahaanPermissionTest.php` | Various | Browser permission test |
