# Halaman Insentif
> Portal: Karyawan | URL: `/karyawan/insentif-saya`

## Fungsi
Halaman untuk **melihat insentif/komisi** yang diperoleh karyawan dari pelanggan yang berhasil ditagih.
Karyawan dapat melihat daftar insentif mereka sendiri — berapa komisi yang diterima dari setiap pembayaran yang berhasil dikumpulkan.

## Fitur
- **Tabel daftar insentif** — menampilkan insentif karyawan dengan pelanggan, jumlah, dan status
- **Pencarian** — mencari insentif berdasarkan nama pelanggan (tekan Enter untuk mencari)
- **Filter status** — menyaring insentif berdasarkan status: Aktif, Nonaktif, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `karyawan-insentif.list` | Melihat tabel insentif sendiri dan sidebar menu |
| **Tambah Insentif** | `karyawan-insentif.create` | Membuat data insentif baru |
| **Edit Insentif** | `karyawan-insentif.edit` | Mengubah data insentif |
| **Hapus Insentif** | `karyawan-insentif.delete` | Menghapus insentif (dapat dipulihkan lagi) |
| **Pulihkan Insentif** | `karyawan-insentif.restore` | Mengembalikan insentif yang sudah dihapus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/insentif-saya` | — | `auth:employee` | `karyawan-insentif.list` |
| POST | `/karyawan/insentif-saya` | — | `auth:employee` | `karyawan-insentif.create` |
| PUT | `/karyawan/insentif-saya/{id}` | — | `auth:employee` | `karyawan-insentif.edit` |
| DELETE | `/karyawan/insentif-saya/{id}` | — | `auth:employee` | `karyawan-insentif.delete` |
| PATCH | `/karyawan/insentif-saya/{id}/restore` | — | `auth:employee` | `karyawan-insentif.restore` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Karyawan/InsentifSaya.vue`
### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\EmpIncentive` | `emp_incentives` | Model utama — data insentif karyawan |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_144033_create_emp_incentives_table` | `emp_incentives` |
### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/InsentifPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/Karyawan/InsentifSayaViewTest.php` | Various | Browser view test |
