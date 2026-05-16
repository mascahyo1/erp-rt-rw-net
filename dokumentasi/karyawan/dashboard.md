# Dashboard
> Portal: Karyawan | URL: `/karyawan/dashboard`

## Fungsi
Halaman utama karyawan yang menampilkan **ringkasan pekerjaan penagihan**.
Menampilkan jumlah pelanggan yang perlu ditagih, tagihan bulan ini, dan total pembayaran yang sudah dikumpulkan.

## Fitur
- **Kartu statistik** — menampilkan ringkasan jumlah: pelanggan yang perlu ditagih, tagihan bulan ini, dan total pembayaran terkumpul

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Dashboard** | — | Semua karyawan yang login dapat melihat dashboard |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/dashboard` | `employee.dashboard` | `auth:employee` | — |

### Controller
Closure (inline route) — loads stats: customer_ditagih, tagihan_bulan_ini, pembayaran_collection

### View
`resources/js/Pages/Karyawan/Dashboard.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/DashboardTest.php` | Various | Browser test with screenshot |
