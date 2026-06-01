# Halaman Role Web Karyawan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/role-web-karyawan`

## Fungsi
Halaman untuk mengelola **role (peran)** yang digunakan untuk karyawan di portal web karyawan.
Role menentukan hak akses apa saja yang dimiliki karyawan terhadap menu dan fitur di portal karyawan (customer, langganan, tagihan, insentif, riwayat pembayaran).

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
| **Lihat Daftar** | `role-web-karyawan.list` | Melihat tabel role dan sidebar menu |
| **Lihat Detail** | `role-web-karyawan.list` | Melihat detail role + permission dikelompokkan per modul (modal `max-w-2xl`) |
| **Tambah Role** | `role-web-karyawan.create` | Membuat role baru dengan nama, deskripsi, status, dan permission |
| **Edit Role** | `role-web-karyawan.edit` | Mengubah role (pre-filled dengan data existing) |
| **Hapus Role** | `role-web-karyawan.delete` | Menghapus role (dapat dipulihkan lagi) |
| **Pulihkan Role** | `role-web-karyawan.restore` | Mengembalikan role yang sudah dihapus |
| **Bulk Aktifkan** | `role-web-karyawan.edit` | Mengaktifkan banyak role sekaligus |
| **Bulk Nonaktifkan** | `role-web-karyawan.edit` | Menonaktifkan banyak role sekaligus |
| **Bulk Hapus** | `role-web-karyawan.delete` | Menghapus banyak role sekaligus |

## Komponen Reusable: PermissionGroupChecklist
Sama seperti Role Perusahaan — menggunakan komponen `PermissionGroupChecklist` (lihat dokumentasi `halaman-role-perusahaan.md`).

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/role-web-karyawan` | `operator-perusahaan.role-web-karyawan.index` | `auth:admin-company` | `role-web-karyawan.list` |
| POST | `/operator-perusahaan/role-web-karyawan` | `operator-perusahaan.role-web-karyawan.store` | `auth:admin-company` | `role-web-karyawan.create` |
| PUT | `/operator-perusahaan/role-web-karyawan/{role}` | `operator-perusahaan.role-web-karyawan.update` | `auth:admin-company` | `role-web-karyawan.edit` |
| POST | `/operator-perusahaan/role-web-karyawan/bulk-status` | `operator-perusahaan.role-web-karyawan.bulk-status` | `auth:admin-company` | `role-web-karyawan.edit` |
| DELETE | `/operator-perusahaan/role-web-karyawan/{role}` | `operator-perusahaan.role-web-karyawan.destroy` | `auth:admin-company` | `role-web-karyawan.delete` |
| POST | `/operator-perusahaan/role-web-karyawan/bulk-delete` | `operator-perusahaan.role-web-karyawan.bulk-delete` | `auth:admin-company` | `role-web-karyawan.delete` |
| POST | `/operator-perusahaan/role-web-karyawan/{id}/restore` | `operator-perusahaan.role-web-karyawan.restore` | `auth:admin-company` | `role-web-karyawan.restore` |

### Controller
`App\Http\Controllers\OperatorPerusahaan\RoleWebKaryawanController@index`
`App\Http\Controllers\OperatorPerusahaan\RoleWebKaryawanController@store`
`App\Http\Controllers\OperatorPerusahaan\RoleWebKaryawanController@update`
`App\Http\Controllers\OperatorPerusahaan\RoleWebKaryawanController@bulkToggleStatus`
`App\Http\Controllers\OperatorPerusahaan\RoleWebKaryawanController@destroy`
`App\Http\Controllers\OperatorPerusahaan\RoleWebKaryawanController@bulkDelete`
`App\Http\Controllers\OperatorPerusahaan\RoleWebKaryawanController@restore`

### View
- `resources/js/Pages/OperatorPerusahaan/RoleWebKaryawan.vue`
- `resources/js/Components/PermissionGroupChecklist.vue` (komponen reusable)

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Role` | `roles` | Model utama — role dengan scope `karyawan_perusahaan` |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_140234_create_roles_table` | `roles` |

### Data yang Dikirim Controller ke Frontend
`index()` mengirim `roles` paginated, dengan setiap item berisi:
- `id`, `nama_role`, `deskripsi`, `status`
- `permission_count` (jumlah), `permission_ids` (array of UUID), `permissions` (array of {id, nama, deskripsi})
- `dihapus`, `deleted_at`, `restored_at`, `created_at`, `updated_at`, `created_by`, `updated_by`
- Serta `availablePermissions` (semua permission di scope `karyawan_perusahaan`)

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorPerusahaan/RoleWebKaryawanTest.php` | `test_*` | Feature CRUD + permission test (PHPUnit) |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/RoleWebKaryawanCRUDTest.cjs` | Various | Browser CRUD test dengan screenshot |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/RoleWebKaryawanPermissionTest.cjs` | Various | Browser permission test |
