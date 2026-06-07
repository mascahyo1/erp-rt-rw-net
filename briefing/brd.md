# BRD — ERP RT/RW Net

> **Versi:** 1.1.0  
> **Tanggal update:** 2026-06-07  
> **Status:** Aktif (in-development)  
> **Owner:** Internal Team  
> **Menggantikan:** `briefing/brd.md` v1.0 (5 Mei 2026)

---

## 1. Latar Belakang

### 1.1 Konteks Industri
Penyedia layanan internet skala **RT/RW Net** (Rukun Tetangga / Rukun Warga Net) adalah operator ISP komunitas yang melayani kawasan perumahan padat di Indonesia. Model bisnis: warga satu RT/RW patungan biaya infrastruktur (kabel fiber / wireless), tagihan iuran bulanan ke operator, operator bagi hasil dengan kolektor lapangan.

### 1.2 Statistik Pasar (estimasi 2024-2026)
| Metrik | Nilai | Sumber |
|---|---|---|
| Jumlah RT/RW Net di Indonesia | ~25.000–40.000 operator | Asosiasi RT/RW Net, observasi komunitas |
| Operator dengan 100–500 pelanggan | Mayoritas (60%+) | Observasi lapangan |
| Operator dengan > 1000 pelanggan | ± 5% | Sama |
| Pangsa pasar RT/RW Net di ISP lokal | Signifikan di kota tier 2–3, kawasan padat | Sama |
| Pertumbuhan per tahun | 10–15% (ekspansi ke kawasan baru) | Sama |

### 1.3 Masalah Operasional
- **Pencatatan manual**: pelanggan, paket, tagihan, pembayaran di buku / spreadsheet terpisah.
- **Penagihan tidak terstruktur**: tidak ada tracking status pembayaran real-time.
- **Insentif kolektor tidak terukur**: penghargaan untuk penagih lapangan tidak transparan.
- **Multi-RT/RW Net**: pemilik yang mengelola beberapa operator tidak bisa mengelola semuanya dalam satu sistem.
- **Laporan manual**: rekap dari spreadsheet butuh waktu berjam-jam.
- **Bukti pembayaran hilang**: kwitansi kertas / foto bukti transfer tidak terarsip rapi.
- **Konfigurasi tersebar**: setting seperti ukuran upload, kompresi gambar, kontak perusahaan tersebar di kode.

---

## 2. Pernyataan Masalah (Problem Statement)

1. **Pencatatan manual** → tidak efisien, rentan kesalahan, sulit diaudit.
2. **Penagihan tidak terstruktur** → tidak ada tracking status pembayaran per pelanggan secara real-time.
3. **Insentif penagih tidak tercatat** → karyawan lapangan tidak mendapat penghargaan terukur dan transparan.
4. **Tidak ada laporan terpadu** → pemilik kesulitan mendapatkan laporan periodik.
5. **Multi-perusahaan** → pemilik beberapa RT/RW Net tidak bisa mengelola semuanya dalam satu sistem.
6. **Bukti pembayaran tidak terarsip** → dispute pelanggan sulit diselesaikan.
7. **Konfigurasi sistem tersebar** → setting tidak ada admin panel untuk ubah tanpa deploy.

---

## 3. Tujuan (Goals)

Membangun **ERP berbasis web** yang:
- Mendigitalisasi pencatatan pelanggan, paket, penagihan, dan pembayaran.
- Menyediakan **pelacakan status penagihan** dan **riwayat transaksi** secara real-time.
- Menghitung & mencatat **insentif karyawan penagih** (tunai & non-tunai) secara otomatis dengan alur approval.
- Menyajikan **laporan harian / mingguan / bulanan / tahunan** yang siap pakai (PDF + Excel).
- Mendukung **multi-perusahaan (multi-tenant)** dengan **Dynamic RBAC & granular permission** per tenant.
- Memberikan **pengalaman pengguna modern**: SPA, responsif, dark/light/auto theme.
- Menyediakan **portal terpisah** untuk tiap persona: Super Admin (SaaS), Admin Perusahaan, Karyawan lapangan, Pelanggan.
- **Import/Export Excel** masif untuk migrasi data dari sistem lama & backup operasional.
- **Cetak PDF** invoice, kwitansi, dan laporan dengan logo perusahaan.
- **Audit trail** lengkap untuk semua aksi sensitif.

