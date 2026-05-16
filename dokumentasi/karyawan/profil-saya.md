# Profil Saya
> Portal: Karyawan | URL: `/karyawan/profil-saya`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/profil-saya` | — | `auth:employee` | `profil-saya.list` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/Karyawan/ProfilSaya.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/ProfilSayaViewTest.php` | Various | Browser view test |
