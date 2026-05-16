# Halaman Role Perusahaan
> Portal: Operator SaaS | URL: `/operator-saas/role-perusahaan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/role-perusahaan` | `operator-saas.role-perusahaan.index` | `auth:web`, `ensure.user.active:web` | `role-perusahaan.list` |
| POST | `/operator-saas/role-perusahaan` | `operator-saas.role-perusahaan.store` | `auth:web`, `ensure.user.active:web` | `role-perusahaan.create` |
| PUT | `/operator-saas/role-perusahaan/{role}` | `operator-saas.role-perusahaan.update` | `auth:web`, `ensure.user.active:web` | `role-perusahaan.edit` |
| POST | `/operator-saas/role-perusahaan/bulk-status` | `operator-saas.role-perusahaan.bulk-status` | `auth:web`, `ensure.user.active:web` | `role-perusahaan.edit` |
| DELETE | `/operator-saas/role-perusahaan/{role}` | `operator-saas.role-perusahaan.destroy` | `auth:web`, `ensure.user.active:web` | `role-perusahaan.delete` |
| POST | `/operator-saas/role-perusahaan/bulk-delete` | `operator-saas.role-perusahaan.bulk-delete` | `auth:web`, `ensure.user.active:web` | `role-perusahaan.delete` |
| POST | `/operator-saas/role-perusahaan/{id}/restore` | `operator-saas.role-perusahaan.restore` | `auth:web`, `ensure.user.active:web` | `role-perusahaan.restore` |

## Controller
`App\Http\Controllers\RolePerusahaanController@index`
`App\Http\Controllers\RolePerusahaanController@store`
`App\Http\Controllers\RolePerusahaanController@update`
`App\Http\Controllers\RolePerusahaanController@bulkToggleStatus`
`App\Http\Controllers\RolePerusahaanController@destroy`
`App\Http\Controllers\RolePerusahaanController@bulkDelete`
`App\Http\Controllers\RolePerusahaanController@restore`

## View
`resources/js/Pages/OperatorSaas/RolePerusahaan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/RolePerusahaanTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/RolePerusahaanCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorSaas/RolePerusahaanPermissionTest.php` | Various | Browser permission test |
