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

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Customer` | `customers` | Data pelanggan — total pelanggan yang ditagih |
| `App\Models\CustInternet` | `cust_internets` | Data langganan (join ke `customers`) |
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Data tagihan bulan ini (join ke `cust_internets`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_142201_create_customers_table` | `customers` |
| `2026_05_11_142443_create_cust_internets_table` | `cust_internets` |
| `2026_05_11_143143_create_cust_internet_invcs_table` | `cust_internet_invcs` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/DashboardTest.php` | Various | Browser test with screenshot |