---

## 4. Cakupan Fitur (High-Level Scope)

| Modul | Deskripsi |
|---|---|
| Multi-Tenant + Dynamic RBAC | Isolasi data per perusahaan, role & permission customizable per tenant |
| Multi-Guard Authentication | 4 guard terpisah: admin-saas, admin-company, employee, customer |
| Landing Page | Portal publik + 4 halaman login (SaaS, Perusahaan, Karyawan, Customer) |
| Manajemen Perusahaan (SaaS) | CRUD perusahaan oleh Operator SaaS, aktivasi/nonaktif |
| Perusahaan Saya | Self-management perusahaan oleh Admin Perusahaan (profil, logo, alamat) |
| Manajemen Karyawan | CRUD karyawan, assign ke perusahaan, role web karyawan |
| Manajemen Pelanggan | CRUD pelanggan, status aktif/nonaktif, foto profil, KTP, KK, koordinat GPS |
| Manajemen Paket (Daftar Paket) | CRUD paket internet, harga, bandwidth, masa aktif |
| Paket Pelanggan (Langganan) | Assign paket ke pelanggan, tracking masa aktif, histori perubahan |
| Penagihan (Invoicing) | Generate tagihan otomatis per bulan, tracking status, cetak PDF |
| Pembayaran Pelanggan | Metode pembayaran (Tunai / Transfer), upload bukti, review |
| Riwayat Pembayaran | Tracking semua pembayaran, review oleh admin, approval flow |
| Insentif Karyawan | Kalkulasi otomatis, histori per karyawan, approval + pencairan |
| Riwayat Insentif | Tracking insentif yang sudah/belum di-approve, review flow |
| Laporan | Harian, mingguan, bulanan, tahunan — pendapatan, piutang, insentif, performa |
| Konfigurasi SaaS | Setting platform-level: upload limit, auto-compress, kontak, email |
| Konfigurasi Perusahaan | Setting per-tenant: tagline, kontak, branding |
| Dashboard Realtime | Widget statistik via Laravel Reverb WebSocket |
| UI/UX Modern | Dark/Light/Auto mode, Responsif, SPA via Inertia.js |
| Import/Export Excel | Untuk hampir semua modul CRUD (template + download) |
| PDF Generation | Invoice cetak, kwitansi pembayaran, laporan, logo perusahaan |
| Audit Trail | Log semua aksi sensitif (spatie/laravel-activitylog) |

---

## 4b. Persyaratan UI/UX (Non-Fungsional Frontend)

| Aspek | Spesifikasi |
|---|---|
| **Color Mode** | 3 pilihan: 🌞 Light Mode, 🌙 Dark Mode, 🖥️ Auto (mengikuti preferensi OS via `prefers-color-scheme`). Disimpan di localStorage per user. Toggle switch di navbar & halaman profil. |
| **Responsivitas** | Mobile-first design. Breakpoint: `sm` (640px), `md` (768px), `lg` (1024px), `xl` (1280px), `2xl` (1536px). Sidebar auto-collapse di layar kecil. Tabel jadi swipeable card di mobile. |
| **SPA Experience** | Inertia.js — navigasi antar halaman tanpa full page reload. Loading progress bar (NProgress style). Preserve scroll position. Partial reloads untuk performa. |
| **Modern Aesthetic** | Gradasi subtle, glassmorphism (backdrop blur), micro-interactions, skeleton loading, smooth transitions (200-300ms), rounded corners (xl/2xl), shadow hierarchy. |
| **Aksesibilitas (A11y)** | WCAG 2.1 AA minimal. Kontras warna cukup, focus ring terlihat, ARIA labels, keyboard navigable, screen reader friendly. |
| **Tipografi** | Font Inter (body) + JetBrains Mono (kode/angka). Consistent type scale (12px–48px). |
| **Animasi** | Transisi halaman Inertia (fade/slide), hover effects, skeleton loader, toast notifications animasi slide-in. |

