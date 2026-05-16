---
name: crud-table-standard
description: Standarisasi halaman CRUD dengan tabel data — mencakup pola table responsif, multi-column sort, search on-enter, filter status, pagination dengan page-length selector, modal create/edit/detail/delete, dan validasi form.
---

# CRUD Table Standard

Standar pembuatan halaman CRUD frontend di project ERP RT/RW Net (Laravel + Inertia + Vue 3 + Tailwind CSS 4 + Font Awesome 7).

## Struktur Komponen & Routing

Setiap halaman CRUD wajib memiliki:

```
resources/js/Pages/<Module>/<PageName>.vue
```

Gunakan `OperatorSaasLayout` (prefix `/operator-saas/`) untuk halaman operator SaaS.

**Tambahkan juga:**
1. **Route** di `routes/web.php` — `Route::get('/operator-saas/<slug>', fn() => Inertia::render('OperatorSaas/<PageName>'));`
2. **Sidebar menu** di `resources/js/Layouts/OperatorSaasLayout.vue` — tambahkan `{ label, href, icon }` ke array `menuItems`.

## Checklist Fitur Full

### 1. Layout Container
- [ ] Layout `<main>` pakai `py-6 px-4 sm:px-6 lg:px-8 w-full max-w-full overflow-x-hidden` — fluid container dengan padding responsif.
- [ ] Wrapper konten utama di layout wajib pakai `min-w-0` agar flex container tidak melebar mengikuti konten anak.
- [ ] Filter bar & header pakai `flex-col sm:flex-row` + `gap-3`.
- [ ] Filter pills pakai `flex gap-1 flex-wrap` + `whitespace-nowrap`.

### 1.5 Breadcrumbs (WAJIB untuk halaman non-dashboard)
- [ ] **Setiap halaman selain dashboard/beranda wajib punya breadcrumbs** di bagian atas konten.
- [ ] Format: `Home / Dashboard / Module` dengan `fa-home`, `fa-chevron-right` separator.
- [ ] Item terakhir bold tanpa link, item sebelumnya link abu-abu hover indigo.

```html
<nav class="flex items-center gap-1.5 text-sm">
  <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"><i class="fas fa-home"></i></Link>
  <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
  <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Dashboard</Link>
  <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
  <span class="text-gray-900 dark:text-white font-medium">Admin Perusahaan</span>
</nav>
```

### 2. Table
- [ ] **Responsif scroll horizontal** — `overflow-x-auto` + `min-w-[700px]` (sesuaikan jumlah kolom).
- [ ] **Whitespace nowrap** di cell pendek (email, status, telepon, aksi).
- [ ] **Empty state** — icon + "Tidak ada data...".
- [ ] **Avatar/inisial** — kolom nama menampilkan inisial huruf pertama atau icon.
- [ ] **Checkbox + Bulk Action** — kolom pertama checkbox. Bulk bar muncul saat ada item dipilih: Aktifkan (hijau), Nonaktifkan (amber), Hapus (merah).
- [ ] **Kolom No dihapus** — tidak perlu nomor urut baris.

### 3. Multi-Column Sort
- [ ] Klik header: tambah sort field `asc` → klik lagi toggle `desc` → klik lagi hapus.
- [ ] Tiap kolom aktif tampil **ikon arah + nomor urut** inline horizontal (bukan stacked vertikal).
- [ ] Kolom tidak di-sort: ikon `fa-sort` abu-abu samar.
- [ ] Sort additive (multi-kolom bisa di-stack).

**Template header sort:**
```html
<th @click="sort('nama')" class="px-4 py-3 ... cursor-pointer select-none ...">
  <span class="inline-flex items-center gap-1">
    Nama
    <span v-if="sortOrder('nama')" class="inline-flex items-center gap-0.5 text-indigo-500 dark:text-indigo-400">
      <i :class="['fas', sortIcon('nama'), 'text-[10px]']"></i>
      <span class="text-[10px] font-bold leading-none">{{ sortOrder('nama') }}</span>
    </span>
    <i v-else class="fas fa-sort text-[10px] text-gray-400 dark:text-gray-500"></i>
  </span>
</th>
```

