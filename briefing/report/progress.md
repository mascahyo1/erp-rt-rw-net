# Project Progress — ERP RT/RW Net

> **Last update:** 2026-06-08 (Senin) — bug fix CountryCodeSelect, no progress delta  
> **Source of truth:** File ini adalah satu-satunya sumber kebenaran status modul & progress %.  
> **Update rule:** Setiap habis commit, file ini + `daily/` + `weekly/` di-update manual.  
> **Calculation basis:** Per-portal weighted average, dengan checklist (Backend / UI / Test / Docs) per modul.  
> **Catatan user (2026-06-07):** Estimasi awal terlalu optimis. Revisi di bawah ini lebih jujur.

---

## Ringkasan Cepat (untuk ditanya klien)

| Metrik | Nilai |
|---|---|
| **Overall Progress** | **~60%** (Phase 1 MVP, **di-revisi** dari 92% awal yang over-estimate) |
| **SaaS Portal** | **~70%** — hampir selesai, deep test belum diverifikasi aman |
| **Perusahaan Portal** | **~65%** — hampir selesai, otomasi (generate invoice cron, cek belum bayar) masih PR banyak |
| **Karyawan Portal** | **~20%** — login doang, 7 menu lain kosong / minimal |
| **Customer Portal** | **~15%** — login doang, 5 menu lain kosong / minimal |
| **Landing & Auth** | **~100%** — fully done |
| **Sisa Kerja (Phase 1)** | **Significant**: otomasi cron, fill karyawan + customer pages, deep test many modules, polish |

### Jawaban Cepat untuk Klien

**"Progress sampai mana?"** → "MVP Phase 1 sudah ~60% selesai. SaaS + Perusahaan hampir selesai (masing-masing ~70% / ~65%) tapi masih ada PR penting. Karyawan + Customer masih login doang, perlu diisi. Plus otomasi generate invoice + cek piutang masih PR."

**"Kemarin ngerjain apa?"** → Lihat `daily/` untuk hari yang ditanyakan.

**"Minggu ini ngerjain apa?"** → Lihat `weekly/` untuk minggu tersebut.

**"Sisa kerja apa?"** → Lihat section "Sisa Kerja" di bawah. Highlight: otomasi cron generate invoice, isi konten karyawan + customer portal, deep test banyak modul.

---

## Detail Per Modul (Status Jujur)

### Checklist Schema
Setiap modul dicek 4 aspek:
- **Backend**: Model + Controller + Routes + Permission
- **UI**: Vue page + table + form modal + filter + sort + search
- **Test**: Playwright deep verify (headed)
- **Docs**: Halaman dokumentasi di `dokumentasi/`

Status: ✅ Done | 🟡 Partial | ❌ Not Started | ➖ N/A

### Realita per Portal

**Operator SaaS** (hampir selesai, deep test unsure): semua 10 modul punya Backend ✅ UI ✅ Docs ✅. Tapi deep test banyak yang belum diverifikasi "aman" (banyak yang belum ditulis sama sekali). Plus Dashboard belum punya tests.

**Operator Perusahaan** (hampir selesai, otomasi gap): CRUD ✅ UI ✅ untuk ~12 modul. Tapi:
- ❌ **OTOMASI**: `php artisan invoice:generate` belum ada — hanya manual button di `TagihanController::generate()`
- ❌ **OTOMASI**: Cek piutang outstanding / "siapa saja yang belum bayar" belum ada dashboard/report khusus
- ❌ **OTOMASI**: Scheduler cron belum ada (routes/console.php kosong)
- 🟡 Test coverage banyak yang belum deep

**Karyawan** (baru login doang):
- ✅ Login page works
- 🟡 Dashboard, Customer, LanggananCustomer, Tagihan, InsentifSaya, RiwayatPembayaran — Vue page ADA di filesystem tapi **konten minimal / placeholder**
- ❌ Most functionality: belum ada CRUD operations
- ❌ Tests: belum ada

