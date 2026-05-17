# Halaman Langganan
> Portal: Karyawan | URL: `/karyawan/langganan-customer`

## Fungsi
Halaman untuk **melihat data langganan pelanggan** yang ditugaskan kepada karyawan.
Karyawan dapat melihat paket internet yang dipakai oleh pelanggan untuk keperluan penagihan.

## Fitur
- **Tabel daftar langganan** — menampilkan langganan pelanggan dengan paket, harga, dan status
- **Pencarian** — mencari langganan berdasarkan nama pelanggan atau paket (tekan Enter untuk mencari)
- **Filter status** — menyaring langganan berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `karyawan-langganan.list` | Melihat tabel langganan dan sidebar menu |
| **Tambah Langganan** | `karyawan-langganan.create` | Mendaftarkan pelanggan ke paket baru |
| **Edit Langganan** | `karyawan-langganan.edit` | Mengubah data langganan |
| **Hapus Langganan** | `karyawan-langganan.delete` | Menghapus langganan (dapat dipulihkan lagi) |
| **Pulihkan Langganan** | `karyawan-langganan.restore` | Mengembalikan langganan yang sudah dihapus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/langganan-customer` | — | `auth:employee` | `karyawan-langganan.list` |
| POST | `/karyawan/langganan-customer` | — | `auth:employee` | `karyawan-langganan.create` |
| PUT | `/karyawan/langganan-customer/{id}` | — | `auth:employee` | `karyawan-langganan.edit` |
| DELETE | `/karyawan/langganan-customer/{id}` | — | `auth:employee` | `karyawan-langganan.delete` |
| PATCH | `/karyawan/langganan-customer/{id}/restore` | — | `auth:employee` | `karyawan-langganan.restore` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Karyawan/LanggananCustomer.vue`

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
| `tests/Browser/Feature/Karyawan/LanggananPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/Karyawan/LanggananCustomerViewTest.php` | Various | Browser view test |
