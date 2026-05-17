# Halaman Konfigurasi
> Portal: Operator SaaS | URL: `/operator-saas/konfigurasi`

## Fungsi
Halaman untuk mengatur **konfigurasi sistem SaaS** seperti kontak, email, dan pengaturan umum yang berlaku untuk seluruh platform.
Konfigurasi ini akan digunakan di halaman landing page seperti Hubungi Kami dan Kebijakan Privasi.

## Fitur
- **Tabel daftar konfigurasi** — menampilkan semua konfigurasi yang sudah dibuat
- **Pencarian** — mencari konfigurasi berdasarkan key atau value (tekan Enter untuk mencari)
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `konfigurasi.list` | Melihat tabel konfigurasi dan sidebar menu |
| **Tambah Konfigurasi** | `konfigurasi.create` | Membuat konfigurasi baru dengan mengisi key dan value |
| **Edit Konfigurasi** | `konfigurasi.edit` | Mengubah nilai konfigurasi yang sudah ada |
| **Hapus Konfigurasi** | `konfigurasi.delete` | Menghapus konfigurasi |
| **Bulk Hapus** | `konfigurasi.delete` | Menghapus banyak konfigurasi sekaligus |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-saas/konfigurasi` | `operator-saas.konfigurasi.index` | `auth:web`, `ensure.user.active:web` | `konfigurasi.list` |
| POST | `/operator-saas/konfigurasi` | `operator-saas.konfigurasi.store` | `auth:web`, `ensure.user.active:web` | `konfigurasi.create` |
| PUT | `/operator-saas/konfigurasi/{saasConfig}` | `operator-saas.konfigurasi.update` | `auth:web`, `ensure.user.active:web` | `konfigurasi.edit` |
| DELETE | `/operator-saas/konfigurasi/{saasConfig}` | `operator-saas.konfigurasi.destroy` | `auth:web`, `ensure.user.active:web` | `konfigurasi.delete` |
| POST | `/operator-saas/konfigurasi/bulk-delete` | `operator-saas.konfigurasi.bulk-delete` | `auth:web`, `ensure.user.active:web` | `konfigurasi.delete` |

### Controller
`App\Http\Controllers\SaasConfigController@index`
`App\Http\Controllers\SaasConfigController@store`
`App\Http\Controllers\SaasConfigController@update`
`App\Http\Controllers\SaasConfigController@destroy`
`App\Http\Controllers\SaasConfigController@bulkDelete`

### View
`resources/js/Pages/OperatorSaas/Konfigurasi.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\SaasConfig` | `saas_configs` | Model utama — konfigurasi global SaaS |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_224754_create_saas_configs_table` | `saas_configs` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Feature/OperatorSaas/SaasConfigTest.php` | Various | Feature CRUD test |
| `tests/Browser/Feature/OperatorSaas/KonfigurasiCRUDTest.php` | Various | Browser CRUD test with screenshot |
| `tests/Browser/Feature/OperatorSaas/KonfigurasiPermissionTest.php` | Various | Browser permission test |
