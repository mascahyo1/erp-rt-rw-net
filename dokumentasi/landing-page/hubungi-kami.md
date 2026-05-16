# Hubungi Kami
> Portal: Landing Page | URL: `/hubungi-kami`

## Fungsi
Halaman yang berisi **informasi kontak** perusahaan.
Pengunjung dapat melihat alamat, nomor telepon, email, dan informasi kontak lainnya untuk menghubungi penyedia layanan.

## Fitur
- **Tampilan kontak** — menampilkan alamat, telepon, email, dan informasi kontak perusahaan

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Halaman** | — | Semua pengunjung dapat melihat halaman Hubungi Kami |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/hubungi-kami` | — | — | — |

### Controller
Closure (inline route) — loads contact config from `App\Models\SaasConfig`

### View
`resources/js/Pages/Landing/HubungiKami.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| — | — | No dedicated test file found |