```js
const sortFields = ref([]);
function sort(field) {
  const existing = sortFields.value.findIndex(s => s.field === field);
  if (existing !== -1) {
    if (sortFields.value[existing].dir === 'asc') sortFields.value[existing].dir = 'desc';
    else sortFields.value.splice(existing, 1);
  } else { sortFields.value.push({ field, dir: 'asc' }); }
}
function sortIcon(f) { const s = sortFields.value.find(s => s.field === f); return s ? (s.dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort'; }
function sortOrder(f) { const i = sortFields.value.findIndex(s => s.field === f); return i !== -1 ? i + 1 : null; }
```

### 4. Search & Filter
- [ ] **Search on Enter** — `searchInput` ref terpisah, apply ke `search` saat Enter atau klik tombol cari.
- [ ] **Clear ✕ button** di dalam search bar.
- [ ] **Status filter** — tombol pill group "Semua / Aktif / Nonaktif".
- [ ] **Dropdown filter (SearchableSelect)** — untuk opsi >10. Gunakan `<SearchableSelect>` (`@/Components/SearchableSelect.vue`). Fitur: search, infinite scroll, debounce 300ms.
- [ ] **Reset filter** — link merah muncul saat ada filter aktif.

### 4.5 SearchableSelect Usage
```html
<!-- Untuk filter bar -->
<SearchableSelect
  :model-value="perusahaanFilter"
  :options="perusahaanOptions"
  placeholder="Semua Perusahaan"
  @update:model-value="applyPerusahaanFilter"
/>

<!-- Untuk form CRUD -->
<SearchableSelect
  v-model="form.perusahaan"
  :options="perusahaanOptions"
  placeholder="— Pilih Perusahaan —"
/>
```
Props: `modelValue`, `options` (`[{value, label}]`), `placeholder`, `searchable`, `pageSize`, `debounceMs`.
Events: `@update:model-value`, `@search` (debounced), `@load-more`.

### 5. Pagination
- [ ] **Page length selector** — dropdown "Tampilkan 5/10/25/50/100" di kiri bawah.
- [ ] **Navigasi halaman** — `<< < [1] [2] [3] > >>` selalu tampil, disabled saat halaman pertama/terakhir.
- [ ] Halaman aktif di-highlight `bg-indigo-600 text-white`.
- [ ] Info "dari N data".

### 6. Bulk Action Bar
```html
<div v-if="selectedIds.length > 0" class="flex items-center justify-between px-4 py-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl shadow-sm">
  <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">
    <i class="fas fa-check-circle mr-1.5"></i> {{ selectedIds.length }} data dipilih
  </span>
  <div class="flex items-center gap-2">
    <button @click="bulkSetStatus('Aktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 text-white hover:bg-emerald-700"><i class="fas fa-check mr-1"></i> Aktifkan</button>
    <button @click="bulkSetStatus('Nonaktif')" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-amber-600 text-white hover:bg-amber-700"><i class="fas fa-ban mr-1"></i> Nonaktifkan</button>
    <button @click="bulkDelete()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-600 text-white hover:bg-red-700"><i class="fas fa-trash-alt mr-1"></i> Hapus</button>
  </div>
</div>
```

### 7. Modal CRUD
- [ ] **Create modal** — form dengan validasi, field wajib `*` merah.
- [ ] **Detail modal** — info lengkap + tombol "Edit".
- [ ] **Edit modal** — form pre-filled.
- [ ] **Delete modal** — konfirmasi dengan nama item.
- [ ] Semua modal pakai `<Teleport to="body">` + `bg-black/50 backdrop-blur-sm` + `<Transition name="modal">`.

### 8. Validasi Form
```js
function validateForm() {
  const errors = {};
  if (!form.value.nama.trim()) errors.nama = 'Nama wajib diisi';
  if (!form.value.email.trim()) errors.email = 'Email wajib diisi';
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) errors.email = 'Format email tidak valid';
  if (!form.value.no_telp.trim()) errors.no_telp = 'No. telepon wajib diisi';
  formErrors.value = errors;
  return Object.keys(errors).length === 0;
}
```

