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

## Larangan

- ❌ Langsung `edit`/`write` tanpa todo + sign-off.
- ❌ Skip screenshot verification setelah edit.
- ❌ Silent iteration pada visual issue.
- ❌ Respons panjang tanpa `path:line`.
- ❌ Bertanya di akhir respons (no engagement fishing).
