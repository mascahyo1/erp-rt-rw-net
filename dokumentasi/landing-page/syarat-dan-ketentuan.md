# Syarat dan Ketentuan
> Portal: Landing Page | URL: `/syarat-dan-ketentuan`

## Fungsi
Halaman yang berisi **syarat dan ketentuan penggunaan** platform.
Pengunjung dapat membaca aturan dan ketentuan yang berlaku saat menggunakan layanan ERP RT/RW Net.

## Fitur
- **Konten ketentuan** — menampilkan syarat dan ketentuan penggunaan platform

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Halaman** | — | Semua pengunjung dapat melihat halaman Syarat dan Ketentuan |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/syarat-dan-ketentuan` | — | — | — |

### Controller
Closure (inline route) — loads `contact.email_terms` from `App\Models\SaasConfig`

### View
`resources/js/Pages/Landing/SyaratKetentuan.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| — | — | No dedicated test file found |
