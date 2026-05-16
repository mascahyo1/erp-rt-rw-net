# Halaman Role SaaS
> Portal: Operator SaaS | URL: `/operator-saas/role-saas`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/role-saas` | `operator-saas.role-saas.index` | `auth:web`, `ensure.user.active:web` | `role-saas.list` |
| POST | `/operator-saas/role-saas` | `operator-saas.role-saas.store` | `auth:web`, `ensure.user.active:web` | `role-saas.create` |
| PUT | `/operator-saas/role-saas/{role}` | `operator-saas.role-saas.update` | `auth:web`, `ensure.user.active:web` | `role-saas.edit` |
| POST | `/operator-saas/role-saas/bulk-status` | `operator-saas.role-saas.bulk-status` | `auth:web`, `ensure.user.active:web` | `role-saas.edit` |
| DELETE | `/operator-saas/role-saas/{role}` | `operator-saas.role-saas.destroy` | `auth:web`, `ensure.user.active:web` | `role-saas.delete` |
| POST | `/operator-saas/role-saas/bulk-delete` | `operator-saas.role-saas.bulk-delete` | `auth:web`, `ensure.user.active:web` | `role-saas.delete` |
| POST | `/operator-saas/role-saas/{id}/restore` | `operator-saas.role-saas.restore` | `auth:web`, `ensure.user.active:web` | `role-saas.restore` |

## Controller
`App\Http\Controllers\RoleSaasController@index`
`App\Http\Controllers\RoleSaasController@store`
`App\Http\Controllers\RoleSaasController@update`
`App\Http\Controllers\RoleSaasController@bulkToggleStatus`
`App\Http\Controllers\RoleSaasController@destroy`
`App\Http\Controllers\RoleSaasController@bulkDelete`
`App\Http\Controllers\RoleSaasController@restore`

## View
`resources/js/Pages/OperatorSaas/RoleSaaS.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/RoleSaasTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/RoleSaaSCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorSaas/RoleSaaSPermissionTest.php` | Various | Browser permission test |
