---
description: Multi-perusahaan, dynamic RBAC, and granular permissions. Use when the user adds a new feature, new page, new action, or asks about permissions/roles/akses.
---

## RBAC & Multi-Perusahaan

### 1. Struktur Permission (Granular)

```
module.action
```

Contoh:
```
users.view
users.create
users.edit
users.delete
users.export
users.import
users.bulk-delete
```

Format: `{module}.{action}` — selalu lowercase, satu kata per segmen.

### 2. Role & Permission (Dynamic)

```php
// Role: Admin, Manager, Staff, Viewer — bisa dibuat dinamis
// Permission di-assign ke role, role di-assign ke user

// Cek permission di Controller
if (auth()->user()->can('users.delete')) { ... }

// Middleware
Route::middleware('can:users.export')->get(...);
```

### 2b. Share Permission ke Inertia (HandleInertiaRequests)

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'auth' => [
            'user' => $request->user(),
            'permissions' => $request->user()?->getAllPermissions()->pluck('name'),
        ],
        'flash' => [
            'message' => $request->session()->get('message'),
            'error' => $request->session()->get('error'),
        ],
    ]);
}
```

### 2c. Cek Permission di Vue (Inertia)

```vue
<!-- Di halaman mana pun -->
<button v-if="$page.props.auth.permissions.includes('users.edit')">
    Edit
</button>

<!-- Atau dengan computed untuk konsistensi -->
<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const can = (perm) => usePage().props.auth.permissions.includes(perm);
</script>

<template>
    <button v-if="can('users.edit')">Edit</button>
    <button v-if="can('users.delete')">Hapus</button>
</template>
```

### 3. Multi-Perusahaan (Scoping Data)

```php
// Setiap model yang scoped ke perusahaan
trait BelongsToPerusahaan
{
    public static function bootBelongsToPerusahaan(): void
    {
        static::addGlobalScope('perusahaan', function ($query) {
            if (auth()->check() && auth()->user()->perusahaan_id) {
                $query->where('perusahaan_id', auth()->user()->perusahaan_id);
            }
        });

        static::creating(function ($model) {
            if (auth()->check() && auth()->user()->perusahaan_id) {
                $model->perusahaan_id = auth()->user()->perusahaan_id;
            }
        });
    }
}
```

### 4. Aturan Wajib

**SETIAP FITUR BARU HARUS:**
1. Daftarkan permission-nya di seeder/database seeder
2. Assign permission ke role yang relevan
3. Share permission via `HandleInertiaRequests` (selalu)
4. Gunakan `v-if="can('module.action')"` di Vue untuk kontrol akses UI
5. Gunakan middleware `can:` di route untuk kontrol akses server-side
6. Jika pakai policy, daftarkan di `AuthServiceProvider`

### 5. Seeder Permission

```php
// Buat seeder untuk register permission baru
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view',
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.export', 'users.import', 'users.bulk-delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'reports.view', 'reports.export',
            // Tambahkan permission baru di sini setiap ada fitur baru
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
```

### 6. Checklist Fitur Baru

Saat membuat fitur baru, pastikan:
- [ ] Permission didaftarkan di `PermissionSeeder`
- [ ] Permission di-assign ke role di `RoleSeeder`
- [ ] Permission dishare ke Inertia di `HandleInertiaRequests`
- [ ] Vue component pakai `v-if="can('module.action')"` untuk tombol/menu
- [ ] Route menggunakan `->middleware('can:...')`
- [ ] Controller mengecek authorization (`$this->authorize()`)
- [ ] Data discope ke perusahaan jika multi-perusahaan
