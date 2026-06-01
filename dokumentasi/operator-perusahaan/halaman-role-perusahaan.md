# Halaman Role Perusahaan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/role-perusahaan`

## Fungsi
Halaman untuk mengelola **role (peran)** admin perusahaan.
Role menentukan hak akses apa saja yang dimiliki admin perusahaan terhadap menu dan fitur di portal operator perusahaan.

## Fitur
- **Tabel daftar role** — menampilkan semua role dengan nama, deskripsi, jumlah permission, status, dan tanggal dibuat
- **Pencarian** — mencari role berdasarkan nama (tekan Enter untuk mencari)
- **Filter status** — menyaring role berdasarkan status: Semua, Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom (Nama Role, Status, Tgl Dibuat) untuk mengurutkan data
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100
- **Permission Checklist** — pilih permission per modul dengan search, filter, dan "Pilih Semua" (modal `max-w-4xl`)
- **Detail Permission** — lihat permission dikelompokkan per modul dengan badge
- **Dark mode** — semua modal, tabel, dan form support light & dark mode

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `role-perusahaan-op.list` | Melihat tabel role dan sidebar menu |
| **Lihat Detail** | `role-perusahaan-op.list` | Melihat detail role + permission dikelompokkan per modul (modal `max-w-2xl`) |
| **Tambah Role** | `role-perusahaan-op.create` | Membuat role baru dengan nama, deskripsi, status, dan permission |
| **Edit Role** | `role-perusahaan-op.edit` | Mengubah role (pre-filled dengan data existing) |
| **Hapus Role** | `role-perusahaan-op.delete` | Menghapus role (dapat dipulihkan lagi) |
| **Pulihkan Role** | `role-perusahaan-op.restore` | Mengembalikan role yang sudah dihapus |
| **Bulk Aktifkan** | `role-perusahaan-op.edit` | Mengaktifkan banyak role sekaligus |
| **Bulk Nonaktifkan** | `role-perusahaan-op.edit` | Menonaktifkan banyak role sekaligus |
| **Bulk Hapus** | `role-perusahaan-op.delete` | Menghapus banyak role sekaligus |

## Komponen Reusable: PermissionGroupChecklist
Modal Tambah/Edit Role menggunakan komponen Vue `PermissionGroupChecklist` yang menampilkan permission dikelompokkan per modul (Admin Perusahaan, Konfigurasi, Customer, Karyawan, Tagihan, dst) dengan fitur:
- **Search bar** untuk mencari permission by modul / aksi / deskripsi
- **Counter** "X / Y dipilih" real-time
- **Pilih Semua / Bersihkan** untuk permission yang sedang ditampilkan
- **Per-group select all** dengan indeterminate state (untuk partial selection)
- **Collapsible per group** — klik header untuk expand/collapse
- **Badge per group** — `0/6` (abu-abu), `3/6` (amber), `6/6` (hijau) untuk status
- **Grid 2 kolom** per group untuk readability
- **Dark mode** — semua state punya kontras yang cukup

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/role-perusahaan` | `operator-perusahaan.role-perusahaan.index` | `auth:admin-company` | `role-perusahaan-op.list` |
| POST | `/operator-perusahaan/role-perusahaan` | `operator-perusahaan.role-perusahaan.store` | `auth:admin-company` | `role-perusahaan-op.create` |
| PUT | `/operator-perusahaan/role-perusahaan/{role}` | `operator-perusahaan.role-perusahaan.update` | `auth:admin-company` | `role-perusahaan-op.edit` |
| POST | `/operator-perusahaan/role-perusahaan/bulk-status` | `operator-perusahaan.role-perusahaan.bulk-status` | `auth:admin-company` | `role-perusahaan-op.edit` |
| DELETE | `/operator-perusahaan/role-perusahaan/{role}` | `operator-perusahaan.role-perusahaan.destroy` | `auth:admin-company` | `role-perusahaan-op.delete` |
| POST | `/operator-perusahaan/role-perusahaan/bulk-delete` | `operator-perusahaan.role-perusahaan.bulk-delete` | `auth:admin-company` | `role-perusahaan-op.delete` |
| POST | `/operator-perusahaan/role-perusahaan/{id}/restore` | `operator-perusahaan.role-perusahaan.restore` | `auth:admin-company` | `role-perusahaan-op.restore` |

### Controller
`App\Http\Controllers\OperatorPerusahaan\RolePerusahaanController@index`
`App\Http\Controllers\OperatorPerusahaan\RolePerusahaanController@store`
`App\Http\Controllers\OperatorPerusahaan\RolePerusahaanController@update`
`App\Http\Controllers\OperatorPerusahaan\RolePerusahaanController@bulkToggleStatus`
`App\Http\Controllers\OperatorPerusahaan\RolePerusahaanController@destroy`
`App\Http\Controllers\OperatorPerusahaan\RolePerusahaanController@bulkDelete`
`App\Http\Controllers\OperatorPerusahaan\RolePerusahaanController@restore`

### View
- `resources/js/Pages/OperatorPerusahaan/RolePerusahaan.vue`
- `resources/js/Components/PermissionGroupChecklist.vue` (komponen reusable)

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Role` | `roles` | Model utama — role dengan scope `admin_perusahaan` |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_140234_create_roles_table` | `roles` |

### Data yang Dikirim Controller ke Frontend
`index()` mengirim `roles` yang sudah dipaginate, dan setiap item berisi:
- `id`, `nama_role`, `deskripsi`, `status`
- `permission_count` (jumlah), `permission_ids` (array of UUID), `permissions` (array of {id, nama, deskripsi})
- `dihapus`, `deleted_at`, `restored_at`, `created_at`, `updated_at`, `created_by`, `updated_by`
- Serta `availablePermissions` (semua permission di scope `admin_perusahaan`) untuk form

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorPerusahaan/RolePerusahaanTest.php` | `test_*` | Feature CRUD + permission test (PHPUnit) |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/RolePerusahaanCRUDTest.cjs` | Various | Browser CRUD test dengan screenshot |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/RolePerusahaanPermissionTest.cjs` | Various | Browser permission test |
