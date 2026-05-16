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

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/TagihanDetailViewTest.php` | Various | Browser view test |
