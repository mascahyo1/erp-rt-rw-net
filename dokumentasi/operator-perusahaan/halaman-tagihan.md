# Halaman Tagihan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/tagihan`

## Fungsi
Halaman untuk mengelola **data tagihan bulanan** pelanggan.
Tagihan dibuat otomatis setiap bulan untuk setiap pelanggan yang memiliki langganan aktif.

## Fitur
- **Tabel daftar tagihan** — menampilkan semua tagihan dengan pelanggan, periode, jumlah, dan status
- **Pencarian** — mencari tagihan berdasarkan nama pelanggan atau periode (tekan Enter untuk mencari)
- **Filter status** — menyaring tagihan berdasarkan status: Belum Dibayar, Lunas, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `tagihan.list` | Melihat tabel tagihan dan sidebar menu |
| **Tambah Tagihan** | `tagihan.create` | Membuat tagihan baru secara manual |
| **Edit Tagihan** | `tagihan.edit` | Mengubah jumlah atau status tagihan |
| **Hapus Tagihan** | `tagihan.delete` | Menghapus tagihan (dapat dipulihkan lagi) |
| **Pulihkan Tagihan** | `tagihan.restore` | Mengembalikan tagihan yang sudah dihapus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/tagihan` | — | `auth:admin-company` | `tagihan.list` |
| POST | `/operator-perusahaan/tagihan` | — | `auth:admin-company` | `tagihan.create` |
| PUT | `/operator-perusahaan/tagihan/{id}` | — | `auth:admin-company` | `tagihan.edit` |
| DELETE | `/operator-perusahaan/tagihan/{id}` | — | `auth:admin-company` | `tagihan.delete` |
| PATCH | `/operator-perusahaan/tagihan/{id}/restore` | — | `auth:admin-company` | `tagihan.restore` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/Tagihan.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/TagihanPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/OperatorPerusahaan/TagihanViewTest.php` | Various | Browser view test |
