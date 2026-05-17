# Login Perusahaan
> Portal: Landing Page | URL: `/login-perusahaan`

## Fungsi
Halaman **login untuk admin perusahaan**.
Admin perusahaan memasukkan email dan password untuk masuk ke portal operator perusahaan.

## Fitur
- **Form login** — input email dan password
- **Validasi throttling** — membatasi percobaan login untuk keamanan

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Form Login** | — | Semua pengunjung dapat melihat halaman login perusahaan |
| **Login** | — | Memasukkan email dan password untuk masuk |
| **Logout** | — | Keluar dari sesi operator perusahaan |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/login-perusahaan` | `operator-perusahaan.login` | — | — |
| POST | `/login-perusahaan` | — | `throttle:5,1` | — |
| POST | `/logout-perusahaan` | `operator-perusahaan.logout` | — | — |

### Controller
`App\Http\Controllers\Auth\AdminCompanySessionController@create`
`App\Http\Controllers\Auth\AdminCompanySessionController@store`
`App\Http\Controllers\Auth\AdminCompanySessionController@destroy`

### View
`resources/js/Pages/Landing/LoginPerusahaan.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\AdminCompany` | `admin_companies` | Model utama — autentikasi admin perusahaan |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_135636_create_admin_companies_table` | `admin_companies` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/Auth/AdminCompanyAuthenticationTest.php` | `test_login_screen_can_be_rendered` | Feature test |
| `tests/Browser/Feature/OperatorPerusahaan/LoginTest.php` | Various | Browser login test |
