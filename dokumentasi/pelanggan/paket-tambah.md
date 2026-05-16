# Paket Tambah
> Portal: Pelanggan | URL: `/customer/paket-saya/tambah`

## Fungsi
Halaman untuk **memilih dan mendaftar paket internet baru**.
Pelanggan dapat melihat daftar paket yang tersedia dan memilih paket yang ingin dilanggan.

## Fitur
- **Daftar paket tersedia** — menampilkan paket internet yang bisa dipilih pelanggan
- **Form pendaftaran** — memilih paket dan konfirmasi pendaftaran

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Paket Tersedia** | — | Semua pelanggan yang login dapat melihat paket tersedia |
| **Daftar Paket** | — | Mendaftar paket internet baru |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/customer/paket-saya/tambah` | — | `auth:customer` | — |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Customer/PaketTambah.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/PaketTambahViewTest.php` | Various | Browser view test |
