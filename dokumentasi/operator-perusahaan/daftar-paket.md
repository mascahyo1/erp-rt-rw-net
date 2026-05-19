# Daftar Paket
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/daftar-paket`

## Fungsi
Halaman untuk **mengelola paket layanan internet** yang tersedia untuk dipilih pelanggan.
Admin perusahaan dapat menambah, mengubah, menghapus, dan memulihkan paket internet termasuk konfigurasi FUP (Fair Usage Policy).

---

## Form Create / Edit / Detail

| Field | Type | Wajib | Keterangan |
|-------|------|-------|-----------|
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
| Nama Paket | ✅ | Nama + avatar initial |
| Harga | ✅ | Format Rp |
| Speed | — | Download ↓ / Upload ↑ kbps |
| Quota | — | Quota GB (+ Unlimited) |
| Billing Cycle | ✅ | Harian / Mingguan / Bulanan / Tahunan |
| Langganan Aktif | — | Jumlah langganan aktif pakai paket ini |
| Estimasi Pendapatan | — | Rumus: Langganan Aktif × Harga |
| Status | ✅ | Badge: Aktif (hijau) / Nonaktif (merah) / Terhapus (merah dicoret) |
| Aksi | — | Detail, Edit, Hapus / Pulihkan |

---

## Import / Export Excel

Menggunakan **PhpSpreadsheet** (`phpoffice/phpspreadsheet`).

### Aturan Wajib
1. **Tidak ada angka notasi ilmiah.** Semua cell numerik yang berpotensi panjang (telepon, NIK, nomor akun) wajib pakai:
   ```php
   $sheet->setCellValueExplicit(
       $this->excelColumn($col++) . $row,
       trim($value) ?: '-',
       \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
   );
   ```
2. **Export:** Download file `.xlsx` dengan format rapi — header bold, auto-width, border tipis.
3. **Import:** Upload file `.xlsx` / `.csv`, validasi per baris, insert batch (chunk 500 baris), return jumlah sukses + error rows.
4. **Template:** Endpoint download template kosong dengan header kolom sesuai format import.

### Permission Import / Export

| Permission | Keterangan |
|-----------|------------|
| `paket.export` | Download export Excel (semua / selected via checkbox) |
| `paket.import` | Upload import Excel + download template import |

### Route Import / Export

| Method | URI | Permission | Keterangan |
|--------|-----|-----------|------------|
| GET | `/operator-perusahaan/daftar-paket/export` | `paket.export` | Download semua paket (.xlsx) |
| GET | `/operator-perusahaan/daftar-paket/export?ids=` | `paket.export` | Download selected via checkbox |
| GET | `/operator-perusahaan/daftar-paket/template` | `paket.import` | Download template import kosong (nebeng import) |
| POST | `/operator-perusahaan/daftar-paket/import` | `paket.import` | Upload + proses file import |

### Controller Methods (PaketController)

```
index()           → list + filter + sort + pagination
store()           → create
update()          → edit
destroy()         → soft delete
restore()         → restore
bulkDelete()      → bulk soft delete
bulkToggleStatus()→ bulk aktif/nonaktif
bulkRestore()     → bulk restore
export()          → download Excel semua data
export() + ?ids=  → download Excel selected
template()        → download template import kosong
import()          → upload + validasi + insert batch
```

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
| **Export Excel** | `paket.export` | Download Excel — semua data atau selected via checkbox |
| **Import Excel** | `paket.import` | Upload .xlsx/.csv, validasi, insert batch + download template |

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

### Permission Enum (tambahan)

Tambahkan di `app/Enums/Permissions.php`:

```php
case PaketExport  = 'paket.export';
case PaketImport  = 'paket.import';
```

Dan di method `forScope('admin_perusahaan')`:

```php
self::PaketExport->value,
self::PaketImport->value,
```

### Migration
`database/migrations/2026_05_11_141134_create_internet_packages_table.php`

### Test Case
| File | Keterangan |
|------|-----------|
| `tests/Browser/Feature/OperatorPerusahaan/DaftarPaketViewTest.php` | Browser view test — render + kolom |
| `tests/Browser/Feature/OperatorPerusahaan/DaftarPaketCRUDTest.php` | Browser CRUD test — search, filter, sort, delete, bulk delete, langganan+estimasi |
| `tests/Browser/Feature/OperatorPerusahaan/DaftarPaketImportExportTest.php` | Browser import/export test — template, export, import modal |
| `tests/Browser/Feature/OperatorPerusahaan/DaftarPaketPermissionTest.php` | Granular permission test — list, create, export, import |
