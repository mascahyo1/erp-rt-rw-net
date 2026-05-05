# PRD — ERP RT/RW Net

## 1. Multi-Tenant & Dynamic RBAC
### 1.1 Struktur Tenant
- Satu database, multi tabel tenant (shared database).
- Tabel utama: `tenants`, `tenant_user`, `roles`, `permissions`.
- Setiap tenant punya: nama perusahaan, logo, alamat, kontak, branding warna.

### 1.2 Dynamic RBAC
- **Role** dibuat per tenant (Super Admin, Owner, Admin Keuangan, Kolektor, dsb).
- **Permission** granular: `pelanggan.view`, `pelanggan.create`, `pelanggan.edit`, `pelanggan.delete`, `tagihan.view`, `tagihan.bayar`, `tagihan.approve`, `insentif.view`, `insentif.approve`, `laporan.view`, `laporan.export`, `pengguna.manage`, `roles.manage`, dsb.
- Role bisa punya banyak permission. User bisa punya banyak role.
- Middleware Laravel + Policy untuk otorisasi server-side.
- Inertia middleware handle redirect jika tidak punya akses.

### 1.3 Super Admin Global
- Bisa mengelola semua tenant.
- Bisa membuat / menonaktifkan tenant.

---

## 2. Manajemen Pelanggan
- Fields: Nama, Alamat, No HP, Koordinat GPS (opsional), Status (aktif/nonaktif).
- Satu pelanggan bisa punya histori paket.
- Pelanggan terelasi ke tenant (tidak bisa lintas tenant).
- Soft delete untuk keperluan historis.

## 3. Manajemen Paket
- Fields: Nama paket, kecepatan (Mbps), harga bulanan, masa aktif, deskripsi.
- Harga bisa berbeda antar tenant.
- Riwayat perubahan harga.

## 4. Penagihan (Invoicing)
### 4.1 Generate Otomatis
- Invoice dibuat otomatis via `php artisan invoice:generate` (scheduler setiap awal bulan).
- Atau manual untuk pelanggan baru.
- Nomor invoice: `INV/RT/{tenant_code}/{YYYYMM}/{sequential}`.

### 4.2 Status Tracking
```
┌─────────────┐     ┌─────────────────────┐     ┌───────┐     ┌──────────┐
│  BELUM BAYAR │────▶│ MENUNGGU KONFIRMASI │────▶│ LUNAS │     │ KADALUARSA│
└─────────────┘     └─────────────────────┘     └───────┘     └──────────┘
```
- `belum_bayar` → default saat generate.
- `menunggu_konfirmasi` → setelah upload bukti transfer / karyawan input pembayaran.
- `lunas` → setelah admin verifikasi.
- `kadaluarsa` → lewat jatuh tempo tanpa pembayaran.

### 4.3 Riwayat Tracking
- Setiap perubahan status dicatat di `invoice_logs`: `invoice_id`, `old_status`, `new_status`, `user_id`, `catatan`, `timestamp`.

---

## 5. Pembayaran Tunai
- **Karyawan penagih** datang ke rumah pelanggan, menerima uang tunai.
- Input melalui form: pilih invoice → jumlah bayar → catatan → submit.
- Status invoice otomatis berubah ke `lunas` (atau `menunggu_konfirmasi` tergantung konfigurasi tenant).
- **Insentif otomatis** terhitung saat pembayaran tercatat.
- Jika pembayaran **penuh**: status `lunas`, insentif penuh.
- Jika pembayaran **sebagian**: piutang berkurang, insentif proporsional.

## 6. Pembayaran Non-Tunai
- Pelanggan transfer ke **rekening pribadi karyawan** atau **rekening perusahaan**.
- **Upload bukti transfer** (JPG/PNG/PDF, max 5MB, disimpan di S3/MinIO).
- Karyawan penagih mengisi form: pilih invoice → upload bukti → catatan → submit.
- Status invoice → `menunggu_konfirmasi`.
- Admin/Keuangan verifikasi bukti → status `lunas`.
- **Insentif** tetap tercatat untuk karyawan yang menagih.
- Jika bukti ditolak → status kembali ke `belum_bayar` + catatan penolakan.

## 7. Insentif Karyawan
### 7.1 Konfigurasi
- Setiap tenant bisa mengatur persentase / nominal insentif per karyawan.
- Bisa flat (Rp X / penagihan) atau persentase (% dari jumlah tagihan yang dilunasi).
- Bisa berbeda per metode pembayaran (tunai vs non-tunai).

