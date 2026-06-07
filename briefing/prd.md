# PRD — ERP RT/RW Net

> **Versi:** 1.1.0  
> **Tanggal update:** 2026-06-07  
> **Status:** Aktif (in-development)  
> **Owner:** Internal Team  
> **Menggantikan:** `briefing/prd.md` v1.0 (11 Mei 2026)

---

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

## 12a. Tech Stack Detail

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

---

## 13. Multi-Guard Authentication

### 13.1 Konsep
Aplikasi punya **4 guard autentikasi terpisah** (Laravel multi-auth), masing-masing untuk satu portal/persona. User tidak bisa lintas guard tanpa logout.

| Guard | Tipe User | Portal URL | Login URL | Dashboard URL |
|---|---|---|---|---|
| `admin_saas` | Operator SaaS / Super Admin | `/operator-saas/*` | `/login-operator-saas` | `/operator-saas/dashboard` |
| `admin_company` | Admin Perusahaan | `/operator-perusahaan/*` | `/login-perusahaan` | `/operator-perusahaan/dashboard` |
| `employee` | Karyawan Penagih | `/karyawan/*` | `/login-karyawan` | `/karyawan/dashboard` |
| `customer` | Pelanggan | `/customer/*` | `/login-customer` | `/customer/dashboard` |

### 13.2 State & Redirect Logic
- Setelah login berhasil, redirect ke dashboard masing-masing guard.
- Middleware `auth:{guard}` di tiap route group.
- Logout: invalidate session untuk guard yang aktif saja.
- Cross-guard tab: di browser beda, session tetap independen.

### 13.3 Session & Cookie
- Session driver: `database` (production) atau `file` (dev).
- Cookie name: prefixed per guard (`erp_saas_session`, `erp_company_session`, dst).
- CSRF token: standard Laravel, per-session.

### 13.4 Password Policy
- Minimum 8 karakter, harus ada huruf besar, kecil, angka, simbol.
- Bcrypt hash dengan cost factor 12.
- Reset password via email token (link valid 1 jam).
- Failed login throttle: 5 attempt → lock 15 menit.

---

## 14. Landing Page & Public Portal

### 14.1 Halaman Publik
| URL | Tujuan |
|---|---|
| `/` | Landing page utama — hero, fitur, pricing (opsional), CTA ke 4 login portal |
| `/login-operator-saas` | Form login Operator SaaS |
| `/login-perusahaan` | Form login Admin Perusahaan + dropdown pilih perusahaan |
| `/login-karyawan` | Form login Karyawan |
| `/login-customer` | Form login Pelanggan |
| `/register-perusahaan` | Self-service registrasi perusahaan baru (opsional, butuh approval Operator SaaS) |

### 14.2 Landing Page Components
- Hero section dengan gradient background & animasi subtle.
- Feature grid (5–6 fitur utama dengan ikon FA).
- Demo screenshot / mockup device.
- Testimonial section (opsional).
- CTA button: "Daftarkan Perusahaan Anda" + "Login".
- Footer: kontak, social media, copyright.

### 14.3 Dropdown Login (perusahaan)
Untuk `admin_company` guard, login form ada **dropdown pilih perusahaan** karena 1 user `admin@netsejahtera.com` bisa di-invite ke multiple perusahaan dengan role berbeda. Pilih perusahaan dulu, lalu credentials.

---

## 15. Perusahaan (SaaS-Level Company Management)

### 15.1 Overview
Operator SaaS (`/operator-saas/perusahaan`) mengelola master data semua perusahaan RT/RW Net yang berlangganan platform.

### 15.2 Fields
| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `name` | string | Ya | Nama perusahaan (e.g., "PT Net Sejahtera Abadi") |
| `email` | string | Ya | Email kontak utama (unique) |
| `phone_country_code` | string | Tidak | Kode negara, default "+62" |
| `phone_number` | string | Tidak | Nomor telepon (tanpa kode negara) |
| `address` | text | Tidak | Alamat lengkap |
| `is_active` | boolean | Ya | Status aktif/nonaktif tenant |
| `logo` | file (image) | Tidak | Logo perusahaan (auto-compress ke WebP) |

### 15.3 Fitur
- CRUD + soft delete + restore + bulk action.
- Filter: status aktif/nonaktif, search by name/email.
- Logo preview, auto-compress saat upload.
- Tombol "Login as Admin" (super admin bisa impersonate tenant untuk debugging).
- Toggle `is_active` untuk suspend tenant.

### 15.4 Edge Case
- Suspend tenant (`is_active=false`) → semua user di tenant tersebut tidak bisa login, data tetap aman.
- Restore soft-deleted tenant → konfirmasi extra (butuh verifikasi ownership).

---

