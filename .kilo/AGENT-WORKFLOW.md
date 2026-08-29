# AGENT WORKFLOW — NON-NEGOTIABLE REMINDER

Peringatan keras untuk agent: AGENTS.md sudah menuliskan workflow, tapi agent sering skip. Baca dan patuhi 5 langkah ini **SEBELUM** coding apa pun:

## 1. Konfirmasi requirement (≥95% confident)
- Jika user memberi scope jelas (✅ marks, list eksplisit) → boleh langsung.
- Jika ambigu → tanya dengan opsi konkret, jangan open-ended.

## 2. Tulis todo list → TUNGGU sign-off user
- `todowrite` dulu.
- **Berhenti dan tunggu user konfirmasi/verifikasi todo** sebelum edit file pertama.
- Jangan langsung `edit`/`write` walau scope sudah jelas.

## 3. Satu file pada satu waktu
- Edit 1 file → pause.
- **Screenshot headed browser** + verify.
- **Re-read diff** untuk typo / efek samping.
- Lanjut ke file berikutnya hanya jika sudah yakin.

## 4. Visual issue? Deskripsi text-based DULU
- Jangan silent iteration.
- Uraikan current vs expected: row-by-row, posisi, warna, sebelum minta fix.
- Tunggu user konfirmasi sebelum ubah CSS lagi.

## 5. Respons dalam Bahasa Indonesia
- Singkat, dengan `path:line` saat refer kode.

---

## Pemicu: tugas baru masuk

Setiap kali dapat tugas baru (user prompt selain sapaan/pertanyaan meta), **default behavior**:

1. Acknowledge tugas dalam 1-2 kalimat.
2. `todowrite` dengan langkah-langkah.
3. **Tanya user** untuk verifikasi todo (contoh: "Todo sudah benar? Lanjut?").
4. Baru mulai coding setelah user konfirmasi.

## Standar Testing Wajib (STANDARDS.md §7 — ringkas)

- **Playwright primary:** `tests/Browser/Playwright/Feature/**/*.cjs` — `headless:false`, `slowMo:300-500`, `.cjs` only. Lihat `STANDARDS.md §7` & `workflow.md`.
- **Per langkah wajib:** `assert/check` + `screenshot/shot` + `network (waitForResponse)` + `video` jika kritis. No silent step.
- **Cakupan Deep Verify:** login → list → CRUD all types → validasi 422 → file upload → soft-delete/bulk → import/export → dark/responsive → no JS errors.
- **Template:** `DeepVerifyKonfigurasiSaaS.cjs` — copy untuk fitur baru, jangan `QuickVerify*`.
- **PHPUnit:** hanya backend murni, via `.\parallel-test.ps1`. Jangan untuk verify UI.
- **Dusk:** legacy, jangan buat baru.
- **Enforcement:** `node scripts/check-testing-standards.cjs` harus 0 violation sebelum push (kontrak §1+§7).

## Larangan

- ❌ Langsung `edit`/`write` tanpa todo + sign-off.
- ❌ Skip screenshot verification setelah edit.
- ❌ Silent iteration pada visual issue.
- ❌ Respons panjang tanpa `path:line`.
- ❌ Bertanya di akhir respons (no engagement fishing).
- ❌ Buat test E2E baru tanpa `assert`+`screenshot`+`network` per langkah (§7.2).
- ❌ Pakai `headless:true` untuk debug visual (hanya CI) atau `QuickVerify*` prefix.
