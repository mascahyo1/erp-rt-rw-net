# Halaman Langganan
> Portal: Karyawan | URL: `/karyawan/langganan-customer`

## Fungsi
Halaman untuk **melihat data langganan pelanggan** yang ditugaskan kepada karyawan.
Karyawan dapat melihat paket internet yang dipakai oleh pelanggan untuk keperluan penagihan.

## Fitur
- **Tabel daftar langganan** — menampilkan langganan pelanggan dengan paket, harga, dan status
- **Pencarian** — mencari langganan berdasarkan nama pelanggan atau paket (tekan Enter untuk mencari)
- **Filter status** — menyaring langganan berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `karyawan-langganan.list` | Melihat tabel langganan dan sidebar menu |
| **Tambah Langganan** | `karyawan-langganan.create` | Mendaftarkan pelanggan ke paket baru |
| **Edit Langganan** | `karyawan-langganan.edit` | Mengubah data langganan |
| **Hapus Langganan** | `karyawan-langganan.delete` | Menghapus langganan (dapat dipulihkan lagi) |
| **Pulihkan Langganan** | `karyawan-langganan.restore` | Mengembalikan langganan yang sudah dihapus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/langganan-customer` | — | `auth:employee` | `karyawan-langganan.list` |
| POST | `/karyawan/langganan-customer` | — | `auth:employee` | `karyawan-langganan.create` |
| PUT | `/karyawan/langganan-customer/{id}` | — | `auth:employee` | `karyawan-langganan.edit` |
| DELETE | `/karyawan/langganan-customer/{id}` | — | `auth:employee` | `karyawan-langganan.delete` |
| PATCH | `/karyawan/langganan-customer/{id}/restore` | — | `auth:employee` | `karyawan-langganan.restore` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Karyawan/LanggananCustomer.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/LanggananPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/Karyawan/LanggananCustomerViewTest.php` | Various | Browser view test |
