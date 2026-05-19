# Halaman Langganan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/langganan-customer`

## Fungsi
Halaman untuk mengelola **data langganan pelanggan** — paket internet apa yang dipakai pelanggan, harganya, dan status aktifnya.
Langganan adalah hubungan antara pelanggan dengan paket layanan internet yang mereka pilih.

### form create / edit / detail
nama pelanggan
nama paket
usage_upload_kb
usage_download_kb
internet_status select option: active,inactive,suspended,terminated,company_notes

### kolom datatable
nama customer
paket customer
status
aksi

## Fitur
- **Tabel daftar langganan** — menampilkan semua langganan dengan pelanggan, paket, harga, dan status
- **Pencarian** — mencari langganan berdasarkan nama pelanggan atau paket (tekan Enter untuk mencari)
- **Filter status** — menyaring langganan berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `langganan.list` | Melihat tabel langganan dan sidebar menu |
| **Tambah Langganan** | `langganan.create` | Mendaftarkan pelanggan ke paket baru |
| **Edit Langganan** | `langganan.edit` | Mengubah data langganan yang sudah ada |
| **Hapus Langganan** | `langganan.delete` | Menghapus langganan (dapat dipulihkan lagi) |
| **Pulihkan Langganan** | `langganan.restore` | Mengembalikan langganan yang sudah dihapus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/langganan-customer` | — | `auth:admin-company` | `langganan.list` |
| POST | `/operator-perusahaan/langganan-customer` | — | `auth:admin-company` | `langganan.create` |
| PUT | `/operator-perusahaan/langganan-customer/{id}` | — | `auth:admin-company` | `langganan.edit` |
| DELETE | `/operator-perusahaan/langganan-customer/{id}` | — | `auth:admin-company` | `langganan.delete` |
| PATCH | `/operator-perusahaan/langganan-customer/{id}/restore` | — | `auth:admin-company` | `langganan.restore` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/LanggananCustomer.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\CustInternet` | `cust_internets` | Model utama — data langganan pelanggan |
| `App\Models\Customer` | `customers` | Join — nama pelanggan (via `customer_id`) |
| `App\Models\InternetPackage` | `internet_packages` | Join — nama paket & harga (via `internet_package_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_142443_create_cust_internets_table` | `cust_internets` |
| `2026_05_11_142201_create_customers_table` | `customers` |
| `2026_05_11_141134_create_internet_packages_table` | `internet_packages` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/LanggananPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/OperatorPerusahaan/LanggananCustomerViewTest.php` | Various | Browser view test |