### 7.2 Kalkulasi Otomatis
- Saat pembayaran tercatat (tunai langsung, non-tunai setelah verifikasi), insentif langsung ter-generate.
- Queue job `CalculateIncentiveJob` untuk performa.
- Riwayat insentif: `karyawan_id`, `invoice_id`, `jumlah_insentif`, `metode_pembayaran`, `tanggal`.

### 7.3 Approval & Pencairan
- Owner/Admin bisa melihat daftar insentif yang sudah terakumulasi.
- Status insentif: `pending` → `approved` → `paid`.
- Filter per karyawan, rentang tanggal, status.

---

## 8. Laporan
### 8.1 Jenis Laporan
| Laporan | Frekuensi | Isi |
|---|---|---|
| Pendapatan | Harian / Mingguan / Bulanan / Tahunan | Total pemasukan dari pembayaran lunas |
| Piutang | Real-time | Daftar invoice belum lunas & kadaluarsa |
| Insentif Karyawan | Bulanan | Total insentif per karyawan |
| Performa Penagih | Bulanan | Jumlah penagihan sukses per karyawan |
| Pelanggan Baru / Nonaktif | Bulanan | Churn rate |

### 8.2 Export
- PDF (Dompdf / Barryvdh Laravel PDF)
- Excel (Laravel Excel / PhpSpreadsheet)

### 8.3 Dashboard
- **Realtime** (via Laravel Reverb): notifikasi pembayaran baru, status update.
- Widget: total pelanggan aktif, pendapatan bulan berjalan, piutang outstanding, top kolektor.

---

## 9. Notifikasi Real-time (Laravel Reverb)
- Broadcast event saat:
  - Invoice baru ter-generate → notifikasi ke pelanggan (opsional via WA/Email).
  - Pembayaran tunai masuk → admin dapat notifikasi.
  - Bukti transfer diupload → admin dapat notifikasi untuk verifikasi.
  - Verifikasi selesai → karyawan penagih dapat notifikasi (insentif approval).
- Inertia + Vue 3 menangkap event Reverb di frontend via `Echo`.

---

## 10. Upload & Storage (S3 Compatible / MinIO)
- Semua file upload (bukti transfer, avatar, dokumen) disimpan ke S3-compatible storage (MinIO).
- Laravel Filesystem disk `s3` dikonfigurasi endpoint ke MinIO.
- File dienkripsi saat upload (opsional).
- Private bucket — akses via signed URL (temporary) untuk keamanan.

---

## 11. Non-Functional Requirements
| Aspek | Spesifikasi |
|---|---|
| Responsivitas | Mobile-first, UI adaptif (Tailwind responsive) |
| Performa | Lazy load data via Inertia partial reloads; Redis cache |
| Keamanan | CSRF, XSS, SQL Injection (Laravel built-in); Rate limiting API; Signed URL S3 |
| Audit Trail | Semua aksi user tercatat (spatie/laravel-activitylog) |
| Backup | Otomatis backup database + file S3 terjadwal |

---

## 12. Tech Stack Detail

### Backend
- **Laravel 13** — Full-stack framework (latest stable, dirilis Q1 2026)
- **Laravel Reverb 1.10** — WebSocket server (real-time events)
- **Laravel Echo** — Client-side WebSocket listener
- **Laravel Queue** (Redis) — Background job processing
- **Laravel Scheduler** — Cron untuk generate invoice, backup
- **spatie/laravel-permission** — RBAC foundation + custom extension untuk dynamic tenant
- **spatie/laravel-activitylog** — Audit trail
- **stancl/tenancy** atau custom multi-tenant — Shared DB approach
- **barryvdh/laravel-dompdf** — PDF export
- **laravel/sanctum** — (opsional) Mobile API auth

### Frontend
- **Vue 3.5+** (Composition API + `<script setup>`)
- **Inertia.js 2** — Bridge Laravel ↔ Vue, SSR-ready
- **Tailwind CSS 4.2** — Utility-first CSS framework (Vite plugin)
- **Flowbite 4.0** — Tailwind component library (modals, tables, forms, sidebar, navbar)
- **Font Awesome 7** — SVG icon library (64,647 ikon, 14 icon packs)
- **Ziggy** — Laravel named routes di JavaScript

### Infrastructure
- **MySQL 8.4 / MariaDB 11** — Primary database
- **Redis 7** — Cache + Queue driver
- **MinIO** — S3-compatible object storage (self-hosted)
- **Laragon** — Local development environment (Windows)
- **Nginx + PHP-FPM 8.4** — Production server
- **Supervisor** — Queue worker process manager
