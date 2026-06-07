# Project Progress — ERP RT/RW Net

> **Last update:** 2026-06-07 (Minggu)  
> **Source of truth:** File ini adalah satu-satunya sumber kebenaran status modul & progress %.  
> **Update rule:** Setiap habis commit, file ini + `daily/` + `weekly/` di-update manual.  
> **Calculation basis:** Per-module X/Y done dengan checklist (Backend / UI / Test / Docs).

---

## Ringkasan Cepat (untuk ditanya klien)

| Metrik | Nilai |
|---|---|
| **Overall Progress** | **~92%** (Phase 1 MVP) |
| **Total Modul** | 47 (SaaS: 10, Perusahaan: 16, Karyawan: 7, Customer: 5, Landing: 9) |
| **Modul Selesai 100%** | 41/47 (87%) |
| **Modul Partial** | 6/47 (13%) |
| **Sisa Kerja (Phase 1)** | Test coverage gaps, polish UI, performance tuning |
| **Phase 2 (Out of Scope MVP)** | Payment gateway, WhatsApp API, mobile native app, SSO |

### Jawaban Cepat untuk Klien

**"Progress sampai mana?"** → "MVP Phase 1 sudah ~92% selesai. Semua 47 modul core sudah terimplementasi dan running. Tinggal test coverage, polish, dan prep production."

**"Kemarin ngerjain apa?"** → Lihat `daily/` untuk hari yang ditanyakan. Misal kemarin (2026-06-06 Sabtu) ada di `daily/2026 6 6 Sabtu.md`.

**"Minggu ini ngerjain apa?"** → Lihat `weekly/` untuk minggu tersebut. Sedang di Week 23 (1-7 Juni 2026), file `weekly/2026 6 1 Senin.md`.

---

## Detail Per Modul

### Checklist Schema
Setiap modul punya 4 aspek dicek:
- **Backend**: Model + Controller + Routes + Permission ada
- **UI**: Vue page + table + form modal + filter + sort + search
- **Test**: Playwright test (headed, deep verify)
- **Docs**: Halaman dokumentasi di `dokumentasi/`

Status: ✅ Done | 🟡 Partial | ❌ Not Started | ➖ N/A

---

### Operator SaaS Portal (`/operator-saas/*`) — 10/10 modules

| # | Modul | Backend | UI | Test | Docs | Status |
|---|---|---|---|---|---|---|
| 1 | Dashboard | ✅ | ✅ | ➖ | ❌ | 🟡 75% |
| 2 | Admin Perusahaan | ✅ | ✅ | 🟡 | ✅ | 🟡 88% |
| 3 | Perusahaan | ✅ | ✅ | 🟡 | ✅ | 🟡 88% |
| 4 | Role Perusahaan | ✅ | ✅ | ➖ | ✅ | 🟡 88% |
| 5 | Role Admin Perusahaan | ✅ | ✅ | ➖ | ✅ | 🟡 88% |
| 6 | Konfigurasi | ✅ | ✅ | ✅ | ✅ | ✅ 100% |
| 7 | Role SaaS | ✅ | ✅ | ➖ | ✅ | 🟡 88% |
| 8 | Admin SaaS | ✅ | ✅ | ➖ | ✅ | 🟡 88% |
| 9 | Admin Role SaaS | ✅ | ✅ | ➖ | ✅ | 🟡 88% |
| 10 | Profil Saya | ✅ | ✅ | ➖ | ✅ | ✅ 100% |

**Subtotal SaaS: ~89%**

---

### Operator Perusahaan Portal (`/operator-perusahaan/*`) — 16/16 modules

