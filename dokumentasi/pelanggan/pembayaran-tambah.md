# Pembayaran Tambah
> Portal: Pelanggan | URL: `/customer/riwayat-pembayaran/tambah`

## Fungsi
Halaman untuk **melakukan pembayaran tagihan** — mengunggah bukti pembayaran untuk tagihan yang belum lunas.
Pelanggan mengisi form pembayaran dengan jumlah, metode pembayaran, dan bukti transfer.

## Fitur
- **Form pembayaran** — mengisi jumlah, memilih tagihan, metode pembayaran, dan mengunggah bukti transfer

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Form Pembayaran** | — | Semua pelanggan yang login dapat membuka form pembayaran |
| **Kirim Pembayaran** | — | Mengirim bukti pembayaran untuk diverifikasi |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/customer/riwayat-pembayaran/tambah` | — | `auth:customer` | — |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Customer/PembayaranTambah.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\CustInternetPayment` | `cust_internet_payments` | Model utama — pencatatan pembayaran baru |
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Join — invoice yang dibayar (via `cust_internet_invc_id`) |
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
| — | — | No dedicated test file found |