## 16. Perusahaan Saya (Company Self-Management)

### 16.1 Overview
Admin Perusahaan (`/operator-perusahaan/perusahaan-saya`) mengelola profil perusahaannya sendiri (bukan perusahaan lain). Hanya menampilkan data perusahaan yang terkait dengan `auth()->user()->company_id`.

### 16.2 Fields
Sama dengan section 15.2, plus:
| `bank_name` | string | Tidak | Nama bank untuk transfer |
| `bank_account_number` | string | Tidak | Nomor rekening |
| `bank_account_holder` | string | Tidak | Atas nama rekening |

### 16.3 Fitur
- Edit profil + logo.
- Lihat statistik ringkas: jumlah pelanggan aktif, tagihan bulan ini, kolektor.
- Hanya `perusahaan-saya.ubah` permission yang dibutuhkan.

### 16.4 PDF Download
- Download PDF Company Profile (format A4) dengan logo + kontak + statistik.
- PDF generation: `barryvdh/laravel-dompdf`, table-based layout (no flex — Dompdf limitation).

---

## 17. Manajemen Karyawan

### 17.1 Overview
Admin Perusahaan (`/operator-perusahaan/karyawan`) mengelola data karyawan/penagih yang bekerja di perusahaan mereka.

### 17.2 Fields
| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `name` | string | Ya | Nama lengkap |
| `email` | string | Ya | Email login (unique per company) |
| `phonecountry_code` | string | Tidak | Kode negara |
| `phone_number` | string | Tidak | Nomor telepon |
| `address` | text | Tidak | Alamat |
| `is_active` | boolean | Ya | Status aktif/nonaktif |
| `password` | string | Tidak (saat create) | Bcrypt; dikirim via email invitation |
| `role_web_karyawan_id` | uuid | Tidak | Role untuk portal karyawan |
| `company_id` | uuid | Ya (auto) | Auto-fill dari auth user |

### 17.3 Role Web Karyawan
- Role untuk hak akses di portal `/karyawan/*`.
- Granular: `karyawan.tagihan.view`, `karyawan.tagihan.input_pembayaran`, `karyawan.insentif.view`, dll.
- Custom role per tenant (mirip Admin Company Role).

### 17.4 Fitur
- CRUD + soft delete + restore + bulk action.
- Filter: status aktif/nonaktif, search by name/email/phone.
- Tombol "Reset Password" (admin trigger reset, email dikirim).
- Toggle `is_active` untuk suspend karyawan.

---

## 18. Konfigurasi SaaS

### 18.1 Overview
Operator SaaS (`/operator-saas/konfigurasi`) mengelola setting platform-level yang berlaku global untuk SEMUA tenant. Disimpan di tabel `saas_configs` dengan UUID v7 primary key.

### 18.2 Fields
| Field | Tipe | Keterangan |
|---|---|---|
| `key` | string (unique, soft-deleted excluded) | Identifier konfigurasi (e.g., `default_upload_max_file_size_in_kb`) |
| `type` | enum | `text` / `file` / `number` / `boolean` / `kredensial` |
| `value` | string | Nilai konfigurasi (untuk boolean dinormalisasi ke `'true'`/`'false'`) |
| `description` | text nullable | Penjelasan konfigurasi |

### 18.3 Default Keys (di-seed)
| Key | Type | Default Value | Keterangan |
|---|---|---|---|
| `contact.phone` | text | `+62 812-3456-7890` | Telepon kontak platform |
| `contact.email` | text | `support@rtrwnet.id` | Email support |
| `contact.address` | text | Alamat | Alamat kantor SaaS |
| `default_upload_max_width_and_height_image` | number | `1920` | Max dimensi image (px) |
| `default_upload_max_file_size_in_kb` | number | `2048` | Max file size (KB) = 2MB |
| `default_auto_compress_file_upload` | boolean | `true` | Auto-compress ke WebP |
| `app.stripe_secret` | kredensial | (placeholder) | Stripe API key (masked di UI) |
| `app.maintenance_mode` | boolean | `false` | Mode maintenance platform |

### 18.4 Type Behavior
- **`text` / `file`**: textarea input, plain value.
- **`number`**: input `type="number"`, normalized to canonical string.
- **`boolean`**: select option `true`/`false`, selalu disimpan sebagai string `'true'`/`'false'` (di-normalize via `filter_var(..., FILTER_VALIDATE_BOOLEAN)` untuk handle `1`/`0`/`on`/`off`/`yes`/`no`).
- **`kredensial`**: input `type="password"` dengan eye toggle (type toggles `password` ↔ `text`), masked di tabel & detail (`••••••••`), user klik eye untuk reveal. Cocok untuk API key, token, secret.

