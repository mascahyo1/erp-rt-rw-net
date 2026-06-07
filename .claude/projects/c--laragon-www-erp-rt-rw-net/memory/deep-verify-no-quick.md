---
name: deep-verify-no-quick
description: Tidak ada "quick verify" — testing harus deep, teliti, menyeluruh. Rename file test dari QuickVerify* ke DeepVerify* dan tambah cakupan verifikasi.
metadata:
  type: feedback
---

Di repo ini **TIDAK ADA** yang namanya "quick verify" — semua testing harus **deep, teliti, dan menyeluruh** sesuai workflow `teliti-workflow.md`. Prefix `QuickVerify*` di file test misleading dan harus dihindari.

**Why:** User repeatedly menekankan workflow = teliti. "Quick" mengimplikasikan smoke test atau shallow check, sedangkan user ingin test yang:
- Verify setiap aspek perubahan UI (modal, table, filter, sort, bulk action, soft-delete, import/export, dark mode, responsive)
- Verify edge case (validation error, empty state, type switch preserves value, etc.)
- Verify backend roundtrip (data persist benar, unique rule exclude soft-deleted, dll)
- Verify permissions (create vs edit vs delete gate, role-based visibility)

**How to apply:**
- File test baru: pakai prefix `DeepVerify{Module}.cjs` atau nama deskriptif `Verify{SpecificScenario}.cjs` (e.g. `VerifyKonfigurasiBooleanEdit.cjs`, `VerifyKredensialToggle.cjs`).
- JANGAN pakai `QuickVerify*` untuk test baru.
- Kalau modify existing `QuickVerify*` file (yang dibuat user sebelumnya), boleh — tapi jangan rename tanpa izin karena bisa break reference.
- Test yang dibuat harus mencakup: login, navigation, modal open/close, CRUD roundtrip, filter, sort, bulk action, soft-delete, import/export, dark mode, responsive, error handling, validasi.
- Bukan sekadar "page loads, no error" — itu smoke test, bukan deep verify.

**Related rules:**
- Lihat juga `teliti-workflow.md` (workflow overall) dan `testing-prioritas-playwright.md` (engine pilihan).
- Lihat `push-after-commit.md` untuk aturan backup.
