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

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Model utama — data tagihan bulanan |
| `App\Models\CustInternet` | `cust_internets` | Join — data langganan (via `cust_internet_id`) |
| `App\Models\Customer` | `customers` | Join — nama pelanggan (via `cust_internets.customer_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_143143_create_cust_internet_invcs_table` | `cust_internet_invcs` |
| `2026_05_11_142443_create_cust_internets_table` | `cust_internets` |
| `2026_05_11_142201_create_customers_table` | `customers` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/TagihanPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/OperatorPerusahaan/TagihanViewTest.php` | Various | Browser view test |
