# Profil Saya
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/profil-saya`

## Fungsi
Halaman untuk **melihat profil** akun admin perusahaan sendiri.
Menampilkan informasi nama, email, dan detail akun admin perusahaan yang sedang login.

## Fitur
- **Tampilan profil** — menampilkan informasi akun admin perusahaan

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Profil** | — | Semua admin perusahaan yang login dapat melihat profil sendiri |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/profil-saya` | — | `auth:admin-company` | — |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/ProfilSaya.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/ProfilSayaViewTest.php` | Various | Browser view test |
