# Halaman Konfigurasi Perusahaan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/konfigurasi-perusahaan`

> **Perbedaan dengan halaman SaaS**: Halaman ini menyimpan konfigurasi **perusahaan (scoped by `company_id`)** di tabel `company_configs`. Halaman **SaaS** di `/operator-saas/konfigurasi` menyimpan konfigurasi **global** di tabel `saas_configs`. Keduanya punya fitur CRUD + import/export Excel + soft-delete yang serupa, tapi dipisah karena konteks multi-tenant.

## Fungsi
Halaman untuk **mengelola konfigurasi perusahaan** dalam format key-value (disimpan di tabel `company_configs`, scoped by `company_id` user yang login).
Tipe konfigurasi yang didukung: `text`, `file`, `number`, `boolean`. Tiap entri memiliki field opsional `description` untuk penjelasan.

## Fitur
- **Tampilan daftar konfigurasi** dengan tabel (Key, Type, Value, Description, Tgl Dibuat)
- **Pencarian** berdasarkan Key atau Value
- **Filter tipe** (Semua / Teks / File / Angka / Boolean)
- **Filter terhapus** (Aktif / Terhapus) — toggle untuk melihat data soft-deleted
- **Sort** multi-kolom (Key, Type, Tgl Dibuat)
- **Pagination** dengan page-length selector (5/10/25/50/100)
- **CRUD lengkap** — Create / Read (Detail) / Update / Delete + Bulk Delete
- **Soft delete + Restore** — data terhapus dapat dipulihkan via filter "Terhapus" + bulk "Pulihkan"
- **Value masking + Show/Hide toggle** — value default masked (`••••••••`), klik eye untuk reveal (di datatable, detail modal, edit modal, create modal)
- **Import Excel** (.xlsx, .csv) + **Download template** + **Export Excel** (semua / selected / ber-filter)

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat daftar** | `konfigurasi-perusahaan.list` | Melihat halaman + sidebar menu |
| **Tambah** | `konfigurasi-perusahaan.create` | Buka modal Create, simpan via POST (auto-set `company_id`) |
| **Edit** | `konfigurasi-perusahaan.edit` | Buka modal Edit (value masked default), simpan via PUT |
| **Hapus** | `konfigurasi-perusahaan.delete` | Buka modal Delete konfirmasi, hapus via DELETE (soft delete) |
| **Bulk Hapus** | `konfigurasi-perusahaan.delete` | Hapus banyak data sekaligus (soft delete) |
| **Pulihkan** | `konfigurasi-perusahaan.restore` | Pulihkan data terhapus (saat filter "Terhapus" aktif, dari bulk action) |
| **Export** | `konfigurasi-perusahaan.export` | Download Excel semua / selected / ber-filter |
| **Import** | `konfigurasi-perusahaan.import` | Upload file Excel/CSV; download template |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/konfigurasi-perusahaan` | `konfigurasi-perusahaan.index` | `auth:admin-company` | `konfigurasi-perusahaan.list` |
| POST | `/operator-perusahaan/konfigurasi-perusahaan` | `konfigurasi-perusahaan.store` | `auth:admin-company` | `konfigurasi-perusahaan.create` |
| PUT | `/operator-perusahaan/konfigurasi-perusahaan/{companyConfig}` | `konfigurasi-perusahaan.update` | `auth:admin-company` | `konfigurasi-perusahaan.edit` |
| DELETE | `/operator-perusahaan/konfigurasi-perusahaan/{companyConfig}` | `konfigurasi-perusahaan.destroy` | `auth:admin-company` | `konfigurasi-perusahaan.delete` |
| POST | `/operator-perusahaan/konfigurasi-perusahaan/bulk-delete` | `konfigurasi-perusahaan.bulkDelete` | `auth:admin-company` | `konfigurasi-perusahaan.delete` |
| POST | `/operator-perusahaan/konfigurasi-perusahaan/bulk-restore` | `konfigurasi-perusahaan.bulkRestore` | `auth:admin-company` | `konfigurasi-perusahaan.restore` |
| GET | `/operator-perusahaan/konfigurasi-perusahaan/export` | `konfigurasi-perusahaan.export` | `auth:admin-company` | `konfigurasi-perusahaan.export` |
| GET | `/operator-perusahaan/konfigurasi-perusahaan/template` | `konfigurasi-perusahaan.template` | `auth:admin-company` | `konfigurasi-perusahaan.export` |
| POST | `/operator-perusahaan/konfigurasi-perusahaan/import` | `konfigurasi-perusahaan.import` | `auth:admin-company` | `konfigurasi-perusahaan.import` |

### Controller
`App\Http\Controllers\OperatorPerusahaan\KonfigurasiPerusahaanController`

Methods:
- `index(Request)` — list + filter + sort + paginate, **scoped by `company_id` user** (Inertia)
- `store(Request)` — validasi + create, **auto-set `company_id` dari auth user** (404 jika ditimpa)
- `update(Request, CompanyConfig $companyConfig)` — validasi + update, **404 jika `company_id` tidak match** (anti cross-company)
- `destroy(Request, CompanyConfig $companyConfig)` — soft delete per item
- `bulkDelete(Request)` — soft delete banyak, scoped by company
- `bulkRestore(Request)` — restore dari trashed, scoped by company
- `export(Request)` — unduh `.xlsx` (semua / by-id / by-filter / by-terhapus), scoped by company
- `downloadTemplate()` — unduh template `.xlsx`
- `import(Request)` — parse `.xlsx`/`.csv`, key unique per-company (skip jika sudah ada di perusahaan ini)

### View
`resources/js/Pages/OperatorPerusahaan/KonfigurasiPerusahaan.vue`

Modals: Detail, Create/Edit (gabung, switch by state), Delete, Import.

Form field `Value` bersifat dinamis mengikuti `Type`:
- `text`/`file` → `<textarea>` (multi-line)
- `number` → `<input type="number">`
- `boolean` → `<select>` `true`/`false`

Value masking: per-row Set `revealedIds` untuk datatable; `createValueVisible`/`editValueVisible`/`detailValueVisible` untuk masing-masing modal. Default masked (eye toggle to reveal).

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\CompanyConfig` | `company_configs` | Model utama — konfigurasi per perusahaan. Field: `id` (UUIDv7), `company_id` (FK ke `companies`), `key` (unique per company), `type`, `value`, `description` (nullable), timestamps, blameable, soft delete. Trait: `HasUuidV7`, `HasBlameable`, `HasSoftDelete`. Relasi: `company()`. |
| `App\Models\SaasConfig` | `saas_configs` | **Bukan** untuk halaman ini — halaman SaaS global. Halaman ini hanya baca `company_configs`. |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_224942_create_company_configs_table` | `company_configs` (create, type enum text\|file) |
| `2026_06_06_140000_rename_descripton_to_description_in_company_configs_table` | rename kolom `descripton` → `description` |
| `2026_06_06_140001_add_soft_delete_to_company_configs_table` | tambah `deleted_at`, `deleted_by_*`, `restored_at`, `restored_by_*` |
| `2026_06_06_140002_expand_company_configs_type_enum` | expand `type` enum jadi `text\|file\|number\|boolean` |

### Permissions (enum + seeder)
Daftar permission di `app/Enums/Permissions.php`:
- `konfigurasi-perusahaan.list`
- `konfigurasi-perusahaan.create`
- `konfigurasi-perusahaan.edit`
- `konfigurasi-perusahaan.delete`
- `konfigurasi-perusahaan.restore`
- `konfigurasi-perusahaan.export`
- `konfigurasi-perusahaan.import`

Didaftarkan di `Permissions::forScope('admin_perusahaan')` — role `Admin` default perusahaan memiliki ketujuh permission ini.

### Multi-tenant Safety
- **Index** → `where('company_id', $companyId)` di query utama
- **Store** → `Rule::unique('company_configs')->where('company_id', $companyId)` (key unique per company, bukan global)
- **Update/Destroy** → 404 jika `companyConfig->company_id !== auth()->user()->company_id`
- **BulkDelete/BulkRestore/Export/Import** → semua query di-scope by `company_id`
- **Restore** → `where('company_id', $companyId)->whereIn('id', $ids)->restore()`

### Test Case
| File | Engine | Scope |
|------|--------|-------|
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/QuickVerifyKonfigurasiPerusahaan.cjs` | Playwright (Node, headed) | Smoke + dark + responsive |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/KonfigurasiPerusahaanCRUDHeaded.cjs` | Playwright (Node, headed) | Full E2E CRUD + masking + soft-delete + import/export Excel + dark mode + responsive (mobile/tablet/desktop) |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/VerifyKonfigurasiV2.cjs` | Playwright (Node, headed) | Verifikasi v2: terhapus filter, value masking, show/hide, row click |

## Format Excel

### Template
| Key (wajib unique per company) | Type (text\|file\|number\|boolean) | Value | Description |
|---|---|---|---|
| `company.tagline` | `text` | `ISP terbaik di kota Anda` | Tagline landing page |
| `company.max_devices` | `number` | `5` | Maksimum perangkat per pelanggan |
| `company.is_active` | `boolean` | `true` | Status aktif perusahaan |

### Import
- Baris pertama = header.
- Kolom wajib: `Key` (unique per company, skip jika sudah ada di perusahaan ini), `Type` (valid: `text|file|number|boolean`).
- `Value` & `Description` sesuai kebutuhan.
- Lihat pesan `success` / `warning` di toast untuk ringkasan hasil.

### Export
- Mengikuti filter aktif (search + type + terhapus).
- Kolom: Key, Type, Value, Description, Tgl Dibuat, Tgl Diperbarui.
- Atau gunakan `Export Selected` (centang baris dulu) untuk subset.
