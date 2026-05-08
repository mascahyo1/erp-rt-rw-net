---
description: Generate or modify CRUD operations with blameable tracking, Excel import/export, checklist, and bulk actions. Use when the user creates a new CRUD, adds a resource, or modifies data management.
---

## Komponen CRUD

### 1. Blameable Trait

Setiap model HARUS punya tracking siapa yang create/update/delete/restore.

```php
// app/Traits/Blameable.php
trait Blameable
{
    public static function bootBlameable(): void
    {
        static::creating(function ($model) {
            $model->created_by_type = auth()->check() ? get_class(auth()->user()) : null;
            $model->created_by_id = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by_type = auth()->check() ? get_class(auth()->user()) : null;
            $model->updated_by_id = auth()->id();
        });

        static::deleting(function ($model) {
            if ($model->usesSoftDelete()) {
                $model->deleted_by_type = auth()->check() ? get_class(auth()->user()) : null;
                $model->deleted_by_id = auth()->id();
                $model->saveQuietly();
                return false; // Prevent hard delete, use soft delete
            }
        });

        static::restoring(function ($model) {
            $model->restored_by_type = auth()->check() ? get_class(auth()->user()) : null;
            $model->restored_by_id = auth()->id();
        });
    }

    // Relationships
    public function createdBy() { return $this->morphTo('created_by'); }
    public function updatedBy() { return $this->morphTo('updated_by'); }
    public function deletedBy() { return $this->morphTo('deleted_by'); }
    public function restoredBy() { return $this->morphTo('restored_by'); }

    protected function usesSoftDelete(): bool
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($this));
    }
}
```

**Kolom migration:**
```php
$table->nullableMorphs('created_by');
$table->nullableMorphs('updated_by');
$table->nullableMorphs('deleted_by');
$table->nullableMorphs('restored_by');
```

### 2. Import & Export Excel

Setiap CRUD harus support import dan export Excel.

**Export:** Ikuti rules di skill `excel-export`.
**Import:** Gunakan PhpSpreadsheet + validasi per baris.

```php
// Template import
// 1. Upload file
// 2. Baca sheet pertama
// 3. Validasi header kolom
// 4. Validasi tiap baris data
// 5. Insert batch (chunk per 500 baris)
// 6. Return success + error rows jika ada yang gagal
```

### 3. Checklist & Bulk Action (Vue + Inertia)

```vue
<script setup>
import { ref, computed } from 'vue';

const props = defineProps({ items: Array });

const selected = ref([]);
const allSelected = computed({
    get: () => selected.value.length === props.items.length,
    set: (val) => selected.value = val ? props.items.map(i => i.id) : [],
});

const toggleRow = (id) => {
    const idx = selected.value.indexOf(id);
    idx === -1 ? selected.value.push(id) : selected.value.splice(idx, 1);
};

const bulkDelete = () => {
    if (!confirm(`Hapus ${selected.value.length} item?`)) return;
    Inertia.post('/users/bulk-delete', { ids: selected.value });
};

const bulkExport = () => {
    window.location.href = `/users/export?ids=${selected.value.join(',')}`;
};
</script>

<template>
    <!-- Select All + Bulk Action Bar -->
    <div v-show="selected.length > 0" class="bulk-action-bar">
        <span>{{ selected.length }} item dipilih</span>
        <button @click="bulkDelete" class="btn-danger">
            <i class="fas fa-trash" /> Hapus
        </button>
        <button @click="bulkExport" class="btn-success">
            <i class="fas fa-download" /> Export
        </button>
        <button @click="bulkUpdateStatus" class="btn-primary">
            <i class="fas fa-check" /> Ubah Status
        </button>
    </div>

    <table>
        <thead>
            <tr>
                <th><input type="checkbox" v-model="allSelected"></th>
                <th>Name</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="item in items" :key="item.id">
                <td><input type="checkbox" :value="item.id" v-model="selected"></td>
                <td>{{ item.name }}</td>
                <td>
                    <Link :href="`/users/${item.id}/edit`" class="btn-edit">Edit</Link>
                    <button @click="bulkDelete">Hapus</button>
                </td>
            </tr>
        </tbody>
    </table>
</template>
```

- Checklist di tiap baris + "Select All" di header
- Bulk action bar muncul hanya saat `selected.length > 0`
- Minimum action: Delete, Export, Ubah Status
- Konfirmasi sebelum eksekusi bulk delete (pakai `confirm()` atau modal)

### 4. Standard Route (Laravel resource)
```php
Route::resource('users', UserController::class);
Route::post('users/bulk-delete', [UserController::class, 'bulkDelete']);
Route::post('users/bulk-update', [UserController::class, 'bulkUpdate']);
Route::get('users/export', [UserController::class, 'export']);
Route::post('users/import', [UserController::class, 'import']);
```

### 5. Form & Validasi
- Gunakan Laravel Form Request untuk validasi
- Tampilkan error per field di bawah input (Inertia `$page.props.errors.field`)
- Konfirmasi sebelum delete (modal Vue atau `confirm()`)
- Redirect kembali ke halaman list — Inertia otomatis menangani flash message via `$page.props.flash.message`
