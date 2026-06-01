# Halaman Karyawan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/karyawan`

## Fungsi
Halaman untuk mengelola **data karyawan** perusahaan.
Karyawan adalah petugas yang bertugas menagih pelanggan dan mencatat pembayaran di lapangan.

## Fitur
- **Tabel daftar karyawan** — menampilkan semua karyawan dengan **Kode**, nama, email, nomor HP, dan status
- **Pencarian** — mencari karyawan berdasarkan nama, email, atau nomor HP (tekan Enter untuk mencari)
- **Filter status** — menyaring karyawan berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom (termasuk **Kode**) untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100
- **Import Excel** — unggah data karyawan massal dari file `.xlsx` / `.csv` (termasuk Kode)
- **Export Excel** — unduh data karyawan (semua atau hanya yang dipilih) ke file `.xlsx` (termasuk Kode)
- **Template Excel** — unduh template kosong + 1 baris contoh untuk format import

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `karyawan.list` | Melihat tabel karyawan dan sidebar menu |
| **Tambah Karyawan** | `karyawan.create` | Membuat akun karyawan baru dengan mengisi nama, email, password, nomor HP, dan status |
| **Edit Karyawan** | `karyawan.edit` | Mengubah data karyawan yang sudah ada |
| **Hapus Karyawan** | `karyawan.delete` | Menghapus karyawan (dapat dipulihkan lagi) |
| **Pulihkan Karyawan** | `karyawan.restore` | Mengembalikan karyawan yang sudah dihapus |
| **Bulk Aktifkan** | `karyawan.edit` | Mengaktifkan banyak karyawan sekaligus |
| **Bulk Nonaktifkan** | `karyawan.edit` | Menonaktifkan banyak karyawan sekaligus |
| **Bulk Hapus** | `karyawan.delete` | Menghapus banyak karyawan sekaligus |
| **Bulk Pulihkan** | `karyawan.restore` | Memulihkan banyak karyawan sekaligus (saat filter Terhapus=Ya) |
| **Import Excel** | `karyawan.import` | Unggah data karyawan massal; download template & submit file `.xlsx`/`.csv` |
| **Download Template** | `karyawan.import` | Mengunduh template kosong + baris contoh untuk import |
| **Export Semua** | `karyawan.export` | Mengunduh seluruh data karyawan ke file `.xlsx` |
| **Export Selected** | `karyawan.export` | Mengunduh data karyawan yang dipilih (checkbox) ke file `.xlsx` |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/karyawan` | `operator-perusahaan.karyawan.index` | `auth:admin-company` | `karyawan.list` |
| POST | `/operator-perusahaan/karyawan` | `operator-perusahaan.karyawan.store` | `auth:admin-company` | `karyawan.create` |
| PUT | `/operator-perusahaan/karyawan/{employee}` | `operator-perusahaan.karyawan.update` | `auth:admin-company` | `karyawan.edit` |
| POST | `/operator-perusahaan/karyawan/bulk-status` | `operator-perusahaan.karyawan.bulkStatus` | `auth:admin-company` | `karyawan.edit` |
| DELETE | `/operator-perusahaan/karyawan/{employee}` | `operator-perusahaan.karyawan.destroy` | `auth:admin-company` | `karyawan.delete` |
| POST | `/operator-perusahaan/karyawan/bulk-delete` | `operator-perusahaan.karyawan.bulkDelete` | `auth:admin-company` | `karyawan.delete` |
| POST | `/operator-perusahaan/karyawan/bulk-restore` | `operator-perusahaan.karyawan.bulkRestore` | `auth:admin-company` | `karyawan.restore` |
| PATCH | `/operator-perusahaan/karyawan/{id}/restore` | `operator-perusahaan.karyawan.restore` | `auth:admin-company` | `karyawan.restore` |
| GET | `/operator-perusahaan/karyawan/export` | `operator-perusahaan.karyawan.export` | `auth:admin-company` | `karyawan.export` |
| GET | `/operator-perusahaan/karyawan/template` | `operator-perusahaan.karyawan.template` | `auth:admin-company` | `karyawan.import` |
| POST | `/operator-perusahaan/karyawan/import` | `operator-perusahaan.karyawan.import` | `auth:admin-company` | `karyawan.import` |

### Controller
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@index`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@store`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@update`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@bulkToggleStatus`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@destroy`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@bulkDelete`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@restore`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@bulkRestore`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@export`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@template`
`App\Http\Controllers\OperatorPerusahaan\KaryawanController@import`

### View
`resources/js/Pages/OperatorPerusahaan/Karyawan.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Employee` | `employees` | Model utama — data karyawan |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_140604_create_employees_table` | `employees` |

### Format Import Excel
| Kolom | Wajib | Keterangan |
|-------|-------|------------|
| Kode | Tidak | Kode karyawan internal (mis. `KRY001`); **unik per perusahaan** — boleh duplikat antar perusahaan |
| Nama | Ya | Nama lengkap karyawan |
| Email | Ya | Email unik per perusahaan; akan ditolak jika duplikat |
| Kode Negara | Tidak | Default `+62` jika kosong |
| No. Telepon | Tidak | Disimpan sebagai string agar awalan `0` tidak hilang |
| No. NIK | Tidak | Nomor KTP |
| No. KK | Tidak | Nomor Kartu Keluarga |
| Status (Aktif/Nonaktif) | Tidak | `Aktif` (default) atau `Nonaktif` (case-insensitive) |
| Password | Tidak | Default `password123` jika kosong; akan di-hash dengan bcrypt |

### Format Export Excel
| Kolom | Sumber |
|-------|--------|
| Kode | `employees.code` (`-` jika kosong) |
| Nama | `employees.name` |
| Email | `employees.email` |
| Kode Negara | `employees.phone_country_code` |
| No. Telepon | `employees.phone_number` |
| No. NIK | `employees.no_nik` |
| No. KK | `employees.no_kk` |
| Status | `employees.is_active` (`Aktif` / `Nonaktif`) |

### Aturan Unik Kode Karyawan
- Kolom `code` (Kode) bersifat **opsional** (boleh NULL).
- Jika diisi, harus **unik dalam lingkup 1 perusahaan** (`unique(company_id, code)`).
- Boleh ada kode yang sama di perusahaan berbeda (mis. `KRY001` di PT A dan PT B tidak konflik).
- Validasi dilakukan pada: form Tambah, form Edit, dan Import Excel.

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorPerusahaan/KaryawanTest.php` | `test_*` | Feature CRUD + import/export test (PHPUnit) |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/KaryawanCRUDTest.cjs` | Various | Browser CRUD test dengan screenshot |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/KaryawanImportExportTest.cjs` | Various | Browser import/export test (modal, template, export all, export selected, validasi) |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/KaryawanPermissionTest.cjs` | Various | Browser permission test untuk setiap granular `karyawan.*` permission |
