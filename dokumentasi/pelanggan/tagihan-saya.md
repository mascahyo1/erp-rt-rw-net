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

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/TagihanSayaViewTest.php` | Various | Browser view test |
