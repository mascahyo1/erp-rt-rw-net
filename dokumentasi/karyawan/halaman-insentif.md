# Halaman Insentif
> Portal: Karyawan | URL: `/karyawan/insentif-saya`

## Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/insentif-saya` | — | `auth:employee` | `karyawan-insentif.list` |
| POST | `/karyawan/insentif-saya` | — | `auth:employee` | `karyawan-insentif.create` |
| PUT | `/karyawan/insentif-saya/{id}` | — | `auth:employee` | `karyawan-insentif.edit` |
| DELETE | `/karyawan/insentif-saya/{id}` | — | `auth:employee` | `karyawan-insentif.delete` |
| PATCH | `/karyawan/insentif-saya/{id}/restore` | — | `auth:employee` | `karyawan-insentif.restore` |

## Controller
Closure (inline route)

## View
`resources/js/Pages/Karyawan/InsentifSaya.vue`

## Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/InsentifPermissionTest.php` | Various | Browser permission test |
| `tests/Browser/Feature/Karyawan/InsentifSayaViewTest.php` | Various | Browser view test |
