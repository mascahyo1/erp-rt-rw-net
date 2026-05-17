# Halaman Riwayat Pembayaran
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/riwayat-pembayaran`

## Fungsi
Halaman untuk mengelola **riwayat pembayaran tagihan** dari pelanggan.
Mencatat setiap pembayaran yang dilakukan pelanggan beserta jumlah, metode, dan status verifikasinya.

## Fitur
- **Tabel daftar riwayat** — menampilkan semua riwayat pembayaran dengan pelanggan, jumlah, tanggal, dan status
- **Pencarian** — mencari riwayat berdasarkan nama pelanggan (tekan Enter untuk mencari)
- **Filter status** — menyaring riwayat berdasarkan status: Menunggu, Disetujui, Ditolak, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `riwayat-pembayaran.list` | Melihat tabel riwayat dan sidebar menu |
| **Tambah Riwayat** | `riwayat-pembayaran.create` | Mencatat pembayaran baru |
| **Edit Riwayat** | `riwayat-pembayaran.edit` | Mengubah data riwayat pembayaran |
| **Hapus Riwayat** | `riwayat-pembayaran.delete` | Menghapus riwayat (dapat dipulihkan lagi) |
| **Pulihkan Riwayat** | `riwayat-pembayaran.restore` | Mengembalikan riwayat yang sudah dihapus |
| **Verifikasi Pembayaran** | `riwayat-pembayaran.persetujuan` | Menyetujui atau menolak pembayaran pelanggan |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/riwayat-pembayaran` | — | `auth:admin-company` | `riwayat-pembayaran.list` |
| POST | `/operator-perusahaan/riwayat-pembayaran` | — | `auth:admin-company` | `riwayat-pembayaran.create` |
| PUT | `/operator-perusahaan/riwayat-pembayaran/{id}` | — | `auth:admin-company` | `riwayat-pembayaran.edit` |
| DELETE | `/operator-perusahaan/riwayat-pembayaran/{id}` | — | `auth:admin-company` | `riwayat-pembayaran.delete` |
| PATCH | `/operator-perusahaan/riwayat-pembayaran/{id}/restore` | — | `auth:admin-company` | `riwayat-pembayaran.restore` |
| POST | `/operator-perusahaan/riwayat-pembayaran/{id}/approve` | — | `auth:admin-company` | `riwayat-pembayaran.persetujuan` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/RiwayatPembayaran.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\CustInternetPayment` | `cust_internet_payments` | Model utama — riwayat pembayaran tagihan |
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Join — invoice yang dibayar (via `cust_internet_invc_id`) |
| `App\Models\CustInternet` | `cust_internets` | Join — data langganan (via `cust_internet_invcs.cust_internet_id`) |
| `App\Models\Customer` | `customers` | Join — nama pelanggan (via `cust_internets.customer_id`) |

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
| `tests/Browser/Feature/OperatorPerusahaan/RiwayatPembayaranPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/OperatorPerusahaan/RiwayatPembayaranViewTest.php` | Various | Browser view test |
