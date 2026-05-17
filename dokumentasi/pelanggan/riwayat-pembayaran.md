# Riwayat Pembayaran
> Portal: Pelanggan | URL: `/customer/riwayat-pembayaran`

## Fungsi
Halaman untuk **melihat riwayat pembayaran tagihan** yang sudah dilakukan pelanggan.
Menampilkan semua pembayaran yang pernah dilakukan, termasuk jumlah, tanggal, dan status verifikasi.
Dari halaman ini pelanggan bisa menambah pembayaran baru untuk melunasi tagihan.

## Fitur
- **Tabel daftar pembayaran** — menampilkan semua riwayat pembayaran dengan jumlah, tanggal, dan status
- **Pencarian** — mencari pembayaran berdasarkan tanggal (tekan Enter untuk mencari)
- **Filter status** — menyaring pembayaran berdasarkan status: Menunggu, Disetujui, atau Ditolak
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Riwayat** | — | Semua pelanggan yang login dapat melihat riwayat pembayaran sendiri |
| **Tambah Pembayaran** | — | Membuka form pembayaran baru |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/customer/riwayat-pembayaran` | — | `auth:customer` | — |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Customer/RiwayatPembayaran.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\CustInternetPayment` | `cust_internet_payments` | Model utama — riwayat pembayaran |
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Join — invoice (via `cust_internet_invc_id`) |
| `App\Models\CustInternet` | `cust_internets` | Join — langganan (via `cust_internet_invcs.cust_internet_id`) |
| `App\Models\InternetPackage` | `internet_packages` | Join — paket (via `cust_internets.internet_package_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_143638_create_cust_internet_payments_table` | `cust_internet_payments` |
| `2026_05_11_143143_create_cust_internet_invcs_table` | `cust_internet_invcs` |
| `2026_05_11_142443_create_cust_internets_table` | `cust_internets` |
| `2026_05_11_141134_create_internet_packages_table` | `internet_packages` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/RiwayatPembayaranViewTest.php` | Various | Browser view test |
