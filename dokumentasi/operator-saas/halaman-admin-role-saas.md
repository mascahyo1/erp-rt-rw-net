# Halaman Admin Role SaaS
> Portal: Operator SaaS | URL: `/operator-saas/admin-role-saas`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/admin-role-saas` | `operator-saas.admin-role-saas.index` | `auth:web`, `ensure.user.active:web` | `admin-role-saas.list` |
| POST | `/operator-saas/admin-role-saas` | `operator-saas.admin-role-saas.store` | `auth:web`, `ensure.user.active:web` | `admin-role-saas.create` |
| PUT | `/operator-saas/admin-role-saas/{modelHasRole}` | `operator-saas.admin-role-saas.update` | `auth:web`, `ensure.user.active:web` | `admin-role-saas.edit` |
| DELETE | `/operator-saas/admin-role-saas/{modelHasRole}` | `operator-saas.admin-role-saas.destroy` | `auth:web`, `ensure.user.active:web` | `admin-role-saas.delete` |
| POST | `/operator-saas/admin-role-saas/bulk-delete` | `operator-saas.admin-role-saas.bulk-delete` | `auth:web`, `ensure.user.active:web` | `admin-role-saas.delete` |

## Controller
`App\Http\Controllers\AdminRoleSaasController@index`
`App\Http\Controllers\AdminRoleSaasController@store`
`App\Http\Controllers\AdminRoleSaasController@update`
`App\Http\Controllers\AdminRoleSaasController@destroy`
`App\Http\Controllers\AdminRoleSaasController@bulkDelete`

## View
`resources/js/Pages/OperatorSaas/AdminRoleSaaS.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/AdminRoleSaasTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/AdminRoleSaaSCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorSaas/AdminRoleSaaSPermissionTest.php` | Various | Browser permission test |
