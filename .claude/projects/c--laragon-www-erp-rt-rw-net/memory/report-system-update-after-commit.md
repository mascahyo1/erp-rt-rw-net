---
name: report-system-update-after-commit
description: Setiap habis commit, WAJIB update briefing/report/ (progress.md + daily + weekly) + push ke remote. Bisa jawab pertanyaan klien tentang progress tanpa hitung ulang.
metadata:
  type: workflow
---

**Setiap habis commit di repo ini, WAJIB update 3 file report + push.** Tanpa ini, agent tidak bisa jawab pertanyaan klien tentang progress, "kemarin ngerjain apa", "minggu ini progress berapa %".

**Why:** User pernah nanya "kalau ditanya klien progress sampai mana?", "ada daily report dan weekly report?", "berapa persen progressnya?" — tanpa sistem report, agent harus re-derive dari git log + baca semua file (lambat + tidak reliable). Dengan sistem report yang maintained, agent langsung baca file yang sudah disusun.

**How to apply — TRIGGER seteah commit sukses:**

1. **Append ke `briefing/report/daily/{YYYY M D Hari}.md`** — kalau file belum ada, create. Isi:
   - Subject commit + hash + area yang disentuh
   - File yang berubah (estimasi dari `git show --stat`)
   - Tests added/passed
   - Bugs fixed
   - Carry-over ke besok

2. **Append ke `briefing/report/weekly/{YYYY M D Senin}.md`** — nama file pakai Senin dari minggu ISO yang sama. Isi ringkasan:
   - Highlight / wins minggu ini
   - Progress delta
   - Yang berlanjut ke minggu depan

3. **Update `briefing/report/progress.md`** kalau ada modul status yang berubah:
   - Update status modul (🟡 → ✅)
   - Update kolom "Last update" di paling atas
   - Append ke "Update Log" dengan tanggal + perubahan
   - Kalau overall % berubah, recalculate

4. **Push ke remote** — `git push origin main` (per `push-after-commit.md` memory)

**Format filename (TETAP, tidak boleh diubah):**
- Daily: `briefing/report/daily/{YYYY M D Hari}.md` — e.g., `2026 6 7 Minggu.md`
- Weekly: `briefing/report/weekly/{YYYY M D Senin}.md` — e.g., `2026 6 1 Senin.md` (Senin = Monday yang start minggu ISO)
- Progress: `briefing/report/progress.md` (single source of truth, tidak ada tanggal di nama)

**Indonesian day names untuk filename (lowercase):**
senin, selasa, rabu, kamis, jumat, sabtu, minggu

**Cara jawab pertanyaan klien (workflow cepat):**
- "Progress sampai mana?" → Baca `briefing/report/progress.md` section "Ringkasan Cepat"
- "Kemarin ngerjain apa?" → `briefing/report/daily/{kemarin Y M D Hari}.md`
- "Minggu ini?" → `briefing/report/weekly/{Senin minggu ini Y M D Senin}.md`
- "Status modul X?" → Search di `briefing/report/progress.md` tabel "Detail Per Modul"
- "Sisa kerja?" → Section "Sisa Kerja (Phase 1 MVP — yang belum selesai)" di progress.md

**Related memories:**
- `push-after-commit.md` — backup cloud setiap commit
- `teliti-workflow.md` — workflow overall
- `deep-verify-no-quick.md` — standar testing
