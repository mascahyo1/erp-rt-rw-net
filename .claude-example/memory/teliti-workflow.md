---
name: teliti-workflow
description: Workflow teliti untuk setiap task baru — konfirmasi dulu, todo list, screenshot+verify setiap file
metadata:
  type: feedback
---

**Workflow standar setiap menerima task baru:**

1. **Konfirmasi & Tanya** — baca task dengan teliti, Tanya clarifying questions sampai 95% confident tentang REQUIREMENTS (bukan implementation detail). Jangan mulai coding kalau masih ada ambiguitas.

2. **Buat Todo List** — tulis langkah-langkah yang akan dikerjakan. **TUNGGU user verifikasi** todo list sebelum mulai kerja. User akan confirm atau adjust.

3. **Implementasi file-by-file** dengan discipline tinggi:
   - Selesai ubah 1 file → **take screenshot dari web** (headed browser, `headless: false`) untuk verify perubahan ke UI
   - **Verify sendiri** — lihat screenshot, cek apakah sesuai expected. Kalo ada yg aneh, fix dulu sebelum lanjut
   - **Re-check code** file yang baru diubah — baca ulang, cek typo, cek logic, cek side effects
   - Baru lanjut ke file berikutnya

4. **Teliti** — slow down, jangan asal-asalan. Kalau test gagal, fix root cause bukan workaround. Kalau gak yakin, Tanya user, jangan nebak.

**Why:** User pernah ngalamin bug dari rushing + iterasi ngawur. Workflow ini nge-force pause & verify di setiap step sehingga bug ke-detect early, bukan di akhir.

**How to apply:**
- Default pakai `headless: false` di Playwright supaya user + saya bisa lihat browser
- Screenshot ke `tests/Browser/Playwright/result/{portal}/` setelah setiap perubahan UI
- Pakai `pdftotext` untuk verify PDF content (gak bisa lihat gambar langsung)
- TodoWrite di awal task, update progress secara real-time
- Kalau ragu, Tanya user dengan opsi konkret (bukan open-ended question)
- Hindari iterasi berulang tanpa feedback visual dari user
