# Halaman Admin Perusahaan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/admin-perusahaan`

## Fungsi
Halaman untuk mengelola **akun admin perusahaan** dari sisi operator perusahaan.
Admin perusahaan adalah pengelola yang bertugas mengelola pelanggan, karyawan, dan layanan di perusahaannya.

## Fitur
- **Tabel daftar admin perusahaan** — menampilkan semua admin dengan nama, email, dan status
- **Pencarian** — mencari admin berdasarkan nama atau email (tekan Enter untuk mencari)
- **Filter status** — menyaring admin berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `admin-perusahaan.list` | Melihat tabel admin dan sidebar menu |
| **Tambah Admin** | `admin-perusahaan.create` | Membuat akun admin perusahaan baru |
| **Edit Admin** | `admin-perusahaan.edit` | Mengubah data admin yang sudah ada |
| **Hapus Admin** | `admin-perusahaan.delete` | Menghapus admin (dapat dipulihkan lagi) |
| **Pulihkan Admin** | `admin-perusahaan.restore` | Mengembalikan admin yang sudah dihapus |
| **Bulk Aktifkan** | `admin-perusahaan.edit` | Mengaktifkan banyak admin sekaligus |
| **Bulk Nonaktifkan** | `admin-perusahaan.edit` | Menonaktifkan banyak admin sekaligus |
| **Bulk Hapus** | `admin-perusahaan.delete` | Menghapus banyak admin sekaligus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/admin-perusahaan` | `operator-perusahaan.admin-perusahaan.index` | `auth:admin-company` | `admin-perusahaan.list` |
| POST | `/operator-perusahaan/admin-perusahaan` | `operator-perusahaan.admin-perusahaan.store` | `auth:admin-company` | `admin-perusahaan.create` |
| PUT | `/operator-perusahaan/admin-perusahaan/{adminCompany}` | `operator-perusahaan.admin-perusahaan.update` | `auth:admin-company` | `admin-perusahaan.edit` |
| POST | `/operator-perusahaan/admin-perusahaan/bulk-status` | `operator-perusahaan.admin-perusahaan.bulkStatus` | `auth:admin-company` | `admin-perusahaan.edit` |
| DELETE | `/operator-perusahaan/admin-perusahaan/{adminCompany}` | `operator-perusahaan.admin-perusahaan.destroy` | `auth:admin-company` | `admin-perusahaan.delete` |
| POST | `/operator-perusahaan/admin-perusahaan/bulk-delete` | `operator-perusahaan.admin-perusahaan.bulkDelete` | `auth:admin-company` | `admin-perusahaan.delete` |
| PATCH | `/operator-perusahaan/admin-perusahaan/{id}/restore` | `operator-perusahaan.admin-perusahaan.restore` | `auth:admin-company` | `admin-perusahaan.restore` |

### Controller
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@index`
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@store`
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@update`
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@bulkToggleStatus`
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@destroy`
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@bulkDelete`
`App\Http\Controllers\OperatorPerusahaan\AdminPerusahaanController@restore`

### View
`resources/js/Pages/OperatorPerusahaan/AdminPerusahaan.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\AdminCompany` | `admin_companies` | Model utama — akun admin perusahaan |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_135636_create_admin_companies_table` | `admin_companies` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorPerusahaan/AdminPerusahaanTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorPerusahaan/AdminPerusahaanCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorPerusahaan/AdminPerusahaanPermissionTest.php` | Various | Browser permission test |
