# Beranda
> Portal: Landing Page | URL: `/`

## Fungsi
Halaman utama website yang menjadi **pintu masuk** bagi semua pengguna.
Pengunjung dapat melihat informasi umum tentang platform ERP RT/RW Net dan memilih menu untuk login sesuai perannya (operator SaaS, perusahaan, pelanggan, atau karyawan).

## Fitur
- **Navigasi** — menu menuju halaman Tentang Kami, Hubungi Kami, Syarat & Ketentuan, Kebijakan Privasi
- **Tombol login** — akses cepat ke halaman login masing-masing peran

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Beranda** | — | Semua pengunjung dapat melihat halaman beranda |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/` | `landing.home` | — | — |

### Controller
Closure (inline route)

### View
`resources/js/Pages/Landing/Home.vue`

### Test Case
| File | Method | Description |
|------|--------|-------------|
| — | — | No dedicated test file found |
