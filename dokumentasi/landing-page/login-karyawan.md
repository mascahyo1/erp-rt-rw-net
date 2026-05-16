# Login Karyawan
> Portal: Landing Page | URL: `/login-karyawan`

## Fungsi
Halaman **login untuk karyawan**.
Karyawan memasukkan email dan password untuk masuk ke portal karyawan.

## Fitur
- **Form login** — input email dan password

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Form Login** | — | Semua pengunjung dapat melihat halaman login karyawan |
| **Login** | — | Memasukkan email dan password untuk masuk |
| **Logout** | — | Keluar dari sesi karyawan |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/login-karyawan` | `employee.login` | — | — |
| POST | `/login-karyawan` | — | — | — |
| POST | `/logout-karyawan` | `employee.logout` | — | — |

### Controller
`App\Http\Controllers\Auth\EmployeeSessionController@create`
`App\Http\Controllers\Auth\EmployeeSessionController@store`
`App\Http\Controllers\Auth\EmployeeSessionController@destroy`

### View
`resources/js/Pages/Karyawan/Login.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/Auth/EmployeeAuthenticationTest.php` | `test_login_screen_can_be_rendered` | Feature test |
| `tests/Browser/Feature/Karyawan/LoginTest.php` | Various | Browser login test |
