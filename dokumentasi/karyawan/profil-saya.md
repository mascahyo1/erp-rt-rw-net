# Profil Saya
> Portal: Karyawan | URL: `/karyawan/profil-saya`

## Fungsi
Halaman untuk **melihat profil** akun karyawan sendiri.
Menampilkan informasi nama, email, nomor HP, dan detail akun karyawan yang sedang login.

## Fitur
- **Tampilan profil** — menampilkan informasi akun karyawan

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Profil** | `profil-saya.list` | Melihat profil sendiri |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/karyawan/profil-saya` | — | `auth:employee` | `profil-saya.list` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Karyawan/ProfilSaya.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/ProfilSayaViewTest.php` | Various | Browser view test |
