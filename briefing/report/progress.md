# Project Progress — ERP RT/RW Net

> **Last update:** 2026-06-16 (Selasa) — Auth Security Layer 3 DONE (email verification pelanggan) + Playwright infra refactor (env-driven BASE_URL di 87 file)
> **Source of truth:** File ini adalah satu-satunya sumber kebenaran status modul & progress %.  
> **Update rule:** Setiap habis commit, file ini + `daily/` + `weekly/` di-update manual.  
> **Calculation basis:** Per-portal weighted average, dengan checklist (Backend / UI / Test / Docs) per modul.  
> **Catatan user (2026-06-07):** Estimasi awal terlalu optimis. Revisi di bawah ini lebih jujur.
>
> ⚠️ **CATATAN UPDATE 2026-06-16**: 5 commit baru sejak update 2026-06-14:
> - `0630542` — **Auth Security Layer 3 (Email Verification Pelanggan)**: register → email link → `email_verified_at` terisi → baru boleh login. Hard block di login kalau belum verified. Manual override field di web Perusahaan + Karyawan (badge Verified + tombol Tandai/Reset + bulk action). 1 tabel token + 1 migration alter + 1 controller + 1 notification + 1 Vue page + 2 Vue updates + 2 permission baru. **Status: dari "Phase 2" naik ke "DONE MVP"**.
> - `69256c1` — **feat(auth): pisah phone jadi country_code + number** di register pelanggan. Schema + UI form update.
> - `332a837` — **test(playwright): refactor baseUrl pakai env** `PLAYWRIGHT_BASE_URL` (initial 4 file)
> - `e9740de` — **test(playwright): refactor paths dinamis + DEEP login test** (semua 4 portal: page_renders + wrong_password + company_required + company_mismatch + inactive_user_rejected + guest_redirect; pre-create real inactive user via PHP helper, cek error message spesifik)
> - `7205bba` — **test(playwright): bulk refactor 86 file pakai env BASE_URL** (semua `http://erp-rt-rw-net.test` + `C:/laragon/...` + `/c/laragon/...` jadi dynamic; `bulk-refactor-baseurl.cjs` generator script)
>
> 3 lapis auth security (Turnstile + throttle + email verification) sudah **DONE** untuk Pelanggan. SaaS/Perusahaan/Karyawan tetap tanpa email verification (internal, tidak butuh).

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
| 6b | Tagihan Otomasi (cron) | ✅ | ✅ | ❌ | 🟡 | 🟡 75% (Day 1 artisan+scheduler done, Day 2-3 piutang+widget+UI carry-over) |
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
| 2 | Login Operator SaaS | ✅ 100% (termasuk **captcha + throttle**) |
| 3 | Login Perusahaan | ✅ 100% (termasuk **captcha + throttle**) |
| 4 | Login Karyawan | ✅ 100% (termasuk **captcha + throttle**) |
| 5 | Login Pelanggan | ✅ 100% (login + register, keduanya **captcha + throttle**) |
| 6 | Tentang Kami | ✅ 100% |
| 7 | Syarat & Ketentuan | ✅ 100% |
| 8 | Kebijakan Privasi | ✅ 100% |
| 9 | Hubungi Kami | ✅ 100% |
| 10 | Lupa Password Operator SaaS | ✅ 100% (form + email + reset, **captcha + throttle**) |
| 11 | Lupa Password Perusahaan | ✅ 100% (multi-tenant, **captcha + throttle**) |
| 12 | Lupa Password Karyawan | ✅ 100% (multi-tenant, **captcha + throttle**) |
| 13 | Lupa Password Pelanggan | ✅ 100% (multi-tenant, **captcha + throttle**) |

**Landing & Auth Subtotal: ~100%** (semua endpoint punya captcha + throttle 5/menit per IP)

**Security layer (3 lapis DONE 2026-06-16)**: 3 lapis proteksi di SEMUA auth endpoint Pelanggan (login + register + forgot + reset):
1. **Throttle 5/menit** per IP (shared counter across all routes) — anti-brute-force — DONE 2026-06-13
2. **Cloudflare Turnstile captcha** per attempt (testing key: 1x... always pass) — anti-bot — DONE 2026-06-14
3. **Email verification** (link di email + hard-block login kalau belum verified + manual override di web admin/karyawan) — DONE 2026-06-14 → 2026-06-16

SaaS/Perusahaan/Karyawan login tetap tanpa email verification (internal portal, bukan customer-facing).

---

## Sisa Kerja (Phase 1 MVP — diurutkan prioritas)

### 🔴 Prioritas Tinggi (Blok untuk launch)

