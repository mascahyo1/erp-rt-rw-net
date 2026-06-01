<?php

namespace Tests\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\Permission;
use App\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RolePerusahaanTest extends TestCase
{

    protected AdminCompany $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'admin_perusahaan');
    }

    public function test_guest_cannot_access_role_perusahaan_page()
    {
        $this->get('/operator-perusahaan/role-perusahaan')
            ->assertRedirect('/login-perusahaan');
    }

    public function test_authenticated_can_view_role_perusahaan_page()
    {
        $this->actingAs($this->user, 'admin-company')
            ->get('/operator-perusahaan/role-perusahaan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/RolePerusahaan')
                ->has('roles')
                ->has('availablePermissions')
            );
    }

    public function test_role_listing_includes_permission_ids_for_prefill()
    {
        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $this->user->company_id,
            'name' => 'Role Dengan Permissions',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $perms = Permission::where('scope', 'admin_perusahaan')->limit(2)->get();
        $role->permissions()->sync($perms->pluck('id'));

        $response = $this->actingAs($this->user, 'admin-company')
            ->get('/operator-perusahaan/role-perusahaan');
        $response->assertOk();

        $roles = $response->original->getData()['page']['props']['roles']->toArray();
        $row = collect($roles['data'])->firstWhere('id', $role->id);
        $this->assertNotNull($row);
        $this->assertArrayHasKey('permission_ids', $row);
        $this->assertArrayHasKey('permissions', $row);
        $this->assertCount(2, $row['permission_ids']);
    }

    public function test_can_create_role_perusahaan()
    {
        $this->actingAs($this->user, 'admin-company');

        $response = $this->post('/operator-perusahaan/role-perusahaan', [
            'nama_role' => 'Role Test Create',
            'deskripsi' => 'Test deskripsi',
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role berhasil ditambahkan.');
        $this->assertDatabaseHas('roles', [
            'scope' => 'admin_perusahaan',
            'company_id' => $this->user->company_id,
            'name' => 'Role Test Create',
            'is_active' => true,
        ]);
    }

    public function test_can_create_role_perusahaan_with_permissions()
    {
        $this->actingAs($this->user, 'admin-company');
        $perms = Permission::where('scope', 'admin_perusahaan')->limit(2)->get();
        $response = $this->post('/operator-perusahaan/role-perusahaan', [
            'nama_role' => 'Role With Perms',
            'status' => 'Aktif',
            'permission_ids' => $perms->pluck('id')->toArray(),
        ]);
        $response->assertRedirect();
        $role = Role::where('name', 'Role With Perms')->first();
        $this->assertNotNull($role);
        $this->assertCount(2, $role->permissions);
    }

    public function test_create_role_validation_fails_with_empty_name()
    {
        $this->actingAs($this->user, 'admin-company');
        $response = $this->post('/operator-perusahaan/role-perusahaan', [
            'nama_role' => '',
            'status' => 'Aktif',
        ]);
        $response->assertSessionHasErrors(['nama_role']);
    }

    public function test_can_update_role_perusahaan_preserves_prefilled_permissions()
    {
        $this->actingAs($this->user, 'admin-company');
        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $this->user->company_id,
            'name' => 'Old Role',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $perms = Permission::where('scope', 'admin_perusahaan')->limit(3)->get();
        $role->permissions()->sync($perms->pluck('id'));

        $newPerms = Permission::where('scope', 'admin_perusahaan')->limit(1)->get();
        $response = $this->put("/operator-perusahaan/role-perusahaan/{$role->id}", [
            'nama_role' => 'New Role',
            'status' => 'Aktif',
            'permission_ids' => $newPerms->pluck('id')->toArray(),
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'New Role']);
        $this->assertCount(1, $role->fresh()->permissions);
    }

    public function test_can_delete_role_perusahaan()
    {
        $this->actingAs($this->user, 'admin-company');
        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $this->user->company_id,
            'name' => 'Delete Role Test',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $response = $this->delete("/operator-perusahaan/role-perusahaan/{$role->id}");
        $response->assertRedirect();
        $this->assertSoftDeleted('roles', ['id' => $role->id]);
    }

    public function test_can_restore_role_perusahaan()
    {
        $this->actingAs($this->user, 'admin-company');
        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $this->user->company_id,
            'name' => 'Restore Test',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $role->delete();
        $response = $this->patch("/operator-perusahaan/role-perusahaan/{$role->id}/restore");
        $response->assertRedirect();
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'deleted_at' => null]);
    }
}
