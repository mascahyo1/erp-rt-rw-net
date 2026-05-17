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

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\Employee` | `employees` | Model utama — akun karyawan yang sedang login (`auth()->user()`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_140604_create_employees_table` | `employees` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/Karyawan/ProfilSayaViewTest.php` | Various | Browser view test |
