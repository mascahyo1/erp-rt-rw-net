# Halaman Tagihan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/tagihan`

## Fungsi
Halaman untuk mengelola **data tagihan bulanan** pelanggan.
Tagihan dibuat secara manual, via import Excel, atau via generate massal.

---

## Form Create / Edit

| Field | Type | Wajib | Keterangan |
|-------|------|-------|-----------|
| Langganan | select | ✅ | Pilih langganan customer |
| Awal Usage | date | — | Tanggal mulai periode penggunaan |
| Akhir Usage | date | — | Tanggal akhir periode penggunaan |
| Total Tagihan | number | ✅ | Jumlah tagihan sebelum diskon & pajak |
| Diskon | number | — | Jumlah diskon |
| Pajak | number | — | Jumlah pajak |
| Jatuh Tempo | date | — | Batas waktu pembayaran |
| Deskripsi | text | — | Keterangan tambahan |

---

## Kolom Datatable

| Kolom | Sortable | Keterangan |
|-------|----------|-----------|
| ☐ | — | Checkbox bulk select |
| No. Invoice | ✅ | Font-mono |
| Pelanggan | — | Initial avatar + nama |
| Awal Usage | ✅ | Tanggal mulai (center) |
| Akhir Usage | ✅ | Tanggal akhir (center) |
| Total | ✅ | Right-aligned, format currency |
| Diskon | — | Right-aligned |
| Pajak | — | Right-aligned |
| Grand Total | — | Right-aligned, bold |
| Jatuh Tempo | ✅ | Tanggal |
| Status | ✅ | Badge: Lunas (green) / Belum Bayar (amber) |
| Aksi | — | Detail, Edit, Hapus / Pulihkan |

---

## Fitur

- **Tabel daftar tagihan** — menampilkan semua tagihan dengan detail lengkap
- **Pencarian** — cari berdasarkan no. invoice atau nama pelanggan
- **Filter Status** — Semua / Lunas / Belum Bayar / Kadaluarsa
- **Filter Terhapus** — Tidak / Ya
- **Urutkan** — semua kolom sortable (asc/desc toggle)
- **Pagination** — 5, 10, 25, 50, 100 per halaman
- **Bulk Action** — Hapus / Pulihkan massal via checkbox
- **CRUD** — Create, Edit, Detail, Delete, Restore per item
- **Generate Massal** — buat tagihan untuk semua langganan aktif pada periode tertentu (tahun + bulan)
- **Import Excel** — upload file .xlsx/.csv, validasi per baris, insert batch (chunk 500)
- **Export Excel** — download semua data atau selected saja
- **Template Import** — download template kosong

---

## Generate Massal

| Field | Type | Wajib | Keterangan |
|-------|------|-------|-----------|
| Tahun | number | ✅ | 2020-2099 |
| Bulan | select | ✅ | 1-12 |
| Jatuh Tempo | date | — | Default: 30 hari setelah akhir bulan |

**Rules:**
- Hanya membuat tagihan untuk langganan yang **status aktif** (`internet_status = active`)
- Tidak membuat duplikat — jika tagihan untuk periode tersebut sudah ada, skip
- Menggunakan `billing_amount` dari langganan sebagai `total_amount`

---

## Import / Export Excel

### Aturan Wajib
1. **Tidak ada angka notasi ilmiah** — gunakan `setCellValueExplicit` dengan `TYPE_STRING`
2. **Export:** Header bold, auto-width, border tipis
3. **Import:** Validasi per baris, insert batch (chunk 500)
4. **Template:** Download via `/template`

### Import Column Mapping (8 kolom)

| Index | Field | Keterangan |
|-------|-------|-----------|
| 0 | No. Langganan | Required — harus ada di `cust_internets.account_number` |
| 1 | Awal Usage | Format YYYY-MM-DD, nullable |
| 2 | Akhir Usage | Format YYYY-MM-DD, nullable |
| 3 | Total | Numeric, default 0 |
| 4 | Diskon | Numeric, default 0 |
| 5 | Pajak | Numeric, default 0 |
| 6 | Jatuh Tempo | Format YYYY-MM-DD, nullable |
| 7 | Deskripsi | String, nullable |

### Export Column Headers (11 kolom)

`No. Invoice, Pelanggan, No. Langganan, Awal Usage, Akhir Usage, Total, Diskon, Pajak, Grand Total, Jatuh Tempo, Status`

---

## Permission & Aksi

