# Paket Saya
> Portal: Pelanggan | URL: `/customer/paket-saya`

## Fungsi
Halaman untuk **melihat langganan paket internet** yang dimiliki pelanggan.
Pelanggan dapat melihat paket apa yang sedang mereka gunakan, termasuk kecepatan, harga, dan status langganan.
Dari halaman ini pelanggan juga bisa menambah paket baru atau melihat detail paket yang sudah ada.

## Fitur
- **Tabel daftar paket** — menampilkan paket yang dimiliki pelanggan dengan nama, kecepatan, harga, dan status
- **Tombol tambah paket** — mengarahkan ke halaman pemilihan paket baru

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Paket Saya** | — | Semua pelanggan yang login dapat melihat paket sendiri |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/customer/paket-saya` | — | `auth:customer` | — |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Customer/PaketSaya.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\CustInternet` | `cust_internets` | Model utama — langganan aktif pelanggan |
| `App\Models\InternetPackage` | `internet_packages` | Join — detail paket (via `internet_package_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_142443_create_cust_internets_table` | `cust_internets` |
| `2026_05_11_141134_create_internet_packages_table` | `internet_packages` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/PaketSayaViewTest.php` | Various | Browser view test |
