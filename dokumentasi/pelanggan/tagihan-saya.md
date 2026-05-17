# Tagihan Saya
> Portal: Pelanggan | URL: `/customer/tagihan-saya`

## Fungsi
Halaman untuk **melihat daftar tagihan bulanan** pelanggan.
Pelanggan dapat melihat tagihan yang harus dibayar, termasuk periode, jumlah, dan status pembayaran (Lunas atau Belum Dibayar).
Dari halaman ini pelanggan bisa membuka detail tagihan untuk melihat rincian lebih lanjut.

## Fitur
- **Tabel daftar tagihan** — menampilkan semua tagihan dengan periode, jumlah, dan status
- **Pencarian** — mencari tagihan berdasarkan periode (tekan Enter untuk mencari)
- **Filter status** — menyaring tagihan berdasarkan status: Belum Dibayar atau Lunas
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Tagihan** | — | Semua pelanggan yang login dapat melihat tagihan sendiri |
| **Lihat Detail** | — | Membuka detail tagihan |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/customer/tagihan-saya` | — | `auth:customer` | — |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Customer/TagihanSaya.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Model utama — daftar tagihan pelanggan |
| `App\Models\CustInternet` | `cust_internets` | Join — data langganan (via `cust_internet_id`) |
| `App\Models\InternetPackage` | `internet_packages` | Join — paket (via `cust_internets.internet_package_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_143143_create_cust_internet_invcs_table` | `cust_internet_invcs` |
| `2026_05_11_142443_create_cust_internets_table` | `cust_internets` |
| `2026_05_11_141134_create_internet_packages_table` | `internet_packages` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/TagihanSayaViewTest.php` | Various | Browser view test |
