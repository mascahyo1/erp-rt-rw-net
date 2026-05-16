# Halaman Admin SaaS
> Portal: Operator SaaS | URL: `/operator-saas/admin-saas`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/admin-saas` | `operator-saas.admin-saas.index` | `auth:web`, `ensure.user.active:web` | `admin-saas.list` |
| POST | `/operator-saas/admin-saas` | `operator-saas.admin-saas.store` | `auth:web`, `ensure.user.active:web` | `admin-saas.create` |
| PUT | `/operator-saas/admin-saas/{adminSaas}` | `operator-saas.admin-saas.update` | `auth:web`, `ensure.user.active:web` | `admin-saas.edit` |
| POST | `/operator-saas/admin-saas/bulk-status` | `operator-saas.admin-saas.bulk-status` | `auth:web`, `ensure.user.active:web` | `admin-saas.edit` |
| DELETE | `/operator-saas/admin-saas/{adminSaas}` | `operator-saas.admin-saas.destroy` | `auth:web`, `ensure.user.active:web` | `admin-saas.delete` |
| POST | `/operator-saas/admin-saas/bulk-delete` | `operator-saas.admin-saas.bulk-delete` | `auth:web`, `ensure.user.active:web` | `admin-saas.delete` |
| POST | `/operator-saas/admin-saas/{id}/restore` | `operator-saas.admin-saas.restore` | `auth:web`, `ensure.user.active:web` | `admin-saas.restore` |
| POST | `/operator-saas/admin-saas/bulk-restore` | `operator-saas.admin-saas.bulk-restore` | `auth:web`, `ensure.user.active:web` | `admin-saas.restore` |

## Controller
`App\Http\Controllers\AdminSaasController@index`
`App\Http\Controllers\AdminSaasController@store`
`App\Http\Controllers\AdminSaasController@update`
`App\Http\Controllers\AdminSaasController@bulkToggleStatus`
`App\Http\Controllers\AdminSaasController@destroy`
`App\Http\Controllers\AdminSaasController@bulkDelete`
`App\Http\Controllers\AdminSaasController@restore`
`App\Http\Controllers\AdminSaasController@bulkRestore`

## View
`resources/js/Pages/OperatorSaas/AdminSaaS.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/AdminSaasTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/AdminSaasCRUDTest.php` | Various | Browser CRUD test with screenshot |
