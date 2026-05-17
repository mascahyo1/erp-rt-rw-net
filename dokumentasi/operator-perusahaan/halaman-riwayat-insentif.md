# Halaman Riwayat Insentif
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/riwayat-insentif`

## Fungsi
Halaman untuk mengelola **riwayat pencairan/pembayaran insentif** kepada karyawan.
Mencatat setiap kali insentif dibayarkan kepada karyawan beserta jumlah dan status persetujuannya.

## Fitur
- **Tabel daftar riwayat** — menampilkan semua riwayat insentif dengan karyawan, jumlah, tanggal, dan status persetujuan
- **Pencarian** — mencari riwayat berdasarkan nama karyawan (tekan Enter untuk mencari)
- **Filter status** — menyaring riwayat berdasarkan status: Menunggu, Disetujui, Ditolak, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `riwayat-insentif.list` | Melihat tabel riwayat dan sidebar menu |
| **Tambah Riwayat** | `riwayat-insentif.create` | Mencatat pencairan insentif baru |
| **Edit Riwayat** | `riwayat-insentif.edit` | Mengubah data riwayat insentif |
| **Hapus Riwayat** | `riwayat-insentif.delete` | Menghapus riwayat (dapat dipulihkan lagi) |
| **Pulihkan Riwayat** | `riwayat-insentif.restore` | Mengembalikan riwayat yang sudah dihapus |
| **Setujui/Tolak** | `riwayat-insentif.persetujuan` | Menyetujui atau menolak pencairan insentif |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/riwayat-insentif` | — | `auth:admin-company` | `riwayat-insentif.list` |
| POST | `/operator-perusahaan/riwayat-insentif` | — | `auth:admin-company` | `riwayat-insentif.create` |
| PUT | `/operator-perusahaan/riwayat-insentif/{id}` | — | `auth:admin-company` | `riwayat-insentif.edit` |
| DELETE | `/operator-perusahaan/riwayat-insentif/{id}` | — | `auth:admin-company` | `riwayat-insentif.delete` |
| PATCH | `/operator-perusahaan/riwayat-insentif/{id}/restore` | — | `auth:admin-company` | `riwayat-insentif.restore` |
| POST | `/operator-perusahaan/riwayat-insentif/{id}/approve` | — | `auth:admin-company` | `riwayat-insentif.persetujuan` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/RiwayatInsentif.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\EmpIncentiveLog` | `emp_incentive_logs` | Model utama — riwayat pencairan insentif |
| `App\Models\EmpIncentive` | `emp_incentives` | Join — data insentif (via `emp_incentive_id`) |
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Join — invoice terkait (via `cust_internet_invcs_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_144240_create_emp_incentive_logs_table` | `emp_incentive_logs` |
| `2026_05_11_144033_create_emp_incentives_table` | `emp_incentives` |
| `2026_05_11_143143_create_cust_internet_invcs_table` | `cust_internet_invcs` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/RiwayatInsentifPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/OperatorPerusahaan/RiwayatInsentifViewTest.php` | Various | Browser view test |
