@component('mail::message')
# Reset Password - {{ $appName ?? config('app.name') }}

Halo **{{ $user->name }}**,

Kami menerima permintaan reset password untuk akun Anda. Klik tombol di bawah ini untuk membuat password baru:

@component('mail::button', ['url' => $url, 'color' => 'success'])
Reset Password Anda
@endcomponent

Link ini akan kadaluarsa dalam **{{ $expireMinutes ?? 60 }} menit**.

Jika Anda tidak meminta reset password, abaikan email ini. Password Anda saat ini tetap aman.

---

**Butuh bantuan?**
Hubungi admin perusahaan Anda atau balas email ini.

@component('mail::subcopy')
Jika tombol di atas tidak berfungsi, copy dan paste URL berikut ke browser Anda:
{{ $url }}
@endcomponent

@endcomponent
