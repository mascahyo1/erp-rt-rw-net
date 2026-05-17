# Tagihan Detail
> Portal: Pelanggan | URL: `/customer/tagihan-saya/detail`

## Fungsi
Halaman untuk **melihat rincian tagihan** tertentu.
Menampilkan informasi lengkap tagihan — periode, jumlah, rincian biaya, dan status pembayaran.

## Fitur
- **Tampilan detail tagihan** — menampilkan informasi lengkap tagihan yang dipilih

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Detail Tagihan** | — | Semua pelanggan yang login dapat melihat detail tagihan sendiri |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/customer/tagihan-saya/detail` | — | `auth:customer` | — |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Customer/TagihanDetail.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Model utama — detail tagihan |
| `App\Models\CustInternet` | `cust_internets` | Join — data langganan (via `cust_internet_id`) |
| `App\Models\InternetPackage` | `internet_packages` | Join — paket (via `cust_internets.internet_package_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_143143_create_cust_internet_invcs_table` | `cust_internet_invcs` |
| `2026_05_11_142443_create_cust_internets_table` | `cust_internets` |
| `2026_05_11_141134_create_internet_packages_table` | `internet_packages` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/TagihanDetailViewTest.php` | Various | Browser view test |
