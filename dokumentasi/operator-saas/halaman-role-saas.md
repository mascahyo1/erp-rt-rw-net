# Halaman Role SaaS
> Portal: Operator SaaS | URL: `/operator-saas/role-saas`

## Fungsi
Halaman untuk mengelola **role (peran)** yang bisa diberikan kepada admin SaaS.
Role menentukan hak akses apa saja yang dimiliki admin SaaS terhadap menu dan fitur di portal operator SaaS.

Contoh: Role "Super Admin" bisa akses semua menu, sedangkan Role "Admin Terbatas" hanya bisa melihat data tanpa bisa mengubahnya.

## Fitur
- **Tabel daftar role** — menampilkan semua role yang sudah dibuat, dilengkapi nama dan jumlah permission
- **Pencarian** — mencari role berdasarkan nama (tekan Enter untuk mencari)
- **Filter status** — menyaring role berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100
- **Permission Checklist** — pilih permission per modul dengan search, filter, dan "Pilih Semua"
- **Dark mode** — semua modal, tabel, dan form support light & dark mode

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `role-saas.list` | Melihat tabel role dan sidebar menu |
| **Tambah Role** | `role-saas.create` | Membuat role baru dengan mengisi nama, deskripsi, status, dan permission per modul |
| **Edit Role** | `role-saas.edit` | Mengubah nama, deskripsi, status, dan permission role; pre-filled dengan data existing |
| **Detail Role** | `role-saas.list` | Melihat detail role + permission dikelompokkan per modul (modal `max-w-2xl`) |
| **Hapus Role** | `role-saas.delete` | Menghapus role (dapat dipulihkan lagi) |
| **Pulihkan Role** | `role-saas.restore` | Mengembalikan role yang sudah dihapus |
| **Bulk Aktifkan** | `role-saas.edit` | Mengaktifkan banyak role sekaligus |
| **Bulk Nonaktifkan** | `role-saas.edit` | Menonaktifkan banyak role sekaligus |
| **Bulk Hapus** | `role-saas.delete` | Menghapus banyak role sekaligus |

## Komponen Reusable: PermissionGroupChecklist
Modal Tambah/Edit Role menggunakan komponen Vue `PermissionGroupChecklist` yang menampilkan permission dikelompokkan per modul (Admin Perusahaan, Role SaaS, dll) dengan fitur:
- **Search bar** untuk mencari permission by modul / aksi / deskripsi
- **Counter** "X / Y dipilih" real-time
- **Pilih Semua / Bersihkan** untuk permission yang sedang ditampilkan
- **Per-group select all** dengan indeterminate state (untuk partial selection)
- **Collapsible per group** — klik header untuk expand/collapse
- **Badge per group** — `0/6` (abu-abu), `3/6` (amber), `6/6` (hijau) untuk status
- **Grid 2 kolom** per group untuk readability
- **Dark mode** — semua state punya kontras yang cukup

### View
- `resources/js/Pages/OperatorSaas/RoleSaaS.vue`
- `resources/js/Components/PermissionGroupChecklist.vue` (komponen reusable)

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/role-saas` | `operator-saas.role-saas.index` | `auth:web`, `ensure.user.active:web` | `role-saas.list` |
| POST | `/operator-saas/role-saas` | `operator-saas.role-saas.store` | `auth:web`, `ensure.user.active:web` | `role-saas.create` |
| PUT | `/operator-saas/role-saas/{role}` | `operator-saas.role-saas.update` | `auth:web`, `ensure.user.active:web` | `role-saas.edit` |
| POST | `/operator-saas/role-saas/bulk-status` | `operator-saas.role-saas.bulk-status` | `auth:web`, `ensure.user.active:web` | `role-saas.edit` |
| DELETE | `/operator-saas/role-saas/{role}` | `operator-saas.role-saas.destroy` | `auth:web`, `ensure.user.active:web` | `role-saas.delete` |
| POST | `/operator-saas/role-saas/bulk-delete` | `operator-saas.role-saas.bulk-delete` | `auth:web`, `ensure.user.active:web` | `role-saas.delete` |
| POST | `/operator-saas/role-saas/{id}/restore` | `operator-saas.role-saas.restore` | `auth:web`, `ensure.user.active:web` | `role-saas.restore` |

### Controller
`App\Http\Controllers\RoleSaasController@index`
`App\Http\Controllers\RoleSaasController@store`
`App\Http\Controllers\RoleSaasController@update`
`App\Http\Controllers\RoleSaasController@bulkToggleStatus`
`App\Http\Controllers\RoleSaasController@destroy`
`App\Http\Controllers\RoleSaasController@bulkDelete`
`App\Http\Controllers\RoleSaasController@restore`

### View
`resources/js/Pages/OperatorSaas/RoleSaaS.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Role` | `roles` | Model utama — role dengan scope `admin-saas` |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_140234_create_roles_table` | `roles` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/RoleSaasTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/RoleSaaSCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorSaas/RoleSaaSPermissionTest.php` | Various | Browser permission test |
