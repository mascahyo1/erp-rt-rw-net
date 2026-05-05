# BRD — ERP RT/RW Net

## 1. Latar Belakang
Banyak penyedia layanan internet skala RT/RW (RT/RW Net) masih mengandalkan **pencatatan manual** untuk operasional sehari-hari — mulai dari data pelanggan, penagihan, pembayaran, hingga insentif karyawan. Cara ini rawan kehilangan data, tidak transparan, dan menyulitkan pembuatan laporan periodik.

## 2. Pernyataan Masalah (Problem Statement)
1. **Pencatatan manual** → tidak efisien, rentan kesalahan, sulit diaudit.
2. **Penagihan tidak terstruktur** → tidak ada tracking status pembayaran per pelanggan secara real-time.
3. **Insentif penagih tidak tercatat** → karyawan yang turun langsung ke lapangan (tunai maupun non-tunai) tidak mendapat penghargaan yang terukur dan transparan.
4. **Tidak ada laporan terpadu** → pemilik bisnis kesulitan mendapatkan laporan harian / mingguan / bulanan / tahunan.
5. **Multi-perusahaan** → pemilik yang mengelola beberapa perusahaan RT/RW Net tidak bisa mengelola semuanya dalam satu sistem dengan hak akses fleksibel.

## 3. Tujuan (Goals)
Membangun **ERP berbasis web** yang:
- Mendigitalisasi pencatatan pelanggan, paket, penagihan, dan pembayaran.
- Menyediakan **pelacakan status penagihan** dan **riwayat transaksi** secara real-time.
- Menghitung & mencatat **insentif karyawan penagih** (tunai & non-tunai) secara otomatis.
- Menyajikan **laporan harian / mingguan / bulanan / tahunan** yang siap pakai.
- Mendukung **multi-perusahaan (multi-tenant)** dengan **Dynamic RBAC & granular permission**.
- Memberikan **pengalaman pengguna modern**: SPA, responsif di semua device, dukungan dark/light/auto theme.

## 4. Cakupan Fitur (High-Level Scope)
| Modul | Deskripsi |
|---|---|
| Manajemen Pelanggan | CRUD pelanggan, status aktif/nonaktif, riwayat paket |
| Manajemen Paket | Harga, bandwidth, masa berlaku, per perusahaan |
| Penagihan (Invoicing) | Generate tagihan otomatis, tracking status: `belum bayar` / `menunggu konfirmasi` / `lunas` / `kadaluarsa` |
| Pembayaran Tunai | Input pembayaran kas, tracking karyawan penagih, auto hitung insentif |
| Pembayaran Non-Tunai | Upload / verifikasi bukti transfer (rekening pribadi / perusahaan), tracking karyawan penagih, auto hitung insentif |
| Insentif Karyawan | Kalkulasi otomatis, riwayat, approval |
| Laporan | Harian, mingguan, bulanan, tahunan — pendapatan, piutang, insentif, performa karyawan |
| Multi-Perusahaan | Isolasi data per perusahaan, branding per tenant |
| Dynamic RBAC | Role & permission bisa dikustomisasi per tenant (granular: view, create, edit, delete, approve) |
| **UI/UX Modern** | Dark Mode, Light Mode, Auto (ikut OS), Responsif (mobile-first), SPA experience via Inertia, animasi transisi halus |

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

## 5. Tech Stack

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
| Storage | **S3 / S3 Compatible** (MinIO) | Upload bukti transfer, dokumen pelanggan |
| Database | **MySQL 8.4 / MariaDB 11** | Relasional, transaksi ACID |
| Queue | **Redis 7** | Antrean job (generate invoice, kalkulasi insentif, notifikasi) |

## 6. Arsitektur Singkat
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

## 7. MVP Prioritas
1. Multi-tenant + Dynamic RBAC
2. Manajemen Pelanggan + Paket
3. Generate Tagihan Otomatis
4. Pembayaran Tunai + Non-Tunai + Tracking Status
5. Insentif Karyawan
6. Laporan Harian & Bulanan
7. Dashboard Realtime (via Reverb)
8. **UI/UX Modern** (Dark/Light/Auto mode, Responsif, SPA)