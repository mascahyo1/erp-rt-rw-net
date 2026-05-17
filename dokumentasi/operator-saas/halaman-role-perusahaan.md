# Halaman Role Perusahaan
> Portal: Operator SaaS | URL: `/operator-saas/role-perusahaan`

## Fungsi
Halaman untuk mengelola **role (peran)** yang bisa diberikan kepada admin perusahaan.
Role menentukan hak akses apa saja yang dimiliki admin perusahaan terhadap menu dan fitur di portal perusahaan.

Contoh: Role "Admin Penuh" bisa akses semua menu, sedangkan Role "Admin Terbatas" hanya bisa lihat data pelanggan tanpa bisa mengubahnya.

## Fitur
- **Tabel daftar role** — menampilkan semua role yang sudah dibuat, dilengkapi nama, status, dan perusahaan
- **Pencarian** — mencari role berdasarkan nama (tekan Enter untuk mencari)
- **Filter status** — menyaring role berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `role-perusahaan.list` | Melihat tabel role dan sidebar menu |
| **Tambah Role** | `role-perusahaan.create` | Membuat role baru dengan mengisi nama, status, dan perusahaan |
| **Edit Role** | `role-perusahaan.edit` | Mengubah nama dan status role yang sudah ada |
| **Hapus Role** | `role-perusahaan.delete` | Menghapus role (dapat dipulihkan lagi) |
| **Pulihkan Role** | `role-perusahaan.restore` | Mengembalikan role yang sudah dihapus |
| **Bulk Aktifkan** | `role-perusahaan.edit` | Mengaktifkan banyak role sekaligus |
| **Bulk Nonaktifkan** | `role-perusahaan.edit` | Menonaktifkan banyak role sekaligus |
| **Bulk Hapus** | `role-perusahaan.delete` | Menghapus banyak role sekaligus |
| **Bulk Pulihkan** | `role-perusahaan.restore` | Memulihkan banyak role sekaligus |

## Teknis
### Route
| Method | URI | Permission |
|--------|-----|------------|
| GET | `/operator-saas/role-perusahaan` | `role-perusahaan.list` |
| POST | `/operator-saas/role-perusahaan` | `role-perusahaan.create` |
| PUT | `/operator-saas/role-perusahaan/{role}` | `role-perusahaan.edit` |
| POST | `/operator-saas/role-perusahaan/bulk-status` | `role-perusahaan.edit` |
| DELETE | `/operator-saas/role-perusahaan/{role}` | `role-perusahaan.delete` |
| POST | `/operator-saas/role-perusahaan/bulk-delete` | `role-perusahaan.delete` |
| POST | `/operator-saas/role-perusahaan/{id}/restore` | `role-perusahaan.restore` |

### Controller
`App\Http\Controllers\RolePerusahaanController`

### View
`resources/js/Pages/OperatorSaas/RolePerusahaan.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Role` | `roles` | Model utama — role dengan scope `admin-company` |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_140234_create_roles_table` | `roles` |

### Test Case
| File | Keterangan |
|------|-----------|
| `tests/Feature/OperatorSaas/RolePerusahaanTest.php` | Test fungsional CRUD |
| `tests/Browser/Feature/OperatorSaas/RolePerusahaanCRUDTest.php` | Test browser + screenshot |
| `tests/Browser/Feature/OperatorSaas/RolePerusahaanPermissionTest.php` | Test izin akses granular |
