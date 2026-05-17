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

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Customer` | `customers` | Data pelanggan — total & pelanggan aktif |
| `App\Models\Employee` | `employees` | Data karyawan — karyawan aktif |
| `App\Models\CustInternet` | `cust_internets` | Data langganan — langganan aktif (join ke `customers`) |
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Data tagihan — tagihan bulan ini (join ke `cust_internets` → `customers`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_142201_create_customers_table` | `customers` |
| `2026_05_11_140604_create_employees_table` | `employees` |
| `2026_05_11_142443_create_cust_internets_table` | `cust_internets` |
| `2026_05_11_143143_create_cust_internet_invcs_table` | `cust_internet_invcs` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/DashboardViewTest.php` | Various | Browser view test |