### 9. Telepon Split (kode_negara + no_telp)
- [ ] Field telepon dipisah: dropdown `kode_negara` (`+62`, `+60`, dll) + input `no_telp`.
- [ ] Di tabel dan detail: `formatTelepon(item) → '+62 81234567890'`.

## Pola State Lengkap

```js
const items = ref([{ id, nama, email, status, kode_negara, no_telp, created_at }]);
const searchInput = ref('');
const search = ref('');
const statusFilter = ref('');
const perusahaanFilter = ref(''); // jika ada filter dropdown tambahan
const sortFields = ref([]);
const selectedIds = ref([]);
const selectAll = ref(false);
const currentPage = ref(1);
const perPage = ref(5);
const perPageOptions = [5, 10, 25, 50, 100];
const selectedItem = ref(null);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const form = ref({});
const formErrors = ref({});
const nextId = ref(6);
const kodeNegaraList = ['+62', '+60', '+65', '+66', '+84', '+1', '+44', '+81', '+86'];
```

## Design Tokens

| Element | Light | Dark |
|---------|-------|------|
| Table bg | `bg-white` | `dark:bg-gray-800` |
| Table border | `border-gray-200` | `dark:border-gray-700` |
| THead bg | `bg-gray-50` | `dark:bg-gray-900` |
| Row hover | `hover:bg-gray-50` | `dark:hover:bg-gray-700/30` |
| Text utama | `text-gray-900` | `dark:text-white` |
| Text sekunder | `text-gray-600` | `dark:text-gray-400` |
| Input bg | `bg-white` | `dark:bg-gray-900` |
| Input border | `border-gray-300` | `dark:border-gray-600` |
| Focus ring | `focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500` | sama |
| Primary btn | `bg-indigo-600 hover:bg-indigo-700 text-white` | sama |
| Status Aktif | `bg-emerald-100 text-emerald-700` | `dark:bg-emerald-900/30 dark:text-emerald-400` |
| Status Nonaktif | `bg-red-100 text-red-700` | `dark:bg-red-900/30 dark:text-red-400` |
| Modal backdrop | `bg-black/50 backdrop-blur-sm` | sama |
| Modal bg | `bg-white` | `dark:bg-gray-800` |

## Referensi Implementasi Lengkap

- `resources/js/Pages/OperatorSaas/AdminPerusahaan.vue` — CRUD dengan avatar inisial, password field
- `resources/js/Pages/OperatorSaas/Perusahaan.vue` — CRUD dengan alamat, tanpa password
- `resources/js/Pages/OperatorSaas/RolePerusahaan.vue` — CRUD dengan SearchableSelect, filter dropdown
- `resources/js/Components/SearchableSelect.vue` — Komponen reusable searchable dropdown
- `resources/js/Layouts/OperatorSaasLayout.vue` — Sidebar menu + layout responsif
- `routes/web.php` — Route pattern untuk halaman operator SaaS

## Konvensi Testing

### JANGAN PERNAH:
- ❌ Membuat file `.env.testing` — project ini development di lokal, server dev dan production terpisah. Pakai langsung `.env` yang ada.
- ❌ Bypass CSRF secara global di production code — CSRF hanya di-bypass untuk **feature test** (PHPUnit) via `$this->withoutMiddleware([VerifyCsrfToken::class])` di `TestCase.php`. Browser test (Dusk) tetap menggunakan CSRF normal yang otomatis di-handle oleh Laravel Dusk.
- ❌ Mengubah `bootstrap/app.php` untuk menonaktifkan CSRF middleware secara global.

### Struktur Test
```
tests/
  Feature/            → PHPUnit HTTP tests (CSRF di-bypass di TestCase)
    Auth/             → Authentication tests
    OperatorSaas/     → SaaS admin CRUD tests
    OperatorPerusahaan/ → Company admin CRUD tests
  Browser/            → Laravel Dusk browser tests (CSRF normal via DUSK_ENABLED)
    Feature/
      OperatorSaas/   → SaaS admin browser tests
      OperatorPerusahaan/ → Company admin browser tests
      Karyawan/       → Employee browser tests
      Pelanggan/      → Customer browser tests
    screenshots/      → Dusk screenshots organized by portal/module/test
```

