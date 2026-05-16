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

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/PembayaranDetailViewTest.php` | Various | Browser view test |