### 18.5 Fitur UI
- Filter type (Semua / Teks / File / Angka / Boolean / Kredensial) + filter status (Aktif / Terhapus).
- Search by key/value/description.
- Sort by key/type/created_at.
- Per-page dropdown (5/10/25/50/100).
- Bulk select + bulk delete.
- Soft delete + restore dari filter Terhapus.
- Import/Export Excel dengan template download.
- Field `key` harus unique (kecuali soft-deleted record dengan key sama).

### 18.6 Permission
- `konfigurasi.list`, `konfigurasi.create`, `konfigurasi.ubah`, `konfigurasi.hapus`, `konfigurasi.restore`, `konfigurasi.import`, `konfigurasi.export`.

---

## 19. Konfigurasi Perusahaan

### 19.1 Overview
Admin Perusahaan (`/operator-perusahaan/konfigurasi-perusahaan`) mengelola setting per-tenant. Disimpan di tabel `company_configs` dengan composite uniqueness (company_id + key).

### 19.2 Fields
Sama dengan section 18.2, plus `company_id` (foreign key ke companies, auto-fill).

### 19.3 Default Keys (di-seed)
| Key | Type | Default Value | Keterangan |
|---|---|---|---|
| `company.tagline` | text | "ISP terpercaya dengan jangkauan luas di kota Anda" | Tagline di landing tenant |
| `company.bank_name` | text | (kosong) | Bank transfer |
| `company.bank_account_number` | text | (kosong) | Nomor rekening |
| `company.bank_account_holder` | text | (kosong) | Atas nama |
| `company.is_active` | boolean | `true` | Status perusahaan |
| `company.mikrotik_api_key` | kredensial | (placeholder) | MikroTik API key |

### 19.4 Fitur
Sama persis dengan Konfigurasi SaaS (section 18.5-18.6), tapi scoped per `company_id`. UI color scheme berbeda (sky untuk Perusahaan, indigo untuk SaaS).

---

## 20. Paket Pelanggan (Langganan)

### 20.1 Overview
Assign paket internet ke pelanggan, tracking masa aktif. Disimpan di tabel `paket_pelanggan`.

### 20.2 Fields
| Field | Tipe | Keterangan |
|---|---|---|
| `customer_id` | uuid (FK) | Pelanggan |
| `paket_id` | uuid (FK) | Paket yang di-assign |
| `start_date` | date | Tanggal mulai |
| `end_date` | date nullable | Tanggal berakhir (null = aktif terus) |
| `status` | enum | `active` / `expired` / `cancelled` |
| `monthly_price` | decimal | Harga saat assignment (snapshot, bisa beda dari harga paket saat ini) |

### 20.3 Status Otomatis
- `active` → default saat create.
- `expired` → jika `end_date < today`.
- `cancelled` → jika admin cancel manual (refund / permintaan pelanggan).

### 20.4 Generate Tagihan
- Setiap awal bulan, scheduler cron `php artisan invoice:generate` membuat tagihan untuk semua `paket_pelanggan` berstatus `active`.
- Tagihan menggunakan `monthly_price` snapshot, bukan harga paket saat ini.
- Invoice number: `INV/RT/{tenant_code}/{YYYYMM}/{sequential}`.

### 20.5 Riwayat
- Setiap perubahan paket (upgrade/downgrade) → buat record `paket_pelanggan` baru + set `end_date` lama.
- Histori bisa dilihat di detail pelanggan.

---

## 21. Pembayaran Pelanggan (Payment Method Config)

### 21.1 Overview
Konfigurasi metode pembayaran yang tersedia untuk tenant. Disimpan di tabel `pembayaran_pelanggan` (atau `company_payment_methods`).

### 21.2 Fields
| Field | Tipe | Keterangan |
|---|---|---|
| `company_id` | uuid (FK) | Tenant |
| `name` | string | Nama metode (e.g., "Tunai", "Transfer Bank BCA") |
| `provider` | enum | `internal` (saat ini hanya ini) |
| `method` | enum | `tunai` / `transfer_manual` |
| `requires_proof` | boolean | Apakah wajib upload bukti |
| `bank_name` | string nullable | Nama bank (untuk transfer) |
| `bank_account_number` | string nullable | No rek (untuk transfer) |
| `bank_account_holder` | string nullable | Atas nama (untuk transfer) |
| `is_active` | boolean | Status aktif |

### 21.3 MVP Scope
- Hanya `tunai` dan `transfer_manual` (diverifikasi manual oleh admin).
- Integrasi payment gateway (Midtrans, Xendit) → Phase 2.

### 21.4 UI
- Admin Perusahaan: CRUD di `/operator-perusahaan/pembayaran-pelanggan`.
- Karyawan & Customer: dropdown pilih metode saat input pembayaran.

---

