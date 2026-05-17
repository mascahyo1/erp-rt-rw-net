# Login Pelanggan
> Portal: Landing Page | URL: `/login-pelanggan`

## Fungsi
Halaman **login dan pendaftaran untuk pelanggan**.
Pelanggan dapat login dengan email dan password, atau mendaftar akun baru jika belum memiliki akun.

## Fitur
- **Form login** — input email dan password
- **Form pendaftaran** — pelanggan baru dapat mendaftar akun
- **Validasi throttling** — membatasi percobaan login untuk keamanan

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Form Login** | — | Semua pengunjung dapat melihat halaman login pelanggan |
| **Login** | — | Memasukkan email dan password untuk masuk |
| **Daftar Akun** | — | Mendaftar akun pelanggan baru |
| **Logout** | — | Keluar dari sesi pelanggan |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/login-pelanggan` | `customer.login` | — | — |
| POST | `/login-pelanggan` | — | `throttle:5,1` | — |
| POST | `/logout-pelanggan` | `customer.logout` | — | — |
| POST | `/daftar-pelanggan` | `customer.register` | — | — |

### Controller
`App\Http\Controllers\Auth\CustomerSessionController@create`
`App\Http\Controllers\Auth\CustomerSessionController@store`
`App\Http\Controllers\Auth\CustomerSessionController@destroy`
`App\Http\Controllers\Auth\CustomerSessionController@register`

### View
`resources/js/Pages/Landing/LoginPelanggan.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Customer` | `customers` | Model utama — autentikasi pelanggan |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_142201_create_customers_table` | `customers` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/Auth/CustomerAuthenticationTest.php` | `test_login_screen_can_be_rendered` | Feature test |
| `tests/Browser/Feature/Pelanggan/LoginTest.php` | Various | Browser login test |
