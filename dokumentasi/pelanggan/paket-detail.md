# Paket Detail
> Portal: Pelanggan | URL: `/customer/paket-saya/detail`

## Fungsi
Halaman untuk **melihat detail paket internet** yang dimiliki pelanggan.
Menampilkan informasi lengkap tentang paket yang sedang dilanggan — nama paket, kecepatan, harga, tanggal mulai, dan status.

## Fitur
- **Tampilan detail paket** — menampilkan informasi lengkap paket yang dilanggan

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Detail Paket** | — | Semua pelanggan yang login dapat melihat detail paket sendiri |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/customer/paket-saya/detail` | — | `auth:customer` | — |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Customer/PaketDetail.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| — | — | No dedicated test file found |