## 22. Customer Portal

### 22.1 Overview
Portal read-only untuk pelanggan (`/customer/*`). Pelanggan login dengan email + password, hanya bisa lihat data miliknya.

### 22.2 Menu Sidebar
```
[
  { label: 'Dashboard',     href: '/customer/dashboard',            icon: 'fa-tachometer-alt' },
  { label: 'Profil Saya',  href: '/customer/profil-saya',          icon: 'fa-user' },
  { label: 'Paket Saya',   href: '/customer/paket-saya',           icon: 'fa-box' },
  { label: 'Tagihan Saya', href: '/customer/tagihan-saya',         icon: 'fa-file-invoice' },
  { label: 'Riwayat Pembayaran', href: '/customer/riwayat-pembayaran', icon: 'fa-history' },
]
```

### 22.3 Fitur per Menu
| Menu | Fitur |
|---|---|
| **Dashboard** | Ringkasan: tagihan belum bayar (jumlah & nominal), histori bayar bulan ini, info paket aktif |
| **Profil Saya** | Edit data pribadi (nama, alamat, phone, email, password). Upload foto profil (auto-compress WebP). |
| **Paket Saya** | Lihat paket yang sedang aktif + histori paket sebelumnya. |
| **Tagihan Saya** | List tagihan (semua status), filter by status, lihat detail, download PDF invoice |
| **Riwayat Pembayaran** | List pembayaran + status review, filter by date, download kwitansi PDF |

### 22.4 Akses
- Read-only (tidak ada create/update/delete).
- Data otomatis difilter by `customer_id` (server-side guard).
- Tidak bisa akses data customer lain.

### 22.5 Customer Registration
- Self-service: tidak ada register. Customer di-invite oleh Admin Perusahaan via email (link set password).

---

## 23. Karyawan Portal

### 23.1 Overview
Portal mobile-first untuk karyawan/penagih lapangan (`/karyawan/*`). Read + limited write (input pembayaran).

### 23.2 Menu Sidebar
```
[
  { label: 'Dashboard',          href: '/karyawan/dashboard',            icon: 'fa-tachometer-alt' },
  { label: 'Profil Saya',        href: '/karyawan/profil-saya',          icon: 'fa-user' },
  { label: 'Customer',           href: '/karyawan/customer',             icon: 'fa-users' },
  { label: 'Langganan Customer', href: '/karyawan/langganan-customer',    icon: 'fa-link' },
  { label: 'Tagihan',            href: '/karyawan/tagihan',              icon: 'fa-file-invoice' },
  { label: 'Insentif Saya',      href: '/karyawan/insentif-saya',        icon: 'fa-coins' },
  { label: 'Riwayat Pembayaran', href: '/karyawan/riwayat-pembayaran',   icon: 'fa-history' },
]
```

### 23.3 Fitur per Menu
| Menu | Fitur |
|---|---|
| **Dashboard** | Widget: tagihan assigned untuk ditagih, insentif bulan ini, top customers |
| **Profil Saya** | Edit data diri + ubah password + upload foto |
| **Customer** | Lihat list customer (read-only), lihat detail, lihat tagihan per customer |
| **Langganan Customer** | Lihat paket customer yang di-handle |
| **Tagihan** | List tagihan yang di-handle, filter by status, **input pembayaran tunai/non-tunai** (modal form), upload bukti, lihat detail |
| **Insentif Saya** | List insentif yang sudah/belum di-approve, total, filter by date |
| **Riwayat Pembayaran** | List pembayaran yang di-input sendiri + status review |

### 23.4 Akses
- Read semua + write terbatas (input pembayaran untuk tagihan assigned).
- Data auto-filter by karyawan (`auth()->user()->id`).
- Tidak bisa edit/delete tagihan atau customer.

---

## 24. PDF Generation

### 24.1 Paket
- **barryvdh/laravel-dompdf** — Composer wrapper untuk Dompdf.

### 24.2 Jenis PDF
| PDF | Trigger | Layout |
|---|---|---|
| **Invoice** | Tombol "Cetak" di detail tagihan | A4 portrait, header logo + kontak, detail tagihan, footer "Terima kasih" |
| **Kwitansi Pembayaran** | Tombol "Download Kwitansi" di Riwayat Pembayaran | A5 landscape, kop + logo, nominal, metode bayar, signature box |
| **Laporan Pendapatan** | Tombol "Export PDF" di dashboard laporan | Multi-page, summary + tabel per kategori |
| **Laporan Insentif** | Tombol "Export PDF" di Riwayat Insentif | Multi-page, per karyawan |
| **Company Profile** | Tombol "Download PDF" di Perusahaan Saya | A4 portrait, profil lengkap + logo |

