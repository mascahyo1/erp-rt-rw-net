# CLAUDE.md

## Techstack

- **Backend:** Laravel
- **Frontend:** VueJS + InertiaJS
- **UI:** Flowbite + FontAwesome
- **Spreadsheet:** PhpSpreadsheet
- **Realtime:** Reverb

## Testing: Playwright (NodeJS)
- **Tool:** Playwright dengan NodeJS
- **Alasan:** Lebih stabil, support screenshot & video recording, setup lebih simpel
- **Dokumentasi:** [tests/README.md](tests/README.md)
- Laravel Dusk dan Unit/Feature Test **tidak dipakai** (deprecated — tidak stabil, video recording ribet)

## Pendekatan Debugging

Kalau bingung dengan bug, jangan ragu — log setiap langkah dari awal sampai akhir. Gunakan `\Log::info()` atau `logger()` di setiap titik kritis sehingga alur terlihat jelas. atau console log kalau di front end dan minta user untuk ngecek lognya

## Frontend

- Seresponsif mungkin (mobile-first)
- Light & dark mode — ikuti tema OS (`prefers-color-scheme`)
- Tampilan modern dan bersih
- Animasi untuk feedback interaksi (hover, klik)
- Transisi antar halaman (Inertia page transitions)

## Arsitektur Hybrid: Inertia + AJAX

**WAJIB baca** [dokumentasi/CONVENTIONS.md](dokumentasi/CONVENTIONS.md) sebelum menulis form baru atau mereview kode form.

Singkatnya:
- **Navigasi** (sidebar, breadcrumb, redirect) → pakai **Inertia**
- **CRUD form** (create/edit/delete di modal atau inline) → pakai **Pure AJAX** + JSON response
- **Dilarang**: `form.put()` + `forceFormData: true` untuk form dengan file upload (PHP bug, Inertia quirk)
- **Dilarang**: `form._method = 'PUT'` (tidak otomatis masuk body)

## Instruksi Standar (selalu aktif)

### 1. Bug & Logic
- Hindari null reference — selalu cek null sebelum akses property
- Tangani edge case (input kosong, nilai negatif, boundary)
- Validasi semua input dari user atau API eksternal

### 2. Keamanan
- Jangan hardcode kredensial, API key, atau secret
- Gunakan parameterized query, bukan string concatenation untuk SQL
- Escape output untuk cegah XSS
- Validasi dan sanitasi input user

### 3. Kualitas Kode
- Nama variabel/fungsi harus deskriptif dan jelas
- Satu fungsi = satu tanggung jawab
- Hindari magic number — gunakan konstanta
- Hapus kode yang tidak dipakai, jangan dikomen

### 4. Performa
- Hindari N+1 query di loop
- Gunakan eager loading untuk relasi yang dibutuhkan
- Operasi I/O yang berat harus async jika memungkinkan

### Gaya Jawaban
- Jawab dengan bahasa Indonesia
- Singkat dan langsung ke poin
- Sertakan path file dan nomor baris saat merujuk kode
