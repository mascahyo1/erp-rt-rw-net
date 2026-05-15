<?php

namespace Database\Seeders;

use App\Enums\Permissions;
use App\Models\ModelHasRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // 1. SEED ALL PERMISSIONS
        // ============================================================
        $this->seedPermissions('operator_saas');
        $this->seedPermissions('admin_perusahaan');
        $this->seedPermissions('karyawan_perusahaan');

        // ============================================================
        // 2. CREATE DEFAULT ROLES + ASSIGN ALL PERMISSIONS
        // ============================================================

        // --- Operator SaaS: "Super Admin" ---
        $saasRole = Role::firstOrCreate(
            ['scope' => 'operator_saas', 'name' => 'Super Admin'],
            [
                'id' => Str::uuid(),
                'display_order' => 1,
                'is_active' => true,
                'description' => 'Role default dengan semua akses penuh di Operator SaaS.',
            ]
        );
        $saasPermIds = Permission::where('scope', 'operator_saas')->pluck('id');
        $this->syncPermissions($saasRole, $saasPermIds);

        // Assign to existing AdminSaas users (Super Admin Demo + Admin Operator Demo)
        \App\Models\AdminSaas::all()->each(function ($admin) use ($saasRole) {
            \DB::table('model_has_roles')->updateOrInsert([
                'role_id' => $saasRole->id,
                'model_type' => 'App\Models\AdminSaas',
                'model_id' => $admin->id,
            ], [
                'id' => Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // --- Operator Perusahaan: "Admin" ---
        $perusahaanRole = Role::firstOrCreate(
            ['scope' => 'admin_perusahaan', 'name' => 'Admin'],
            [
                'id' => Str::uuid(),
                'display_order' => 1,
                'is_active' => true,
                'description' => 'Role default dengan semua akses penuh di Operator Perusahaan.',
            ]
        );
        $perusahaanPermIds = Permission::where('scope', 'admin_perusahaan')->pluck('id');
        $this->syncPermissions($perusahaanRole, $perusahaanPermIds);

        // Assign to existing AdminCompany users
        \App\Models\AdminCompany::all()->each(function ($admin) use ($perusahaanRole) {
            \DB::table('model_has_roles')->updateOrInsert([
                'role_id' => $perusahaanRole->id,
                'model_type' => 'App\Models\AdminCompany',
                'model_id' => $admin->id,
            ], [
                'id' => Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // --- Karyawan: "Default" ---
        $karyawanRole = Role::firstOrCreate(
            ['scope' => 'karyawan_perusahaan', 'name' => 'Default'],
            [
                'id' => Str::uuid(),
                'display_order' => 1,
                'is_active' => true,
                'description' => 'Role default dengan akses standard di Web Karyawan.',
            ]
        );
        $karyawanPermIds = Permission::where('scope', 'karyawan_perusahaan')->pluck('id');
        $this->syncPermissions($karyawanRole, $karyawanPermIds);

        // Assign to existing Employee users
        \App\Models\Employee::all()->each(function ($emp) use ($karyawanRole) {
            \DB::table('model_has_roles')->updateOrInsert([
                'role_id' => $karyawanRole->id,
                'model_type' => 'App\Models\Employee',
                'model_id' => $emp->id,
            ], [
                'id' => Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    private function seedPermissions(string $scope): void
    {
        $perms = Permissions::forScope($scope);
        $order = 1;

        foreach ($perms as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'scope' => $scope],
                [
                    'id' => Str::uuid(),
                    'display_order' => $order++,
                    'description' => $this->permissionDescription($name),
                ]
            );
        }
    }

    private function permissionDescription(string $name): string
    {
        report("seeding: $name");
        $parts = explode('.', $name);
        $module = $parts[0];
        $action = $parts[1] ?? '';

        $actionLabels = [
            'list' => 'Lihat daftar',
            'create' => 'Tambah',
            'edit' => 'Ubah & toggle status',
            'detail' => 'Lihat detail',
            'delete' => 'Hapus & bulk delete',
            'restore' => 'Pulihkan & bulk restore',
        ];

        $actionLabel = $actionLabels[$action] ?? $action;

        return "$actionLabel $module";
    }

    private function syncPermissions(Role $role, $permissionIds): void
    {
        \DB::table('role_permissions')->where('role_id', $role->id)->delete();

        $inserts = [];
        foreach ($permissionIds as $pId) {
            $inserts[] = [
                'id' => Str::uuid(),
                'role_id' => $role->id,
                'permission_id' => $pId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($inserts)) {
            \DB::table('role_permissions')->insert($inserts);
        }
    }
}