**Customer** (baru login doang):
- ✅ Login page works
- 🟡 Dashboard, ProfilSaya, PaketSaya, TagihanSaya, RiwayatPembayaran — Vue page ADA tapi **konten minimal / placeholder**
- ❌ Most functionality: read-only belum connected to real data
- ❌ Tests: belum ada

---

### Operator SaaS Portal (`/operator-saas/*`) — 10/10 modules

| # | Modul | Backend | UI | Test | Docs | Status |
|---|---|---|---|---|---|---|
| 1 | Dashboard | ✅ | ✅ | ➖ | ❌ | 🟡 75% |
| 2 | Admin Perusahaan | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 3 | Perusahaan | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 4 | Role Perusahaan | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 5 | Role Admin Perusahaan | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 6 | Konfigurasi | ✅ | ✅ | ✅ | ✅ | ✅ 100% |
| 7 | Role SaaS | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 8 | Admin SaaS | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 9 | Admin Role SaaS | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 10 | Profil Saya | ✅ | ✅ | 🟡 | ✅ | 🟡 75% (search bug fixed 2026-06-08) |

**SaaS Subtotal: ~70%** (Backend+UI+Docs mostly ✅, tapi deep test banyak ❌)

---

### Operator Perusahaan Portal (`/operator-perusahaan/*`) — 16/16 modules

| # | Modul | Backend | UI | Test | Docs | Status |
|---|---|---|---|---|---|---|
| 1 | Dashboard | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 2 | Perusahaan Saya | ✅ | ✅ | ✅ | ✅ | ✅ 100% |
| 3 | Daftar Paket | ✅ | ✅ | ✅ | ✅ | ✅ 100% |
| 4 | Customer | ✅ | ✅ | ✅ | ✅ | ✅ 100% (responsive top bar + 5 modal fix 2026-06-08) |
| 5 | Langganan Customer | ✅ | ✅ | ✅ | ✅ | ✅ 100% |
| 6 | Tagihan (CRUD) | ✅ | ✅ | 🟡 | ✅ | 🟡 88% |
| 6a | Tagihan Generate (manual) | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 6b | Tagihan Otomasi (cron) | ❌ | ❌ | ❌ | 🟡 | ❌ 0% |
| 6c | Tagihan Piutang Report | ❌ | ❌ | ❌ | ❌ | ❌ 0% |
| 7 | Insentif | ✅ | ✅ | 🟡 | ✅ | 🟡 75% |
| 8 | Riwayat Insentif | ✅ | ✅ | ✅ | ✅ | ✅ 100% |
| 9 | Riwayat Pembayaran | ✅ | ✅ | 🟡 | ✅ | 🟡 75% |
| 10 | Admin Perusahaan | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 11 | Role Perusahaan | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 12 | Admin Role Perusahaan | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 13 | Karyawan | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 14 | Role Web Karyawan | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 15 | Admin Role Web Karyawan | ✅ | ✅ | ❌ | ✅ | 🟡 75% |
| 16 | Konfigurasi Perusahaan | ✅ | ✅ | ✅ | ✅ | ✅ 100% |

**Perusahaan Subtotal: ~65%** (CRUD ✅ tapi **otomasi 0% + deep test banyak ❌**)

---

### Karyawan Portal (`/karyawan/*`) — 7/7 modules

| # | Modul | Backend | UI | Test | Docs | Status |
|---|---|---|---|---|---|---|
| 1 | Login | ✅ | ✅ | 🟡 | ✅ | ✅ 100% |
| 2 | Dashboard | 🟡 | ✅ | ❌ | ✅ | 🟡 50% |
| 3 | Profil Saya | 🟡 | ✅ | ❌ | ✅ | 🟡 50% |
| 4 | Customer (read) | 🟡 | ✅ | ❌ | ✅ | 🟡 50% |
| 5 | Langganan Customer (read) | 🟡 | ✅ | ❌ | ✅ | 🟡 50% |
| 6 | Tagihan + input bayar | 🟡 | ✅ | ❌ | ✅ | 🟡 50% |
| 7 | Insentif Saya | 🟡 | ✅ | ❌ | ✅ | 🟡 50% |
| 8 | Riwayat Pembayaran | 🟡 | ✅ | ❌ | ✅ | 🟡 50% |

