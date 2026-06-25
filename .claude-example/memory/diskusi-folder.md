---
name: diskusi-folder
description: "Folder briefing/diskusi/ untuk catatan diskusi pre-implementation, terpisah dari briefing/report/ (daily/weekly/progress final)"
metadata: 
  node_type: memory
  type: reference
  originSessionId: a9fcc23e-1fb0-452e-a6bc-66777865340e
---

Diskusi pre-implementation ditulis di `briefing/diskusi/YYYY-MM-DD-{slug}.md` — **terpisah** dari `briefing/report/` (daily/weekly/progress final).

**Kenapa folder terpisah:**
- `briefing/report/` = hasil final (sudah commit, sudah delivered)
- `briefing/diskusi/` = WIP (belum commit, masih bolak-balik klarifikasi)

**Naming convention:** `briefing/diskusi/YYYY-MM-DD-{slug}.md` — slug singkat deskripsi topiknya.

**Workflow:**
1. User buka topik baru → buat file diskusi di folder ini, tulis context + confirmed decisions + open questions
2. User jawab pertanyaan → update file diskusi (section "Yang Sudah Diputuskan" + "Open Questions")
3. Setelah final → buat planning file terpisah (misal `2026-06-17-final-plan.md`) atau langsung commit implementation
4. Setelah commit → referensi di `briefing/report/progress.md` Update Log

**Why:** User bilang "tulis dulu di file briefing aja di folder diskusi dulu biar ndak lupa" — 2026-06-17 saat diskusi `is_testing` flag + Midtrans 3-opsi.

**How to apply:** Setiap ada diskusi baru yang belum siap di-implement, tulis dulu di folder diskusi, jangan langsung planning/kode.
