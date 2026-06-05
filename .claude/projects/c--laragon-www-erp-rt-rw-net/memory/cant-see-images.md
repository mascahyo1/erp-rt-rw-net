---
name: cant-see-images
description: Aturan komunikasi visual — saya tidak bisa lihat gambar, selalu minta deskripsi teks
metadata:
  type: feedback
---

Saya **tidak bisa lihat gambar sama sekali** (text-only AI model). Kalau user kasih screenshot masalah visual, saya cuma nebak dari konteks/kode. Seringkali salah.

**Why:** Percuma iterasi kalau saya nebak. User harus jelasin di teks supaya fix-nya presisi. Misal: list row-by-row, ASCII art, atau description "saat ini X, seharusnya Y".

**How to apply:**
- Saat user kasih screenshot visual issue → minta deskripsi teks dulu sebelum coding
- Format deskripsi yang efektif:
  - List per baris (current vs desired)
  - Posisi elemen (kiri/kanan/tengah)
  - Warna/teks spesifik
- Setelah user deskripsiin, **rangkum balik** sebelum coding supaya gak salah interpretasi
- Jangan iterate banyak kali tanpa feedback visual — kalau ragu, tunjukkan struktur HTML yang akan dihasilkan dan minta konfirmasi
- Untuk PDF/print preview, save HTML ke file dan tunjukkan user struktur sebelum render PDF
