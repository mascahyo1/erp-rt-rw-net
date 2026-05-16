# Halaman Role SaaS
> Portal: Operator SaaS | URL: `/operator-saas/role-saas`

## Fungsi
Halaman untuk mengelola **role (peran)** yang bisa diberikan kepada admin SaaS.
Role menentukan hak akses apa saja yang dimiliki admin SaaS terhadap menu dan fitur di portal operator SaaS.

Contoh: Role "Super Admin" bisa akses semua menu, sedangkan Role "Admin Terbatas" hanya bisa melihat data tanpa bisa mengubahnya.

## Fitur
- **Tabel daftar role** — menampilkan semua role yang sudah dibuat, dilengkapi nama dan status
- **Pencarian** — mencari role berdasarkan nama (tekan Enter untuk mencari)
- **Filter status** — menyaring role berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `role-saas.list` | Melihat tabel role dan sidebar menu |
| **Tambah Role** | `role-saas.create` | Membuat role baru dengan mengisi nama dan status |
| **Edit Role** | `role-saas.edit` | Mengubah nama dan status role yang sudah ada |
| **Hapus Role** | `role-saas.delete` | Menghapus role (dapat dipulihkan lagi) |
| **Pulihkan Role** | `role-saas.restore` | Mengembalikan role yang sudah dihapus |
| **Bulk Aktifkan** | `role-saas.edit` | Mengaktifkan banyak role sekaligus |
| **Bulk Nonaktifkan** | `role-saas.edit` | Menonaktifkan banyak role sekaligus |
| **Bulk Hapus** | `role-saas.delete` | Menghapus banyak role sekaligus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/role-saas` | `operator-saas.role-saas.index` | `auth:web`, `ensure.user.active:web` | `role-saas.list` |
| POST | `/operator-saas/role-saas` | `operator-saas.role-saas.store` | `auth:web`, `ensure.user.active:web` | `role-saas.create` |
| PUT | `/operator-saas/role-saas/{role}` | `operator-saas.role-saas.update` | `auth:web`, `ensure.user.active:web` | `role-saas.edit` |
| POST | `/operator-saas/role-saas/bulk-status` | `operator-saas.role-saas.bulk-status` | `auth:web`, `ensure.user.active:web` | `role-saas.edit` |
| DELETE | `/operator-saas/role-saas/{role}` | `operator-saas.role-saas.destroy` | `auth:web`, `ensure.user.active:web` | `role-saas.delete` |
| POST | `/operator-saas/role-saas/bulk-delete` | `operator-saas.role-saas.bulk-delete` | `auth:web`, `ensure.user.active:web` | `role-saas.delete` |
| POST | `/operator-saas/role-saas/{id}/restore` | `operator-saas.role-saas.restore` | `auth:web`, `ensure.user.active:web` | `role-saas.restore` |

### Controller
`App\Http\Controllers\RoleSaasController@index`
`App\Http\Controllers\RoleSaasController@store`
`App\Http\Controllers\RoleSaasController@update`
`App\Http\Controllers\RoleSaasController@bulkToggleStatus`
`App\Http\Controllers\RoleSaasController@destroy`
`App\Http\Controllers\RoleSaasController@bulkDelete`
`App\Http\Controllers\RoleSaasController@restore`

### View
`resources/js/Pages/OperatorSaas/RoleSaaS.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/RoleSaasTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/RoleSaaSCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorSaas/RoleSaaSPermissionTest.php` | Various | Browser permission test |
