# Dashboard
> Portal: Operator SaaS | URL: `/operator-saas/dashboard`

## Fungsi
Halaman utama operator SaaS yang menampilkan **ringkasan statistik seluruh sistem**.
Menampilkan jumlah perusahaan aktif, admin perusahaan, admin SaaS, pelanggan, karyawan, dan langganan aktif secara real-time.

## Fitur
- **Kartu statistik** — menampilkan ringkasan jumlah: perusahaan aktif, admin perusahaan, admin SaaS, pelanggan, karyawan, langganan aktif

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Dashboard** | — | Semua admin SaaS yang login dapat melihat dashboard |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/dashboard` | `operator-saas.dashboard` | `auth:web`, `ensure.user.active:web` | — |

### Controller
Closure (inline route) — loads stats: perusahaan_aktif, admin_perusahaan_aktif, admin_saas, pelanggan_aktif, karyawan_aktif, langganan_aktif

### View
`resources/js/Pages/OperatorSaas/Dashboard.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Company` | `companies` | Data perusahaan — total perusahaan terdaftar |
| `App\Models\Customer` | `customers` | Data pelanggan — total seluruh pelanggan |
| `App\Models\Employee` | `employees` | Data karyawan — total seluruh karyawan |
| `App\Models\AdminCompany` | `admin_companies` | Data admin perusahaan — total admin |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_135231_create_companies_table` | `companies` |
| `2026_05_11_142201_create_customers_table` | `customers` |
| `2026_05_11_140604_create_employees_table` | `employees` |
| `2026_05_11_135636_create_admin_companies_table` | `admin_companies` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorSaas/DashboardViewTest.php` | Various | Browser view test |
