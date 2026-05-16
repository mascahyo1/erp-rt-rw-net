<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
        ]);
        $this->withHeader('X-CSRF-TOKEN', 'test');
        $this->withHeader('X-Requested-With', 'XMLHttpRequest');
    }

    protected function assignDefaultRole($user, string $scope): void
    {
        $role = \App\Models\Role::where('scope', $scope)
            ->where('is_active', true)
            ->first();

        if (! $role) {
            $role = \App\Models\Role::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'scope' => $scope,
                'name' => 'Test Role',
                'display_order' => 1,
                'is_active' => true,
            ]);

            $perms = \App\Models\Permission::where('scope', $scope)->get();
            if ($perms->isEmpty()) {
                $this->seedPermissionsForScope($scope);
                $perms = \App\Models\Permission::where('scope', $scope)->get();
            }

            $inserts = [];
            foreach ($perms as $p) {
                $inserts[] = [
                    'id' => \Illuminate\Support\Str::uuid(),
                    'role_id' => $role->id,
                    'permission_id' => $p->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            if (! empty($inserts)) {
                \DB::table('role_permissions')->insert($inserts);
            }
        }

        \DB::table('model_has_roles')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'role_id' => $role->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedPermissionsForScope(string $scope): void
    {
        $perms = \App\Enums\Permissions::forScope($scope);
        $order = 1;
        foreach ($perms as $name) {
            \App\Models\Permission::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'name' => $name,
                'scope' => $scope,
                'display_order' => $order++,
            ]);
        }
    }
}
