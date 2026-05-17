# Halaman Customer
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/customer`

## Fungsi
Halaman untuk mengelola **data pelanggan** yang terdaftar di perusahaan.
Pelanggan adalah pengguna akhir yang berlangganan layanan internet dari perusahaan.

## Fitur
- **Tabel daftar pelanggan** — menampilkan semua pelanggan dengan nama, email, alamat, telepon, dan status
- **Pencarian** — mencari pelanggan berdasarkan nama, email, atau alamat (tekan Enter untuk mencari)
- **Filter status** — menyaring pelanggan berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `customer.list` | Melihat tabel pelanggan dan sidebar menu |
| **Tambah Pelanggan** | `customer.create` | Membuat pelanggan baru dengan mengisi nama, email, alamat, telepon, dan status |
| **Edit Pelanggan** | `customer.edit` | Mengubah data pelanggan yang sudah ada |
| **Hapus Pelanggan** | `customer.delete` | Menghapus pelanggan (dapat dipulihkan lagi) |
| **Pulihkan Pelanggan** | `customer.restore` | Mengembalikan pelanggan yang sudah dihapus |
| **Bulk Aktifkan** | `customer.edit` | Mengaktifkan banyak pelanggan sekaligus |
| **Bulk Nonaktifkan** | `customer.edit` | Menonaktifkan banyak pelanggan sekaligus |
| **Bulk Hapus** | `customer.delete` | Menghapus banyak pelanggan sekaligus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/customer` | `operator-perusahaan.customer.index` | `auth:admin-company` | `customer.list` |
| POST | `/operator-perusahaan/customer` | `operator-perusahaan.customer.store` | `auth:admin-company` | `customer.create` |
| PUT | `/operator-perusahaan/customer/{customer}` | `operator-perusahaan.customer.update` | `auth:admin-company` | `customer.edit` |
| POST | `/operator-perusahaan/customer/bulk-status` | `operator-perusahaan.customer.bulkStatus` | `auth:admin-company` | `customer.edit` |
| DELETE | `/operator-perusahaan/customer/{customer}` | `operator-perusahaan.customer.destroy` | `auth:admin-company` | `customer.delete` |
| POST | `/operator-perusahaan/customer/bulk-delete` | `operator-perusahaan.customer.bulkDelete` | `auth:admin-company` | `customer.delete` |
| PATCH | `/operator-perusahaan/customer/{id}/restore` | `operator-perusahaan.customer.restore` | `auth:admin-company` | `customer.restore` |

### Controller
`App\Http\Controllers\OperatorPerusahaan\CustomerController@index`
`App\Http\Controllers\OperatorPerusahaan\CustomerController@store`
`App\Http\Controllers\OperatorPerusahaan\CustomerController@update`
`App\Http\Controllers\OperatorPerusahaan\CustomerController@bulkToggleStatus`
`App\Http\Controllers\OperatorPerusahaan\CustomerController@destroy`
`App\Http\Controllers\OperatorPerusahaan\CustomerController@bulkDelete`
`App\Http\Controllers\OperatorPerusahaan\CustomerController@restore`

### View
`resources/js/Pages/OperatorPerusahaan/Customer.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Customer` | `customers` | Model utama — data pelanggan |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_142201_create_customers_table` | `customers` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorPerusahaan/CustomerTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorPerusahaan/CustomerCRUDTest.php` | Various | Browser CRUD test with screenshot |
