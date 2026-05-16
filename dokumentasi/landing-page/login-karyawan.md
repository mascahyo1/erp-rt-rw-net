# Login Karyawan
> Portal: Landing Page | URL: `/login-karyawan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/login-karyawan` | `employee.login` | — | — |
| POST | `/login-karyawan` | — | — | — |
| POST | `/logout-karyawan` | `employee.logout` | — | — |

## Controller
`App\Http\Controllers\Auth\EmployeeSessionController@create`
`App\Http\Controllers\Auth\EmployeeSessionController@store`
`App\Http\Controllers\Auth\EmployeeSessionController@destroy`

## View
`resources/js/Pages/Karyawan/Login.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/Auth/EmployeeAuthenticationTest.php` | `test_login_screen_can_be_rendered` | Feature test |
| `tests/Browser/Feature/Karyawan/LoginTest.php` | Various | Browser login test |
