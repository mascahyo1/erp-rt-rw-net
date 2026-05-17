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

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\AdminCompany` | `admin_companies` | Model utama — akun admin perusahaan yang sedang login (`auth()->user()`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_135636_create_admin_companies_table` | `admin_companies` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/ProfilSayaViewTest.php` | Various | Browser view test |
