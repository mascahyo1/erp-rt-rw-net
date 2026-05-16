# Halaman Role Admin Perusahaan
> Portal: Operator SaaS | URL: `/operator-saas/role-admin-perusahaan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/role-admin-perusahaan` | `operator-saas.role-admin-perusahaan.index` | `auth:web`, `ensure.user.active:web` | `role-admin-perusahaan.list` |
| GET | `/operator-saas/role-admin-perusahaan/admins-by-company` | `operator-saas.role-admin-perusahaan.admins-by-company` | `auth:web`, `ensure.user.active:web` | `role-admin-perusahaan.list` |
| GET | `/operator-saas/role-admin-perusahaan/roles-by-company` | `operator-saas.role-admin-perusahaan.roles-by-company` | `auth:web`, `ensure.user.active:web` | `role-admin-perusahaan.list` |
| POST | `/operator-saas/role-admin-perusahaan` | `operator-saas.role-admin-perusahaan.store` | `auth:web`, `ensure.user.active:web` | `role-admin-perusahaan.create` |
| PUT | `/operator-saas/role-admin-perusahaan/{modelHasRole}` | `operator-saas.role-admin-perusahaan.update` | `auth:web`, `ensure.user.active:web` | `role-admin-perusahaan.edit` |
| DELETE | `/operator-saas/role-admin-perusahaan/{modelHasRole}` | `operator-saas.role-admin-perusahaan.destroy` | `auth:web`, `ensure.user.active:web` | `role-admin-perusahaan.delete` |
| POST | `/operator-saas/role-admin-perusahaan/bulk-delete` | `operator-saas.role-admin-perusahaan.bulk-delete` | `auth:web`, `ensure.user.active:web` | `role-admin-perusahaan.delete` |

## Controller
`App\Http\Controllers\RoleAdminPerusahaanController@index`
`App\Http\Controllers\RoleAdminPerusahaanController@adminsByCompany`
`App\Http\Controllers\RoleAdminPerusahaanController@rolesByCompany`
`App\Http\Controllers\RoleAdminPerusahaanController@store`
`App\Http\Controllers\RoleAdminPerusahaanController@update`
`App\Http\Controllers\RoleAdminPerusahaanController@destroy`
`App\Http\Controllers\RoleAdminPerusahaanController@bulkDelete`

## View
`resources/js/Pages/OperatorSaas/RoleAdminPerusahaan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/RoleAdminPerusahaanTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/RoleAdminPerusahaanCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorSaas/RoleAdminPerusahaanPermissionTest.php` | Various | Browser permission test |