| Aksi | Izin Diperlukan | Single | Bulk |
|------|----------------|---------|------|
| **Lihat Daftar** | `tagihan.list` | — | — |
| **Tambah Tagihan** | `tagihan.create` | ✅ | — |
| **Generate Massal** | `tagihan.create` | ✅ | — |
| **Import Excel** | `tagihan.create` | — | — |
| **Edit Tagihan** | `tagihan.edit` | ✅ | — |
| **Detail Tagihan** | `tagihan.detail` | ✅ | — |
| **Hapus Tagihan** | `tagihan.delete` | ✅ | ✅ |
| **Pulihkan Tagihan** | `tagihan.restore` | ✅ | ✅ |
| **Export Excel** | `tagihan.export` | — | ✅ |
| **Export PDF** | `tagihan.export` | ✅ | — | (termasuk **logo perusahaan** light di header) |
| **Export Word** | `tagihan.export` | ✅ | — |
| **Download Template** | `tagihan.create` | — | — |

---

## Teknis

### Route

| Method | URI | Permission |
|--------|-----|------------|
| GET | `/operator-perusahaan/tagihan` | `tagihan.list` |
| POST | `/operator-perusahaan/tagihan` | `tagihan.create` |
| PUT | `/operator-perusahaan/tagihan/{custInternetInvc}` | `tagihan.edit` |
| DELETE | `/operator-perusahaan/tagihan/{custInternetInvc}` | `tagihan.delete` |
| PATCH | `/operator-perusahaan/tagihan/{id}/restore` | `tagihan.restore` |
| POST | `/operator-perusahaan/tagihan/bulk-delete` | `tagihan.delete` |
| POST | `/operator-perusahaan/tagihan/bulk-restore` | `tagihan.restore` |
| POST | `/operator-perusahaan/tagihan/bulk-status` | `tagihan.edit` |
| GET | `/operator-perusahaan/tagihan/export` | `tagihan.export` |
| GET | `/operator-perusahaan/tagihan/export?ids=` | `tagihan.export` |
| GET | `/operator-perusahaan/tagihan/{id}/export-pdf` | `tagihan.export` |
| GET | `/operator-perusahaan/tagihan/{id}/export-word` | `tagihan.export` |
| GET | `/operator-perusahaan/tagihan/template` | `tagihan.create` |
| POST | `/operator-perusahaan/tagihan/import` | `tagihan.create` |
| POST | `/operator-perusahaan/tagihan/generate` | `tagihan.create` |

### Permissions (11 total)
```
tagihan.list     → Lihat halaman daftar tagihan
tagihan.create   → Tambah, generate, import, template
tagihan.edit     → Edit tagihan + bulk status
tagihan.detail   → Lihat detail tagihan
tagihan.delete   → Hapus tagihan + bulk delete
tagihan.restore  → Pulihkan tagihan + bulk restore
tagihan.export   → Export Excel
tagihan.import   → (via create)
tagihan.generate → Generate massal
```

### Controller
`App\Http\Controllers\OperatorPerusahaan\TagihanController`

### Downstream — Logo Perusahaan di Invoice PDF & Word
Invoice PDF (`exportPdf`) dan Word (`exportWord`) otomatis memuat **logo perusahaan light** di header (diambil dari kolom `companies.logo` yang diupload di halaman [Perusahaan](halaman-perusahaan.md) atau [Perusahaan Saya](perusahaan-saya.md)). Sumber logo dibaca via `CompanyConfig::getLogo($companyId)` yang:
1. Pertama cek `companies.logo` (kolom terbaru)
2. Fallback ke `company_configs.value WHERE key = 'logo'` (legacy)

Logo diambil via `Storage::disk('minio')->url($path)` lalu di-proxy lewat `file.proxy` route (agar DomPDF / PhpWord bisa fetch dengan auth).

