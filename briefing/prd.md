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

## 11. UI/UX Design System

### 11.1 Color Mode (Dark / Light / Auto)
| Mode | Deskripsi | Implementasi |
|---|---|---|
| 🌞 **Light Mode** | Tema terang default. Background putih/abu-abu, teks gelap. | Tailwind `light` class (default). |
| 🌙 **Dark Mode** | Tema gelap untuk kenyamanan mata di malam hari. Background gray-900, teks putih/abu. | Tailwind `dark:` prefix. Toggle via class `dark` di `<html>`. |
| 🖥️ **Auto (OS Default)** | Mengikuti preferensi sistem operasi pengguna. | CSS `prefers-color-scheme` media query. Tailwind `@media (prefers-color-scheme: dark)` via `darkMode: 'media'`. |

**Mekanisme penyimpanan preferensi:**
- Simpan pilihan user di `localStorage` key `theme` dengan nilai: `light`, `dark`, atau `system`.
- Saat pertama kali akses (tidak ada localStorage) → default ke `system`.
- Listener `matchMedia('(prefers-color-scheme: dark)')` untuk mendeteksi perubahan OS secara real-time.
- Toggle switch di **navbar** (ikon ☀️/🌙) dan opsi di **halaman profil**.
- Flowbite menyediakan komponen `DarkThemeToggle` yang bisa langsung dipakai.

**Contoh implementasi di `app.js`:**
```js
// On mount
const theme = localStorage.getItem('theme') || 'system';
if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
}
```

### 11.2 Responsivitas (Mobile-First)
| Breakpoint | Lebar | Target Device |
|---|---|---|
| Default | < 640px | Smartphone (mobile-first) |
| `sm` | ≥ 640px | Smartphone landscape |
| `md` | ≥ 768px | Tablet |
| `lg` | ≥ 1024px | Laptop kecil |
| `xl` | ≥ 1280px | Desktop |
| `2xl` | ≥ 1536px | Desktop besar |

**Perilaku responsif per komponen:**
| Komponen | Mobile (< 768px) | Desktop (≥ 768px) |
|---|---|---|
| **Sidebar** | Off-screen (slide overlay dari kiri), hamburger toggle | Fixed, lebar 260px, bisa collapse ke 64px (ikon only) |
| **Tabel Data** | Swipe horizontal card (setiap row jadi card vertikal) | Tabel normal |
| **Form** | Full width, label di atas input | Bisa grid 2-3 kolom, label di samping |
| **Modal** | Full screen (bottom sheet style) | Centered, max-width 500-800px |
| **Dashboard Widget** | 1 kolom (stack) | 2-4 kolom grid |
| **Navbar** | Logo + hamburger + user avatar | Logo + menu horizontal + search bar + user dropdown |
| **Filter/Search** | Expandable dropdown | Inline horizontal |

### 11.3 SPA Experience (via Inertia.js)
- **Tidak ada full page reload** — semua navigasi via XHR/fetch.
- **Loading progress bar** — NProgress style, warna sky-500, muncul di atas halaman saat request Inertia.
- **Preserve scroll position** — dikelola otomatis oleh Inertia (`preserveState`).
- **Partial reloads** — hanya reload komponen yang berubah (`only: ['users']`).
- **Prefetch** — prefetch link saat hover (opsional via `@prefetch` directive Inertia).
- **Title dinamis** — `<Head title="...">` berubah sesuai halaman.
- **Back/Forward** — browser navigation tetap berfungsi (Inertia menggunakan History API).
- **Error handling** — modal error toast saat validasi gagal, tidak perlu reload halaman.

### 11.4 Modern Aesthetic
| Elemen | Spesifikasi |
|---|---|
| **Warna Primer** | Gradasi sky-500 → indigo-600 (`from-sky-500 to-indigo-600`) |
| **Gradasi Background** | Subtle radial gradient blob dengan opacity 10-20%, posisi absolute di belakang konten |
| **Glassmorphism** | `bg-white/80 backdrop-blur-md` untuk navbar & card overlay |
| **Border radius** | Konsisten: tombol & card `rounded-xl` (12px), modal `rounded-2xl` (16px), input `rounded-lg` (8px) |
| **Shadow Hierarchy** | 3 level: `shadow-sm` (card default), `shadow-md` (card hover), `shadow-xl` (modal/dropdown), `shadow-2xl` (mockup hero) |
| **Skeleton Loading** | Placeholder shimmer animation saat data loading. Komponen `SkeletonLoader.vue` (pulsing gradient) |
| **Micro-interactions** | Hover scale (1.02), active press (0.98), focus ring (ring-2 ring-sky-500 ring-offset-2) |
| **Toast Notifikasi** | Slide-in dari kanan atas, durasi 4 detik, 4 varian: success (emerald), error (red), warning (amber), info (sky) |
| **Empty State** | Ilustrasi SVG + teks panduan + CTA button saat data kosong |
| **Transition** | Semua transisi 200-300ms ease-in-out (`transition-all duration-200`) |

