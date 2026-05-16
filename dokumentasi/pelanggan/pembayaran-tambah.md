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

### Test Case
| File | Method | Description |
|------|--------|-------------|
| — | — | No dedicated test file found |
