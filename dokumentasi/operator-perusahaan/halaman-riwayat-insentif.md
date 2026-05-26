# Halaman Riwayat Insentif
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/riwayat-insentif`

## Fungsi
Halaman untuk mengelola **riwayat pencairan/pembayaran insentif** kepada karyawan.
Mencatat setiap kali insentif dibayarkan kepada karyawan beserta jumlah dan status persetujuannya.

## Fitur
- **Tabel daftar riwayat** — menampilkan semua riwayat insentif denganKolom: Insentif, No. Invoice, Pelanggan, Pengaju, Jumlah, Tanggal, Status, Tgl Dibuat
- **Pencarian** — mencari riwayat berdasarkan nama insentif atau Invoice (tekan Enter atau klik tombol Cari)
- **Filter** (tombol Filter klik untuk apply, bukan onChange):
  - Status: Semua, Pending, Disetujui, Ditolak
  - Kode Invoice: input text filter
  - Tanggal Jatuh Tempo: dari tgl & sampai tgl
  - Terhapus: Tidak / Ya
- **Urutkan** — klik judul kolom untuk mengurutkan data
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100
- **Select all / checkbox per row** — klik row untuk toggle checkbox, header checkbox untuk select all
- **CRUD** — Create, Read, Update (hanya status pending), Delete (soft delete), Restore
- **Review** — Approve/Reject per item atau bulk (hanya status pending)
- **Import/Export Excel** — download template, import dari Excel, export berdasarkan filter aktif

## Field Input Create/Edit
| Field | Type | Wajib | Keterangan |
|-------|------|-------|------------|
| Kode Invoice | text | Ya | Nomor invoice |
| Insentif | select ajax | Ya | Pilih dari daftar emp_incentives |
| Invoice | select ajax | Ya | Pilih dari daftar cust_internet_invcs |
| Jumlah | number | Ya | Nominal insentif |
| Tanggal | date | Ya | Tanggal pengajuan |
| Diajukan Untuk | select ajax | Tidak | Pilih karyawan (employees) |
| Alasan Pengajuan | textarea | Tidak | Alasan为什么要申请 |
| Bukti Pengajuan | file | Tidak | Upload file (jpg, jpeg, png, pdf) |

## Field Detail
| Field | Keterangan |
|-------|------------|
| Insentif | Nama insentif yang diajukan |
| No. Invoice | Nomor invoice |
| Pelanggan | Nama pelanggan + no. telepon |
| Jumlah | Nominal (format Rp) |
| Tanggal | Tanggal pengajuan |
| Pengaju | Nama yang mengajukan |
| Status | Badge: Pending/Disetujui/Ditolak |
| Alasan | Alasan pengajuan (jika ada) |
| Bukti Pengajuan | Link ke file attachment |
| Tgl Dibuat | Timestamp pembuatan |
| Diubah | Timestamp update |
| Tgl Direview | Timestamp review (jika sudah direview) |
| Alasan Review | Alasan persetujuan/penolakan |

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `riwayat-insentif.list` | Melihat tabel riwayat dan sidebar menu |
| **Tambah Riwayat** | `riwayat-insentif.create` | Membuka modal tambah \\
| **Lihat Detail** | `riwayat-insentif.detail` | Membuka modal detail |
| **Edit Riwayat** | `riwayat-insentif.edit` | Membuka modal edit (hanya jika status pending) |
| **Hapus Riwayat** | `riwayat-insentif.delete` | Soft delete riwayat |
| **Pulihkan Riwayat** | `riwayat-insentif.restore` | Restore riwayat yang dihapus |
| **Review/Setujui/Tolak** | `riwayat-insentif.persetujuan` | Review persetujuan atau penolakan |
| **Import** | `riwayat-insentif.import` | Import dari Excel |
| **Export** | `riwayat-insentif.export` | Export ke Excel |
| **Download Template** | `riwayat-insentif.import` | Download template Excel |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/riwayat-insentif` | — | `auth:admin-company` | `riwayat-insentif.list` |
| POST | `/operator-perusahaan/riwayat-insentif` | — | `auth:admin-company` | `riwayat-insentif.create` |
| PUT | `/operator-perusahaan/riwayat-insentif/{id}` | — | `auth:admin-company` | `riwayat-insentif.edit` |
| DELETE | `/operator-perusahaan/riwayat-insentif/{id}` | — | `auth:admin-company` | `riwayat-insentif.delete` |
| PATCH | `/operator-perusahaan/riwayat-insentif/{id}/restore` | — | `auth:admin-company` | `riwayat-insentif.restore` |
| POST | `/operator-perusahaan/riwayat-insentif/{id}/review` | — | `auth:admin-company` | `riwayat-insentif.persetujuan` |
| POST | `/operator-perusahaan/riwayat-insentif/bulk-review` | — | `auth:admin-company` | `riwayat-insentif.persetujuan` |
| POST | `/operator-perusahaan/riwayat-insentif/import` | — | `auth:admin-company` | `riwayat-insentif.import` |
| GET | `/operator-perusahaan/riwayat-insentif/export` | — | `auth:admin-company` | `riwayat-insentif.export` |
| GET | `/operator-perusahaan/riwayat-insentif/template` | — | `auth:admin-company` | `riwayat-insentif.import` |

### Controller
`App\Http\Controllers\OperatorPerusahaan\RiwayatInsentifController.php`

### View
`resources/js/Pages/OperatorPerusahaan/RiwayatInsentif.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\EmpIncentiveLog` | `emp_incentive_logs` | Log/p_history insentif |
| `App\Models\EmpIncentive` | `emp_incentives` | Data insentif (join via emp_incentive_id) |
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Invoice (join via cust_internet_invcs_id) |
| `App\Models\Employee` | `employees` | Data karyawan (join via submitted_by_id) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_144240_create_emp_incentive_logs_table` | `emp_incentive_logs` |
| `2026_05_11_144033_create_emp_incentives_table` | `emp_incentives` |
| `2026_05_26_100001_add_code_to_emp_incentives_table` | `emp_incentives` (add code) |
| `2026_05_26_100002_add_code_to_employees_table` | `employees` (add code) |

### Kolom Export Excel
| Kolom | Keterangan |
|-------|------------|
| Kode Insentif | Kode insentif |
| No Invoice | Nomor invoice |
| Nama Insentif | Nama insentif |
| Date | Tanggal pengajuan |
| Amount | Nominal |
| Status | Status review |
| Kode Karyawan | Kode employee (submitted_by) |
| Alasan Pengajuan | Reason |
| Alasan Review | Review reason |

### Test Case - Playwright
| File | Description |
|------|-------------|
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/RiwayatInsentifFullTest.cjs` | Full CRUD, light/dark mode, responsive, checkbox, modals |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/RiwayatInsentifPermissionTest.cjs` | Granular permission RBAC for each permission |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/RiwayatInsentifDarkModeTest.cjs` | Dark mode detailed checks on all modals |
| `tests/Browser/deprecatedoldFeature/OperatorPerusahaan/RiwayatInsentifPermissionTest.php` | Laravel Dusk permission test (deprecated) |
| `tests/Browser/deprecatedoldFeature/OperatorPerusahaan/RiwayatInsentifViewTest.php` | Laravel Dusk view test (deprecated) |

### Menjalankan Test
```bash
cd tests/Browser/Playwright/Feature/OperatorPerusahaan
node RiwayatInsentifFullTest.cjs
node RiwayatInsentifPermissionTest.cjs
node RiwayatInsentifDarkModeTest.cjs
```