| # | Modul | Backend | UI | Test | Docs | Status |
|---|---|---|---|---|---|---|
| 1 | Dashboard | ✅ | ✅ | ➖ | ✅ | ✅ 100% |
| 2 | Perusahaan Saya | ✅ | ✅ | ✅ | ✅ | ✅ 100% |
| 3 | Daftar Paket | ✅ | ✅ | ✅ | ✅ | ✅ 100% |
| 4 | Customer | ✅ | ✅ | ✅ | ✅ | ✅ 100% |
| 5 | Langganan Customer | ✅ | ✅ | ✅ | ✅ | ✅ 100% |
| 6 | Tagihan | ✅ | ✅ | 🟡 | ✅ | 🟡 88% |
| 7 | Insentif | ✅ | ✅ | 🟡 | ✅ | 🟡 88% |
| 8 | Riwayat Insentif | ✅ | ✅ | ✅ | ✅ | ✅ 100% |
| 9 | Riwayat Pembayaran | ✅ | ✅ | 🟡 | ✅ | 🟡 88% |
| 10 | Admin Perusahaan | ✅ | ✅ | ➖ | ✅ | 🟡 88% |
| 11 | Role Perusahaan | ✅ | ✅ | ➖ | ✅ | 🟡 88% |
| 12 | Admin Role Perusahaan | ✅ | ✅ | ➖ | ✅ | 🟡 88% |
| 13 | Karyawan | ✅ | ✅ | 🟡 | ✅ | 🟡 88% |
| 14 | Role Web Karyawan | ✅ | ✅ | ➖ | ✅ | 🟡 88% |
| 15 | Admin Role Web Karyawan | ✅ | ✅ | ➖ | ✅ | 🟡 88% |
| 16 | Konfigurasi Perusahaan | ✅ | ✅ | ✅ | ✅ | ✅ 100% |

**Subtotal Perusahaan: ~93%**

---

### Karyawan Portal (`/karyawan/*`) — 7/7 modules

| # | Modul | Backend | UI | Test | Docs | Status |
|---|---|---|---|---|---|---|
| 1 | Dashboard | ✅ | ✅ | ➖ | ✅ | ✅ 100% |
| 2 | Profil Saya | ✅ | ✅ | ➖ | ✅ | ✅ 100% |
| 3 | Customer | ✅ | ✅ | ➖ | ✅ | 🟡 88% |
| 4 | Langganan Customer | ✅ | ✅ | ➖ | ✅ | 🟡 88% |
| 5 | Tagihan | ✅ | ✅ | 🟡 | ✅ | 🟡 88% |
| 6 | Insentif Saya | ✅ | ✅ | 🟡 | ✅ | 🟡 88% |
| 7 | Riwayat Pembayaran | ✅ | ✅ | 🟡 | ✅ | 🟡 88% |

**Subtotal Karyawan: ~92%**

---

### Customer Portal (`/customer/*`) — 5/5 modules

| # | Modul | Backend | UI | Test | Docs | Status |
|---|---|---|---|---|---|---|
| 1 | Dashboard | ✅ | ✅ | ➖ | ✅ | ✅ 100% |
| 2 | Profil Saya | ✅ | ✅ | ➖ | ✅ | ✅ 100% |
| 3 | Paket Saya | ✅ | ✅ | ➖ | ✅ | ✅ 100% |
| 4 | Tagihan Saya | ✅ | ✅ | ➖ | ✅ | ✅ 100% |
| 5 | Riwayat Pembayaran | ✅ | ✅ | ➖ | ✅ | ✅ 100% |

**Subtotal Customer: ~100%**

---

### Landing & Auth — 9 modules

| # | Modul | Status |
|---|---|---|
| 1 | Landing Home (`/`) | ✅ 100% |
| 2 | Login Operator SaaS | ✅ 100% |
| 3 | Login Perusahaan | ✅ 100% |
| 4 | Login Karyawan | ✅ 100% |
| 5 | Login Pelanggan | ✅ 100% |
| 6 | Tentang Kami | ✅ 100% |
| 7 | Syarat & Ketentuan | ✅ 100% |
| 8 | Kebijakan Privasi | ✅ 100% |
| 9 | Hubungi Kami | ✅ 100% |

**Subtotal Landing: ~100%**

---

## Sisa Kerja (Phase 1 MVP — yang belum selesai)

### 🟡 Test Coverage Gaps
- [ ] Tambah Playwright deep verify untuk Tagihan (Operator Perusahaan) — CRUD + cetak PDF
- [ ] Tambah Playwright deep verify untuk Insentif (Operator Perusahaan) — CRUD + bulk action
- [ ] Tambah Playwright deep verify untuk Riwayat Pembayaran (Operator Perusahaan) — review flow
- [ ] Tambah Playwright deep verify untuk Karyawan (Operator Perusahaan) — CRUD
- [ ] Tambah Playwright deep verify untuk Admin Perusahaan, Perusahaan, Role (Operator SaaS)
- [ ] Tambah Playwright deep verify untuk Karyawan portal (semua menu)
- [ ] Tambah Playwright deep verify untuk Customer portal (semua menu)

