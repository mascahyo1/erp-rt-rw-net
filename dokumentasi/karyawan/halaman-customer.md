# Halaman Customer
> Portal: Karyawan | URL: `/karyawan/customer`

## Fungsi
Halaman untuk **melihat data pelanggan** yang ditugaskan kepada karyawan.
Karyawan dapat melihat dan mencari pelanggan yang menjadi tanggung jawab penagihannya.
Karyawan hanya bisa melihat data, tidak bisa menambah/mengedit/menghapus pelanggan.

## Fitur
- **Tabel daftar pelanggan** — menampilkan pelanggan dengan nama, email, alamat, telepon, dan status
- **Pencarian** — mencari pelanggan berdasarkan nama, email, atau alamat (tekan Enter untuk mencari)
- **Filter status** — menyaring pelanggan berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `karyawan-customer.list` | Melihat tabel pelanggan dan sidebar menu |
| **Tambah Pelanggan** | `karyawan-customer.create` | Membuat pelanggan baru |
| **Edit Pelanggan** | `karyawan-customer.edit` | Mengubah data pelanggan |
| **Hapus Pelanggan** | `karyawan-customer.delete` | Menghapus pelanggan (dapat dipulihkan lagi) |
| **Pulihkan Pelanggan** | `karyawan-customer.restore` | Mengembalikan pelanggan yang sudah dihapus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/customer` | — | `auth:employee` | `karyawan-customer.list` |
| POST | `/karyawan/customer` | — | `auth:employee` | `karyawan-customer.create` |
| PUT | `/karyawan/customer/{id}` | — | `auth:employee` | `karyawan-customer.edit` |
| DELETE | `/karyawan/customer/{id}` | — | `auth:employee` | `karyawan-customer.delete` |
| PATCH | `/karyawan/customer/{id}/restore` | — | `auth:employee` | `karyawan-customer.restore` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Karyawan/Customer.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/CustomerPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/Karyawan/CustomerViewTest.php` | Various | Browser view test |
