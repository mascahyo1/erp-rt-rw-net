<?php

namespace Tests\Feature\OperatorSaas;

use App\Models\AdminSaas;
use App\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleSaasTest extends TestCase
{

    protected AdminSaas $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminSaas::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'operator_saas');
    }

    public function test_guest_cannot_access_role_saas_page()
    {
        $this->get('/operator-saas/role-saas')
            ->assertRedirect('/login-operator-saas');
    }

    public function test_authenticated_can_view_role_saas_page()
    {
        $this->actingAs($this->user, 'web')
            ->get('/operator-saas/role-saas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/RoleSaaS')
                ->has('roles')
                ->has('availablePermissions')
            );
    }

    public function test_role_listing_includes_permission_ids_and_permissions()
    {
        $role = Role::create(['scope' => 'operator_saas', 'name' => 'Role With Perms', 'is_active' => true, 'display_order' => 1]);
        $perms = \App\Models\Permission::where('scope', 'operator_saas')->limit(3)->get();
        $role->permissions()->sync($perms->pluck('id'));

        $response = $this->actingAs($this->user, 'web')->get('/operator-saas/role-saas');
        $response->assertOk();
        $roles = $response->original->getData()['page']['props']['roles']->toArray();
        $row = collect($roles['data'])->firstWhere('id', $role->id);
        $this->assertNotNull($row);
        $this->assertArrayHasKey('permission_ids', $row);
        $this->assertArrayHasKey('permissions', $row);
        $this->assertCount(3, $row['permission_ids']);
        $this->assertCount(3, $row['permissions']);
    }

    public function test_can_create_role_saas_with_permissions()
    {
        $this->actingAs($this->user, 'web');
        $perms = \App\Models\Permission::where('scope', 'operator_saas')->limit(2)->get();
        $response = $this->post('/operator-saas/role-saas', [
            'nama_role' => 'Role With Perms Create',
            'status' => 'Aktif',
            'permission_ids' => $perms->pluck('id')->toArray(),
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['name' => 'Role With Perms Create']);
        $role = Role::where('name', 'Role With Perms Create')->first();
        $this->assertCount(2, $role->permissions()->pluck('permissions.id'));
    }

    public function test_can_update_role_saas_replaces_permissions()
    {
        $this->actingAs($this->user, 'web');
        $role = Role::create(['scope' => 'operator_saas', 'name' => 'Before', 'is_active' => true, 'display_order' => 1]);
        $perms = \App\Models\Permission::where('scope', 'operator_saas')->limit(3)->get();
        $role->permissions()->sync($perms->pluck('id'));

        $newPerms = \App\Models\Permission::where('scope', 'operator_saas')->limit(1)->get();
        $response = $this->put("/operator-saas/role-saas/{$role->id}", [
            'nama_role' => 'After',
            'status' => 'Aktif',
            'permission_ids' => $newPerms->pluck('id')->toArray(),
        ]);
        $response->assertRedirect();
        $this->assertCount(1, $role->fresh()->permissions);
    }

    public function test_can_create_role_saas()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->post('/operator-saas/role-saas', [
            'nama_role' => 'SaaS Role Test',
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role SaaS berhasil ditambahkan.');
        $this->assertDatabaseHas('roles', [
            'scope' => 'operator_saas',
            'name' => 'SaaS Role Test',
            'is_active' => true,
        ]);
    }

    public function test_create_role_saas_validation_fails_with_empty_fields()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->post('/operator-saas/role-saas', [
            'nama_role' => '',
            'status' => 'Aktif',
        ]);

        $response->assertSessionHasErrors(['nama_role']);
    }

    public function test_can_update_role_saas()
    {
        $this->actingAs($this->user, 'web');
        $role = Role::create([
            'scope' => 'operator_saas',
            'name' => 'Old SaaS Role',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $response = $this->put("/operator-saas/role-saas/{$role->id}", [
            'nama_role' => 'Updated SaaS Role',
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role SaaS berhasil diperbarui.');
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Updated SaaS Role']);
    }

    public function test_can_delete_role_saas()
    {
        $this->actingAs($this->user, 'web');
        $role = Role::create([
            'scope' => 'operator_saas',
            'name' => 'Delete SaaS Role',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $response = $this->delete("/operator-saas/role-saas/{$role->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role SaaS berhasil dihapus.');
        $this->assertSoftDeleted('roles', ['id' => $role->id]);
    }

    public function test_can_restore_deleted_role_saas()
    {
        $this->actingAs($this->user, 'web');
        $role = Role::create([
            'scope' => 'operator_saas',
            'name' => 'Restore SaaS Role',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $role->delete();

        $response = $this->post("/operator-saas/role-saas/{$role->id}/restore");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role SaaS berhasil dipulihkan.');
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'deleted_at' => null]);
    }

    public function test_can_bulk_delete_role_saas()
    {
        $this->actingAs($this->user, 'web');
        $roles = collect(range(1, 3))->map(fn ($i) => Role::create([
            'scope' => 'operator_saas',
            'name' => "Bulk SaaS Role {$i}",
            'is_active' => true,
            'display_order' => $i,
        ]));

        $response = $this->post('/operator-saas/role-saas/bulk-delete', [
            'ids' => $roles->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '3 role SaaS berhasil dihapus.');
        foreach ($roles as $r) {
            $this->assertSoftDeleted('roles', ['id' => $r->id]);
        }
    }

    public function test_can_bulk_toggle_status_role_saas()
    {
        $this->actingAs($this->user, 'web');
        $roles = collect(range(1, 2))->map(fn ($i) => Role::create([
            'scope' => 'operator_saas',
            'name' => "Bulk Status SaaS {$i}",
            'is_active' => false,
            'display_order' => $i,
        ]));

        $response = $this->post('/operator-saas/role-saas/bulk-status', [
            'ids' => $roles->pluck('id')->toArray(),
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role SaaS berhasil diaktifkan.');
        foreach ($roles as $r) {
            $this->assertDatabaseHas('roles', ['id' => $r->id, 'is_active' => true]);
        }
    }

    public function test_terhapus_filter_shows_only_trashed_records()
    {
        $this->actingAs($this->user, 'web');
        Role::create(['scope' => 'operator_saas', 'name' => 'Active A', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'operator_saas', 'name' => 'Active B', 'is_active' => true, 'display_order' => 2]);
        $trashed = Role::create(['scope' => 'operator_saas', 'name' => 'Trashed SaaS', 'is_active' => true, 'display_order' => 3]);
        $trashed->delete();

        $response = $this->get('/operator-saas/role-saas?terhapus=ya');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/RoleSaaS')
                ->has('roles.data', 1)
            );
    }

    public function test_status_filter_filters_correctly()
    {
        $this->actingAs($this->user, 'web');
        Role::create(['scope' => 'operator_saas', 'name' => 'Active A', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'operator_saas', 'name' => 'Active B', 'is_active' => true, 'display_order' => 2]);
        Role::create(['scope' => 'operator_saas', 'name' => 'Inactive', 'is_active' => false, 'display_order' => 3]);

        $response = $this->get('/operator-saas/role-saas?status=Aktif');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/RoleSaaS')
                ->has('roles.data', 3)
            );
    }

    public function test_search_filter_filters_by_name()
    {
        $this->actingAs($this->user, 'web');
        Role::create(['scope' => 'operator_saas', 'name' => 'Alpha SaaS', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'operator_saas', 'name' => 'Beta SaaS', 'is_active' => true, 'display_order' => 2]);
        Role::create(['scope' => 'operator_saas', 'name' => 'Gamma SaaS', 'is_active' => true, 'display_order' => 3]);

        $response = $this->get('/operator-saas/role-saas?search=gamma');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/RoleSaaS')
                ->has('roles.data', 1)
            );
    }
}
