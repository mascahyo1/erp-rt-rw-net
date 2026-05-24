# Halaman Langganan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/langganan-customer`

## Fungsi
Halaman untuk mengelola **data langganan pelanggan** — paket internet apa yang dipakai pelanggan, harganya, dan status aktifnya.
Langganan adalah hubungan antara pelanggan dengan paket layanan internet yang mereka pilih.

### form create / edit / detail
- nama pelanggan (searchable select ajax infinite scroll)
- nama paket internet (searchable select ajax infinite scroll)
- no. akun (text input, unik per customer)
- router sn (text input, nullable)
- alamat (text input, nullable)
- koordinat long (text input, nullable)
- koordinat lat (text input, nullable)
- usage upload kb (number input)
- usage download kb (number input)
- internet_status select option: active, inactive, suspended, terminated
- company_notes (textarea, nullable)

### kolom datatable
| Kolom | Keterangan |
|-------|------------|
| Checkbox | Untuk pilih banyak data (bulk action) |
| Nama Customer | Nama pelanggan yang langganan |
| No. Akun | Nomor akun langganan (format: ACC-XXXX) |
| Nama Paket | Nama paket internet yang dipilih |
| Status | Status langganan: Aktif, Nonaktif, Suspend, Terminasi |
| Aksi | Tombol lihat, edit, hapus / pulihkan |

## Fitur
- **Tabel daftar langganan** — menampilkan semua langganan dengan pelanggan, paket, no. akun, dan status
- **Pencarian** — mencari langganan berdasarkan nama pelanggan atau nomor akun (tekan Enter untuk mencari)
- **Filter status** — menyaring langganan berdasarkan status: Aktif, Nonaktif, Suspend, Terminasi
- **Filter terhapus** — menampilkan data yang dihapus (soft delete) untuk dipulihkan
- **Urutkan** — klik judul kolom untuk mengurutkan data
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100
- **Select infinite scroll** — dropdown pelanggan & paket dengan pencarian ajax
- **Bulk actions** — aktifkan, nonaktifkan, hapus, pulihkan beberapa data sekaligus
- **Export Excel** — export data yang dipilih atau semua data
- **Import Excel** — import data dari file Excel dengan template

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `langganan.list` | Melihat tabel langganan dan sidebar menu |
| **Tambah Langganan** | `langganan.create` | Mendaftarkan pelanggan ke paket baru |
| **Edit Langganan** | `langganan.edit` | Mengubah data langganan yang sudah ada |
| **Hapus Langganan** | `langganan.delete` | Menghapus langganan (dapat dipulihkan lagi) |
| **Pulihkan Langganan** | `langganan.restore` | Mengembalikan langganan yang sudah dihapus |
| **Export** | `langganan.list` | Export data langganan ke Excel |
| **Import** | `langganan.create` | Import data langganan dari Excel |

