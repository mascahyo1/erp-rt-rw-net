# Halaman Admin Role Perusahaan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/admin-role-perusahaan`

## Fungsi
Halaman untuk mengelola **pemetaan (mapping) admin ↔ role** di perusahaan.
Setiap admin perusahaan harus di-assign ke satu role. Role menentukan permission apa saja yang dimiliki admin tersebut di portal operator perusahaan.

## Fitur
- **Tabel daftar mapping** — menampilkan semua assignment admin↔role dengan avatar admin, email, role pill, dan tanggal penugasan
- **Pencarian** — mencari admin berdasarkan nama
- **Urutkan** — klik judul kolom (Admin, Role, Tgl Dibuat) untuk mengurutkan data
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100
- **Import Excel** — unggah mapping massal dari file `.xlsx` / `.csv` (berdasarkan Email Admin + Role)
- **Export Excel** — unduh semua mapping atau hanya yang dipilih ke file `.xlsx`
- **Template Excel** — unduh template kosong + 1 baris contoh untuk format import
- **Dark mode** — semua modal, tabel, dan form support light & dark mode
- **Responsive** — tampilan adaptif untuk mobile (375px), tablet (768px), dan desktop (1366px+)

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `admin-role-perusahaan-op.list` | Melihat tabel mapping |
| **Lihat Detail** | `admin-role-perusahaan-op.list` | Melihat detail admin + role (modal `max-w-lg`) |
| **Tambah Mapping** | `admin-role-perusahaan-op.create` | Memetakan admin ke role baru (modal `max-w-md/lg`) |
| **Edit Mapping** | `admin-role-perusahaan-op.edit` | Mengubah role admin (pre-filled) |
| **Hapus Mapping** | `admin-role-perusahaan-op.delete` | Menghapus assignment (modal konfirmasi) |
| **Bulk Hapus** | `admin-role-perusahaan-op.delete` | Menghapus banyak mapping sekaligus |
| **Import** | `admin-role-perusahaan-op.import` | Upload file Excel untuk mapping massal (modal `max-w-md`) |
| **Download Template** | `admin-role-perusahaan-op.import` | Mengunduh template `.xlsx` |
| **Export Semua** | `admin-role-perusahaan-op.export` | Mengunduh semua data ke `.xlsx` |
| **Export Selected** | `admin-role-perusahaan-op.export` | Mengunduh data yang dipilih (checkbox) |

## Format Excel

### Format Import
| Kolom | Wajib | Keterangan |
|-------|-------|------------|
| Nama Admin | Tidak | Untuk referensi saja |
| Email Admin | **Ya** | Email harus ada di database perusahaan ini |
| Role | **Ya** | Nama role persis (case-sensitive) |

### Format Export
| Kolom | Sumber |
|-------|--------|
| Nama Admin | `admin_companies.name` |
| Email Admin | `admin_companies.email` |
| Role | `roles.name` |
| Tanggal Ditugaskan | `model_has_roles.created_at` |

### Aturan Import
- **Upsert**: jika (Email Admin, Role) sudah ada → update role-nya. Jika belum ada → buat baru.
- Validasi: Email Admin + Role **wajib diisi**, dan harus ditemukan di database perusahaan ini.
- Baris yang gagal akan dilewati dengan pesan error.

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/admin-role-perusahaan` | `admin-role-perusahaan.index` | `auth:admin-company` | `admin-role-perusahaan-op.list` |
| POST | `/operator-perusahaan/admin-role-perusahaan` | `admin-role-perusahaan.store` | `auth:admin-company` | `admin-role-perusahaan-op.create` |
| PUT | `/operator-perusahaan/admin-role-perusahaan/{modelHasRole}` | `admin-role-perusahaan.update` | `auth:admin-company` | `admin-role-perusahaan-op.edit` |
| DELETE | `/operator-perusahaan/admin-role-perusahaan/{modelHasRole}` | `admin-role-perusahaan.destroy` | `auth:admin-company` | `admin-role-perusahaan-op.delete` |
| POST | `/operator-perusahaan/admin-role-perusahaan/bulk-delete` | `admin-role-perusahaan.bulkDelete` | `auth:admin-company` | `admin-role-perusahaan-op.delete` |
| GET | `/operator-perusahaan/admin-role-perusahaan/export` | `admin-role-perusahaan.export` | `auth:admin-company` | `admin-role-perusahaan-op.export` |
| GET | `/operator-perusahaan/admin-role-perusahaan/template` | `admin-role-perusahaan.template` | `auth:admin-company` | `admin-role-perusahaan-op.import` |
| POST | `/operator-perusahaan/admin-role-perusahaan/import` | `admin-role-perusahaan.import` | `auth:admin-company` | `admin-role-perusahaan-op.import` |

### Controller
`App\Http\Controllers\OperatorPerusahaan\AdminRolePerusahaanController@index`
`App\Http\Controllers\OperatorPerusahaan\AdminRolePerusahaanController@store`
`App\Http\Controllers\OperatorPerusahaan\AdminRolePerusahaanController@update`
`App\Http\Controllers\OperatorPerusahaan\AdminRolePerusahaanController@destroy`
`App\Http\Controllers\OperatorPerusahaan\AdminRolePerusahaanController@bulkDelete`
`App\Http\Controllers\OperatorPerusahaan\AdminRolePerusahaanController@export`
`App\Http\Controllers\OperatorPerusahaan\AdminRolePerusahaanController@template`
`App\Http\Controllers\OperatorPerusahaan\AdminRolePerusahaanController@import`

### View
`resources/js/Pages/OperatorPerusahaan/AdminRolePerusahaan.vue`

### Models
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\ModelHasRole` | `model_has_roles` | Tabel pivot — menghubungkan admin ↔ role |
| `App\Models\AdminCompany` | `admin_companies` | Admin yang akan di-assign |
| `App\Models\Role` | `roles` | Role yang di-assign (scope `admin_perusahaan`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_140504_create_model_has_roles_table` | `model_has_roles` |
| `2026_05_11_135636_create_admin_companies_table` | `admin_companies` |
| `2026_05_11_140234_create_roles_table` | `roles` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorPerusahaan/AdminRolePerusahaanTest.php` | `test_*` | Feature CRUD + import/export + permission test (PHPUnit) |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/AdminRolePagesInspect.cjs` | Various | Comprehensive visual test (3 viewports × 2 themes) untuk kedua admin-role pages |
