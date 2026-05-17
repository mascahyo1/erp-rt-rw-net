# Halaman Admin SaaS
> Portal: Operator SaaS | URL: `/operator-saas/admin-saas`

## Fungsi
Halaman untuk mengelola **akun admin SaaS** (super admin) yang bisa mengakses seluruh sistem.
Admin SaaS adalah pengelola tertinggi yang bertugas mengawasi seluruh perusahaan dan pengguna dalam platform.

## Fitur
- **Tabel daftar admin SaaS** — menampilkan semua admin SaaS dengan nama, email, dan status
- **Pencarian** — mencari admin berdasarkan nama atau email (tekan Enter untuk mencari)
- **Filter status** — menyaring admin berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `admin-saas.list` | Melihat tabel admin dan sidebar menu |
| **Tambah Admin** | `admin-saas.create` | Membuat akun admin SaaS baru dengan mengisi nama, email, password, dan status |
| **Edit Admin** | `admin-saas.edit` | Mengubah data admin SaaS yang sudah ada |
| **Hapus Admin** | `admin-saas.delete` | Menghapus admin SaaS (dapat dipulihkan lagi) |
| **Pulihkan Admin** | `admin-saas.restore` | Mengembalikan admin SaaS yang sudah dihapus |
| **Bulk Aktifkan** | `admin-saas.edit` | Mengaktifkan banyak admin sekaligus |
| **Bulk Nonaktifkan** | `admin-saas.edit` | Menonaktifkan banyak admin sekaligus |
| **Bulk Hapus** | `admin-saas.delete` | Menghapus banyak admin sekaligus |
| **Bulk Pulihkan** | `admin-saas.restore` | Memulihkan banyak admin sekaligus |

## Teknis
### Route
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

### Controller
`App\Http\Controllers\AdminSaasController@index`
`App\Http\Controllers\AdminSaasController@store`
`App\Http\Controllers\AdminSaasController@update`
`App\Http\Controllers\AdminSaasController@bulkToggleStatus`
`App\Http\Controllers\AdminSaasController@destroy`
`App\Http\Controllers\AdminSaasController@bulkDelete`
`App\Http\Controllers\AdminSaasController@restore`
`App\Http\Controllers\AdminSaasController@bulkRestore`

### View
`resources/js/Pages/OperatorSaas/AdminSaaS.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\AdminSaas` | `admin_saas` | Model utama — akun admin operator SaaS |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_12_000505_create_admin_saas_table` | `admin_saas` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/AdminSaasTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/AdminSaasCRUDTest.php` | Various | Browser CRUD test with screenshot |
