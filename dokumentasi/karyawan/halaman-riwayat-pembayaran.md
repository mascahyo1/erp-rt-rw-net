# Halaman Riwayat Pembayaran
> Portal: Karyawan | URL: `/karyawan/riwayat-pembayaran`

## Fungsi
Halaman untuk **melihat dan mencatat riwayat pembayaran** dari pelanggan yang ditugaskan kepada karyawan.
Karyawan mencatat setiap pembayaran tagihan yang diterima dari pelanggan di lapangan.

## Fitur
- **Tabel daftar riwayat** — menampilkan semua riwayat pembayaran dengan pelanggan, jumlah, tanggal, dan status
- **Pencarian** — mencari riwayat berdasarkan nama pelanggan (tekan Enter untuk mencari)
- **Filter status** — menyaring riwayat berdasarkan status: Menunggu, Disetujui, Ditolak, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `karyawan-riwayat-pembayaran.list` | Melihat tabel riwayat dan sidebar menu |
| **Tambah Pembayaran** | `karyawan-riwayat-pembayaran.create` | Mencatat pembayaran baru dari pelanggan |
| **Edit Pembayaran** | `karyawan-riwayat-pembayaran.edit` | Mengubah data pembayaran |
| **Hapus Pembayaran** | `karyawan-riwayat-pembayaran.delete` | Menghapus pembayaran (dapat dipulihkan lagi) |
| **Pulihkan Pembayaran** | `karyawan-riwayat-pembayaran.restore` | Mengembalikan pembayaran yang sudah dihapus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/riwayat-pembayaran` | — | `auth:employee` | `karyawan-riwayat-pembayaran.list` |
| POST | `/karyawan/riwayat-pembayaran` | — | `auth:employee` | `karyawan-riwayat-pembayaran.create` |
| PUT | `/karyawan/riwayat-pembayaran/{id}` | — | `auth:employee` | `karyawan-riwayat-pembayaran.edit` |
| DELETE | `/karyawan/riwayat-pembayaran/{id}` | — | `auth:employee` | `karyawan-riwayat-pembayaran.delete` |
| PATCH | `/karyawan/riwayat-pembayaran/{id}/restore` | — | `auth:employee` | `karyawan-riwayat-pembayaran.restore` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Karyawan/RiwayatPembayaran.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\CustInternetPayment` | `cust_internet_payments` | Model utama — riwayat pembayaran |
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Join — invoice (via `cust_internet_invc_id`) |
| `App\Models\CustInternet` | `cust_internets` | Join — langganan (via `cust_internet_invcs.cust_internet_id`) |
| `App\Models\Customer` | `customers` | Join — pelanggan (via `cust_internets.customer_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_143638_create_cust_internet_payments_table` | `cust_internet_payments` |
| `2026_05_11_143143_create_cust_internet_invcs_table` | `cust_internet_invcs` |
| `2026_05_11_142443_create_cust_internets_table` | `cust_internets` |
| `2026_05_11_142201_create_customers_table` | `customers` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/RiwayatPembayaranPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/Karyawan/RiwayatPembayaranViewTest.php` | Various | Browser view test |