### 11.5 Tipografi
| Penggunaan | Font | Weight | Ukuran |
|---|---|---|---|
| Heading 1 (hero) | Inter | Extrabold (800) | 36-60px |
| Heading 2 (section) | Inter | Bold (700) | 30-36px |
| Heading 3 (card title) | Inter | Semibold (600) | 18-20px |
| Body | Inter | Regular (400) | 14-16px |
| Caption / Label | Inter | Medium (500) | 12-14px |
| Kode / Angka | JetBrains Mono | Regular (400) | 12-14px |

### 11.6 Aksesibilitas (A11y)
- **WCAG 2.1 AA** compliance minimal.
- **Kontras warna**: ratio minimal 4.5:1 untuk teks normal, 3:1 untuk teks besar.
- **Focus indicators**: `focus:ring-2 focus:ring-sky-500 focus:ring-offset-2` untuk semua elemen interaktif.
- **Keyboard navigation**: semua menu, modal, dropdown bisa diakses via Tab, Esc, Enter, Arrow keys.
- **ARIA labels**: `aria-label`, `aria-expanded`, `aria-controls`, `role` atribut pada komponen Flowbite.
- **Screen reader**: semantic HTML + `.sr-only` class untuk teks bantu.
- **Skip to content**: link tersembunyi di awal halaman untuk skip navigasi.

---

## 12. Non-Functional Requirements
| Aspek | Spesifikasi |
|---|---|
| **Responsivitas** | Mobile-first, UI adaptif (Tailwind responsive). LCP < 2.5s di mobile. |
| **Theme Support** | Dark / Light / Auto (OS default). Tersimpan di localStorage. Toggle via navbar. |
| **SPA Performance** | Inertia lazy loading + partial reloads. Vite code splitting. First load < 3s, subsequent < 300ms. |
| **Browser Support** | Chrome 100+, Firefox 100+, Safari 16+, Edge 100+. Tidak mendukung IE. |
| **Keamanan** | CSRF, XSS, SQL Injection (Laravel built-in); Rate limiting API; Signed URL S3; CSP header. |
| **Audit Trail** | Semua aksi user tercatat (spatie/laravel-activitylog). Log: user, aksi, model, timestamp, IP, user agent. |
| **Backup** | Otomatis backup database + file S3 terjadwal. Retensi 30 hari. |
| **PWA Ready** (opsional) | Service worker untuk offline caching static assets. Manifest.json untuk installable app.

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
- **Inertia.js 2** — Bridge Laravel ↔ Vue, SSR-ready, SPA experience tanpa full page reload
- **Tailwind CSS 4.2** — Utility-first CSS framework (Vite plugin), `dark:` variant untuk dark mode
- **Flowbite 4.0** — Tailwind component library (modals, tables, forms, sidebar, navbar, `DarkThemeToggle`)
- **Font Awesome 7** — SVG icon library (64,647 ikon, 14 icon packs)
- **Ziggy** — Laravel named routes di JavaScript
- **Inter + JetBrains Mono** — Font family modern untuk body & monospace
- **useTheme Composable** — Vue composable untuk manage dark/light/auto theme + localStorage persistence

### Theme System Detail
```
┌─────────────────────────────────────────────────┐
│                  useTheme()                       │
│                                                   │
│  State: theme = 'light' | 'dark' | 'system'      │
│                                                   │
│  Logic:                                           │
│  1. Baca localStorage('theme') → state           │
│  2. Jika 'system' → cek prefers-color-scheme      │
│  3. Toggle class 'dark' di <html>                │
│  4. Listen matchMedia change event               │
│  5. Watch state → update localStorage + DOM      │
│                                                   │
│  Methods:                                         │
│  - setTheme(mode) → simpan + apply               │
│  - toggleTheme() → light ↔ dark                  │
│  - isDark → computed boolean                     │
│  - themeIcon → computed: 'sun' | 'moon' | 'auto' │
└─────────────────────────────────────────────────┘
```

### SPA Architecture (Inertia.js)
```
┌──────────┐   click link    ┌──────────────┐   XHR/fetch   ┌──────────┐
│  Browser  │ ───────────────▶│  Inertia.js   │ ─────────────▶│ Laravel  │
│  (Vue)    │◀───────────────│  (client)      │◀─────────────│ (server) │
└──────────┘   JSON response └──────────────┘   JSON props  └──────────┘
     │                              │
     │  - No full page reload      │
     │  - Progress bar (NProgress) │
     │  - Scroll preservation      │
     │  - History API (back/fwd)   │
     │  - Partial reloads          │
     └──────────────────────────────┘
```

### Infrastructure
- **MySQL 8.4 / MariaDB 11** — Primary database
- **Redis 7** — Cache + Queue driver
- **MinIO** — S3-compatible object storage (self-hosted)
- **Laragon** — Local development environment (Windows)
- **Nginx + PHP-FPM 8.4** — Production server
- **Supervisor** — Queue worker process manager
