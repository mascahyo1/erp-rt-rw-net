# Halaman Langganan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/langganan-customer`

## Fungsi
Halaman untuk mengelola **data langganan pelanggan** — paket internet apa yang dipakai pelanggan, harganya, dan status aktifnya.
Langganan adalah hubungan antara pelanggan dengan paket layanan internet yang mereka pilih.

## Fitur
- **Tabel daftar langganan** — menampilkan semua langganan dengan pelanggan, paket, harga, dan status
- **Pencarian** — mencari langganan berdasarkan nama pelanggan atau paket (tekan Enter untuk mencari)
- **Filter status** — menyaring langganan berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `langganan.list` | Melihat tabel langganan dan sidebar menu |
| **Tambah Langganan** | `langganan.create` | Mendaftarkan pelanggan ke paket baru |
| **Edit Langganan** | `langganan.edit` | Mengubah data langganan yang sudah ada |
| **Hapus Langganan** | `langganan.delete` | Menghapus langganan (dapat dipulihkan lagi) |
| **Pulihkan Langganan** | `langganan.restore` | Mengembalikan langganan yang sudah dihapus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/langganan-customer` | — | `auth:admin-company` | `langganan.list` |
| POST | `/operator-perusahaan/langganan-customer` | — | `auth:admin-company` | `langganan.create` |
| PUT | `/operator-perusahaan/langganan-customer/{id}` | — | `auth:admin-company` | `langganan.edit` |
| DELETE | `/operator-perusahaan/langganan-customer/{id}` | — | `auth:admin-company` | `langganan.delete` |
| PATCH | `/operator-perusahaan/langganan-customer/{id}/restore` | — | `auth:admin-company` | `langganan.restore` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/LanggananCustomer.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/LanggananPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/OperatorPerusahaan/LanggananCustomerViewTest.php` | Various | Browser view test |