#### ✅ Auth Security (DONE 2026-06-16) — 3 dari 3 lapis
- ✅ **Throttle 5/menit** di semua auth endpoint (login + register + forgot + reset) — DONE 2026-06-13
- ✅ **Cloudflare Turnstile captcha** di semua auth endpoint — DONE 2026-06-14
- ✅ **Email verification** untuk Pelanggan (register → email link → `email_verified_at` → baru boleh login) — DONE 2026-06-14 s.d 2026-06-16
  - Hard block di `CustomerSessionController::store()` kalau `email_verified_at = null`
  - Manual override: field `email_verified_at_action` di Edit modal Customer (Perusahaan + Karyawan) + bulk action "Tandai Verified"
  - Composite key `(email, company_id)` di tabel `email_verifications` (multi-tenant safe)
  - Token di-hash (`Hash::make(Str::random(64))`), expire 60 menit
  - Throttle 5/menit + Turnstile di route kirim-ulang
  - Test E2E `email-verification-pelanggan.cjs` (4 phase: register+verify, hard block login, kirim ulang, admin manual verify)

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
| 2026-06-08 (Senin, sore+) | ~60% | **FATAL security fix** company selection bypass: 3 login views tidak kirim company_id + 3 controller tidak validasi + CompanySearchInput pakai hardcoded fake data (id: 1,2,3) bukan UUID real. Exploit: user bisa login ke company manapun dengan email+password valid. Fix: 3 views add company_id via transform() + 3 controller add validation+match user.company_id + CompanySearchInput fetch dari real API + new endpoint GET /api/companies/search. Test 9/10 PASS (T1/T2/T4/T5 blocked, T3 correct company→dashboard, T6 SaaS no regression). |
| 2026-06-08 (Senin, malm) | ~60% | **Day 1 Otomasi Invoice**: InvoiceGeneratorService (reusable) + GenerateInvoicesCommand (artisan, --month/--due-days/--company) + scheduler (monthlyOn tgl 1 jam 00:00) + TagihanController refactored pakai service + hapus max=2099. 2 Migrations: add billing columns to cust_internets + drop unique invoice_number. Test: CLI 4/4 PASS (T1 idempotent 34 skipped, T2 new month 34 generated, T3 --due-days=14 due=2026-09-14, T4 --company scope 1 company) + UI 5/5 PASS (Generate button → modal → fill → submit → toast → INV-202610 in table). |
| **2026-06-09 s.d 2026-06-12 (gap)** | **~62%** | **(Backfill ringkasan, detail di git log)**: Multiple commit dashboard improvements (4 portal: SaaS, Perusahaan, Karyawan, Pelanggan pakai reusable StatCard + Hero), customer payment Midtrans integration (sandbox), karyawan feature parity (profil, tagihan, riwayat pembayaran, insentif), landing UI polish. Lupa password 4 portal initial implementation. Midtrans manual verification + webhook toggle. Lupa password composite key fix. Login ripple bug fix. |
| **2026-06-13 (Sabtu)** | **~62%** | Throttle 5/menit di 4 login portal (operator-saas, perusahaan, pelanggan, karyawan). Middleware `throttle:5,1` dengan shared counter per IP (Laravel default behavior). Test `LoginThrottleTest.cjs` verifikasi: 4 portal fresh → 5×422 + 1×429. Bug fix login button ripple 382x382. Eksplorasi Cloudflare Tunnel + HMR (tidak feasible: Chrome PNA policy block HTTPS→HTTP loopback). |
| **2026-06-14 (Minggu)** | **~62%** | **🔒 Auth Security Hardening Layer 2 (Cloudflare Turnstile)**: captcha di 4 login portal. Backend: `App\Rules\Turnstile` validation rule (call siteverify API) + 4 controllers + `turnstile_site_key` shared via Inertia. Frontend: 4 login pages widget + window callbacks. Test `TurnstileTest.cjs` 4/4 PASS (422 tanpa captcha) + `TurnstileVisualTest.cjs` 5/6 PASS (happy path superadmin → dashboard). |
| **2026-06-14 (Minggu, siang)** | **~62%** | **🔒 Auth Security Layer 2 lanjutan + Layer 1 upgrade**: Turnstile + throttle 5/menit di customer register + 4 portal lupa/reset password (upgrade throttle dari 30,1 → 5,1). Backend: `ForgotPasswordController::store/update` + `CustomerSessionController::register` validation. Frontend: 4 Vue lupa password pages widget. Test `AuthCaptchaTest.cjs` 15/15 PASS (1 register + 4 forgot + 4 reset + throttle 5/menit). |
| 2026-06-14 (Minggu, malm) | ~63% | **🔒 Auth Security Layer 3 (Email Verification) — initial scaffold**: schema (1 tabel token `email_verifications` + alter `customers` tambah `email_verified_at`) + `EmailVerificationController` (`send`, `confirm`, `form`) + `VerifyEmailNotification` + email template `verify-email.blade.php` + Vue `VerifikasiEmail.vue` + 3 route (`/verifikasi-email-pelanggan`, `/kirim-ulang-verifikasi-pelanggan`, `/verifikasi-email-pelanggan/konfirmasi`) + permission enum `CustomerVerifyEmail` + `KaryawanCustomerVerifyEmail` + `PermissionSeeder` update. Hard block di `CustomerSessionController::store()` (throw ValidationException kalau `email_verified_at = null`) + auto-logout setelah register + redirect ke `/verifikasi-email-pelanggan` (bukan auto-login). Test E2E `email-verification-pelanggan.cjs` 4 phase: register new + verify, hard block login, kirim ulang, admin manual verify. |
| 2026-06-15 (Senin) | ~63% | **Customer Web update (field `email_verified_at`)**: Vue `OperatorPerusahaan/Customer.vue` + `Karyawan/Customer.vue` tambah kolom "Email Verified" di table (badge Verified/Belum) + section di Edit modal (tombol "Tandai Verified" / "Reset Verifikasi" + tanggal) + bulk action "Tandai Verified" toolbar. Backend: `CustomerController::index()` include `email_verified_at` di payload, `update()` terima `email_verified_at_action` ('set'|'reset'|null) → set/reset timestamp, new `bulkVerifyEmail(Request)` + 2 route POST `bulk-verify-email` di Perusahaan + Karyawan. |
| 2026-06-15 (Senin, siang) | ~63% | **feat(auth): pisah phone jadi country_code + number** di register pelanggan (`69256c1`). Schema update: `country_code` + `phone_number` (2 kolom terpisah, bukan 1 string). UI form: 1 baris 2 kolom (kode negara select + telp input) pakai `CountryCodeSelect` component. |
| 2026-06-16 (Selasa, pagi) | ~63% | **test(playwright): refactor baseUrl pakai env** `PLAYWRIGHT_BASE_URL` (`332a837`). Initial refactor 4 file: `feature/auth/EmailVerification/email-verification-pelanggan.cjs` + `feature/OperatorPerusahaan/email-verified-at-admin.cjs` + `feature/Karyawan/email-verified-at-karyawan.cjs` + support `baseUrl.cjs` (env loader, prioritas: process.env → .env → default). Path PROJECT_ROOT dynamic pakai `path.resolve(__dirname, '..', '..', '..')`. |
| 2026-06-16 (Selasa, siang) | ~63% | **test(playwright): refactor paths dinamis + DEEP login test (3 issue)** (`e9740de`). Issue 1: form register pelanggan layout fix (email 1 baris full-width, phone 1 baris 2 kolom). Issue 2: 4 file test pakai baseUrl env-driven (initial, lanjutan dari 332a837). Issue 3: LoginTest DEEP rewrite untuk 4 portal — pre-create real inactive user via PHP helper `testUsers.cjs` (UUID v7 + bcrypt via `User::create()`), test 6-7 case per portal: page_renders, wrong_password (cek error "credentials"/"tidak"), company_required (form tanpa company → error "Pilih perusahaan"), company_mismatch (admin company A + company B → error "tidak terdaftar di perusahaan"), inactive_user_rejected (real inactive user → error "dinonaktifkan"), unverified_email_rejected (Pelanggan only), guest_redirect_to_login. Soft assert pattern `safeTest(name, fn)`. Native Vue setter workaround untuk fill input. Verify via `getErrorTexts()` (`.text-red-500/600/700` + `[role="alert"]`). |
| 2026-06-16 (Selasa, sore) | ~63% | **test(playwright): bulk refactor 86 file pakai env BASE_URL** (`7205bba`). Script generator `support/bulk-refactor-baseurl.cjs` (auto-detect depth folder, 3 pattern handled: `const BASE = 'http://...'`, `this.baseUrl = 'http://...'` class-based → comment + ganti ke `${BASE}`, inline URL di `page.goto()` → `BASE + '/path'`). Plus fix `C:/laragon/...` di require PlaywrightHelper (21 file) → relative path. Plus fix `/c/laragon/...` di `execSync` bash (7 file) → `${PROJECT_BASH}`. Plus fix `cwd: 'C:/laragon/...'` di `DaftarPaketRBACTest` → `path.resolve`. Plus fix hardcoded `C:\\laragon\\...` di PHP bootstrap (3 file) → `${PROJ_WIN}`. Plus fix hardcoded screenshot path di 3 debug script → `path.join(__dirname, '..', 'result', ...)`. **Hasil: 0 hardcoded path tersisa di Feature/*.cjs (non-result)**. |

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