---

## 5. Stakeholder & User Personas

### 5.1 Stakeholder

| Stakeholder | Tipe | Interest | Influence |
|---|---|---|---|
| **Pemilik Platform SaaS** | Internal | Revenue recurring, kepuasan tenant, scale | Tinggi |
| **Operator SaaS (Super Admin)** | Primary user | Manage semua tenant, support, billing | Tinggi |
| **Admin Perusahaan (Tenant Admin)** | Primary user | Operasional harian RT/RW Net, full kontrol | Tinggi |
| **Karyawan (Penagih/Kolektor)** | Primary user | Tagih pelanggan, catat pembayaran, lihat insentif | Sedang |
| **Pelanggan** | End user | Lihat tagihan, bayar, histori | Rendah |
| **Owner/Pemilik RT/RW Net** | Business sponsor | Laporan, profit, scale ke RT/RW baru | Tinggi |
| **Tim Dev / Support** | Internal | Maintainability, bug response time | Sedang |
| **Penyedia Infrastruktur (Mikrotik, dll)** | External | Integrasi API, technical compatibility | Rendah |

### 5.2 User Personas

#### Persona 1: Operator SaaS (Pak Budi)
- **Profil**: Super admin platform, 35 tahun, paham teknologi.
- **Goal**: Onboarding tenant baru, monitor kesehatan platform, support tenant.
- **Pain**: Bayar tim support besar, banyak tenant komplain manual.
- **Permission**: Semua permission di scope `operator_saas` (manage perusahaan, manage admin-perusahaan, manage role-saas, manage konfigurasi SaaS).
- **KPI**: Tenant aktif, MRR (Monthly Recurring Revenue), churn rate.

#### Persona 2: Admin Perusahaan (Bu Sari)
- **Profil**: Admin RT/RW Net "Net Sejahtera", 28 tahun, multi-tasking.
- **Goal**: Kelola 200+ pelanggan, generate tagihan, track kolektor, lihat laporan.
- **Pain**: Spreadsheet berantakan, kolektor komplain soal insentif tidak jelas.
- **Permission**: Semua permission di scope `company` sesuai role (owner / admin / admin-keuangan).
- **KPI**: Piutang tertagih, kepuasan pelanggan, efisiensi operasional.

#### Persona 3: Karyawan Penagih (Mas Andi)
- **Profil**: Kolektor lapangan, 25 tahun, mobile-first user.
- **Goal**: Catat pembayaran tunai di lapangan, upload bukti transfer, lihat insentif sendiri.
- **Pain**: Harus ke kantor dulu untuk catat, foto bukti HP tidak ter-sync.
- **Permission**: Terbatas di `karyawan` portal — lihat tagihan assigned, input pembayaran, lihat insentif sendiri.
- **KPI**: Jumlah tagihan tertagih per hari, insentif terakumulasi.

#### Persona 4: Pelanggan (Ibu Rina)
- **Profil**: Pelanggan rumahan, 40 tahun, internet-only user.
- **Goal**: Cek tagihan, bayar online, lihat histori.
- **Pain**: Tidak yakin tagihan sudah bayar atau belum, kwitansi hilang.
- **Permission**: Read-only portal `customer` — lihat tagihan sendiri, lihat histori pembayaran sendiri.
- **KPI**: Self-service adoption, mengurangi komplain ke admin.

---

## 6. User Journeys

