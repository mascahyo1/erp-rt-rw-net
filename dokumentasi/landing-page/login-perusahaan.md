# Login Perusahaan
> Portal: Landing Page | URL: `/login-perusahaan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/login-perusahaan` | `operator-perusahaan.login` | — | — |
| POST | `/login-perusahaan` | — | `throttle:5,1` | — |
| POST | `/logout-perusahaan` | `operator-perusahaan.logout` | — | — |

## Controller
`App\Http\Controllers\Auth\AdminCompanySessionController@create`
`App\Http\Controllers\Auth\AdminCompanySessionController@store`
`App\Http\Controllers\Auth\AdminCompanySessionController@destroy`

## View
`resources/js/Pages/Landing/LoginPerusahaan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/Auth/AdminCompanyAuthenticationTest.php` | `test_login_screen_can_be_rendered` | Feature test |
| `tests/Browser/Feature/OperatorPerusahaan/LoginTest.php` | Various | Browser login test |
