# Halaman Konfigurasi
> Portal: Operator SaaS | URL: `/operator-saas/konfigurasi`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/konfigurasi` | `operator-saas.konfigurasi.index` | `auth:web`, `ensure.user.active:web` | `konfigurasi.list` |
| POST | `/operator-saas/konfigurasi` | `operator-saas.konfigurasi.store` | `auth:web`, `ensure.user.active:web` | `konfigurasi.create` |
| PUT | `/operator-saas/konfigurasi/{saasConfig}` | `operator-saas.konfigurasi.update` | `auth:web`, `ensure.user.active:web` | `konfigurasi.edit` |
| DELETE | `/operator-saas/konfigurasi/{saasConfig}` | `operator-saas.konfigurasi.destroy` | `auth:web`, `ensure.user.active:web` | `konfigurasi.delete` |
| POST | `/operator-saas/konfigurasi/bulk-delete` | `operator-saas.konfigurasi.bulk-delete` | `auth:web`, `ensure.user.active:web` | `konfigurasi.delete` |

## Controller
`App\Http\Controllers\SaasConfigController@index`
`App\Http\Controllers\SaasConfigController@store`
`App\Http\Controllers\SaasConfigController@update`
`App\Http\Controllers\SaasConfigController@destroy`
`App\Http\Controllers\SaasConfigController@bulkDelete`

## View
`resources/js/Pages/OperatorSaas/Konfigurasi.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/SaasConfigTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/KonfigurasiCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorSaas/KonfigurasiPermissionTest.php` | Various | Browser permission test |
