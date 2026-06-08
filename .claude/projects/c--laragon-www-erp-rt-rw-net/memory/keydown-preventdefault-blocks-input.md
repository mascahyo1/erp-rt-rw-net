---
name: keydown-preventdefault-blocks-input
description: Document-level keydown handler yang manggil e.preventDefault() unconditionally saat dropdown/search open akan block character input — test fill() works, type() doesn't
metadata:
  type: feedback
---

# Document keydown `preventDefault()` blocks typing in search input

Pas bikin component dropdown/search dengan document-level `keydown` listener untuk Arrow/Enter navigation, **JANGAN** panggil `e.preventDefault()` unconditionally saat dropdown open. Ini akan nge-block SEMUA character keys ("j", "a", "p", dll) dari ke-input ke search box.

**Bug yang sempat ke-alami di CountryCodeSelect** (commit fix):
- `onArrowDown` dipasang ke `document.keydown`
- Saat `isOpen.value === true`, fungsi manggil `e.preventDefault()` di awal sebelum branching
- Hasil: user gak bisa ngetik "japan" di search — placeholder tetap "Pilih negara", list 185 negara gak ke-filter
- Test `fill('japan')` works (set value via JS, bypass keydown)
- Test `page.keyboard.type('japan')` fails (simulate real keydown events) — INI yang user alami pas manual test

**Fix**: Hanya panggil `e.preventDefault()` untuk keys yang emang di-handle (ArrowDown, ArrowUp, Enter, Tab). Character keys harus pass through ke input.

```js
// SALAH:
function onArrowDown(e) {
    if (!isOpen.value) { ... return; }
    e.preventDefault();  // <-- blocks ALL keys, including letters!
    if (e.key === 'ArrowDown') { ... }
}

// BENAR:
function onArrowDown(e) {
    if (!isOpen.value) { ... return; }
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        ...
    } else if (e.key === 'Enter') {
        e.preventDefault();
        ...
    } // else: biarkan character keys pass through
}
```

**Cara verify**: kalau ada dropdown/search component baru, test dengan `page.keyboard.type(...)` BUKAN cuma `fill()`. Fill bypass keydown events, type() simulate real human input.

**Why**: type() = real keystrokes, fire keydown+keypress+input events. fill() = JS value setter + input event only. Bug yang gak ke-detect fill akan ke-detect type().

**How to apply**: Setiap bikin component dengan search input + keyboard navigation handler global, audit handler-nya. Pastiin `preventDefault()` scoped per-key, bukan blanket.
