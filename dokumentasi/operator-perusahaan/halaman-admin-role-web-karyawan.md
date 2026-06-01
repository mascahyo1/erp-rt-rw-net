# Halaman Admin Role Web Karyawan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/admin-role-web-karyawan`

## Fungsi
Halaman untuk mengelola **pemetaan (mapping) karyawan ↔ role** di portal web karyawan.
Setiap karyawan harus di-assign ke satu role. Role menentukan permission apa saja yang dimiliki karyawan di portal web karyawan (customer, langganan, tagihan, insentif, riwayat pembayaran).

## Fitur
- **Tabel daftar mapping** — menampilkan semua assignment karyawan↔role dengan avatar karyawan (warna amber), email, role pill, dan tanggal penugasan
- **Pencarian** — mencari karyawan berdasarkan nama
- **Urutkan** — klik judul kolom (Karyawan, Role, Tgl Dibuat) untuk mengurutkan data
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100
- **Import Excel** — unggah mapping massal dari file `.xlsx` / `.csv` (berdasarkan Email Karyawan + Role)
- **Export Excel** — unduh semua mapping atau hanya yang dipilih ke file `.xlsx`
- **Template Excel** — unduh template kosong + 1 baris contoh untuk format import
- **Dark mode** — semua modal, tabel, dan form support light & dark mode
- **Responsive** — tampilan adaptif untuk mobile (375px), tablet (768px), dan desktop (1366px+)

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `admin-role-web-karyawan.list` | Melihat tabel mapping |
| **Lihat Detail** | `admin-role-web-karyawan.list` | Melihat detail karyawan + role (modal `max-w-lg`) |
| **Tambah Mapping** | `admin-role-web-karyawan.create` | Memetakan karyawan ke role baru (modal `max-w-md/lg`) |
| **Edit Mapping** | `admin-role-web-karyawan.edit` | Mengubah role karyawan (pre-filled) |
| **Hapus Mapping** | `admin-role-web-karyawan.delete` | Menghapus assignment (modal konfirmasi) |
| **Bulk Hapus** | `admin-role-web-karyawan.delete` | Menghapus banyak mapping sekaligus |
| **Import** | `admin-role-web-karyawan.import` | Upload file Excel untuk mapping massal (modal `max-w-md`) |
| **Download Template** | `admin-role-web-karyawan.import` | Mengunduh template `.xlsx` |
| **Export Semua** | `admin-role-web-karyawan.export` | Mengunduh semua data ke `.xlsx` |
| **Export Selected** | `admin-role-web-karyawan.export` | Mengunduh data yang dipilih (checkbox) |

## Format Excel

### Format Import
| Kolom | Wajib | Keterangan |
|-------|-------|------------|
| Nama Karyawan | Tidak | Untuk referensi saja |
| Email Karyawan | **Ya** | Email harus ada di database perusahaan ini |
| Role | **Ya** | Nama role persis (case-sensitive) |

### Format Export
| Kolom | Sumber |
|-------|--------|
| Nama Karyawan | `employees.name` |
| Email Karyawan | `employees.email` |
| Role | `roles.name` |
| Tanggal Ditugaskan | `model_has_roles.created_at` |

### Aturan Import
- **Upsert**: jika (Email Karyawan, Role) sudah ada → update role-nya. Jika belum ada → buat baru.
- Validasi: Email Karyawan + Role **wajib diisi**, dan harus ditemukan di database perusahaan ini.
- Baris yang gagal akan dilewati dengan pesan error.

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/admin-role-web-karyawan` | `admin-role-web-karyawan.index` | `auth:admin-company` | `admin-role-web-karyawan.list` |
| POST | `/operator-perusahaan/admin-role-web-karyawan` | `admin-role-web-karyawan.store` | `auth:admin-company` | `admin-role-web-karyawan.create` |
| PUT | `/operator-perusahaan/admin-role-web-karyawan/{modelHasRole}` | `admin-role-web-karyawan.update` | `auth:admin-company` | `admin-role-web-karyawan.edit` |
| DELETE | `/operator-perusahaan/admin-role-web-karyawan/{modelHasRole}` | `admin-role-web-karyawan.destroy` | `auth:admin-company` | `admin-role-web-karyawan.delete` |
| POST | `/operator-perusahaan/admin-role-web-karyawan/bulk-delete` | `admin-role-web-karyawan.bulkDelete` | `auth:admin-company` | `admin-role-web-karyawan.delete` |
| GET | `/operator-perusahaan/admin-role-web-karyawan/export` | `admin-role-web-karyawan.export` | `auth:admin-company` | `admin-role-web-karyawan.export` |
| GET | `/operator-perusahaan/admin-role-web-karyawan/template` | `admin-role-web-karyawan.template` | `auth:admin-company` | `admin-role-web-karyawan.import` |
| POST | `/operator-perusahaan/admin-role-web-karyawan/import` | `admin-role-web-karyawan.import` | `auth:admin-company` | `admin-role-web-karyawan.import` |

### Controller
`App\Http\Controllers\OperatorPerusahaan\AdminRoleWebKaryawanController@index`
`App\Http\Controllers\OperatorPerusahaan\AdminRoleWebKaryawanController@store`
`App\Http\Controllers\OperatorPerusahaan\AdminRoleWebKaryawanController@update`
`App\Http\Controllers\OperatorPerusahaan\AdminRoleWebKaryawanController@destroy`
`App\Http\Controllers\OperatorPerusahaan\AdminRoleWebKaryawanController@bulkDelete`
`App\Http\Controllers\OperatorPerusahaan\AdminRoleWebKaryawanController@export`
`App\Http\Controllers\OperatorPerusahaan\AdminRoleWebKaryawanController@template`
`App\Http\Controllers\OperatorPerusahaan\AdminRoleWebKaryawanController@import`

### View
`resources/js/Pages/OperatorPerusahaan/AdminRoleWebKaryawan.vue`

### Models
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\ModelHasRole` | `model_has_roles` | Tabel pivot — menghubungkan karyawan ↔ role |
| `App\Models\Employee` | `employees` | Karyawan yang akan di-assign |
| `App\Models\Role` | `roles` | Role yang di-assign (scope `karyawan_perusahaan`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_140504_create_model_has_roles_table` | `model_has_roles` |
| `2026_05_11_140604_create_employees_table` | `employees` |
| `2026_05_11_140234_create_roles_table` | `roles` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorPerusahaan/AdminRoleWebKaryawanTest.php` | `test_*` | Feature CRUD + import/export + permission test (PHPUnit) |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/AdminRolePagesInspect.cjs` | Various | Comprehensive visual test (3 viewports × 2 themes) untuk kedua admin-role pages |
