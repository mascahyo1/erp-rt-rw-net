---
name: thorough-first-time-no-slop
description: "User extremely demanding — work harus thorough first time, no slop, no bolak-balik banyak prompt. Selalu cek existing patterns, multi-PIC, searchable, dll upfront"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# User sangat demanding — kerja thorough dari awal, no slop

## Context
2026-06-25 user venting: "1x prompt gak cukup? 10 gak? 100 gak? 1000 beres gitu ya?" setelah saya ngoding Gangguan feature dengan banyak slop:
- `created_at` dan `issue_dimulai_dari` dicampur-aduk (auto-set, padahal harusnya input manual)
- Field DB `issue_diselesaikan_pada` gak muncul di UI
- `Penanggung Jawab` pakai static `<select>` (slop yang sama dengan `Kode Langganan` kemarin)
- Single PIC (1 penanggung jawab) — padahal real world butuh multiple

User minta: banyak revisi, capek bolak-balik.

## Why
Setiap commit = trust. Kalau commit slop, user capek. User lebih suka:
- 1 prompt panjang dengan semua requirements upfront
- Fix sekaligus semua issue yang ditemukan saat investigation
- Baca pattern existing (RiwayatPembayaran, Tagihan, dll) sebelum nulis

## How to apply

### SEBELUM nulis kode untuk CRUD baru, WAJIB cek:

**1. Field DB vs UI**:
- Field apa saja di migration/DB?
- Field apa saja yang muncul di datatable?
- Field apa yang missing?
- Field apa yang auto-set padahal harusnya input manual?

**2. CRUD UI completeness checklist** (dari [crud/SKILL.md](../skills/crud/SKILL.md)):
- [ ] Filter (search, status, sort, date range)
- [ ] Pagination
- [ ] Bulk action (delete, restore, verify) — WAJIB
- [ ] Checklist + Select All
- [ ] Import/Export Excel
- [ ] All timestamps sortable + visible
- [ ] Field khusus (assigned_to, status, etc) pakai SearchableSelectAjax (search + infinite scroll)
- [ ] data-testid di semua field modal

**3. Relasi many-to-many**:
- Kalau entity bisa punya multiple owner/PIC/tag, pakai pivot table
- Contoh: support_ticket_pics, customer_tags, etc
- BUKAN single FK (assigned_to_employee_id) yang terlalu kaku

**4. Existing patterns to READ first**:
- [dokumentasi/CONVENTIONS.md](../dokumentasi/CONVENTIONS.md) — wajib baca
- `app/Http/Controllers/OperatorPerusahaan/PembayaranController.php` — RiwayatPembayaran pattern
- `app/Http/Controllers/OperatorPerusahaan/TagihanController.php` — Tagihan pattern
- `resources/js/Components/SearchableSelectAjax.vue` — searchable select
- `resources/js/Layouts/*.vue` — menu structure
- `.claude/skills/crud/SKILL.md` — CRUD conventions

**5. Time/sentiasa timestamps**:
- `created_at` = DB insert time (auto)
- `updated_at` = DB update time (auto)
- `deleted_at` = soft delete time (auto)
- `*_dimulai_dari`, `*_diselesaikan_pada` = business timestamps (input manual)
- `*_verified_at`, `*_settled_at` = event timestamps (auto at event time)

JANGAN campur-aduk auto vs manual timestamps. Kalau `issue_dimulai_dari` adalah "kapan masalah terjadi" (business), harusnya input manual — bukan auto-set ke `now()`.

## Konsekuensi
- 1 commit = 1 fitur SELESAI total, no slop
- Kalau user nemu slop setelah commit, langsung: "ya saya slop, fix sekarang"
- Lebih baik 1 prompt panjang dengan semua pertanyaan upfront, daripada 5 prompt bolak-balik
- Selalu verify dengan re-read file + tinker + screenshot per workflow
