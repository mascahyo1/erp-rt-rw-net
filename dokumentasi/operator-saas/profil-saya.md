# Profil Saya
> Portal: Operator SaaS | URL: `/operator-saas/profil-saya`

## Fungsi
Halaman untuk **mengubah profil dan password** akun operator SaaS sendiri.
Admin SaaS dapat memperbarui nama, email, dan password mereka melalui halaman ini.

## Fitur
- **Form ubah profil** — mengubah nama dan email
- **Form ubah password** — mengganti password dengan konfirmasi password baru

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Profil** | — | Semua admin SaaS yang login dapat melihat profil sendiri |
| **Ubah Profil** | — | Mengubah nama dan email |
| **Ubah Password** | — | Mengganti password akun |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/profil-saya` | — | — | — |
| PUT | `/operator-saas/profil-saya` | — | — | — |

### Controller
Closure (inline route) — updates name, email, password with validation

### View
`resources/js/Pages/OperatorSaas/ProfilSaya.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\AdminSaas` | `admin_saas` | Model utama — akun operator SaaS yang sedang login (`auth()->user()`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_12_000505_create_admin_saas_table` | `admin_saas` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorSaas/ProfilSayaTest.php` | Various | Browser test with screenshot |
