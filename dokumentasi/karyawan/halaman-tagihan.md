# Halaman Tagihan
> Portal: Karyawan | URL: `/karyawan/tagihan`

## Fungsi
Halaman untuk **melihat dan mengelola tagihan bulanan** pelanggan yang ditugaskan kepada karyawan.
Karyawan dapat melihat tagihan yang harus ditagih kepada pelanggan dan mencatat status pembayarannya.

## Fitur
- **Tabel daftar tagihan** — menampilkan tagihan pelanggan dengan periode, jumlah, dan status
- **Pencarian** — mencari tagihan berdasarkan nama pelanggan atau periode (tekan Enter untuk mencari)
- **Filter status** — menyaring tagihan berdasarkan status: Belum Dibayar, Lunas, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `karyawan-tagihan.list` | Melihat tabel tagihan dan sidebar menu |
| **Tambah Tagihan** | `karyawan-tagihan.create` | Membuat tagihan baru secara manual |
| **Edit Tagihan** | `karyawan-tagihan.edit` | Mengubah jumlah atau status tagihan |
| **Hapus Tagihan** | `karyawan-tagihan.delete` | Menghapus tagihan (dapat dipulihkan lagi) |
| **Pulihkan Tagihan** | `karyawan-tagihan.restore` | Mengembalikan tagihan yang sudah dihapus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/tagihan` | — | `auth:employee` | `karyawan-tagihan.list` |
| POST | `/karyawan/tagihan` | — | `auth:employee` | `karyawan-tagihan.create` |
| PUT | `/karyawan/tagihan/{id}` | — | `auth:employee` | `karyawan-tagihan.edit` |
| DELETE | `/karyawan/tagihan/{id}` | — | `auth:employee` | `karyawan-tagihan.delete` |
| PATCH | `/karyawan/tagihan/{id}/restore` | — | `auth:employee` | `karyawan-tagihan.restore` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Karyawan/Tagihan.vue`

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
| `tests/Browser/Feature/Karyawan/TagihanPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/Karyawan/TagihanViewTest.php` | Various | Browser view test |