### 6.1 Journey: Pembayaran Tunai oleh Karyawan
```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Karyawan  │───▶│ Login    │───▶│ Pilih    │───▶│ Input    │───▶│ Submit   │
│ di        │    │ karyawan │    │ tagihan  │    │ jumlah   │    │ tunai    │
│ lapangan  │    │ portal   │    │ pelanggan│    │ bayar    │    │          │
└──────────┘    └──────────┘    └──────────┘    └──────────┘    └──────────┘
                                                                  │
                                                                  ▼
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Insentif │◀───│ Auto     │◀───│ Invoice  │◀───│ Status:  │
│ tercatat │    │ kalkulasi│    │ LUNAS    │    │ LUNAS    │
│ (pending)│    │ (job)    │    │ updated  │    │ + notif  │
└──────────┘    └──────────┘    └──────────┘    └──────────┘
```

**Happy path**: Karyawan login → cari tagihan → input nominal → submit → status lunas → insentif otomatis ke-queue → notifikasi ke admin.

### 6.2 Journey: Pembayaran Transfer oleh Pelanggan
```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│Pelanggan │───▶│ Transfer │───▶│ Upload   │───▶│ Status:  │───▶│ Admin    │
│buka      │    │ ke       │    │ bukti    │    │ MENUNGGU│    │ review   │
│portal    │    │ rek.kary.│    │ transfer │    │ KONFIRM │    │ + approve│
└──────────┘    └──────────┘    └──────────┘    └──────────┘    └──────────┘
                                                                  │
                                                                  ▼
                                                             ┌──────────┐
                                                             │ Status:  │
                                                             │ LUNAS    │
                                                             │ + insentif│
                                                             └──────────┘
```

### 6.3 Journey: Onboarding Tenant Baru
```
Operator SaaS → Landing page → Login → Dashboard SaaS → Tambah Perusahaan
→ Isi profil + logo + branding → Assign admin perusahaan → Set konfigurasi awal
→ Admin perusahaan terima email invitation → Login pertama → Setup paket & pelanggan
```

### 6.4 Journey: Generate Tagihan Bulanan (Auto)
```
Cron (1st of month) → Generate tagihan untuk semua pelanggan aktif
→ Kirim notifikasi ke masing-masing pelanggan (opsional WA/email)
→ Tagihan muncul di portal karyawan + customer
```

### 6.5 Journey: Konfigurasi Upload & Kompresi
```
Admin SaaS login → Konfigurasi SaaS → Set max upload size (KB), max resolution, auto-compress
→ Sistem validasi & kompresi otomatis saat upload (foto profil, KTP, KK, bukti bayar)
→ Hemat storage S3/MinIO, file konsisten untuk PDF
```

---

## 7. Risk Analysis

| # | Risiko | Dampak | Probabilitas | Mitigasi |
|---|---|---|---|---|
| R1 | **Data loss** (DB corrupt / server failure) | Kritis | Rendah | Backup harian otomatis, retensi 30 hari, off-site backup, restore procedure tested quarterly |
| R2 | **Payment fraud** (bukti transfer palsu) | Tinggi | Sedang | Verifikasi manual oleh admin; future: integrasi bank API (open banking) untuk auto-validasi |
| R3 | **RBAC bypass** (user naik hak akses sendiri) | Kritis | Rendah | Server-side authorization di semua endpoint (Laravel Policy + middleware), audit trail semua perubahan role, code review mandatory |
| R4 | **Multi-tenant data leak** (tenant A lihat data tenant B) | Kritis | Rendah | Global scope otomatis di setiap model tenant-scoped, integration test mandatory, code review |
| R5 | **Storage cost blow-up** (S3/MinIO penuh) | Sedang | Sedang | Image auto-compress ke WebP, max resolution 1920px, max file size 2MB, monitoring storage |
| R6 | **WebSocket connection storm** (Reverb down saat banyak user) | Sedang | Sedang | Reverb auto-restart, fallback ke polling, rate limit per IP |
| R7 | **File upload abuse** (user upload file besar / berbahaya) | Sedang | Sedang | Validasi mime type + extension + max size di server, MIME sniff, antivirus scan (future) |
| R8 | **Konfigurasi rusak** (admin set value tidak valid) | Rendah | Sedang | Validasi tipe (text/number/boolean/kredensial) di input + filter_var untuk boolean normalization |
| R9 | **Customer churn** (pelanggan pindah provider) | Sedang | Tinggi | Self-service portal, laporan kepuasan, notifikasi tagihan tepat waktu, lihat histori bayar |
| R10 | **Scope creep** (tambah fitur tanpa prioritas) | Sedang | Tinggi | MVP prioritization ketat, setiap fitur baru lewat review Business vs Engineering |

