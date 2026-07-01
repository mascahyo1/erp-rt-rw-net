---
name: robust-selectors-data-testid
description: JANGAN pakai Tailwind utility classes (e.g. .absolute.z-50.mt-1) untuk selector Playwright/test. Pakai data-testid / id / data-* attribute unik.
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# Robust Selectors — data-testid, BUKAN Tailwind Classes

## Context
2026-06-30 debug Turnstile stuck bug. Playwright test pakai selector:
```js
const dds = Array.from(document.querySelectorAll('.absolute.z-50.mt-1'));
// cari dropdown SearchableSelectAjax
```

Tailwind utility classes bisa BERUBAAH:
- Refactor styling → class berubah → selector rusak
- Multi-class (`.absolute.z-50.mt-1`) fragile kalau satu class di-rename
- Tailwind v4 migration dari v3 bisa generate output class beda

User feedback (bahasa Indonesia):
> "jangan gunakan selector rapuh. setiap tag html memiliki class, id atau attribut unique jadi navigasi untuk manipulasi dom dan testing playwright enak"

## Why
- Tailwind classes = styling tool, BUKAN semantic identifier
- Refactor design (class di-rename) → test rusak silent
- Multiple utility classes dipakai di banyak element (collision risk)
- Test susah di-debug kalau selector fragile

## How to apply

### Frontend pattern (Vue):
```vue
<!-- ✅ Pakai data-testid untuk element yang perlu di-target test -->
<button data-testid="btn-buat-tiket" @click="openCreate">Buat Tiket</button>
<input data-testid="input-email" v-model="email" />
<div data-testid="modal-create">...</div>

<!-- ❌ JANGAN pakai Tailwind class untuk identifikasi -->
<button class="bg-amber-600 text-white">Buat Tiket</button>  <!-- class bisa di-refactor -->
```

### Test pattern (Playwright):
```js
// ✅ Pakai data-testid (stable, semantic)
const btn = page.locator('[data-testid="btn-buat-tiket"]');
const input = page.locator('[data-testid="input-email"]');

// ❌ JANGAN pakai Tailwind class
const btn = page.locator('button.bg-amber-600');  // fragile
const input = page.locator('input.pl-10.pr-4');     // fragile

// ✅ Boleh pakai id kalau memang ada (semantic stable)
const logoutLink = page.locator('#logout-button');

// ✅ Boleh pakai semantic attribute
const csrf = page.locator('meta[name="csrf-token"]');
```

### Reference memory: `modal-data-testid-convention.md` (existing)
- WAJIB data-testid di SEMUA field modal Vue (input/select/button/textarea)
- Pattern: `<field>-<purpose>` atau `<element>-<action>`
  - `data-testid="btn-buat-tiket"`
  - `data-testid="input-email"`
  - `data-testid="select-cust-internet"`
  - `data-testid="modal-create"`

### Add data-testid untuk element yg sering di-target:
- Submit buttons: `btn-simpan`, `btn-update`, `btn-confirm-delete`
- Form inputs: `input-<field>`, `select-<field>`, `textarea-<field>`
- Modal containers: `modal-<purpose>` (modal-create, modal-edit, modal-delete)
- Dynamic elements: `chip-additional-pic`, `attachment-row`

### Cek cepat:
```bash
# Cari element tanpa data-testid
grep -r "class=\"bg-" resources/js/Pages --include="*.vue" -l
# Liat kalau ada button/input tanpa data-testid, tambahin
```

## Related
- [modal-data-testid-convention](modal-data-testid-convention.md) — pattern existing
- [testing-with-headed-browser](testing-with-headed-browser.md) — Playwright pattern
