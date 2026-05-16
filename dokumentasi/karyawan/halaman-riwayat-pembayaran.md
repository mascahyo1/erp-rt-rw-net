# Halaman Riwayat Pembayaran
> Portal: Karyawan | URL: `/karyawan/riwayat-pembayaran`

## Fungsi
Halaman untuk **melihat dan mencatat riwayat pembayaran** dari pelanggan yang ditugaskan kepada karyawan.
Karyawan mencatat setiap pembayaran tagihan yang diterima dari pelanggan di lapangan.

## Fitur
- **Tabel daftar riwayat** — menampilkan semua riwayat pembayaran dengan pelanggan, jumlah, tanggal, dan status
- **Pencarian** — mencari riwayat berdasarkan nama pelanggan (tekan Enter untuk mencari)
- **Filter status** — menyaring riwayat berdasarkan status: Menunggu, Disetujui, Ditolak, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `karyawan-riwayat-pembayaran.list` | Melihat tabel riwayat dan sidebar menu |
| **Tambah Pembayaran** | `karyawan-riwayat-pembayaran.create` | Mencatat pembayaran baru dari pelanggan |
| **Edit Pembayaran** | `karyawan-riwayat-pembayaran.edit` | Mengubah data pembayaran |
| **Hapus Pembayaran** | `karyawan-riwayat-pembayaran.delete` | Menghapus pembayaran (dapat dipulihkan lagi) |
| **Pulihkan Pembayaran** | `karyawan-riwayat-pembayaran.restore` | Mengembalikan pembayaran yang sudah dihapus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/riwayat-pembayaran` | — | `auth:employee` | `karyawan-riwayat-pembayaran.list` |
| POST | `/karyawan/riwayat-pembayaran` | — | `auth:employee` | `karyawan-riwayat-pembayaran.create` |
| PUT | `/karyawan/riwayat-pembayaran/{id}` | — | `auth:employee` | `karyawan-riwayat-pembayaran.edit` |
| DELETE | `/karyawan/riwayat-pembayaran/{id}` | — | `auth:employee` | `karyawan-riwayat-pembayaran.delete` |
| PATCH | `/karyawan/riwayat-pembayaran/{id}/restore` | — | `auth:employee` | `karyawan-riwayat-pembayaran.restore` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Karyawan/RiwayatPembayaran.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/RiwayatPembayaranPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/Karyawan/RiwayatPembayaranViewTest.php` | Various | Browser view test |
