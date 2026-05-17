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

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Customer` | `customers` | Data pelanggan — auth user (`auth()->user()`) |
| `App\Models\CustInternet` | `cust_internets` | Data langganan aktif pelanggan |
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Data tagihan pelanggan |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_142201_create_customers_table` | `customers` |
| `2026_05_11_142443_create_cust_internets_table` | `cust_internets` |
| `2026_05_11_143143_create_cust_internet_invcs_table` | `cust_internet_invcs` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/DashboardTest.php` | Various | Browser test with screenshot |
