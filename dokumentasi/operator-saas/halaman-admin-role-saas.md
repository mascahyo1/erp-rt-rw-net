# Halaman Admin Role SaaS
> Portal: Operator SaaS | URL: `/operator-saas/admin-role-saas`

## Fungsi
Halaman untuk **memasangkan role ke admin SaaS** — menentukan role/hak akses apa yang dimiliki oleh masing-masing admin SaaS.
Setiap admin SaaS bisa memiliki satu role yang mengontrol menu dan fitur apa saja yang bisa diakses.

## Fitur
- **Tabel daftar pemasangan** — menampilkan admin SaaS dan role yang sudah dipasangkan
- **Pencarian** — mencari berdasarkan nama admin atau nama role (tekan Enter untuk mencari)
- **Filter perusahaan** — menyaring berdasarkan perusahaan
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `admin-role-saas.list` | Melihat tabel pemasangan dan sidebar menu |
| **Pasang Role** | `admin-role-saas.create` | Memasangkan role ke admin SaaS |
| **Edit Pemasangan** | `admin-role-saas.edit` | Mengubah role yang sudah dipasangkan ke admin SaaS |
| **Hapus Pemasangan** | `admin-role-saas.delete` | Menghapus pemasangan role dari admin SaaS |
| **Bulk Hapus** | `admin-role-saas.delete` | Menghapus banyak pemasangan sekaligus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/admin-role-saas` | `operator-saas.admin-role-saas.index` | `auth:web`, `ensure.user.active:web` | `admin-role-saas.list` |
| POST | `/operator-saas/admin-role-saas` | `operator-saas.admin-role-saas.store` | `auth:web`, `ensure.user.active:web` | `admin-role-saas.create` |
| PUT | `/operator-saas/admin-role-saas/{modelHasRole}` | `operator-saas.admin-role-saas.update` | `auth:web`, `ensure.user.active:web` | `admin-role-saas.edit` |
| DELETE | `/operator-saas/admin-role-saas/{modelHasRole}` | `operator-saas.admin-role-saas.destroy` | `auth:web`, `ensure.user.active:web` | `admin-role-saas.delete` |
| POST | `/operator-saas/admin-role-saas/bulk-delete` | `operator-saas.admin-role-saas.bulk-delete` | `auth:web`, `ensure.user.active:web` | `admin-role-saas.delete` |

### Controller
`App\Http\Controllers\AdminRoleSaasController@index`
`App\Http\Controllers\AdminRoleSaasController@store`
`App\Http\Controllers\AdminRoleSaasController@update`
`App\Http\Controllers\AdminRoleSaasController@destroy`
`App\Http\Controllers\AdminRoleSaasController@bulkDelete`

### View
`resources/js/Pages/OperatorSaas/AdminRoleSaaS.vue`
### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\ModelHasRole` | `model_has_roles` | Model utama — pivot pemasangan role ke admin SaaS |
| `App\Models\AdminSaas` | `admin_saas` | Join — admin SaaS (via `model_type` = `AdminSaas`) |
| `App\Models\Role` | `roles` | Join — role yang dipasangkan (via `role_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_140504_create_model_has_roles_table` | `model_has_roles` |
| `2026_05_12_000505_create_admin_saas_table` | `admin_saas` |
| `2026_05_11_140234_create_roles_table` | `roles` |
### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/AdminRoleSaasTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/AdminRoleSaaSCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorSaas/AdminRoleSaaSPermissionTest.php` | Various | Browser permission test |
