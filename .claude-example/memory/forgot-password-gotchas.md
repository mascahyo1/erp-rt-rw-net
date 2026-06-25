---
name: forgot-password-gotchas
description: Gotchas saat implement Lupa Password multi-tenant dengan composite key — bugs yang sudah di-debug
metadata: 
  node_type: memory
  type: project
  originSessionId: a9fcc23e-1fb0-452e-a6bc-66777865340e
---

# Lupa Password Multi-Tenant: Gotchas

Implementasi Lupa Password untuk 4 portal (operator-saas, perusahaan, karyawan, pelanggan) dengan composite key `{email}||{company_id}` di tabel `password_reset_tokens`. Bug yang muncul & fix:

## 1. Composite key mismatch di 3 tempat

Pakai pola `getEmailForPasswordReset()` return `"{$email}||{$company_id}"` (atau `"{$email}||"` untuk single-tenant). Composite key ini dipakai untuk:
- **Insert** token baru (line 125 `ForgotPasswordController.php`)
- **Delete existing tokens** saat user request ulang (line 240)
- **Lookup token** saat reset (line 168-174)

Kalau salah satu pakai raw email (bukan composite), akan ada:
- `Duplicate entry` SQL error saat insert kedua
- `Token tidak valid` saat user coba reset

**Why**: Database composite PK = `(email, company_id, guard)`. Raw email saja = composite PK konflik untuk user sama di company berbeda.

**How to apply**: Selalu pakai `$user->getEmailForPasswordReset()` di semua 3 tempat — JANGAN `$user->email`.

## 2. URL encoding '+' jadi spasi

`$request->query('email')` SUDAH auto-decoded oleh PHP (parse_str convention). **Jangan `urldecode()` lagi** — akan double-decode:
- URL punya `%2B` (encoded `+`) → PHP decodes ke `+` → `urldecode()` rubah `+` jadi ` ` (spasi)
- Untuk email `test+1781247641870@example.com`, jadi `test 1781247641870@example.com` di form
- Submit form → controller lookup token dengan composite `test 178...||{company_id}` → tidak ketemu → "Token tidak valid"

**Why**: `urldecode()` mengikuti convention `application/x-www-form-urlencoded` di mana `+` = spasi. `rawurldecode()` tidak. Tapi lebih baik jangan decode sama sekali karena PHP sudah auto-decode query string.

**How to apply**: Pass `$request->query('email')` langsung ke Vue, jangan `urldecode()` wrapper.

## 3. Validation `email` rule fail untuk composite key

`'email' => 'email'` rule akan reject composite key `"foo@bar.com||uuid"` karena `||` bukan format email valid. Ganti:
```php
'email' => ['required', 'string'],  // bukan ['required', 'email']
```

**Why**: Email SEMENTARA berisi composite key, bukan email asli. Raw email di-extract di controller dengan `strpos($rawEmail, '||')` + `substr()` setelah validation.

**How to apply**: Pakai rule `string` saja untuk field email di forgot-password reset endpoint.

## 4. Inertia form submit: body = `application/x-www-form-urlencoded`

Inertia default pakai `fetch()` dengan FormData/multipart, tapi saat pakai `form.post()` dengan data JSON-y (tanpa file), bisa jadi `x-www-form-urlencoded`. PHP parse ini treat `+` sebagai spasi. Untuk email dengan `+` di local part, ini bug.

**Why**: RFC 1866 (form-urlencoded) `+` = spasi. Berbeda dengan RFC 3986 (URI) yang `+` literal.

**How to apply**: Jika ada field dengan `+` di value, pertimbangkan `form.transform` untuk encode ulang, atau base64 encode field khusus.
