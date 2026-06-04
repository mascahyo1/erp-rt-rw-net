# Halaman Riwayat Pembayaran
> Portal: Operator Perusahaan | URL: `/operator-perusahaan/riwayat-pembayaran`

## Fungsi
Halaman untuk mengelola **riwayat pembayaran tagihan** dari pelanggan.
Mencatat setiap pembayaran yang dilakukan pelanggan beserta jumlah, metode, dan status verifikasinya.

## Fitur
- **Tabel daftar riwayat** — menampilkan semua riwayat pembayaran dengan pelanggan, jumlah, tanggal, dan status
- **Pencarian** — mencari riwayat berdasarkan nama pelanggan (tekan Enter untuk mencari)
- **Filter status** — menyaring riwayat berdasarkan status: Menunggu, Disetujui, Ditolak, atau Terhapus
- **Urutkan** — klik judul kolom untuk mengurutkan data (bisa multi-kolom)
- **Pagination** — pilih jumlah data per halaman: 5, 10, 25, 50, 100

## Aksi
| Aksi | Izin Diperlukan | Keterangan |
|------|----------------|------------|
| **Lihat Daftar** | `riwayat-pembayaran.list` | Melihat tabel riwayat dan sidebar menu |
| **Tambah Riwayat** | `riwayat-pembayaran.create` | Mencatat pembayaran baru |
| **Edit Riwayat** | `riwayat-pembayaran.edit` | Mengubah data riwayat pembayaran |
| **Hapus Riwayat** | `riwayat-pembayaran.delete` | Menghapus riwayat (dapat dipulihkan lagi) |
| **Pulihkan Riwayat** | `riwayat-pembayaran.restore` | Mengembalikan riwayat yang sudah dihapus |
| **Verifikasi Pembayaran** | `riwayat-pembayaran.persetujuan` | Menyetujui atau menolak pembayaran pelanggan |
| **Download PDF** | `riwayat-pembayaran.export` | Download bukti pembayaran PDF (termasuk **logo perusahaan** light di header) |
| **Download Word** | `riwayat-pembayaran.export` | Download bukti pembayaran sebagai file `.docx` |
| **Export Excel** | `riwayat-pembayaran.export` | Export data riwayat ke Excel |

## Teknis
### Route
| Method | URI | Name | Middleware | Permission |
|--------|-----|------|-----------|------------|
| GET | `/operator-perusahaan/riwayat-pembayaran` | — | `auth:admin-company` | `riwayat-pembayaran.list` |
| POST | `/operator-perusahaan/riwayat-pembayaran` | — | `auth:admin-company` | `riwayat-pembayaran.create` |
| PUT | `/operator-perusahaan/riwayat-pembayaran/{id}` | — | `auth:admin-company` | `riwayat-pembayaran.edit` |
| DELETE | `/operator-perusahaan/riwayat-pembayaran/{id}` | — | `auth:admin-company` | `riwayat-pembayaran.delete` |
| PATCH | `/operator-perusahaan/riwayat-pembayaran/{id}/restore` | — | `auth:admin-company` | `riwayat-pembayaran.restore` |
| POST | `/operator-perusahaan/riwayat-pembayaran/{id}/approve` | — | `auth:admin-company` | `riwayat-pembayaran.persetujuan` |
| GET | `/operator-perusahaan/riwayat-pembayaran/export` | — | `auth:admin-company` | `riwayat-pembayaran.export` |
| GET | `/operator-perusahaan/riwayat-pembayaran/{id}/pdf` | — | `auth:admin-company` | `riwayat-pembayaran.export` |

### Controller
`App\Http\Controllers\OperatorPerusahaan\PembayaranController` (route masih inline)

### Downstream — Logo Perusahaan di Bukti Pembayaran PDF & Word
Bukti pembayaran PDF (`downloadPdf`) dan Word (`downloadWord`) otomatis memuat **logo perusahaan light** di header (diambil dari kolom `companies.logo` via accessor `logo_url` di Model `Company`). Logo dark **tidak** dipakai karena kertas biasanya putih.

Sumber logo: perusahaan yang sedang login (diambil via `auth()->user()->company_id`).

**Kompresi logo:** JPG/PNG/WebP yang diupload otomatis dikompres ke WebP oleh `FileUploadService::processLogo()`. SVG disimpan apa adanya. Lihat: [Perusahaan Saya — Field Logo](perusahaan-saya.md#field-logo-di-halaman-ini).

### Known Pre-existing Issues (belum fix, bukan dari fitur logo)

| # | Issue | Dampak di halaman ini |
|---|-------|----------------------|
| 1 | PHP tidak parse `multipart/form-data` body untuk PUT/PATCH/DELETE | Form edit riwayat pembayaran dengan upload bukti (jika ada) akan gagal validasi |
| 2 | Inertia Laravel v2 tidak set `X-Inertia: true` di response redirect | Setelah create/approve/verifikasi, form tidak auto-close, toast tidak auto-fire |

Lihat detail + rekomendasi fix di [Perusahaan Saya — Known Pre-existing Issues](perusahaan-saya.md#known-pre-existing-issues-belum-fix-bukan-dari-fitur-logo).

### View
`resources/js/Pages/OperatorPerusahaan/RiwayatPembayaran.vue`

### Model
| Model | Tabel | Keterangan |
|-------|-------|------------|
| `App\Models\CustInternetPayment` | `cust_internet_payments` | Model utama — riwayat pembayaran tagihan |
| `App\Models\CustInternetInvc` | `cust_internet_invcs` | Join — invoice yang dibayar (via `cust_internet_invc_id`) |
| `App\Models\CustInternet` | `cust_internets` | Join — data langganan (via `cust_internet_invcs.cust_internet_id`) |
| `App\Models\Customer` | `customers` | Join — nama pelanggan (via `cust_internets.customer_id`) |

### Migration
| Migration | Tabel |
|-----------|-------|
| `2026_05_11_143638_create_cust_internet_payments_table` | `cust_internet_payments` |
| `2026_05_11_143143_create_cust_internet_invcs_table` | `cust_internet_invcs` |
| `2026_05_11_142443_create_cust_internets_table` | `cust_internets` |
| `2026_05_11_142201_create_customers_table` | `customers` |

### Test Case
| File | Method | Description |
|------|--------|-------------|
| `tests/Browser/deprecatedoldFeature/OperatorPerusahaan/RiwayatPembayaranPermissionTest.php` | Various | (deprecated) Browser permission test |
| `tests/Browser/deprecatedoldFeature/OperatorPerusahaan/RiwayatPembayaranViewTest.php` | Various | (deprecated) Browser view test |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/RiwayatPembayaranFullTest.cjs` | test_01 – test_12 | Playwright E2E (CRUD + filter + dark mode + responsive) |
| `tests/Browser/Playwright/Feature/OperatorPerusahaan/PdfDownloadLogoTest.cjs` | test_03 – test_04 | Bukti Pembayaran PDF download (logo perusahaan di header) |