## Bulk Actions
| Aksi | Endpoint | Method |
|------|---------|--------|
| Aktifkan | `/operator-perusahaan/langganan-customer/bulk-status` | POST |
| Nonaktifkan | `/operator-perusahaan/langganan-customer/bulk-status` | POST |
| Hapus | `/operator-perusahaan/langganan-customer/bulk-delete` | POST |
| Pulihkan | `/operator-perusahaan/langganan-customer/bulk-restore` | POST |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/langganan-customer` | operator-perusahaan.langganan.index | `auth:admin-company` | `langganan.list` |
| POST | `/operator-perusahaan/langganan-customer` | operator-perusahaan.langganan.store | `auth:admin-company` | `langganan.create` |
| PUT | `/operator-perusahaan/langganan-customer/{id}` | operator-perusahaan.langganan.update | `auth:admin-company` | `langganan.edit` |
| DELETE | `/operator-perusahaan/langganan-customer/{id}` | operator-perusahaan.langganan.destroy | `auth:admin-company` | `langganan.delete` |
| PATCH | `/operator-perusahaan/langganan-customer/{id}/restore` | operator-perusahaan.langganan.restore | `auth:admin-company` | `langganan.restore` |
| POST | `/operator-perusahaan/langganan-customer/bulk-status` | operator-perusahaan.langganan.bulkStatus | `auth:admin-company` | `langganan.edit` |
| POST | `/operator-perusahaan/langganan-customer/bulk-delete` | operator-perusahaan.langganan.bulkDelete | `auth:admin-company` | `langganan.delete` |
| POST | `/operator-perusahaan/langganan-customer/bulk-restore` | operator-perusahaan.langganan.bulkRestore | `auth:admin-company` | `langganan.restore` |
| GET | `/operator-perusahaan/langganan-customer/export` | operator-perusahaan.langganan.export | `auth:admin-company` | `langganan.list` |
| GET | `/operator-perusahaan/langganan-customer/template` | operator-perusahaan.langganan.template | `auth:admin-company` | `langganan.list` |
| POST | `/operator-perusahaan/langganan-customer/import` | operator-perusahaan.langganan.import | `auth:admin-company` | `langganan.create` |

### Controller
`App\Http\Controllers\OperatorPerusahaan\LanggananController`

### View
`resources/js/Pages/OperatorPerusahaan/LanggananCustomer.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\CustInternet` | `cust_internets` | Model utama — data langganan pelanggan |
| `App\Models\Customer` | `customers` | Join — nama pelanggan (via `customer_id`) |
| `App\Models\InternetPackage` | `internet_packages` | Join — nama paket & harga (via `internet_package_id`) |

### Kolom CustInternets
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | uuid | Primary key |
| `customer_id` | uuid | FK ke customers |
| `internet_package_id` | uuid | FK ke internet_packages |
| `account_number` | string | Nomor akun (unik per customer) |
| `router_sn` | string | Router serial number (nullable) |
| `customer_address` | string | Alamat pelanggan (nullable) |
| `customer_address_long` | text | Koordinat longitude (nullable) |
| `customer_address_lat` | decimal(10,7) | Koordinat latitude (nullable) |
| `usage_upload_kb` | decimal | Total upload dalam KB |
| `usage_download_kb` | decimal | Total download dalam KB |
| `internet_status` | enum | active, inactive, suspended, terminated |
| `company_notes` | text | Catatan internal (nullable) |
| `billing_amount` | decimal | Jumlah tagihan (nullable) |
| `billing_cycle_start` | date | Awal siklus billing (nullable) |
| `billing_cycle_end` | date | Akhir siklus billing (nullable) |
| `created_at`, `updated_at` | datetime | Timestamp |
| `deleted_at` | datetime | Soft delete |
| `created_by`, `updated_by`, `deleted_by` | uuid | Blameable |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_142443_create_cust_internets_table` | `cust_internets` |
| `2026_05_24_082445_add_address_columns_to_cust_internets_table` | `cust_internets` (add customer_address, customer_address_long, customer_address_lat) |
| `2026_05_11_142201_create_customers_table` | `customers` |
| `2026_05_11_141134_create_internet_packages_table` | `internet_packages` |

### Validasi
| Field | Aturan |
|-------|--------|
| customer_id | required, exists customers,id |
| internet_package_id | required, exists internet_packages,id |
| account_number | required, string, max:50, unique per customer_id |
| router_sn | nullable, string, max:100 |
| internet_status | required, in:active,inactive,suspended,terminated |
| usage_upload_kb | nullable, numeric, min:0 |
| usage_download_kb | nullable, numeric, min:0 |
| company_notes | nullable, string, max:500 |

### Import Excel
Template kolom: No. Akun, Customer ID (UUID), Paket ID (UUID), Router SN, Status, Usage Upload (KB), Usage Download (KB), Catatan

### Export Excel
Kolom export: No. Akun, Nama Customer, Nama Paket, Router SN, Status, Usage Upload (KB), Usage Download (KB), Tagihan, Catatan, Tanggal Dibuat

### Test Case
| File | Description |
|------|-------------|
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/LanggananCRUDTest.cjs` | CRUD test dengan Playwright NodeJS |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/LanggananPermissionTest.cjs` | Permission & RBAC test |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/LanggananResponsiveTest.cjs` | Responsive UI test |