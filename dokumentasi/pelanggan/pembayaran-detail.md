# Pembayaran Detail
> Portal: Pelanggan | URL: `/customer/riwayat-pembayaran/detail`

## Fungsi
Halaman untuk **melihat rincian pembayaran** yang sudah dilakukan.
Menampilkan informasi lengkap pembayaran — jumlah, tagihan yang dibayar, metode, tanggal, bukti transfer, dan status verifikasi.

## Fitur
- **Tampilan detail pembayaran** — menampilkan informasi lengkap pembayaran termasuk bukti transfer

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Detail Pembayaran** | — | Semua pelanggan yang login dapat melihat detail pembayaran sendiri |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/customer/riwayat-pembayaran/detail` | — | `auth:customer` | — |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Customer/PembayaranDetail.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\CustInternetPayment` | `cust_internet_payments` | Model utama — detail pembayaran |
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Join — invoice (via `cust_internet_invc_id`) |
| `App\Models\CustInternet` | `cust_internets` | Join — langganan (via `cust_internet_invcs.cust_internet_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_143638_create_cust_internet_payments_table` | `cust_internet_payments` |
| `2026_05_11_143143_create_cust_internet_invcs_table` | `cust_internet_invcs` |
| `2026_05_11_142443_create_cust_internets_table` | `cust_internets` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/PembayaranDetailViewTest.php` | Various | Browser view test |
