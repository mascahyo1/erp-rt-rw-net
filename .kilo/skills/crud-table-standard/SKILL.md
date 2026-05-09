---
name: crud-table-standard
description: Standarisasi halaman CRUD dengan tabel data — mencakup pola table responsif, multi-column sort, search on-enter, filter status, pagination dengan page-length selector, modal create/edit/detail/delete, dan validasi form.
---

# CRUD Table Standard

Standar pembuatan halaman CRUD frontend di project ERP RT/RW Net (Laravel + Inertia + Vue 3 + Tailwind CSS 4 + Font Awesome 7).

## Struktur Komponen

Setiap halaman CRUD wajib memiliki:

```
resources/js/Pages/<Module>/<PageName>.vue
```

Gunakan `OperatorSaasLayout` (prefix `/operator-saas/`) untuk halaman operator SaaS.

## Checklist Fitur Wajib

### 1. Layout Container
- [ ] Layout `<main>` pakai `py-6 px-4 sm:px-6 lg:px-8 w-full max-w-full overflow-x-hidden` — fluid container dengan padding responsif.
- [ ] Filter bar & header pakai `flex-col sm:flex-row` + `gap-3`.
- [ ] Filter pills pakai `flex gap-1 flex-wrap` + `whitespace-nowrap`.

### 1.5 Breadcrumbs (WAJIB untuk halaman non-dashboard)
- [ ] **Setiap halaman selain dashboard/beranda wajib punya breadcrumbs** di bagian atas konten.
- [ ] Format: `Dashboard / Module / Submodule` atau `Dashboard / Module / Action (Tambah/Edit/Detail)`.
- [ ] Gunakan icon `fa-home` untuk Dashboard root, `fa-chevron-right` sebagai separator.
- [ ] Item terakhir (halaman aktif) berwarna lebih gelap (`text-gray-900 dark:text-white`) tanpa link.
- [ ] Item sebelumnya berupa link dengan warna `text-gray-500 dark:text-gray-400 hover:text-indigo-600`.
- [ ] Implementasi di dalam page component, di atas header title.

```html
<!-- Contoh breadcrumbs -->
<nav class="flex items-center gap-1.5 text-sm mb-4">
  <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
    <i class="fas fa-home"></i>
  </Link>
  <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
  <Link href="/operator-saas/dashboard" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
    Dashboard
  </Link>
  <i class="fas fa-chevron-right text-[10px] text-gray-400 dark:text-gray-500"></i>
  <span class="text-gray-900 dark:text-white font-medium">Admin Perusahaan</span>
</nav>
```

### 2. Table
- [ ] **Responsif scroll horizontal** — table wrapper pakai `overflow-x-auto`, table diberi `min-w-[700px]` (atau sesuai jumlah kolom) agar di mobile bisa di-scroll horizontal.
- [ ] **Whitespace nowrap** — setiap `<td>` & `<th>` yang isinya pendek (email, status, telepon, aksi) diberi `whitespace-nowrap` agar tidak wrap dan memicu scroll horizontal yang rapi.
- [ ] **Empty state** — saat 0 data tampilkan icon + teks "Tidak ada data...".
- [ ] **Avatar inisial** — kolom nama menampilkan avatar bulat dengan inisial huruf pertama.
- [ ] **Checkbox + Bulk Action** — kolom pertama checkbox untuk multi-select. Tampilkan bulk action bar saat ada item dipilih: Aktifkan, Nonaktifkan, Hapus. No urut dihapus (tidak perlu).

### 3. Multi-Column Sort
- [ ] Klik header kolom: tambahkan sort field dengan arah `asc`.
- [ ] Klik lagi: toggle `asc` → `desc`.
- [ ] Klik lagi: hapus dari sort chain.
- [ ] Setiap kolom yang sedang di-sort menampilkan **nomor urut** (1, 2, 3) dan **ikon arah** (`fa-sort-up`/`fa-sort-down`).
- [ ] Multiple kolom bisa di-sort bersamaan (additive, bukan exclusive).
- [ ] **Implementasi state**: `const sortFields = ref([]); // [{ field, dir }]`

```js
function sort(field) {
  const existing = sortFields.value.findIndex(s => s.field === field);
  if (existing !== -1) {
    if (sortFields.value[existing].dir === 'asc') {
      sortFields.value[existing].dir = 'desc';
    } else {
      sortFields.value.splice(existing, 1);
    }
  } else {
    sortFields.value.push({ field, dir: 'asc' });
  }
}
```

### 4. Search & Filter
- [ ] **Search on Enter** — search bar tidak real-time. Input disimpan di `searchInput` ref terpisah, baru di-apply ke `search` ref saat:
  - Tekan Enter (`@keydown.enter`)
  - Klik tombol search (ikon search di kanan input)
- [ ] **Clear button** — tombol ✕ di dalam search bar untuk reset.
- [ ] **Status filter** — tombol pill group: "Semua" / "Aktif" / "Nonaktif" dengan warna indikatif.
- [ ] **Reset filter** — link "Reset filter" muncul saat ada filter aktif.
- [ ] Filter dan search memicu `currentPage.value = 1`.

### 5. Pagination
- [ ] **Page length selector** — dropdown "Tampilkan [5/10/25/50]" di kiri bawah.
- [ ] **Navigasi halaman** — double-chevron (first/last), single-chevron (prev/next), nomor halaman dengan `flex-wrap`.
- [ ] Halaman aktif di-highlight (bg indigo).
- [ ] Info "dari N data".

### 6. Modal CRUD
- [ ] **Create modal** — form dengan validasi, field wajib ditandai `*` merah.
- [ ] **Detail modal** — info lengkap + tombol "Edit" langsung.
- [ ] **Edit modal** — form pre-filled, password field disembunyikan.
- [ ] **Delete modal** — konfirmasi dengan nama item yang akan dihapus.
- [ ] Semua modal menggunakan `<Teleport to="body">` dengan backdrop `bg-black/50 backdrop-blur-sm`.
- [ ] Animasi enter/leave dengan `<Transition name="modal">`.

### 7. Validasi Form
- [ ] Validasi client-side sebelum submit.
- [ ] Error ditampilkan di bawah field dengan teks merah kecil.
- [ ] Border field berubah merah saat error.
- [ ] Object errors: `const formErrors = ref({})`.

## Pola State

```js
// Data
const items = ref([]);

// Search & Filter
const searchInput = ref('');
const search = ref('');
const statusFilter = ref('');

// Sort
const sortFields = ref([]);

// Pagination
const currentPage = ref(1);
const perPage = ref(5);
const perPageOptions = [5, 10, 25, 50];

// Modal
const selectedItem = ref(null);
const showCreateModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);

// Form
const form = ref({});
const formErrors = ref({});
```

## Design Tokens (Tailwind Classes)

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
| Main container | `py-6 px-4 sm:px-6 lg:px-8 w-full max-w-full overflow-x-hidden` | sama |

## Referensi

File contoh implementasi lengkap:
- `resources/js/Pages/OperatorSaas/AdminPerusahaan.vue`