**Karyawan Subtotal: ~20%** (halaman Vue ada tapi backend logic & content minimal)

---

### Customer Portal (`/customer/*`) — 5/5 modules

| # | Modul | Backend | UI | Test | Docs | Status |
|---|---|---|---|---|---|---|
| 1 | Login | ✅ | ✅ | 🟡 | ✅ | ✅ 100% |
| 2 | Dashboard | ❌ | 🟡 | ❌ | ✅ | ❌ 25% |
| 3 | Profil Saya | 🟡 | ✅ | ❌ | ✅ | 🟡 50% |
| 4 | Paket Saya | ❌ | 🟡 | ❌ | ✅ | ❌ 25% |
| 5 | Tagihan Saya | ❌ | 🟡 | ❌ | ✅ | ❌ 25% |
| 6 | Riwayat Pembayaran | ❌ | 🟡 | ❌ | ✅ | ❌ 25% |

**Customer Subtotal: ~15%** (Vue page ada tapi backend data + functionality belum ada)

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

**Landing Subtotal: ~100%**

---

## Sisa Kerja (Phase 1 MVP — diurutkan prioritas)

### 🔴 Prioritas Tinggi (Blok untuk launch)

#### OTOMASI (Paling Penting, menurut user)
- [ ] **Generate Invoice otomatis via cron** — `php artisan invoice:generate` (scheduler awal bulan)
  - Saat ini: hanya manual button di `TagihanController::generate()` (sudah ada)
  - Yang kurang: artisan command + scheduler entry di `routes/console.php` atau `app/Console/Kernel.php`
  - Effort: ~1 hari (command + test + scheduler entry)
- [ ] **Cek Piutang / "Siapa saja yang belum bayar"** — dashboard widget atau halaman khusus
  - List customer dengan tagihan overdue (status `kadaluarsa` atau `belum_bayar` lewat jatuh tempo)
  - Export ke Excel
  - Notifikasi ke kolektor
  - Effort: ~2 hari
- [ ] **Tagihan Auto-Kadaluarsa** — cron ubah status `belum_bayar` lewat jatuh tempo → `kadaluarsa`
  - Effort: ~0.5 hari

#### Karyawan Portal — Fill in real functionality
- [ ] **Dashboard Karyawan** — widget data real (tagihan assigned, insentif, top customers)
  - Saat ini: Vue page ada tapi data hardcoded/minimal
  - Effort: ~2 hari
- [ ] **Tagihan input bayar (Karyawan)** — form pembayaran tunai + upload bukti
  - Saat ini: Vue page ada, backend partial
  - Effort: ~3 hari (modal form + upload + insentif auto-generate)
- [ ] **Customer (Karyawan read-only)** — list + detail + lihat tagihan per customer
  - Effort: ~1 hari
- [ ] **Insentif Saya** — list + filter + lihat insentif riil
  - Effort: ~1 hari
- [ ] **Riwayat Pembayaran (Karyawan)** — list pembayaran yang di-input sendiri
  - Effort: ~1 hari

#### Customer Portal — Fill in real functionality
- [ ] **Dashboard Customer** — tagihan belum bayar, info paket, histori
  - Effort: ~1.5 hari
- [ ] **Paket Saya** — list paket aktif + histori
  - Effort: ~1 hari
- [ ] **Tagihan Saya** — list tagihan + filter + download PDF invoice
  - Effort: ~2 hari
- [ ] **Profil Saya** — edit + upload foto
  - Effort: ~1 hari
- [ ] **Riwayat Pembayaran** — list + download kwitansi
  - Effort: ~1.5 hari

### 🟡 Prioritas Sedang

