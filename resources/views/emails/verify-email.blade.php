@component('mail::message')
# Verifikasi Email Anda - {{ $appName ?? config('app.name') }}

Halo **{{ $user->name }}**,

Terima kasih telah mendaftar di **{{ $appName ?? config('app.name') }}**! Sebelum akun Anda aktif, kami perlu memverifikasi bahwa email ini memang milik Anda.

Klik tombol di bawah ini untuk memverifikasi email dan mengaktifkan akun Anda:

@component('mail::button', ['url' => $url, 'color' => 'success'])
Verifikasi Email Anda
@endcomponent

Link verifikasi ini akan kadaluarsa dalam **{{ $expireMinutes ?? 60 }} menit**.

---

**Belum menerima email serupa sebelumnya?**
Kemungkinan email ini perlu waktu 1-2 menit untuk sampai. Cek juga folder **spam** atau **promotions** di inbox Anda.

**Tombol tidak berfungsi?**
Copy dan paste URL berikut ke browser Anda:
{{ $url }}

---

**Tidak merasa mendaftar?**
Abaikan email ini. Akun tidak akan dibuat sampai Anda mengklik tombol verifikasi di atas.

**Butuh bantuan?**
Hubungi admin perusahaan tempat Anda mendaftar, atau balas email ini.

@endcomponent
