---
name: cant-see-images
description: Aturan komunikasi visual — saya BISA lihat gambar via Read tool, tapi tetap prefer deskripsi teks untuk presisi
metadata:
  type: feedback
---

# Visual: BISA lihat gambar via Read tool, tapi prefer deskripsi teks

## Status (update 2026-06-25)
**BISA** lihat gambar sekarang via `Read` tool (PNG, JPG, PDF). Tool ini otomatis present visual content. Sudah terbukti di sesi Gangguan + Midtrans testing — saya bisa lihat screenshot modal, Snap UI, error toast, dll.

## Kapan masih perlu deskripsi teks
- Kalau user **potong** sebagian gambar (crop manual) — Read cuma baca file utuh
- Kalau user mau presisi piksel-level (misal "padding 4px terlalu besar" lebih akurat dari yang saya lihat)
- Kalau user mau **multiple states** (before/after side-by-side) — saya cuma bisa lihat 1 gambar per Read
- Kalau interpretasi visual bisa salah (warna, layout) — text lebih reliable
- Untuk **PDF preview**, save HTML ke file dan tunjukkan struktur sebelum render

## When to ask for text description
- User kasih screenshot dengan instruction minim ("kenapa error?") — minta detail
- User kasih screenshot bagian kecil — minta konteks lebih
- Iterasi visual yang sama ≥ 2x — escalate ke text/HTML snippet

## Format deskripsi yang efektif (kalau perlu text)
- List per baris (current vs desired)
- Posisi elemen (kiri/kanan/tengah)
- Warna/teks spesifik
- ASCII art untuk layout

## How to apply
- Default: langsung baca gambar via `Read` tool, interpret sendiri
- Kalau ragu atau iterasi gagal: minta text description
- Setelah baca, **rangkum balik** apa yang saya lihat sebelum coding (konfirmasi understanding)
- Jangan iterate banyak kali tanpa feedback — kalau gagal 2x, minta HTML snippet atau text
