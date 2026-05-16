# Login Pelanggan
> Portal: Landing Page | URL: `/login-pelanggan`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/login-pelanggan` | `customer.login` | — | — |
| POST | `/login-pelanggan` | — | `throttle:5,1` | — |
| POST | `/logout-pelanggan` | `customer.logout` | — | — |
| POST | `/daftar-pelanggan` | `customer.register` | — | — |

## Controller
`App\Http\Controllers\Auth\CustomerSessionController@create`
`App\Http\Controllers\Auth\CustomerSessionController@store`
`App\Http\Controllers\Auth\CustomerSessionController@destroy`
`App\Http\Controllers\Auth\CustomerSessionController@register`

## View
`resources/js/Pages/Landing/LoginPelanggan.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/Auth/CustomerAuthenticationTest.php` | `test_login_screen_can_be_rendered` | Feature test |
| `tests/Browser/Feature/Pelanggan/LoginTest.php` | Various | Browser login test |