---

## 8. Success Metrics (KPI)

### 8.1 KPI Operasional
| Metrik | Target | Sumber Data |
|---|---|---|
| Tenant aktif | ≥ 50 dalam 6 bulan pertama | `companies` table (is_active = true) |
| Avg tagihan tertagih per tenant | ≥ 85% dari total tagihan | `tagihan` table (status = 'lunas') |
| Rata-rata waktu approve insentif | ≤ 3 hari | `insentif` table (created_at → approved_at) |
| Rata-rata waktu review bukti transfer | ≤ 24 jam | `pembayaran_pelanggan` table |

### 8.2 KPI Teknis
| Metrik | Target | Cara Ukur |
|---|---|---|
| Page load time (first load) | < 3 detik | Lighthouse |
| Page load time (subsequent via Inertia) | < 300 ms | Same |
| LCP (Largest Contentful Paint) | < 2.5 detik di mobile | Web Vitals |
| Uptime | ≥ 99% per bulan | Monitoring |
| API response time | p95 < 500 ms | APM |
| JS bundle size (gzipped) | < 200 KB initial | Vite report |
| DB query time (slow log) | < 100 ms | Laravel Telescope / Debugbar |

### 8.3 KPI User Experience
| Metrik | Target | Cara Ukur |
|---|---|---|
| Bounce rate (per portal) | < 20% | Analytics |
| Daily active users per tenant | ≥ 3 (admin) + kolektor | Analytics |
| Mobile usage rate | ≥ 40% dari total traffic | Analytics |
| Dark mode adoption | ≥ 30% dari active users | Frontend tracking |
| PDF download success rate | ≥ 99% | Error tracking |

---

## 9. Asumsi & Constraint

### 9.1 Asumsi
1. Operator RT/RW Net di Indonesia mayoritas menggunakan Android (bukan iOS) untuk operasional.
2. Koneksi internet di lokasi operator bervariasi 4G / WiFi, aplikasi harus resilient terhadap slow connection.
3. Multi-tenant dengan shared database adalah pilihan (vs schema-per-tenant) — asumsi tenant tidak butuh eksklusivitas schema.
4. Bahasa UI: Indonesia (primary), tapi kode & technical identifiers: English.
5. Storage file bukti bayar & logo: max 5MB per file, total estimasi 1GB per 1000 pelanggan per tahun.
6. Paket internet bulanan sebagai unit billing utama (bukan harian / mingguan).
7. Karyawan penagih & admin perusahaan adalah role terpisah (satu user tidak bisa kedua-duanya).
8. Bukti transfer valid selama 30 hari setelah tanggal transfer.

### 9.2 Constraint
- **Budget**: Self-funded MVP, optimasi cost.
- **Timeline**: Target MVP launch Q3 2026.
- **Tim**: Small team (< 5 devs), prioritas maintainability > cutting-edge.
- **Compliance**: GDPR-style privacy untuk data pelanggan (encrypted at rest, signed URLs).
- **Browser support**: Chrome 100+, Firefox 100+, Safari 16+, Edge 100+ (no IE).
- **Server**: Production harus jalan di Linux (Nginx + PHP-FPM 8.4) — development di Windows (Laragon).
- **No third-party payment gateway** di MVP — verifikasi manual saja dulu.
- **No mobile native app** di MVP — web responsif saja.

