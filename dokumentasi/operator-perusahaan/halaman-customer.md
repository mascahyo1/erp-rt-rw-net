# Halaman Customer
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/customer`

## Fungsi
Halaman untuk **mengelola data pelanggan** yang terdaftar di perusahaan.
Pelanggan adalah pengguna akhir yang berlangganan layanan internet dari perusahaan.

---

## Form Create / Edit / Detail

| Field | Type | Wajib | Keterangan |
|-------|------|-------|-----------|
| Kode | text | — | Kode pelanggan (unique per company), opsional |
| Nama | text | ✅ | Nama lengkap pelanggan |
| Email | text | ✅ | Email (unique per company) |
| Password | text | ✅ (create) | Minimal 8 karakter |
| Kode Negara | select | ✅ | Default +62 |
| No. Telepon | text | ✅ | Nomor telepon aktif |
| No. NIK | text | — | Nomor KTP |
| No. KK | text | — | Nomor Kartu Keluarga |
| Foto KTP | file | — | Gambar, max 2MB |
| Foto KK | file | — | Gambar, max 2MB |
| Foto Profil | file | — | Gambar, max 2MB |
| Alamat | textarea | — | Alamat lengkap |
| Status | select | ✅ | Aktif / Nonaktif |

---

## Kolom Datatable

| Kolom | Sortable | Keterangan |
|-------|----------|-----------|
| ☐ | — | Checkbox bulk select |
| Kode | ✅ | Kode pelanggan (font-mono) |
| Nama | ✅ | Initial avatar + nama lengkap |
| Email | ✅ | Alamat email |
| Telepon | — | Kode negara + nomor telepon |
| Alamat | — | Alamat (truncated) |
| Status | ✅ | Badge: Aktif (hijau) / Nonaktif (merah) / Terhapus (merah, opacity 60%) |
| Aksi | — | Detail, Edit, Hapus / Pulihkan |

---

## Fitur

- **Tabel daftar pelanggan** — menampilkan semua pelanggan dengan kode, nama, email, telepon, alamat, status
- **Pencarian** — mencari pelanggan berdasarkan nama, email, atau kode (tekan Enter)
- **Filter Status** — select dropdown: Semua / Aktif / Nonaktif
- **Filter Terhapus** — select dropdown: Tidak / Ya (tampilkan yang sudah dihapus)
- **Urutkan** — klik judul kolom untuk mengurutkan (kode, nama, email, status)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100
- **Bulk Action** — Aktifkan / Nonaktifkan / Hapus / Pulihkan / Export banyak pelanggan sekaligus
- **Import Excel** — upload file, validasi per baris, insert batch
- **Export Excel** — download semua data atau selected via checkbox
- **Template Import** — download template kosong untuk diisi

---

## Permission & Aksi

Kalau user tidak punya izin, tombol disembunyikan dan backend route dikunci (403 Forbidden).

| Aksi | Izin Diperlukan | Single | Bulk |
|------|----------------|---------|------|
| **Lihat Daftar** | `customer.list` | — | — |
| **Tambah Pelanggan** | `customer.create` | ✅ | — |
| **Edit Pelanggan** | `customer.edit` | ✅ | — |
| **Detail Pelanggan** | `customer.detail` | ✅ | — |
| **Hapus Pelanggan** | `customer.delete` | ✅ | ✅ |
| **Pulihkan Pelanggan** | `customer.restore` | ✅ | ✅ |
| **Aktifkan Pelanggan** | `customer.edit` | — | ✅ |
| **Nonaktifkan Pelanggan** | `customer.edit` | — | ✅ |
| **Export Excel** | `customer.export` | — | ✅ |
| **Import Excel** | `customer.import` | — | — |

---

## Teknis

### Route

| Method | URI | Permission |
|--------|-----|------------|
| GET | `/operator-perusahaan/customer` | `customer.list` |
| POST | `/operator-perusahaan/customer` | `customer.create` |
| PUT | `/operator-perusahaan/customer/{customer}` | `customer.edit` |
| DELETE | `/operator-perusahaan/customer/{customer}` | `customer.delete` |
| PATCH | `/operator-perusahaan/customer/{id}/restore` | `customer.restore` |
| POST | `/operator-perusahaan/customer/bulk-status` | `customer.edit` |
| POST | `/operator-perusahaan/customer/bulk-delete` | `customer.delete` |
| POST | `/operator-perusahaan/customer/bulk-restore` | `customer.restore` |
| GET | `/operator-perusahaan/customer/export` | `customer.export` |
| GET | `/operator-perusahaan/customer/export?ids=` | `customer.export` |
| GET | `/operator-perusahaan/customer/template` | `customer.import` |
| POST | `/operator-perusahaan/customer/import` | `customer.import` |

### Permissions (8 total)
```
customer.list    → Lihat halaman daftar pelanggan
customer.create  → Tambah pelanggan baru
customer.edit    → Edit pelanggan + bulk aktif/nonaktif
customer.detail  → Lihat detail pelanggan
customer.delete  → Hapus pelanggan + bulk delete
customer.restore → Pulihkan pelanggan + bulk restore
customer.export  → Download Excel
customer.import  → Upload Excel + download template
```

### Controller
`App\Http\Controllers\OperatorPerusahaan\CustomerController`

### View
`resources/js/Pages/OperatorPerusahaan/Customer.vue`

### Model
`App\Models\Customer`

---

## Testing

### E2E Testing: Playwright (NodeJS)

| Test File | Coverage |
|-----------|----------|
| `CustomerCRUDTest.cjs` | Search, filter, sort, create, delete, restore, checklist, bulk actions |
| `CustomerPermissionTest.cjs` | Granular permission check (HAS vs NOT HAS) |
| `CustomerResponsiveTest.cjs` | Responsive & dark/light mode |

### CRUD Test Cases (CustomerCRUDTest.cjs)

| # | Test | Deskripsi |
|---|------|-----------|
| 01 | `test_01_page_renders` | Page load, HTTP 200, content rendered |
| 02 | `test_02_search` | Pencarian by nama pelanggan |
| 03 | `test_03_filter_status` | Filter Aktif/Nonaktif |
| 04 | `test_04_filter_terhapus` | Filter Terhapus (Ya/Tidak) |
| 05 | `test_05_sort_all_columns` | Sort semua kolom (asc/desc) |
| 06 | `test_06_create_customer` | Tambah pelanggan baru |
| 07 | `test_07_delete_customer` | Soft delete via modal |
| 08 | `test_08_restore_customer` | Pulihkan pelanggan yang dihapus |
| 09 | `test_09_checklist` | Checklist items |
| 10 | `test_10_bulk_delete` | Bulk delete via checkbox |
| 11 | `test_11_bulk_restore` | Bulk restore deleted items |
| 12 | `test_12_bulk_aktifkan` | Bulk aktifkan pelanggan |
| 13 | `test_13_bulk_nonaktifkan` | Bulk nonaktifkan pelanggan |
| 14 | `test_14_export_all` | Export semua data |
| 15 | `test_15_export_selected` | Export selected via checkbox |

### Permission Test Cases (CustomerPermissionTest.cjs)

| Permission | HAS (rbac.full) | NOT HAS (rbac.list) |
|------------|-----------------|---------------------|
| `customer.list` | HTTP 200, sidebar visible | HTTP 403 |
| `customer.create` | "Tambah Customer" button visible | Button hidden |
| `customer.edit` | Edit button + bulk Aktifkan/Nonaktifkan visible | Hidden |
| `customer.detail` | "Detail" button visible, modal opens | Hidden |
| `customer.delete` | "Hapus" button + bulk visible | Hidden |
| `customer.restore` | "Pulihkan" button + bulk visible | Hidden |
| `customer.export` | "Export" button visible | Hidden |
| `customer.import` | "Import" button + "Download Template" visible | Hidden |

### RBAC Users for Testing

| User | Email | Permissions |
|------|-------|-------------|
| Full Access | `rbac.full@rtrwnet.id` | Semua (8 permissions) |
| List Only | `rbac.list@rtrwnet.id` | `customer.list` only |
| No Access | `rbac.no@rtrwnet.id` | None |

Setup: `php tests/Browser/Playwright/setup-rbac-users.php`

### Responsive Test (CustomerResponsiveTest.cjs)

| Viewport | Resolution |
|----------|------------|
| Mobile | 375x667 |
| Mobile Landscape | 812x375 |
| Tablet | 768x1024 |
| Laptop | 1366x768 |
| Desktop | 1920x1080 |

Color schemes: Light & Dark mode

---

## Import / Export Excel

### Aturan Wajib
1. **Tidak ada angka notasi ilmiah** — gunakan `setCellValueExplicit` dengan `TYPE_STRING`
2. **Export:** Header bold, auto-width, border tipis
3. **Import:** Validasi per baris, insert batch (chunk 500)
4. **Template:** Download template kosong via `/template`

### Import Column Mapping

| Index | Field | Keterangan |
|-------|-------|-----------|
| 0 | Kode | String, nullable, unique per company |
| 1 | Nama | Required |
| 2 | Email | Required, valid email, unique per company |
| 3 | Kode Negara | Default +62 |
| 4 | No. Telepon | Required, numeric string |
| 5 | No. NIK | String, nullable |
| 6 | No. KK | String, nullable |
| 7 | Alamat | String, nullable |
| 8 | Status | Aktif/Nonaktif |
| 9 | Password | Default: password123 |

### Export Column Headers (8 kolom)

`Kode, Nama, Email, No. Telepon, No. NIK, No. KK, Alamat, Status`

---

## Model Fields

| Field | Type | Keterangan |
|-------|------|-----------|
| `id` | uuid | Primary key |
| `company_id` | uuid | FK ke companies |
| `code` | string | Kode pelanggan (unique per company), nullable |
| `name` | string | Nama lengkap |
| `email` | string | Email (unique per company) |
| `phone_country_code` | string | Kode negara (+62) |
| `phone_number` | string | Nomor telepon |
| `no_nik` | string, nullable | Nomor KTP |
| `photo_ktp` | string, nullable | Path file foto KTP |
| `no_kk` | string, nullable | Nomor Kartu Keluarga |
| `photo_kk` | string, nullable | Path file foto KK |
| `photo_profile` | string, nullable | Path file foto profil |
| `address` | string, nullable | Alamat lengkap |
| `is_active` | boolean | Status aktif |
| `password` | string | Hashed password |
| SoftDeletes | — | Ada soft delete |
| blameable | — | created_by, updated_by, deleted_by, restored_by |