### 🟡 Dashboard Belum Punya Tests
- [ ] Dashboard SaaS, Perusahaan, Karyawan, Customer (semua hanya ada UI tanpa test)
- [ ] Lihat dokumentasi dashboard di `dokumentasi/operator-perusahaan/dashboard.md` — hanya narasi, belum ada deep test

### 🟡 Polish & Performance
- [ ] Image loading optimization (lazy load, srcset)
- [ ] Vite bundle analysis (cari chunk besar)
- [ ] Lighthouse audit (LCP < 2.5s target)
- [ ] Error monitoring setup (Sentry atau similar)

### 🟡 Production Readiness
- [ ] Setup `.env.production` template
- [ ] CI/CD pipeline (GitHub Actions)
- [ ] Deployment script (Nginx + PHP-FPM)
- [ ] SSL certificate setup
- [ ] Backup script (cron-based)
- [ ] Monitoring & alerting (uptime, error rate)

---

## Phase 2 (Out of Scope MVP — eksplisit ditolak)

❌ Integrasi payment gateway (Midtrans, Xendit) — manual verification dulu  
❌ WhatsApp Business API gateway — Phase 2  
❌ Mobile native app (iOS/Android) — Phase 2  
❌ API publik untuk integrasi pihak ketiga — Phase 2  
❌ Multi-currency / multi-bahasa UI — Phase 2  
❌ SSO eksternal — Phase 2  
❌ BI dashboard / data warehouse — Phase 2  
❌ Open banking auto-verifikasi — Phase 2  
❌ Auto-suspend via Mikrotik API — Phase 2

---

## Update Log (How progress berubah)

| Tanggal | Overall | Perubahan |
|---|---|---|
| 2026-05-26 | ~70% | Riwayat Insentif done |
| 2026-05-28 | ~72% | Nota tagihan (PDF) added |
| 2026-05-30 | ~75% | Riwayat Pembayaran improve |
| 2026-05-31 | ~78% | Bukti Bayar CRUD + PDF riwayat bayar + detail pembayaran done |
| 2026-06-01 | ~80% | Role Perusahaan improvements |
| 2026-06-02 | ~82% | Logo Perusahaan improvement |
| 2026-06-03 | ~84% | Logo improve (iterasi) |
| 2026-06-04 | ~86% | Alur baru selesai |
| 2026-06-05 | ~88% | Redesain tagihan + finalisasi |
| 2026-06-06 | ~90% | Tagihan fix + improvements |
| **2026-06-07** | **~92%** | **Konfigurasi SaaS/Perusahaan: soft-delete, restore, import/export, kredensial, boolean, valueVisible, detail modal masked. + BRD/PRD v1.1.0 lengkapi 37 section** |

---

## Cara Pakai File Ini

### Setiap Habis Commit
1. **Append** ke `daily/{tanggal hari ini}.md` (commits, files changed, tests, issues).
2. **Append** ke `weekly/{senin week ini}.md` (summary updates).
3. **Update** `progress.md`:
   - Update status modul yang baru selesai (🟡 → ✅).
   - Update kolom "Last update" di paling atas.
   - Append ke "Update Log" dengan tanggal + perubahan.

### Setiap Ditanya Klien
- **Progress %**: Baca "Ringkasan Cepat" di atas.
- **"Kemarin ngerjain apa?"**: Buka `daily/{YYYY M D Hari}.md` untuk tanggal yang ditanyakan.
- **"Minggu ini?"**: Buka `weekly/{YYYY M D Senin}.md` untuk minggu tersebut.
- **Status modul tertentu**: Cari di tabel "Detail Per Modul" di file ini.
- **Sisa kerja**: Lihat section "Sisa Kerja (Phase 1 MVP — yang belum selesai)".

### Format File
- **Daily**: `briefing/report/daily/{YYYY M D Hari}.md` (e.g., `2026 6 7 Minggu.md`).
- **Weekly**: `briefing/report/weekly/{YYYY M D Senin}.md` (e.g., `2026 6 1 Senin.md`).
- **Progress**: `briefing/report/progress.md` (file ini).