### 24.3 Layout Rules
⚠️ **Dompdf TIDAK support CSS Flexbox.** Gunakan:
- `<table>` / `<tr>` / `<td>` untuk layout utama.
- `display: table` / `table-cell` untuk grid sederhana.
- `float` atau `inline-block` untuk positioning.
- Hindari `display: flex`, `display: grid`.

### 24.4 Logo Embed
- Logo perusahaan disimpan di S3/MinIO (private).
- Untuk PDF, **embed sebagai base64 data URI** via `Company::getLogoDataUri('logo', 'minio')` — Dompdf tidak bisa fetch URL yang butuh auth.
- Fallback: jika tidak ada logo, gunakan placeholder dengan nama perusahaan.

### 24.5 Template Lokasi
```
resources/views/pdf/
├── invoice.blade.php
├── payment-receipt.blade.php
├── report-revenue.blade.php
├── report-incentive.blade.php
└── company-profile.blade.php
```

---

## 25. Import/Export Excel

### 25.1 Paket
- **PhpSpreadsheet** untuk read & write Excel.
- Format support: `.xlsx` (primary), `.csv` (fallback).

### 25.2 Modul yang Support
Hampir semua modul CRUD support import/export Excel:
- Konfigurasi SaaS, Konfigurasi Perusahaan
- Perusahaan, Admin Perusahaan
- Daftar Paket, Paket Pelanggan
- Customer, Tagihan, Pembayaran
- Karyawan, Insentif
- Riwayat Insentif, Riwayat Pembayaran

### 25.3 Format
- Export: download `.xlsx` dengan header (nama kolom bahasa Indonesia) + data + format number/date.
- Import: upload `.xlsx` atau `.csv`, validasi tiap baris, skip jika duplikat / invalid, summary success/fail.

### 25.4 Template
- Tombol "Download Template" di modal Import.
- Template berisi 3–5 baris contoh + header.
- User isi, lalu upload.

### 25.5 Validasi Import
- Per baris: skip jika key duplikat (existing di DB ATAU di baris sebelumnya).
- Per baris: tipe enum check (e.g., type harus `text`/`file`/`number`/`boolean`/`kredensial`).
- Per baris: required field check.
- Hasil: toast success (jumlah sukses) + warning (jumlah gagal + 5 pesan error pertama).

### 25.6 Filter Export
- Export menghormati filter yang sedang aktif di UI.
- Pilih "Export Semua" atau "Export Selected" (hanya yang di-checklist).
- Query parameter `?ids=uuid1,uuid2` untuk selected.

---

## 26. Soft Delete + Restore + Bulk Action

### 26.1 Overview
Sebagian besar tabel support **soft delete** (data tidak benar-benar dihapus, hanya ditandai `deleted_at`). Pattern ini di-extract ke trait `HasSoftDelete` dan `HasUuidV7`.

### 26.2 Trait `HasSoftDelete`
- Adds `deleted_at` (timestamp), `deleted_by_type` + `deleted_by_id` (polymorphic).
- Adds `deleted_by` & `restored_by` relations.
- Scope: `query` excludes soft-deleted by default; `withTrashed()` includes; `onlyTrashed()` only soft-deleted.
- `delete()` → soft delete; `forceDelete()` → real delete; `restore()` → un-soft-delete.

### 26.3 Trait `HasUuidV7`
- UUID v7 primary key (timestamp-ordered untuk index performance).
- Tidak auto-increment (string key).
- Generated otomatis pada `creating` event.

### 26.4 Trait `HasBlameable`
- Auto-fill `created_by_type`/`created_by_id`, `updated_by_type`/`updated_by_id`, `deleted_by_type`/`deleted_by_id`, `restored_by_type`/`restored_by_id`.
- Polymorphic (bisa AdminSaas, AdminCompany, Employee, Customer, atau null untuk system).

### 26.5 UI Pattern
- Tabel punya filter **Aktif / Terhapus** (2 button).
- Tabel menampilkan badge "Terhapus" di row yang soft-deleted.
- Tombol Restore di kolom Aksi untuk row terhapus.
- Tombol Hapus hanya muncul untuk row aktif (jika punya permission `*.hapus`).
- Bulk action bar muncul saat ada row dipilih:
  - Aktif: tombol "Hapus" (bulk delete).
  - Terhapus: tombol "Pulihkan" (bulk restore).

### 26.6 Unique Validation
- `Rule::unique` default TIDAK exclude soft-deleted.
- Custom rule: `Rule::unique('table', 'col')->ignore($id, 'id')->whereNull('deleted_at')` — exclude soft-deleted dari uniqueness check.
- Contoh: 3 soft-deleted rows dengan key `default_auto_compress_file_upload` + 1 active row → edit active row tidak error "key already taken".

