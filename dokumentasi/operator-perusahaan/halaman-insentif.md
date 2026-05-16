# Halaman Insentif
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/insentif`

## Fungsi
Halaman untuk mengelola **data insentif karyawan** — komisi yang diterima karyawan dari pelanggan yang berhasil ditagih.
Insentif dihitung berdasarkan pembayaran yang berhasil dikumpulkan oleh karyawan.

## Fitur
- **Tabel daftar insentif** — menampilkan semua insentif dengan karyawan, pelanggan, jumlah, dan status
- **Pencarian** — mencari insentif berdasarkan nama karyawan atau pelanggan (tekan Enter untuk mencari)
- **Filter status** — menyaring insentif berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `insentif.list` | Melihat tabel insentif dan sidebar menu |
| **Tambah Insentif** | `insentif.create` | Membuat data insentif baru |
| **Edit Insentif** | `insentif.edit` | Mengubah data insentif yang sudah ada |
| **Hapus Insentif** | `insentif.delete` | Menghapus insentif (dapat dipulihkan lagi) |
| **Pulihkan Insentif** | `insentif.restore` | Mengembalikan insentif yang sudah dihapus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/insentif` | — | `auth:admin-company` | `insentif.list` |
| POST | `/operator-perusahaan/insentif` | — | `auth:admin-company` | `insentif.create` |
| PUT | `/operator-perusahaan/insentif/{id}` | — | `auth:admin-company` | `insentif.edit` |
| DELETE | `/operator-perusahaan/insentif/{id}` | — | `auth:admin-company` | `insentif.delete` |
| PATCH | `/operator-perusahaan/insentif/{id}/restore` | — | `auth:admin-company` | `insentif.restore` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/Insentif.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/InsentifPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/OperatorPerusahaan/InsentifViewTest.php` | Various | Browser view test |