### 9.3 Out of Scope (Explicit)
- ❌ Integrasi payment gateway otomatis (Midtrans, Xendit, dll) — v2.
- ❌ Mobile native app (iOS/Android) — v2.
- ❌ Integrasi WhatsApp gateway resmi (WA Business API) — opsional v2.
- ❌ API publik untuk integrasi pihak ketiga — v2.
- ❌ Multi-currency / multi-bahasa UI — v2.
- ❌ SSO (Single Sign-On) eksternal — v2.
- ❌ BI dashboard / data warehouse — v2.
- ❌ Open banking auto-verifikasi bukti transfer — v2.
- ❌ Auto-suspend pelanggan menunggak via integrasi Mikrotik — v2.

---

## 10. Glossary

| Istilah | Definisi |
|---|---|
| **Tenant** | Satu perusahaan RT/RW Net yang terdaftar di platform. Data diisolasi per tenant. |
| **Admin SaaS (Operator SaaS)** | Super admin platform, mengelola semua tenant. |
| **Admin Perusahaan** | Admin di sisi tenant, mengelola operasional harian RT/RW Net. |
| **Karyawan (Penagih)** | User dengan portal mobile-first, tugas: tagih & catat pembayaran. |
| **Pelanggan (Customer)** | End user yang dilayani RT/RW Net, akses read-only ke histori tagihan. |
| **Paket Internet** | Produk yang dijual: nama, kecepatan (Mbps), harga bulanan. |
| **Langganan (Paket Pelanggan)** | Assignment paket ke pelanggan dengan tanggal mulai & akhir. |
| **Tagihan (Invoice)** | Dokumen penagihan bulanan, status: belum_bayar / menunggu_konfirmasi / lunas / kadaluarsa. |
| **Insentif** | Reward untuk karyawan yang berhasil menagih, % atau nominal flat. |
| **Multi-tenant** | Arsitektur di mana satu aplikasi melayani banyak tenant dengan isolasi data. |
| **RBAC** | Role-Based Access Control — role punya permission, user punya role. |
| **Permission (Hak Akses)** | Aksi granular yang diizinkan/dilarang (e.g., `tagihan.bayar`). |
| **SPA** | Single Page Application — navigasi tanpa full reload (Inertia.js). |
| **Webhook** | HTTP callback untuk event (e.g., payment status update). |
| **WebSocket** | Protokol full-duplex untuk realtime (Laravel Reverb). |
| **Soft Delete** | Penghapusan logical (data tetap ada tapi ditandai), bisa di-restore. |
| **Audit Trail** | Log semua aksi user, untuk compliance & debugging. |
| **Kredensial** | Nilai konfigurasi yang sensitif (API key, token), di-mask di UI. |
| **MinIO** | Object storage S3-compatible self-hosted. |
| **Reverb** | WebSocket server native Laravel. |
| **Inertia** | Adapter SPA untuk Laravel + Vue, tidak perlu REST API terpisah. |
| **Eloquent Global Scope** | Mekanisme Laravel untuk filter query otomatis (untuk tenant isolation). |

---

## 11. Tech Stack

| Layer | Teknologi | Keterangan |
|---|---|---|
| Backend | **Laravel 13** | REST API, ORM Eloquent, Queue Job |
| Frontend | **Vue 3.5+** + **Inertia.js 2** | SPA-like tanpa perlu REST API terpisah; server-side routing Laravel + client-side Vue |
| CSS Framework | **Tailwind CSS 4.2** | Utility-first, JIT compiler via Vite plugin, dark mode via `dark:` variant & `prefers-color-scheme` |
| UI Components | **Flowbite 4.0** | Komponen UI siap pakai berbasis Tailwind, termasuk `DarkThemeToggle`, Sidebar, Navbar, Modal, Table |
| Ikon | **Font Awesome 7** | 64K+ ikon, SVG/JS, 14 icon packs |
| Theme Manager | **Custom Vue Composable** (`useTheme`) | Kelola 3 mode: light/dark/auto, localStorage persistence, OS preference listener |
| Font | **Inter** (body) + **JetBrains Mono** (monospace) | Tipografi modern, variable font, di-load dari Google Fonts atau self-hosted |
| Real-time | **Laravel Reverb 1.10** | WebSocket server native Laravel (real-time notifikasi, status penagihan live) |
| Storage | **S3 / S3 Compatible** (MinIO) | Upload bukti transfer, dokumen pelanggan, logo perusahaan |
| Database | **MySQL 8.4 / MariaDB 11** | Relasional, transaksi ACID |
| Queue | **Redis 7** | Antrean job (generate invoice, kalkulasi insentif, notifikasi) |
| Image Processing | **Intervention/Image 3** | Auto-compress foto ke WebP, resize maintain aspect ratio |
| PDF | **barryvdh/laravel-dompdf** | Cetak invoice, kwitansi, laporan |
| Excel | **PhpSpreadsheet** | Import/export Excel dengan template |
| Audit Trail | **spatie/laravel-activitylog** | Log otomatis semua perubahan model |