#### Test Coverage (Deep Verify)
- [ ] Tambah Playwright deep verify untuk Tagihan (Operator Perusahaan) — CRUD + cetak PDF + otomasi flow
- [ ] Tambah Playwright deep verify untuk Insentif (Operator Perusahaan) — CRUD + bulk action
- [ ] Tambah Playwright deep verify untuk Riwayat Pembayaran (Operator Perusahaan) — review flow
- [ ] Tambah Playwright deep verify untuk Karyawan (Operator Perusahaan) — CRUD
- [ ] Tambah Playwright deep verify untuk SaaS modules (Admin Perusahaan, Perusahaan, Role)
- [ ] Tambah Playwright deep verify untuk Karyawan portal (semua menu)
- [ ] Tambah Playwright deep verify untuk Customer portal (semua menu)
- [ ] Tambah Playwright deep verify untuk Landing + Auth flows

#### Polish & Performance
- [ ] Image loading optimization (lazy load, srcset)
- [ ] Vite bundle analysis (cari chunk besar)
- [ ] Lighthouse audit (LCP < 2.5s target)
- [ ] Error monitoring setup (Sentry atau similar)

### 🟢 Prioritas Rendah (Nice to Have)

- [ ] Dashboard SaaS, Perusahaan, Karyawan, Customer — test + wire data real
- [ ] A11y audit (WCAG 2.1 AA)
- [ ] Production deployment prep (CI/CD, backup script, monitoring)
- [ ] Documentation: tambah flow diagram, screenshot

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
| 2026-05-26 | ~70% | Riwayat Insentif done (revisi: ~30% overall) |
| 2026-05-28 | ~72% | Nota tagihan (PDF) added |
| 2026-05-31 | ~78% | Bukti Bayar CRUD + PDF |
| 2026-06-01 | ~80% | Role Perusahaan improvements |
| 2026-06-05 | ~88% | Redesain tagihan + finalisasi |
| 2026-06-06 | ~90% | Tagihan fix (revisi: ~55% overall) |
| **2026-06-07 (Minggu pagi)** | **~92%** | **Konfigurasi SaaS/Perusahaan 100%, BRD/PRD v1.1.0** |
| **2026-06-07 (Minggu sore — REVISI USER)** | **~60%** | **User merevisi: SaaS 70% (deep test unsure), Perusahaan 65% (otomasi gap), Karyawan 20% (login doang), Customer 15% (login doang). Ditambah: otomasi generate invoice cron + cek piutang masih PR banyak.** |
| 2026-06-08 (Senin) | ~60% | Bug fix CountryCodeSelect: document keydown preventDefault() block typing di search input. Fix affects 4 profil-saya pages + form Admin. 2 commit (fix + memory gotcha). No progress delta (bug fix, bukan fitur baru). Test semua pass, 0 regression. |
| 2026-06-08 (Senin, siang) | ~60% | UI fix Customer.vue: CountryCodeSelect height mismatch (30 vs 42px) di desktop + horizontal overflow 93px di mobile. 1 commit, 1 file. Pattern fix (size default + w responsive + min-w-0) bisa di-replicate ke 4 profil-saya + form Admin lain. Carry-over: apply ke page lain. |
| 2026-06-08 (Senin, sore) | ~60% | UI fix Customer.vue comprehensive: top bar responsive (mobile: tombol full-width stacked, single line) + 5 modal responsive (padding px-4 sm:px-6, title text-base sm:text-lg, footer flex-col-reverse). 5 viewport × 2 theme × 5 modal tested, 0 overflow issues, no regression. CRUD 19/20 PASS. Pattern fix bisa di-replicate ke 5 page lain (Karyawan, AdminPerusahaan, AdminSaaS, Perusahaan, AdminRolePerusahaan). |

---

## Estimasi Effort Sisa (Rough)

Berdasarkan sisa kerja di section "🔴 Prioritas Tinggi":
- OTOMASI: ~3.5 hari (generate cron, piutang report, auto-kadaluarsa)
- Karyawan Portal fill: ~8 hari
- Customer Portal fill: ~7 hari
- Test coverage untuk modul existing: ~5 hari
- **Total estimasi: ~23-25 hari kerja** (1 developer, full focus)

Plus polish + production prep ~5-7 hari tambahan.

**Realistic MVP launch: ~1-1.5 bulan lagi** dengan 1 developer dedicated.
