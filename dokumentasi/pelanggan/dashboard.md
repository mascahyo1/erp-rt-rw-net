# Dashboard
> Portal: Pelanggan | URL: `/customer/dashboard`

## Fungsi
Halaman utama pelanggan yang menampilkan **ringkasan akun**.
Menampilkan jumlah paket aktif, tagihan bulan ini, dan riwayat pembayaran terbaru.

## Fitur
- **Kartu statistik** — menampilkan ringkasan: paket aktif, tagihan bulan ini, riwayat pembayaran

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Dashboard** | — | Semua pelanggan yang login dapat melihat dashboard |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/customer/dashboard` | `customer.dashboard` | `auth:customer` | — |

### Controller
Closure (inline route) — loads stats: paket_aktif, tagihan_bulan_ini, riwayat_pembayaran

### View
`resources/js/Pages/Customer/Dashboard.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/DashboardTest.php` | Various | Browser test with screenshot |
