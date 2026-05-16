# Login Operator SaaS
> Portal: Landing Page | URL: `/login-operator-saas`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/login-operator-saas` | `operator-saas.login` | — | — |
| POST | `/login-operator-saas` | — | `throttle:5,1` | — |
| POST | `/logout-operator-saas` | `operator-saas.logout` | — | — |

## Controller
`App\Http\Controllers\Auth\AuthenticatedSessionController@create`
`App\Http\Controllers\Auth\AuthenticatedSessionController@store`
`App\Http\Controllers\Auth\AuthenticatedSessionController@destroy`

## View
`resources/js/Pages/Landing/LoginOperatorSaaS.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/Auth/AdminSaasAuthenticationTest.php` | `test_login_screen_can_be_rendered` | Feature test |
| `tests/Browser/Feature/OperatorSaas/LoginTest.php` | Various | Browser login test |
