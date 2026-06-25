---
name: crud-checklist-bulk-required
description: "Setiap CRUD baru WAJIB punya checklist (checkbox column) + bulk action (delete, restore, verify/review) — bukan cuma single-action"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# CRUD Checklist + Bulk Action WAJIB

## Context
2026-06-25 saya commit fitur Gangguan dengan single-action only (tiap row punya tombol Edit/Delete/Verify sendiri). User langsung menegur: "slop lagi... bohong besar... checklist aja gak ada, bulk action gak ada".

Skill [crud/SKILL.md](../skills/crud/SKILL.md) sudah **eksplisit** tulis (section 3):
> ### 3. Checklist & Bulk Action (Vue + Inertia)
> ```js
> const selected = ref([]);
> const allSelected = computed({
>   get: () => selected.value.length === props.items.length,
>   set: (val) => selected.value = val ? props.items.map(i => i.id) : [],
> });
> const toggleRow = (id) => { ... };
> const bulkDelete = () => { ... };
> ```
> 
> - Checklist di tiap baris + "Select All" di header
> - Bulk delete + restore + (review/verify kalau applicable)

Tapi saya TIDAK BACA skill ini saat implementasi Gangguan. Result: fitur "selesai" tapi incomplete.

## Why
Checklist + bulk action = baseline CRUD pattern. Tanpa ini, user harus click satu-satu untuk 100+ data. Bohong kalau commit fitur CRUD tanpa fitur ini.

## How to apply

### WAJIB untuk SETIAP CRUD baru (saat ngoding):

**Backend controller methods:**
- `bulkDestroy(Request)` — soft delete many
- `bulkRestore(Request)` — restore many
- `bulkVerify(Request)` atau `bulkReview(Request)` — kalau ada workflow approval
- Validasi: cek `company_id` scope, handle ticket not found

**Routes:**
- `POST /resource/bulk-delete` (POST bukan DELETE — untuk handle array body)
- `POST /resource/bulk-restore`
- `POST /resource/bulk-verify` atau `/bulk-review`

**Vue refs + computeds (di SETIAP CRUD page):**
```js
const selectedIds = ref([]);
const hasSelected = computed(() => selectedIds.value.length > 0);
const isAllSelected = computed({
  get: () => items.value.length > 0 && items.value.every(i => selectedIds.value.includes(i.id)),
  set: (val) => selectedIds.value = val ? items.value.map(i => i.id) : [],
});

function toggleSelect(id) { ... }
function toggleSelectAll() { ... }
```

**Vue table:**
- Checkbox column di paling kiri (header + tiap row)
- Bulk action bar (visible saat `hasSelected`):
  - "X data dipilih"
  - Tombol bulk-delete, bulk-restore, bulk-verify (disesuaikan dengan permissions)
  - Tombol "Batal pilih" untuk clear

**Test E2E:**
- Helper `selectRows(page, n)` untuk click checkbox N row pertama
- Verify bulk action button visible
- Click bulk-delete, confirm modal
- Verify selected rows hilang dari list

### Template ceklis sebelum commit CRUD:
- [ ] selectedIds ref (bukan `const selectedIds = ref([])` doang, tapi DIPAKAI)
- [ ] Checkbox column di table (header + body)
- [ ] isAllSelected computed
- [ ] toggleSelect + toggleSelectAll functions
- [ ] Bulk action bar (visible saat hasSelected)
- [ ] Backend: bulkDestroy, bulkRestore, (+ bulkVerify/bulkReview kalau applicable)
- [ ] Routes: bulk-delete, bulk-restore, (+ bulk-verify/bulk-review)
- [ ] Permissions: bulk operations pakai permission yang sama dgn single (misal 'gangguan.delete' untuk bulkDelete)
- [ ] E2E test: bulk action flow
