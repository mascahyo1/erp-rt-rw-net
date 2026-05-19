# Daftar Paket
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/daftar-paket`

## Fungsi
Halaman untuk **mengelola paket layanan internet** yang tersedia untuk dipilih pelanggan.
Admin perusahaan dapat menambah, mengubah, menghapus, dan memulihkan paket internet termasuk konfigurasi FUP (Fair Usage Policy).

### form create / edit / detail
nama paket
billing_cycle
max_devices
harga
speed_down_kbps
speed_up_kbps
fup_quota_down
fup_quota_up
fup_speed_down_kbps
fup_speed_up_kbps
is_active
description

### kolom datatable
nama paket
speed
billing cycle
quota fup
harga
status


## Fitur
- **Tabel daftar paket** — menampilkan semua paket dengan nama, harga, kecepatan, kuota, billing cycle, dan status
- **Pencarian** — mencari paket berdasarkan nama atau deskripsi (tekan Enter)
- **Filter Status** — menyaring paket berdasarkan status (Aktif / Nonaktif)
- **Filter Terhapus** — menampilkan paket yang sudah dihapus (soft delete)
- **Urutkan** — klik judul kolom untuk mengurutkan (nama, harga, status)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100
- **Bulk Action** — pilih beberapa paket untuk diaktifkan/dinonaktifkan/dihapus/pulihkan sekaligus

## Aksi
Kalau user tidak punya izin, tombol disembunyikan dan backend route dikunci (403 Forbidden).
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `paket.list` | Melihat tabel paket dan sidebar menu |
| **Tambah Paket** | `paket.create` | Membuka modal form, POST ke backend |
| **Edit Paket** | `paket.edit` | Mengubah data paket (nama, harga, speed, quota, FUP, dll) |
| **Detail Paket** | `paket.detail` | Melihat informasi lengkap paket |
| **Hapus Paket** | `paket.delete` | Soft delete (bisa dipulihkan), single & bulk |
| **Pulihkan Paket** | `paket.restore` | Mengembalikan paket yang sudah dihapus, single & bulk |
| **Bulk Aktif/Nonaktif** | `paket.edit` | Toggle status banyak paket sekaligus (nebeng edit) |
| **Bulk Hapus** | `paket.delete` | Hapus banyak paket sekaligus (nebeng delete) |
| **Bulk Pulihkan** | `paket.restore` | Pulihkan banyak paket sekaligus (nebeng restore) |

## Teknis
### Route
| Method | URI | Permission |
|--------|-----|------------|
| GET | `/operator-perusahaan/daftar-paket` | `paket.list` |
| POST | `/operator-perusahaan/daftar-paket` | `paket.create` |
| PUT | `/operator-perusahaan/daftar-paket/{internetPackage}` | `paket.edit` |
| DELETE | `/operator-perusahaan/daftar-paket/{internetPackage}` | `paket.delete` |
| PATCH | `/operator-perusahaan/daftar-paket/{id}/restore` | `paket.restore` |
| POST | `/operator-perusahaan/daftar-paket/bulk-status` | `paket.edit` |
| POST | `/operator-perusahaan/daftar-paket/bulk-delete` | `paket.delete` |

### Controller
`App\Http\Controllers\OperatorPerusahaan\PaketController`

### View
`resources/js/Pages/OperatorPerusahaan/DaftarPaket.vue`

### Model
`App\Models\InternetPackage`

| Field | Type | Keterangan |
|-------|------|-----------|
| `id` | uuid | Primary key |
| `company_id` | uuid | FK ke companies |
| `name` | string | Nama paket |
| `price` | decimal | Harga paket |
| `speed_down_kbps` | decimal | Kecepatan download (kbps) |
| `speed_up_kbps` | decimal | Kecepatan upload (kbps) |
| `quota_gb` | integer | Kuota dalam GB |
| `billing_cycle` | enum | daily, weekly, monthly, yearly |
| `max_devices` | integer, nullable | Maksimal perangkat |
| `is_unlimited` | boolean | Kuota unlimited |
| `fup_quota_down` | integer, nullable | Batas FUP download (GB) |
| `fup_quota_up` | integer, nullable | Batas FUP upload (GB) |
| `fup_speed_down_kbps` | decimal, nullable | Speed setelah FUP download |
| `fup_speed_up_kbps` | decimal, nullable | Speed setelah FUP upload |
| `is_active` | boolean | Status aktif |
| `description` | text, nullable | Deskripsi |

### Migration
`database/migrations/2026_05_11_141134_create_internet_packages_table.php`

### Test Case
| File | Keterangan |
|------|-----------|
| `tests/Browser/Feature/OperatorPerusahaan/DaftarPaketViewTest.php` | Browser view test |
| `tests/Browser/Feature/OperatorPerusahaan/LanggananPermissionTest.php` | Granular permission test |
