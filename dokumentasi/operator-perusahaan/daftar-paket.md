# Daftar Paket
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/daftar-paket`

## Fungsi
Halaman untuk **mengelola paket layanan internet** yang tersedia untuk dipilih pelanggan.
Admin perusahaan dapat menambah, mengubah, menghapus, dan memulihkan paket internet termasuk konfigurasi FUP (Fair Usage Policy).

---

## Form Create / Edit / Detail

| Field | Type | Wajib | Keterangan |
|-------|------|-------|-----------|
| Kode | text | ✅ | Kode paket internet (unique per company) |
| Nama Paket | text | ✅ | Nama paket internet |
| Billing Cycle | select | ✅ | daily / weekly / monthly / yearly |
| Max Devices | number | — | Maksimal perangkat (opsional) |
| Is Unlimited | checkbox | — | Centang bila kuota unlimited |
| Harga | number | ✅ | Harga per billing cycle |
| Speed Down (kbps) | number | ✅ | Kecepatan download default |
| Speed Up (kbps) | number | ✅ | Kecepatan upload default |
| Quota (GB) | number | — | Kuota dalam GB |
| FUP Quota Down (GB) | number | — | Batas download sebelum limit FUP |
| FUP Quota Up (GB) | number | — | Batas upload sebelum limit FUP |
| FUP Speed Down (kbps) | number | — | Kecepatan setelah kena limit FUP |
| FUP Speed Up (kbps) | number | — | Kecepatan upload setelah FUP |
| Status | select | ✅ | Aktif / Nonaktif |
| Deskripsi | textarea | — | Keterangan tambahan |

---

## Kolom Datatable

| Kolom | Sortable | Keterangan |
|-------|----------|-----------|
| ☐ | — | Checkbox bulk select |
| Nama Paket | ✅ | Nama + avatar initial + kode (font-mono di bawah nama) |
| Harga | ✅ | Format Rp, right-aligned |
| Speed | — | Download ↓ / Upload ↑ kbps |
| Quota | — | Quota GB (+ ∞ jika unlimited) |
| Billing | ✅ | Harian / Mingguan / Bulanan / Tahunan |
| Langganan Aktif | ✅ | Jumlah langganan aktif pakai paket ini |
| Estimasi Pendapatan | ✅ | Rumus: Langganan Aktif × Harga, right-aligned |
| Status | ✅ | Badge: Aktif (hijau) / Nonaktif (merah) / Terhapus (merah, opacity 60%) |
| Aksi | — | Detail, Edit, Hapus / Pulihkan |

---

## Fitur

- **Tabel daftar paket** — menampilkan semua paket dengan nama, harga, speed, quota, billing cycle, langganan aktif, pendapatan, status
- **Pencarian** — mencari paket berdasarkan nama atau deskripsi (tekan Enter)
- **Filter Status** — select dropdown: Semua / Aktif / Nonaktif
- **Filter Terhapus** — select dropdown: Tidak / Ya (tampilkan yg sudah dihapus)
- **Urutkan** — klik judul kolom untuk mengurutkan (nama, harga, status)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100
- **Bulk Action** — Aktifkan / Nonaktifkan / Hapus / Pulihkan / Export banyak paket sekaligus
- **Import Excel** — upload file, validasi per baris, insert batch
- **Export Excel** — download semua data atau selected via checkbox
- **Template Import** — download template kosong untuk diisi

---

## Permission & Aksi

Kalau user tidak punya izin, tombol disembunyikan dan backend route dikunci (403 Forbidden).

| Aksi | Izin Diperlukan | Single | Bulk |
|------|----------------|---------|------|
| **Lihat Daftar** | `paket.list` | — | — |
| **Tambah Paket** | `paket.create` | ✅ | — |
| **Edit Paket** | `paket.edit` | ✅ | — |
| **Detail Paket** | `paket.detail` | ✅ | — |
| **Hapus Paket** | `paket.delete` | ✅ | ✅ |
| **Pulihkan Paket** | `paket.restore` | ✅ | ✅ |
| **Aktifkan Paket** | `paket.edit` | — | ✅ |
| **Nonaktifkan Paket** | `paket.edit` | — | ✅ |
| **Export Excel** | `paket.export` | — | ✅ |
| **Import Excel** | `paket.import` | — | — |

---

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
| POST | `/operator-perusahaan/daftar-paket/bulk-restore` | `paket.restore` |
| GET | `/operator-perusahaan/daftar-paket/export` | `paket.export` |
| GET | `/operator-perusahaan/daftar-paket/export?ids=` | `paket.export` |
| GET | `/operator-perusahaan/daftar-paket/template` | `paket.import` |
| POST | `/operator-perusahaan/daftar-paket/import` | `paket.import` |

### Permissions (8 total)
```
paket.list    → Lihat halaman daftar paket
paket.create  → Tambah paket baru
paket.edit    → Edit paket + bulk aktif/nonaktif
paket.detail  → Lihat detail paket
paket.delete  → Hapus paket + bulk delete
paket.restore → Pulihkan paket + bulk restore
paket.export  → Download Excel
paket.import  → Upload Excel + download template
```

### Controller
`App\Http\Controllers\OperatorPerusahaan\PaketController`

### View
`resources/js/Pages/OperatorPerusahaan/DaftarPaket.vue`

### Model
`App\Models\InternetPackage`

---

## Testing

### E2E Testing: Playwright (NodeJS)

| Test File | Coverage |
|-----------|----------|
| `DaftarPaketCRUDTest.cjs` | Search, filter, sort, create, delete, restore, checklist, bulk actions |
| `DaftarPaketPermissionTest.cjs` | Granular permission check (HAS vs NOT HAS) |
| `DaftarPaketResponsiveTest.cjs` | Responsive & dark/light mode |

### CRUD Test Cases (DaftarPaketCRUDTest.cjs)

| # | Test | Deskripsi |
|---|------|-----------|
| 01 | `test_01_page_renders` | Page load, HTTP 200, content rendered |
| 02 | `test_02_search` | Pencarian by nama paket |
| 03 | `test_03_filter_status` | Filter Aktif/Nonaktif |
| 04 | `test_04_filter_terhapus` | Filter Terhapus (Ya/Tidak) |
| 05 | `test_05_sort_all_columns` | Sort semua kolom (asc/desc) |
| 06 | `test_06_create_paket` | Tambah paket baru |
| 07 | `test_07_delete_paket` | Soft delete via modal |
| 08 | `test_08_restore_paket` | Pulihkan paket yang dihapus |
| 09 | `test_09_checklist` | Checklist items |
| 10 | `test_10_bulk_delete` | Bulk delete via checkbox |
| 11 | `test_11_bulk_restore` | Bulk restore deleted items |
| 12 | `test_12_bulk_aktifkan` | Bulk aktifkan paket |
| 13 | `test_13_bulk_nonaktifkan` | Bulk nonaktifkan paket |

### Permission Test Cases (DaftarPaketPermissionTest.cjs)

| Permission | HAS (rbac.full) | NOT HAS (rbac.list) |
|------------|-----------------|---------------------|
| `paket.list` | HTTP 200, sidebar visible | HTTP 403 |
| `paket.create` | "Tambah Paket" button visible | Button hidden |
| `paket.edit` | Edit button + bulk Aktifkan/Nonaktifkan visible | Hidden |
| `paket.detail` | "Detail" button visible, modal opens | Hidden |
| `paket.delete` | "Hapus" button + bulk visible | Hidden |
| `paket.restore` | "Pulihkan" button + bulk visible | Hidden |
| `paket.export` | "Export" button visible | Hidden |
| `paket.import` | "Import" button + "Download Template" visible | Hidden |

### RBAC Users for Testing

| User | Email | Permissions |
|------|-------|-------------|
| Full Access | `rbac.full@rtrwnet.id` | Semua (8 permissions) |
| List Only | `rbac.list@rtrwnet.id` | `paket.list` only |
| No Access | `rbac.no@rtrwnet.id` | None |

Setup: `php tests/Browser/Playwright/setup-rbac-users.php`

### Responsive Test (DaftarPaketResponsiveTest.cjs)

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
| 0 | Kode Paket | Required, unique per company |
| 1 | Nama Paket | Required |
| 2 | Harga | Required, numeric |
| 3 | Billing Cycle | daily/weekly/monthly/yearly |
| 4 | Speed Down (kbps) | Numeric |
| 5 | Speed Up (kbps) | Numeric |
| 6 | Kuota (GB) | Integer |
| 7 | Unlimited | Ya/Tidak |
| 8 | Max Devices | Integer, nullable |
| 9 | FUP Quota Down | Integer, nullable |
| 10 | FUP Quota Up | Integer, nullable |
| 11 | FUP Speed Down | Numeric, nullable |
| 12 | FUP Speed Up | Numeric, nullable |
| 13 | Status | Aktif/Nonaktif |
| 14 | Deskripsi | String, nullable |

### Export Column Headers (16 kolom)

`Nama Paket, Harga, Billing Cycle, Speed Down, Speed Up, Kuota GB, Unlimited, Max Devices, FUP Quota Down, FUP Quota Up, FUP Speed Down, FUP Speed Up, Langganan Aktif, Estimasi Pendapatan, Status, Deskripsi`

---

## Model Fields

| Field | Type | Keterangan |
|-------|------|-----------|
| `id` | uuid | Primary key |
| `company_id` | uuid | FK ke companies |
| `code` | string | Kode paket (unique per company) |
| `name` | string | Nama paket |
| `price` | decimal(20,2) | Harga paket |
| `speed_down_kbps` | decimal(20,2) | Kecepatan download (kbps) |
| `speed_up_kbps` | decimal(20,2) | Kecepatan upload (kbps) |
| `quota_gb` | integer | Kuota dalam GB |
| `billing_cycle` | enum | daily, weekly, monthly, yearly |
| `max_devices` | integer, nullable | Maksimal perangkat |
| `is_unlimited` | boolean | Kuota unlimited |
| `is_active` | boolean | Status aktif |
| `fup_quota_down` | integer, nullable | Batas FUP download (GB) |
| `fup_quota_up` | integer, nullable | Batas FUP upload (GB) |
| `fup_speed_down_kbps` | decimal(20,2), nullable | Speed setelah FUP download |
| `fup_speed_up_kbps` | decimal(20,2), nullable | Speed setelah FUP upload |
| `description` | string, nullable | Deskripsi |
| SoftDeletes | — | Ada soft delete |
| blameable | — | created_by, updated_by, deleted_by, restored_by |