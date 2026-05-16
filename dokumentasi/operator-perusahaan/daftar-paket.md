# Daftar Paket
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/daftar-paket`

## Fungsi
Halaman untuk **melihat daftar paket layanan internet** yang tersedia untuk dipilih pelanggan.
Setiap paket memiliki nama, kecepatan, harga, dan fitur yang bisa dilihat oleh admin perusahaan.

## Fitur
- **Tabel daftar paket** — menampilkan semua paket dengan nama, kecepatan, dan harga
- **Pencarian** — mencari paket berdasarkan nama (tekan Enter untuk mencari)
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `paket.list` | Melihat daftar paket dan sidebar menu |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/daftar-paket` | — | `auth:admin-company` | `paket.list` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/DaftarPaket.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/DaftarPaketViewTest.php` | Various | Browser view test |