---

## 12. Arsitektur Singkat
```
┌─────────────────┐     ┌──────────────────┐     ┌───────────┐
│   Browser        │────▶│  Laravel 13       │────▶│  MySQL    │
│  (Vue 3 +        │     │  + Inertia.js     │     │  + Redis  │
│   Tailwind 4 +   │◀────│  + Reverb         │◀────│  + MinIO  │
│   Flowbite +     │     └──────────────────┘     └───────────┘
│   FA 7 +         │              │
│   useTheme())    │              │ WebSocket (WS)
└─────────────────┘              │
        ▲                         ▼
        │  localStorage     ┌──────────┐
        │  (theme pref)     │ Reverb   │
        └───────────────────│ Server   │
                            └──────────┘
```

**4 Portal** (terpisah guard):
- `/login-operator-saas` → `admin_saas` guard → `/operator-saas/*`
- `/login-perusahaan` → `admin_company` guard → `/operator-perusahaan/*`
- `/login-karyawan` → `employee` guard → `/karyawan/*`
- `/login-customer` → `customer` guard → `/customer/*`

---

## 13. MVP Prioritas

### 13.1 Phase 1 (MVP Launch)
1. Multi-tenant + Dynamic RBAC
2. Multi-guard Authentication (4 portal)
3. Landing Page + 4 Halaman Login
4. Manajemen Perusahaan (SaaS) & Perusahaan Saya
5. Manajemen Karyawan + Role Web Karyawan
6. Manajemen Pelanggan + Paket + Langganan
7. Generate Tagihan Otomatis + Tracking Status
8. Pembayaran Tunai + Non-Tunai + Upload Bukti
9. Riwayat Pembayaran dengan Review & Approval
10. Insentif Karyawan + Kalkulasi Otomatis
11. Riwayat Insentif dengan Review
12. Konfigurasi SaaS (upload limit, auto-compress)
13. Konfigurasi Perusahaan (kontak, branding)
14. Laporan Harian, Bulanan (PDF + Excel)
15. Import/Export Excel untuk modul utama
16. Soft Delete + Restore + Bulk Action
17. UI/UX Modern (Dark/Light/Auto mode, Responsif, SPA)

### 13.2 Phase 2 (Post-MVP)
- Integrasi payment gateway (Midtrans / Xendit)
- Integrasi WhatsApp Business API
- Mobile native app (Android-first)
- Open banking auto-verifikasi
- Auto-suspend pelanggan via Mikrotik API
- API publik untuk integrasi pihak ketiga
- Multi-currency / multi-bahasa

---

## 14. Changelog

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-05-05 | Initial draft (Latar Belakang, Masalah, Tujuan, Scope, Tech Stack, Arsitektur, MVP) |
| 1.1.0 | 2026-06-07 | Tambah: Statistik Pasar, Stakeholder & User Personas, User Journeys, Risk Analysis, Success Metrics, Asumsi & Constraint, Glossary. Update: Scope diperluas ke 22 modul. Tech Stack & Arsitektur di-update dengan portal 4-guard. |
```