**Kompresi logo:** JPG/PNG/WebP yang diupload otomatis dikompres ke WebP oleh `FileUploadService::processLogo()`. SVG disimpan apa adanya. Lihat: [Perusahaan Saya — Field Logo](perusahaan-saya.md#field-logo-di-halaman-ini).

> **Catatan:** Karena kertas biasanya putih, PDF/Word hanya menggunakan versi **light** logo. Untuk versi dark, lihat di halaman CRUD Perusahaan.

### Known Pre-existing Issues (belum fix, bukan dari fitur logo)

| # | Issue | Dampak di halaman ini |
|---|-------|----------------------|
| 1 | PHP tidak parse `multipart/form-data` body untuk PUT/PATCH/DELETE | Form edit tagihan dengan upload file (jika ada) akan gagal validasi |
| 2 | Inertia Laravel v2 tidak set `X-Inertia: true` di response redirect | Setelah edit/hapus tagihan, form tidak auto-close, toast tidak auto-fire |

Lihat detail + rekomendasi fix di [Perusahaan Saya — Known Pre-existing Issues](perusahaan-saya.md#known-pre-existing-issues-belum-fix-bukan-dari-fitur-logo).

### View
`resources/js/Pages/OperatorPerusahaan/Tagihan.vue`

### Model
`App\Models\CustInternetInvc`

---

## Model Fields

| Field | Type | Keterangan |
|-------|------|-----------|
| `id` | uuid | Primary key |
| `cust_internet_id` | uuid | FK ke cust_internets |
| `invoice_number` | string | Unique, format: INV-YYYYMMDD-XXXX |
| `usage_start_date` | date | Tanggal mulai usage (nullable) |
| `usage_end_date` | date | Tanggal akhir usage (nullable) |
| `total_amount` | decimal | Subtotal sebelum diskon/pajak |
| `discount_amount` | decimal | Diskon |
| `tax_amount` | decimal | Pajak |
| `grand_total` | decimal | Final amount |
| `due_date` | date | Jatuh tempo |
| `paid_at` | datetime | Waktu pembayaran (nullable) |
| `payment_status` | enum | paid / unpaid / overdue / rejected |
| `status` | enum | paid / unpaid / cancelled / rejected (legacy) |
| `status_description` | text | Keterangan status (nullable) |
| `status_reason` | text | Alasan/status (nullable) |
| `description` | text | Deskripsi tagihan (nullable) |
| SoftDeletes | — | Ada soft delete |
| blameable | — | created_by, updated_by, deleted_by, restored_by |

---

## Testing

### E2E Testing: Playwright (NodeJS)

| Test File | Coverage |
|-----------|----------|
| `TagihanCRUDTest.cjs` | Search, filter, sort, create, edit, delete, restore, checklist, bulk actions |
| `TagihanImportExportTest.cjs` | Generate, import, export, template download |
| `TagihanPermissionTest.cjs` | Granular permission check (HAS vs NOT HAS) |

### CRUD Test Cases (TagihanCRUDTest.cjs)

| # | Test | Deskripsi |
|---|------|-----------|
| 01 | `test_01_page_renders` | Page load, HTTP 200, content rendered |
| 02 | `test_02_search` | Pencarian by no. invoice |
| 03 | `test_03_filter_status` | Filter Lunas/Belum Bayar |
| 04 | `test_04_filter_terhapus` | Filter Terhapus (Ya/Tidak) |
| 05 | `test_05_sort_all_columns` | Sort semua kolom (asc/desc) |
| 06 | `test_06_create_tagihan` | Tambah tagihan baru |
| 07 | `test_07_edit_tagihan` | Edit tagihan |
| 08 | `test_08_delete_tagihan` | Hapus via modal |
| 09 | `test_09_restore_tagihan` | Pulihkan yang dihapus |
| 10 | `test_10_checklist` | Checklist items |
| 11 | `test_11_bulk_delete` | Bulk delete via checkbox |
| 12 | `test_12_bulk_restore` | Bulk restore deleted items |

### Import/Export Test Cases (TagihanImportExportTest.cjs)

| # | Test | Deskripsi |
|---|------|-----------|
| 01 | `test_01_generate_modal_opens` | Modal generate tampil |
| 02 | `test_02_generate_submit` | Submit generate massal |
| 03 | `test_03_download_template` | Download template Excel |
| 04 | `test_04_import_modal_opens` | Modal import tampil |
| 05 | `test_05_import_file` | Upload dan import file |
| 06 | `test_06_export_all` | Export semua data |
| 07 | `test_07_export_selected` | Export selected via checkbox |
| 08 | `test_08_bulk_status_lunas` | Bulk set Lunas |
| 09 | `test_09_bulk_status_belum` | Bulk set Belum Bayar |

### Permission Test Cases (TagihanPermissionTest.cjs)

| Permission | HAS (rbac.full) | NOT HAS (rbac.list) |
|------------|-----------------|---------------------|
| `tagihan.list` | HTTP 200, sidebar visible | HTTP 403 |
| `tagihan.create` | Generate/Import/Template buttons visible | Hidden |
| `tagihan.edit` | Edit button + bulk status visible | Hidden |
| `tagihan.delete` | Delete button + bulk delete visible | Hidden |
| `tagihan.restore` | Restore button + bulk restore visible | Hidden |
| `tagihan.export` | Export button visible | Hidden |

### RBAC Users for Testing

| User | Email | Permissions |
|------|-------|-------------|
| Full Access | `rbac.full@rtrwnet.id` | Semua (11 permissions) |
| List Only | `rbac.list@rtrwnet.id` | `tagihan.list` only |
| No Access | `rbac.no@rtrwnet.id` | None |