---

## 27. Image Processing Pipeline

### 27.1 Paket
- **Intervention/Image 3** dengan GD driver (default) atau Imagick (opsional).

### 27.2 Konfigurasi
Disimpan di `saas_configs` (dibaca via `SaasConfig::getValue()`):
- `default_upload_max_width_and_height_image` (number, default 1920 px)
- `default_upload_max_file_size_in_kb` (number, default 2048 KB = 2MB)
- `default_auto_compress_file_upload` (boolean, default true)

### 27.3 Pipeline
Saat upload gambar (foto profil, KTP, KK, bukti bayar):
1. **Validasi**: MIME type (jpg, jpeg, png, webp) + size ≤ max_file_size_kb.
2. **Resize (maintain aspect ratio)**: scale down ke max `max_width` × `max_height` jika lebih besar.
3. **Compress to WebP**: convert ke `image/webp`, quality 85% (balance size vs quality).
4. **Store ke MinIO**: folder `general/photos/{uuid7}.webp`, visibility `private`.
5. **Return path**: disimpan di field model, diakses via signed URL untuk display.

### 27.4 SVG Exception
- File **SVG** tidak di-compress (sudah vector).
- Disimpan apa adanya.
- MIME check: `image/svg+xml`.

### 27.5 PDF Exception
- File **PDF** tidak di-compress.
- Disimpan apa adanya di folder `general/documents/`.
- MIME check: `application/pdf`.

### 27.6 Service
`App\Services\FileUploadService`:
- `processImage(UploadedFile, folder)` → return path atau null
- `processDocument(UploadedFile, folder)` → return path atau null
- `processLogo(UploadedFile, folder)` → return path atau null
- `getAcceptedImageExtensions()` → `'jpg,jpeg,png,webp,svg'`
- `getMaxFileSizeKb()` → 2048

---

## 28. Audit Trail (Activity Log)

### 28.1 Paket
- **spatie/laravel-activitylog** — auto-log model events.

### 28.2 Yang Di-log
- Created / Updated / Deleted / Restored events untuk model: Company, Customer, Tagihan, Pembayaran, Insentif, Karyawan, Role, Permission, Konfigurasi.
- Field changes: old → new values (untuk field sensitif seperti `is_active`, `email`, `password` di-hash, `value` untuk konfigurasi kredensial di-mask).
- Actor: polymorphic `causer` (AdminSaas, AdminCompany, Employee, Customer, atau `null` untuk system).
- Context: IP address, user agent, URL.

### 28.3 Tabel `activity_log` (default spatie)
| Column | Tipe | Keterangan |
|---|---|---|
| `id` | bigint | Primary key |
| `log_name` | string | Category (e.g., 'default', 'konfigurasi') |
| `description` | string | Action ('created', 'updated', 'deleted') |
| `subject_type` / `subject_id` | polymorphic | Model yang di-log |
| `causer_type` / `causer_id` | polymorphic | User yang melakukan |
| `properties` | json | Old/new values, metadata |
| `created_at` | timestamp | Kapan |

### 28.4 Retention
- Default: tidak auto-purge (data disimpan selamanya).
- Production: opsional scheduler `php artisan activitylog:clean` yang purge log > 1 tahun.

---

## 29. Permission Matrix

### 29.1 Struktur
Per-module, ada 7 permission standard: `list`, `create`, `ubah`, `detail`, `hapus`, `import`, `export`. Plus permission khusus modul (e.g., `tagihan.persetujuan`, `riwayat-pembayaran.persetujuan`, `riwayat-insentif.persetujuan`).

### 29.2 Daftar Lengkap
Lihat `briefing/permission.md` untuk daftar lengkap. Berikut ringkasan per scope:

#### Operator SaaS scope
- `admin-perusahaan.{list,create,ubah,detail,hapus,import,export}`
- `perusahaan.{list,create,ubah,detail,hapus,import,export}`
- `role-perusahaan.{list,create,ubah,detail,hapus,import,export}`
- `role-admin-perusahaan.{list,create,ubah,detail,hapus,import,export}`
- `konfigurasi.{list,create,ubah,detail,hapus,restore,import,export}`
- `role-saas.{list,create,ubah,detail,hapus,import,export}`
- `admin-saas.{list,create,ubah,detail,hapus,import,export}`
- `admin-role-saas.{list,create,ubah,detail,hapus,import,export}`

