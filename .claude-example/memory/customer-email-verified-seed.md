---
name: customer-email-verified-seed
description: Customer table has email_verified_at (since 2026-06-15). Seed MUST set ini agar customer bisa login.
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# Customer Login: Seed HARUS set `email_verified_at`

## Context
2026-06-30 deploy, customer login error "Email belum diverifikasi" walaupun user udah ada di DB.
Penyebab: migration `2026_06_15_120001_add_email_verified_at_to_customers_table.php` nambah kolom nullable `email_verified_at` di `customers`. Tapi `DemoSeeder.php` untuk 13 customer (sugeng, rini, herman, dll) BELUM set field ini. CustomerSessionController:59-65 logout + throw ValidationException kalau `! $user->hasVerifiedEmail()`.

## Why
- Migration nullable → existing customer rows punya `NULL`
- Seed menjalankan `INSERT` array, kalau gak ada key `email_verified_at` → NULL
- Login controller strict check email_verified_at — NULL = gagal
- Symptom: misleading "Email belum diverifikasi" padahal email valid, customer existe

## How to apply

### Seed pattern (DemoSeeder & ProductionSeeder):
```php
Customer::query()->insert([
    [
        'id' => Str::uuid(),
        'company_id' => $company1Id,
        'code' => 'CUST-001',
        'name' => 'Pak Sugeng',
        'email' => 'sugeng@gmail.com',
        // ... other fields ...
        'is_active' => true,
        'password' => bcrypt('password123'),
        'address' => '...',
        'created_at' => now(),
        'updated_at' => now(),
        'email_verified_at' => now(),  // ← WAJIB! Tambah ini di setiap row
    ],
    // ... more rows
]);
```

### DB manual fix (existing rows):
```bash
php artisan tinker --execute='App\Models\Customer::whereNull("email_verified_at")->update(["email_verified_at" => now()]);'
```

### Future-proof: tambah ke migration baru
Kalau ada migration yg nambah kolom `email_verified_at` ke model apapun, **WAJIB** check + update:
- Migration code: `UPDATE users SET email_verified_at = NOW() WHERE email_verified_at IS NULL`
- Seeders: tambah field di INSERT arrays
- Existing tests: clear error message beda antara "wrong password" vs "unverified"

### Cek cepat:
```bash
# Hitung user unverified
php artisan tinker --execute='echo App\Models\Customer::whereNull("email_verified_at")->count();'
# > 0 = bug, harus di-fix
```

## Related
- [php-upload-limits-deploy](php-upload-limits-deploy.md) — PHP upload sizes
- [deploy-server-state-2026-06-30](deploy-server-state-2026-06-30.md) — server state