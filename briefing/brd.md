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

## 5. Tech Stack

| Layer | Teknologi | Keterangan |
|---|---|---|
| Backend | **Laravel 13** | REST API, ORM Eloquent, Queue Job |
| Frontend | **Vue 3.5+** + **Inertia.js 2** | SPA-like tanpa perlu REST API terpisah; server-side routing Laravel + client-side Vue |
| CSS Framework | **Tailwind CSS 4.2** | Utility-first, JIT compiler via Vite plugin |
| UI Components | **Flowbite 4.0** | Komponen UI siap pakai berbasis Tailwind |
| Ikon | **Font Awesome 7** | 64K+ ikon, SVG/JS, 14 icon packs |
| Real-time | **Laravel Reverb 1.10** | WebSocket server native Laravel (real-time notifikasi, status penagihan live) |
| Storage | **S3 / S3 Compatible** (MinIO) | Upload bukti transfer, dokumen pelanggan |
| Database | **MySQL 8.4 / MariaDB 11** | Relasional, transaksi ACID |
| Queue | **Redis 7** | Antrean job (generate invoice, kalkulasi insentif, notifikasi) |

## 6. Arsitektur Singkat
```
┌─────────────┐     ┌──────────────┐     ┌───────────┐
│   Browser    │────▶│  Laravel      │────▶│  MySQL    │
│  (Vue 3 +    │     │  + Inertia    │     │  + Redis  │
│   Tailwind)  │◀────│  + Reverb     │◀────│  + MinIO  │
└─────────────┘     └──────────────┘     └───────────┘
       ▲                   │
       │  WebSocket (WS)   │
       └───────────────────┘
```

## 7. MVP Prioritas
1. Multi-tenant + Dynamic RBAC
2. Manajemen Pelanggan + Paket
3. Generate Tagihan Otomatis
4. Pembayaran Tunai + Non-Tunai + Tracking Status
5. Insentif Karyawan
6. Laporan Harian & Bulanan
7. Dashboard Realtime (via Reverb)