---
name: crud-ui-complete-checklist
description: "Setiap CRUD WAJIB verify 7 item sebelum bilang \"selesai\" — mencegah slop bolak-balik"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# Anti-Slop Checklist — Wajib verify sebelum commit CRUD

## Context
2026-06-25 user sangat marah ("slop lagi, bohong besar") karena saya commit fitur Gangguan yang masih banyak slop:
- `created_at` vs `issue_dimulai_dari` dicampur (auto vs manual timestamp)
- Filter dropdown masih static `<select>` (gak SearchableSelectAjax)
- Detail modal gak show PIC utama + tambahan
- Legacy column di-keep (padahal feature baru, gak ada backward compat issue)

User minta: kerja thorough, no bolak-balik.

## Why
Tiap kali commit = trust. Kalau slop, user capek. Lebih baik SEKALI commit yang thorough daripada 5x commit fix bolak-balik.

## How to apply — 7 CHECKLIST sebelum bilang "selesai"

### □ 1. Filter dropdown = SearchableSelectAjax (BUKAN static `<select>`)
- SEMUA filter dropdown di header table (search, status, PIC, category, dll) HARUS pakai `SearchableSelectAjax` untuk AJAX + infinite scroll + keyboard nav
- Kalau ada 100+ option, static `<select>` = unusable
- Reference: `resources/js/Components/SearchableSelectAjax.vue`
- Pattern: `url="/<portal>/api/search/<resource>"`, `display-key="name"`

### □ 2. Business timestamps = INPUT MANUAL (bukan auto-set)
- `created_at`, `updated_at`, `deleted_at` = AUTO (Eloquent timestamps)
- `*_dimulai_dari`, `*_diselesaikan_pada`, `*_settled_at` = MANUAL input (datetime-local)
- JANGAN auto-set business timestamp di controller — biarkan user input via form
- Contoh: `issue_dimulai_dari` (kapan masalah terjadi) BUKAN auto `now()`

### □ 3. Detail modal show ALL related entities
- Kalau entity punya main + additional (contoh: PIC utama + PIC tambahan), detail modal HARUS show keduanya sebagai section terpisah
- Field `*_name` (legacy) jangan dipake kalau ada `main_*` (proper)
- Section "Penanggung Jawab" → show "PIC Utama" + "PIC Tambahan" (list/chip)
- Section "Timestamps" → show Tgl Mulai + Tgl Selesai + Created (jangan hide business timestamps)

### □ 4. Legacy column DROP (kalau sudah tidak dipakai)
- Kalau replace single FK (`assigned_to_employee_id`) dengan pivot table (`support_ticket_pics`), DROP kolom legacy
- "Backward compat" itu mitos kalau feature baru, no production data
- JANGAN keep "untuk jaga-jaga" — itu dead code
- Action: migration `dropColumn(['assigned_to_employee_id'])`

### □ 5. data-testid di SEMUA field modal
- Test convention: setiap input/select/textarea/button di modal HARUS punya `data-testid="..."` (lihat [modal-data-testid-convention](modal-data-testid-convention.md))
- Testid pattern: `input-<nama>`, `select-<nama>`, `textarea-<nama>`, `btn-<aksi>`, `data-testid="modal-<nama-modal>"`
- Lupa 1 testid = test E2E slop (gak bisa click element precisely)

### □ 6. E2E test passes + build no error
- `npm run build` → ✓ no error
- E2E test → 100% pass (jangan "22/24" — partial pass = bug)
- Screenshot per step verify visually

### □ 7. `git status` bersih sebelum commit
- `git status` → cek **TIDAK ADA** file `*.png`, `*.html`, `*.webm` di staging area
- Test artifacts harus di-ignore di `.gitignore` (lihat [test-artifacts-no-commit](test-artifacts-no-commit.md))
- Kalau ada file random yang gak seharusnya ke-commit, `git rm --cached <file>`

## Workflow
1. SEBELUM coding: baca existing pattern + cek field DB vs UI
2. SELAMA coding: checklist 1-7 setiap kali nambah field/fitur
3. SETELAH coding: verify 7 checklist, baru bilang "selesai"
4. Kalau user nemu slop setelah commit: langsung akui + fix sekaligus (jangan perbaiki 1-2, fix SEMUA issue yang terlihat)

## Contoh slop yang sering keulang
- "Pak `<select>` statis di filter" → harusnya `SearchableSelectAjax`
- "Auto-set `now()` untuk business timestamp" → harusnya input manual
- "Hide field di detail modal" → harusnya show
- "Keep legacy column" → harusnya drop
- "Lupa data-testid" → test jadi fragile
- "Bidang di DB gak muncul di UI" → cek SEMUA field migration
