# Halaman Perusahaan
> Portal: Operator SaaS | URL: `/operator-saas/perusahaan`

## Fungsi
Halaman untuk mengelola **data perusahaan/tenant** yang terdaftar dalam sistem SaaS.
Setiap perusahaan adalah penyedia layanan internet yang memiliki pelanggan dan karyawan sendiri.

## Fitur
- **Tabel daftar perusahaan** — menampilkan semua perusahaan dengan nama, email, alamat, telepon, dan status
- **Pencarian** — mencari perusahaan berdasarkan nama, email, atau alamat (tekan Enter untuk mencari)
- **Filter status** — menyaring perusahaan berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `perusahaan.list` | Melihat tabel perusahaan dan sidebar menu |
| **Tambah Perusahaan** | `perusahaan.create` | Membuat perusahaan baru dengan mengisi nama, email, alamat, telepon, dan status |
| **Edit Perusahaan** | `perusahaan.edit` | Mengubah data perusahaan yang sudah ada |
| **Hapus Perusahaan** | `perusahaan.delete` | Menghapus perusahaan (dapat dipulihkan lagi) |
| **Pulihkan Perusahaan** | `perusahaan.restore` | Mengembalikan perusahaan yang sudah dihapus |
| **Bulk Aktifkan** | `perusahaan.edit` | Mengaktifkan banyak perusahaan sekaligus |
| **Bulk Nonaktifkan** | `perusahaan.edit` | Menonaktifkan banyak perusahaan sekaligus |
| **Bulk Hapus** | `perusahaan.delete` | Menghapus banyak perusahaan sekaligus |
| **Bulk Pulihkan** | `perusahaan.restore` | Memulihkan banyak perusahaan sekaligus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/perusahaan` | `operator-saas.perusahaan.index` | `auth:web`, `ensure.user.active:web` | `perusahaan.list` |
| GET | `/operator-saas/perusahaan/select-search` | `operator-saas.perusahaan.select-search` | — | — |
| POST | `/operator-saas/perusahaan` | `operator-saas.perusahaan.store` | `auth:web`, `ensure.user.active:web` | `perusahaan.create` |
| PUT | `/operator-saas/perusahaan/{company}` | `operator-saas.perusahaan.update` | `auth:web`, `ensure.user.active:web` | `perusahaan.edit` |
| POST | `/operator-saas/perusahaan/bulk-status` | `operator-saas.perusahaan.bulk-status` | `auth:web`, `ensure.user.active:web` | `perusahaan.edit` |
| DELETE | `/operator-saas/perusahaan/{company}` | `operator-saas.perusahaan.destroy` | `auth:web`, `ensure.user.active:web` | `perusahaan.delete` |
| POST | `/operator-saas/perusahaan/bulk-delete` | `operator-saas.perusahaan.bulk-delete` | `auth:web`, `ensure.user.active:web` | `perusahaan.delete` |
| POST | `/operator-saas/perusahaan/{id}/restore` | `operator-saas.perusahaan.restore` | `auth:web`, `ensure.user.active:web` | `perusahaan.restore` |

### Controller
`App\Http\Controllers\CompanyController@index`
`App\Http\Controllers\CompanyController@selectSearch`
`App\Http\Controllers\CompanyController@store`
`App\Http\Controllers\CompanyController@update`
`App\Http\Controllers\CompanyController@bulkToggleStatus`
`App\Http\Controllers\CompanyController@destroy`
`App\Http\Controllers\CompanyController@bulkDelete`
`App\Http\Controllers\CompanyController@restore`

### View
`resources/js/Pages/OperatorSaas/Perusahaan.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Company` | `companies` | Model utama — data perusahaan |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_135231_create_companies_table` | `companies` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/PerusahaanTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/PerusahaanCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorSaas/PerusahaanPermissionTest.php` | Various | Browser permission test |
