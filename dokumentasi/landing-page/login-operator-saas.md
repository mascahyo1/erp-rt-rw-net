# Login Operator SaaS
> Portal: Landing Page | URL: `/login-operator-saas`

## Fungsi
Halaman **login untuk admin operator SaaS** (super admin).
Admin SaaS memasukkan email dan password untuk masuk ke portal operator SaaS.

## Fitur
- **Form login** — input email dan password
- **Validasi throttling** — membatasi percobaan login untuk keamanan

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Form Login** | — | Semua pengunjung dapat melihat halaman login operator SaaS |
| **Login** | — | Memasukkan email dan password untuk masuk |
| **Logout** | — | Keluar dari sesi operator SaaS |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/login-operator-saas` | `operator-saas.login` | — | — |
| POST | `/login-operator-saas` | — | `throttle:5,1` | — |
| POST | `/logout-operator-saas` | `operator-saas.logout` | — | — |

### Controller
`App\Http\Controllers\Auth\AuthenticatedSessionController@create`
`App\Http\Controllers\Auth\AuthenticatedSessionController@store`
`App\Http\Controllers\Auth\AuthenticatedSessionController@destroy`

### View
`resources/js/Pages/Landing/LoginOperatorSaaS.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\AdminSaas` | `admin_saas` | Model utama — autentikasi operator SaaS |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_12_000505_create_admin_saas_table` | `admin_saas` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/Auth/AdminSaasAuthenticationTest.php` | `test_login_screen_can_be_rendered` | Feature test |
| `tests/Browser/Feature/OperatorSaas/LoginTest.php` | Various | Browser login test |