#### Operator Perusahaan scope
- `perusahaan-saya.{detail,ubah}` (no list — single company view)
- `daftar-paket.{list,create,ubah,detail,hapus,import,export}`
- `pelanggan.{list,create,ubah,detail,hapus,import,export}`
- `paket-pelanggan.{list,create,ubah,detail,hapus,import,export}`
- `pembayaran-pelanggan.{list,create,ubah,detail,hapus,import,export}`
- `konfigurasi-perusahaan.{list,create,ubah,detail,hapus,import,export}`
- `karyawan.{list,create,ubah,detail,hapus,import,export}`
- `role-web-karyawan.{list,create,ubah,detail,hapus,import,export}`
- `admin-role-web-karyawan.{list,create,ubah,detail,hapus,import,export}`
- `tagihan.{list,create,ubah,detail,hapus,import,export}`
- `insentif.{list,create,ubah,detail,hapus,import,export}`
- `riwayat-insentif.{list,create,ubah,detail,hapus,import,export,persetujuan}`
- `riwayat-pembayaran.{list,create,ubah,detail,hapus,import,export,persetujuan}`

#### Permission Khusus
- `tagihan.persetujuan` — setujui/tolak pembayaran
- `riwayat-insentif.persetujuan` — review insentif (modal review terpisah dari edit)
- `riwayat-pembayaran.persetujuan` — review bukti transfer

---

## 30. Notification Channels

### 30.1 Channel Real-time (Reverb WebSocket)
- Invoice baru ter-generate → broadcast ke customer & admin.
- Pembayaran tunai masuk → admin & customer dapat notifikasi.
- Bukti transfer diupload → admin notifikasi untuk verifikasi.
- Verifikasi selesai → karyawan & customer notifikasi.
- Insentif di-approve → karyawan notifikasi.
- New customer registered → admin notifikasi.

### 30.2 Channel Email
- Reset password.
- Invitation karyawan baru (link set password).
- Invitation customer baru.
- Tagihan baru (opsional, bisa di-disable per tenant).
- Laporan bulanan auto-sent ke owner.

### 30.3 Channel WhatsApp (Phase 2)
- Tagihan reminder H-3, H-1, H+0.
- Bukti transfer di-approve/reject.
- Insentif cair.

### 30.4 Channel In-App
- Toast notifications (success/error/warning/info).
- Bell icon di navbar dengan unread count.
- Notification center page dengan read/unread state.

---

## 31. Backup & Disaster Recovery

### 31.1 Backup Strategy
| Aspek | Spesifikasi |
|---|---|
| **Frequency** | Database: harian (incremental) + mingguan (full). Files (S3): real-time replication. |
| **Retention** | 30 hari untuk harian, 90 hari untuk mingguan, 1 tahun untuk bulanan |
| **Storage** | Off-site (S3 Glacier / Azure Blob Cold) — beda region dari production |
| **Encryption** | AES-256 at rest, TLS 1.3 in transit |
| **RPO** (Recovery Point Objective) | ≤ 1 jam (max data loss jika disaster) |
| **RTO** (Recovery Time Objective) | ≤ 4 jam (max downtime jika disaster) |

### 31.2 Disaster Recovery Procedure
- Tested quarterly (restore procedure dry-run).
- Runbook tersimpan di internal wiki.
- On-call rotation untuk engineer.
- Status page untuk customer (status.rtrwnet.id — future).

### 31.3 File Backup
- S3 versioning enabled.
- Lifecycle policy: move to Glacier setelah 90 hari.
- Cross-region replication (jika multi-region production).

---

## 32. Tagihan Cetak (Print Invoice)

### 32.1 Overview
Generate PDF invoice untuk dicetak atau dikirim ke pelanggan via WhatsApp/email. Trigger dari detail tagihan (`/operator-perusahaan/tagihan/{id}/cetak`).

### 32.2 Layout
- A4 portrait, 1 halaman per tagihan.
- Header: Logo perusahaan (embed base64) + nama + alamat + kontak.
- Body: Tabel detail tagihan (kode invoice, tgl jatuh tempo, paket, nominal, status).
- Footer: "Terima kasih atas pembayaran Anda" + signature box.

### 32.3 Implementation
- Controller: `TagihanController::cetak($id)`.
- View: `resources/views/pdf/invoice.blade.php`.
- Library: `barryvdh/laravel-dompdf` dengan `loadView('pdf.invoice', $data)`.
- Filename: `INV-{kode_invoice}.pdf`.

---

## 33. Kredensial Type Behavior (Detail)

### 33.1 Use Case
- API key, token, secret, password yang perlu disimpan di konfigurasi.
- Tidak boleh ter-expose di UI secara default.

### 33.2 UI Pattern
- **Tabel**: value ditampilkan sebagai `••••••••` dengan eye icon toggle. User klik eye untuk reveal temporarily.
- **Form Create/Edit**: input `type="password"` (default masked). Eye toggle untuk switch `type` ke `text` (visible). Value tetap form state, hanya atribut `type` yang berubah.
- **Detail Modal**: default masked (bullets). Eye toggle untuk reveal.

