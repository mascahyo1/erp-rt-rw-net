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

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/RiwayatPembayaranViewTest.php` | Various | Browser view test |
