# Dashboard
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/dashboard`

## Fungsi
Halaman utama operator perusahaan yang menampilkan **ringkasan statistik perusahaan**.
Menampilkan jumlah pelanggan, pelanggan aktif, karyawan aktif, langganan aktif, dan total tagihan bulan ini.

## Fitur
- **Kartu statistik** — menampilkan ringkasan jumlah: total pelanggan, pelanggan aktif, karyawan aktif, langganan aktif, tagihan bulan ini

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Dashboard** | — | Semua admin perusahaan yang login dapat melihat dashboard |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/dashboard` | `operator-perusahaan.dashboard` | `auth:admin-company` | — |

### Controller
Closure (inline route) — loads stats: total_customer, customer_aktif, karyawan_aktif, langganan_aktif, tagihan_bulan_ini

### View
`resources/js/Pages/OperatorPerusahaan/Dashboard.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/DashboardViewTest.php` | Various | Browser view test |