### Sidebar Menu Harus Di-cover Browser Test
Setiap item sidebar wajib memiliki minimal 1 file browser test. Sidebar mengikuti file layout:
- `resources/js/Layouts/OperatorSaasLayout.vue`
- `resources/js/Layouts/OperatorPerusahaanLayout.vue`
- `resources/js/Layouts/KaryawanLayout.vue`
- `resources/js/Layouts/CustomerLayout.vue`

### Pola Browser Test (Dusk)
- Auth Operator SaaS: `$browser->loginAs(AdminSaas::first(), 'web')`
- Auth Operator Perusahaan: `$browser->loginAs(AdminCompany::first(), 'admin-company')`
- Auth Karyawan: `$browser->loginAs(Employee::first(), 'employee')`
- Auth Pelanggan: `$browser->loginAs(Customer::first(), 'customer')`
- Screenshot path: `portal/module/##-test-name/##-step.png`
- Setiap test method: `test_01_page_renders`, `test_02_search`, dst.
- WAJIB baca file Vue terkait dulu untuk dapatkan text selector yang tepat.

### Report Excel
Jalankan `.\parallel-dusk.ps1` untuk menjalankan semua Dusk test parallel dan menghasilkan `tests/Browser/dusk-output/dusk-report-YYYYMMDD-HHMMSS.csv` dengan format:
| jenis web | lokasi file test case | method test case | total assertion | status | description |

Nilai `jenis web`: `web operator saas`, `web operator perusahaan`, `web karyawan`, `web pelanggan`.

CSV memiliki summary rows di bagian bawah: subtotal per web (passed/failed/assertions) + GRAND TOTAL.

### Parallel Dusk Options
```powershell
# Default: 4 worker, semua 4 portal
.\parallel-dusk.ps1

# 8 worker (file-level parallel, lebih cepat)
.\parallel-dusk.ps1 -MaxWorkers 8

# Hanya portal tertentu
.\parallel-dusk.ps1 -Folders "OperatorSaas,Karyawan"

# Kombinasi
.\parallel-dusk.ps1 -MaxWorkers 6 -Folders "OperatorPerusahaan,Pelanggan"
```
Setiap worker menjalankan subset test file yang didistribusi round-robin. Output disimpan di `tests/Browser/dusk-output/worker-N.log` dan `worker-N.xml`.

### Parallel Feature Test
```powershell
# Default: interactive prompt (input jumlah worker)
.\parallel-test.ps1

# 8 worker (file-level parallel, ~5× lebih cepat)
.\parallel-test.ps1 -MaxWorkers 8

# 16 worker
.\parallel-test.ps1 -MaxWorkers 16
```
Setiap worker menjalankan subset `*Test.php` dari `tests/Feature/` (Auth, OperatorSaas, OperatorPerusahaan) yang didistribusi round-robin. Output disimpan di `tests/Browser/dusk-output/ftest-wN.log` dan `ftest-wN.xml`. CSV report di `ftest-report-YYYYMMDD-HHMMSS.csv`.

**ALWAYS use `.\parallel-test.ps1` ketika menjalankan feature tests. Jangan `php artisan test` langsung karena single worker lambat (`~68s` vs `~15s` dengan 8 worker).**

### Testing Conventions
- ❌ JANGAN buat file `.env.testing`
- ❌ JANGAN panggil `php artisan test` langsung — PAKAI `.\parallel-test.ps1`
- ❌ JANGAN bypass CSRF global di production
- ✅ TestCase bypass CSRF via `withoutMiddleware([PreventRequestForgery::class])`
- ✅ `bootstrap/app.php` remove `PreventRequestForgery` di non-production
- ✅ Browser test login pakai `loginAs(AdminSaas::first(), 'web')` dll
- ✅ Setiap sidebar item wajib punya minimal 1 browser test
