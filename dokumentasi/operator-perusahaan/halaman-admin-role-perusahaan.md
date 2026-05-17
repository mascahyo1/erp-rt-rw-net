# Halaman Admin Role Perusahaan
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/admin-role-perusahaan`

## Fungsi
Halaman untuk **melihat pemasangan role ke admin perusahaan** di perusahaan sendiri.
Menampilkan admin perusahaan mana saja yang sudah dipasangkan role dan role apa yang mereka miliki.

## Fitur
- **Tabel daftar pemasangan** — menampilkan admin perusahaan dan role yang sudah dipasangkan

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `admin-role-perusahaan-op.list` | Melihat tabel pemasangan dan sidebar menu |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/admin-role-perusahaan` | — | `auth:admin-company` | `admin-role-perusahaan-op.list` |

### Controller
Closure (inline route)

### View
`resources/js/Pages/OperatorPerusahaan/AdminRolePerusahaan.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\ModelHasRole` | `model_has_roles` | Model utama — pivot pemasangan role ke admin |
| `App\Models\AdminCompany` | `admin_companies` | Join — admin yang dipasangkan (via `model_type` = `AdminCompany`) |
| `App\Models\Role` | `roles` | Join — role yang dipasangkan (via `role_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_140504_create_model_has_roles_table` | `model_has_roles` |
| `2026_05_11_135636_create_admin_companies_table` | `admin_companies` |
| `2026_05_11_140234_create_roles_table` | `roles` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/Feature/OperatorPerusahaan/AdminRolePerusahaanViewTest.php` | Various | Browser view test |
