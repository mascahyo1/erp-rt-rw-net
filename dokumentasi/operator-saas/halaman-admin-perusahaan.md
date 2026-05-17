# Halaman Admin Perusahaan
> Portal: Operator SaaS | URL: `/operator-saas/admin-perusahaan`

## Fungsi
Halaman untuk mengelola **akun admin perusahaan** dari sisi operator SaaS.
Admin perusahaan adalah pengelola tingkat perusahaan yang bertugas mengelola pelanggan, karyawan, dan layanan di perusahaannya.

## Fitur
- **Tabel daftar admin perusahaan** — menampilkan semua admin perusahaan dengan nama, email, perusahaan, dan status
- **Pencarian** — mencari admin berdasarkan nama atau email (tekan Enter untuk mencari)
- **Filter status** — menyaring admin berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `admin-perusahaan.list` | Melihat tabel admin dan sidebar menu |
| **Tambah Admin** | `admin-perusahaan.create` | Membuat akun admin perusahaan baru dengan mengisi nama, email, password, perusahaan, dan status |
| **Edit Admin** | `admin-perusahaan.edit` | Mengubah data admin perusahaan yang sudah ada |
| **Hapus Admin** | `admin-perusahaan.delete` | Menghapus admin perusahaan (dapat dipulihkan lagi) |
| **Pulihkan Admin** | `admin-perusahaan.restore` | Mengembalikan admin perusahaan yang sudah dihapus |
| **Bulk Aktifkan** | `admin-perusahaan.edit` | Mengaktifkan banyak admin sekaligus |
| **Bulk Nonaktifkan** | `admin-perusahaan.edit` | Menonaktifkan banyak admin sekaligus |
| **Bulk Hapus** | `admin-perusahaan.delete` | Menghapus banyak admin sekaligus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/admin-perusahaan` | `operator-saas.admin-perusahaan.index` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.list` |
| POST | `/operator-saas/admin-perusahaan` | `operator-saas.admin-perusahaan.store` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.create` |
| PUT | `/operator-saas/admin-perusahaan/{adminCompany}` | `operator-saas.admin-perusahaan.update` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.edit` |
| POST | `/operator-saas/admin-perusahaan/bulk-status` | `operator-saas.admin-perusahaan.bulk-status` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.edit` |
| DELETE | `/operator-saas/admin-perusahaan/{adminCompany}` | `operator-saas.admin-perusahaan.destroy` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.delete` |
| POST | `/operator-saas/admin-perusahaan/bulk-delete` | `operator-saas.admin-perusahaan.bulk-delete` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.delete` |
| POST | `/operator-saas/admin-perusahaan/{id}/restore` | `operator-saas.admin-perusahaan.restore` | `auth:web`, `ensure.user.active:web` | `admin-perusahaan.restore` |

### Controller
`App\Http\Controllers\AdminCompanyController@index`
`App\Http\Controllers\AdminCompanyController@store`
`App\Http\Controllers\AdminCompanyController@update`
`App\Http\Controllers\AdminCompanyController@bulkToggleStatus`
`App\Http\Controllers\AdminCompanyController@destroy`
`App\Http\Controllers\AdminCompanyController@bulkDelete`
`App\Http\Controllers\AdminCompanyController@restore`

### View
`resources/js/Pages/OperatorSaas/AdminPerusahaan.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\AdminCompany` | `admin_companies` | Model utama — akun admin perusahaan (global) |
| `App\Models\Company` | `companies` | Join — nama perusahaan (via `company_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_135636_create_admin_companies_table` | `admin_companies` |
| `2026_05_11_135231_create_companies_table` | `companies` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/AdminCompanyTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/AdminPerusahaanCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorSaas/AdminPerusahaanPermissionTest.php` | Various | Browser permission test |