### 33.3 Backend Handling
- Disimpan di DB sebagai plain text (encrypted at rest via DB).
- Tidak di-log di activity trail (field `value` di-mask).
- Tidak di-export di Excel (placeholder `••••••••`).

### 33.4 Field Validation
- Required (wajib diisi).
- String max 65535 chars.
- No format restriction (bisa string apa saja, karena tiap API key format beda).

---

## 34. Boolean Normalization (Detail)

### 34.1 Problem
PHP `(bool) "false"` = `true` (non-empty string selalu truthy). Ini bug kalau baca langsung dari DB.

### 34.2 Solution
Semua value boolean di-normalize via `filter_var(..., FILTER_VALIDATE_BOOLEAN)` yang handle:
- Truthy: `1`, `true`, `on`, `yes`
- Falsy: `0`, `false`, `off`, `no`, `null`, `""`

### 34.3 Storage Convention
- Selalu disimpan sebagai string `'true'` atau `'false'`.
- Bukan integer `0`/`1` (biar konsisten dan manusia-readable di phpMyAdmin).
- Saat di-read kembali: `filter_var($value, FILTER_VALIDATE_BOOLEAN)`.

### 34.4 Contoh
```php
// Simpan
SaasConfig::update(['value' => $request->boolean_value]);  // true / false
// Normalized ke 'true' / 'false' string di controller

// Baca
$autoCompress = filter_var(
    SaasConfig::getValue('default_auto_compress_file_upload', true),
    FILTER_VALIDATE_BOOLEAN
);
// Returns true or false
```

---

## 35. Phone Country Code Convention

### 35.1 Field Pattern
Semua field telepon di seluruh modul menggunakan 2 field terpisah:
- `phonecountry_code` (string, e.g., "+62") — dengan kode negara
- `phone_number` (string, e.g., "81234567890") — tanpa kode negara

### 35.2 UI Pattern
- Form input: 2 field side-by-side. Country code = select dropdown dengan daftar kode (default +62). Phone number = text input.
- Display: gabung jadi "+62 812-3456-7890" dengan format.
- Search: gabung di query: `CONCAT(phonecountry_code, phone_number)`.

### 35.3 Validation
- Country code: format `^\+\d{1,4}$`.
- Phone number: format `^\d{6,15}$` (digit only, 6-15 chars).

---

## 36. Sidebar Menu (Per Portal)

Lihat `briefing/sidebar.md` untuk daftar lengkap. Berikut ringkasan per portal:

### Operator SaaS
Dashboard, Admin Perusahaan, Perusahaan, Role Perusahaan, Role Admin Perusahaan, Konfigurasi, Role SaaS, Admin SaaS, Admin Role SaaS.

### Operator Perusahaan
Dashboard, Perusahaan Saya, Daftar Paket, Customer, Langganan Customer, Tagihan, Insentif, Riwayat Insentif, Riwayat Pembayaran, Admin Perusahaan, Role Perusahaan, Admin Role Perusahaan, Karyawan, Role Web Karyawan, Admin Role Web Karyawan, Konfigurasi Perusahaan.

### Karyawan
Dashboard, Profil Saya, Customer, Langganan Customer, Tagihan, Insentif Saya, Riwayat Pembayaran.

### Customer
Dashboard, Profil Saya, Paket Saya, Tagihan Saya, Riwayat Pembayaran.

---

## 37. Changelog PRD

| Versi | Tanggal | Perubahan |
|---|---|---|
| 1.0.0 | 2026-05-11 | Initial draft: 5 core modules (Multi-Tenant, Pelanggan, Paket, Tagihan, Pembayaran, Insentif, Laporan, Notifikasi, Upload, UI/UX, Non-Functional, Tech Stack) |
| 1.1.0 | 2026-06-07 | Tambah: Multi-Guard Auth (#13), Landing Page (#14), Perusahaan (#15), Perusahaan Saya (#16), Karyawan (#17), Konfigurasi SaaS (#18), Konfigurasi Perusahaan (#19), Paket Pelanggan (#20), Pembayaran Pelanggan (#21), Customer Portal (#22), Karyawan Portal (#23), PDF Generation (#24), Import/Export Excel (#25), Soft Delete + Restore (#26), Image Processing (#27), Audit Trail (#28), Permission Matrix (#29), Notification Channels (#30), Backup & DR (#31), Tagihan Cetak (#32), Kredensial Type (#33), Boolean Normalization (#34), Phone Country Code (#35), Sidebar Menu (#36). Reorganize: fixed duplicate numbering (12. Non-Functional vs 12. Tech Stack → split menjadi 11. UI/UX Design System + 12. Non-Functional + 13. Tech Stack Detail). |
