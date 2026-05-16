# Profil Saya
> Portal: Pelanggan | URL: `/customer/profil-saya`

## Fungsi
Halaman untuk **melihat profil** akun pelanggan sendiri.
Menampilkan informasi nama, email, alamat, nomor telepon, dan detail akun pelanggan yang sedang login.

## Fitur
- **Tampilan profil** — menampilkan informasi akun pelanggan

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Profil** | — | Semua pelanggan yang login dapat melihat profil sendiri |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/customer/profil-saya` | — | `auth:customer` | — |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Customer/ProfilSaya.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Pelanggan/ProfilSayaViewTest.php` | Various | Browser view test |
