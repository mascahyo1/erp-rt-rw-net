# Kebijakan Privasi
> Portal: Landing Page | URL: `/kebijakan-privasi`

## Fungsi
Halaman yang berisi **kebijakan privasi** platform.
Pengunjung dapat membaca bagaimana data pribadi mereka dikumpulkan, digunakan, dan dilindungi oleh penyedia layanan.

## Fitur
- **Konten kebijakan** — menampilkan kebijakan privasi platform

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Halaman** | — | Semua pengunjung dapat melihat halaman Kebijakan Privasi |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/kebijakan-privasi` | — | — | — |

### Controller
Closure (inline route) — loads `contact.email_privacy` from `App\Models\SaasConfig`

### View
`resources/js/Pages/Landing/KebijakanPrivasi.vue`

### Model
Tidak ada — halaman statis.

### Migration
Tidak ada.

### Test Case
| File | Method | Description |
|------|--------|-------------|
| — | — | No dedicated test file found |
