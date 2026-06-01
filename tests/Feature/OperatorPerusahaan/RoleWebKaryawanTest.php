<?php

namespace Tests\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\Permission;
use App\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleWebKaryawanTest extends TestCase
{

    protected AdminCompany $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'admin_perusahaan');
    }

    public function test_guest_cannot_access_role_web_karyawan_page()
    {
        $this->get('/operator-perusahaan/role-web-karyawan')
            ->assertRedirect('/login-perusahaan');
    }

    public function test_authenticated_can_view_role_web_karyawan_page()
    {
        $this->actingAs($this->user, 'admin-company')
            ->get('/operator-perusahaan/role-web-karyawan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/RoleWebKaryawan')
                ->has('roles')
                ->has('availablePermissions')
            );
    }

    public function test_role_listing_includes_permission_ids_for_prefill()
    {
        $role = Role::create([
            'scope' => 'karyawan_perusahaan',
            'company_id' => $this->user->company_id,
            'name' => 'Karyawan Role With Perms',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $perms = Permission::where('scope', 'karyawan_perusahaan')->limit(2)->get();
        $role->permissions()->sync($perms->pluck('id'));

        $response = $this->actingAs($this->user, 'admin-company')
            ->get('/operator-perusahaan/role-web-karyawan');
        $response->assertOk();

        $roles = $response->original->getData()['page']['props']['roles']->toArray();
        $row = collect($roles['data'])->firstWhere('id', $role->id);
        $this->assertNotNull($row);
        $this->assertArrayHasKey('permission_ids', $row);
        $this->assertArrayHasKey('permissions', $row);
    }

    public function test_can_create_role_web_karyawan_with_permissions()
    {
        $this->actingAs($this->user, 'admin-company');
        $perms = Permission::where('scope', 'karyawan_perusahaan')->limit(2)->get();
        $response = $this->post('/operator-perusahaan/role-web-karyawan', [
            'nama_role' => 'Karyawan Role Perms',
            'status' => 'Aktif',
            'permission_ids' => $perms->pluck('id')->toArray(),
        ]);
        $response->assertRedirect();
        $role = Role::where('name', 'Karyawan Role Perms')->first();
        $this->assertNotNull($role);
        $this->assertCount(2, $role->permissions);
    }

    public function test_can_update_role_web_karyawan_replaces_permissions()
    {
        $this->actingAs($this->user, 'admin-company');
        $role = Role::create([
            'scope' => 'karyawan_perusahaan',
            'company_id' => $this->user->company_id,
            'name' => 'Before Karyawan',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $perms = Permission::where('scope', 'karyawan_perusahaan')->limit(3)->get();
        $role->permissions()->sync($perms->pluck('id'));

        $newPerms = Permission::where('scope', 'karyawan_perusahaan')->limit(1)->get();
        $response = $this->put("/operator-perusahaan/role-web-karyawan/{$role->id}", [
            'nama_role' => 'After Karyawan',
            'status' => 'Aktif',
            'permission_ids' => $newPerms->pluck('id')->toArray(),
        ]);
        $response->assertRedirect();
        $this->assertCount(1, $role->fresh()->permissions);
    }

    public function test_can_delete_and_restore_role_web_karyawan()
    {
        $this->actingAs($this->user, 'admin-company');
        $role = Role::create([
            'scope' => 'karyawan_perusahaan',
            'company_id' => $this->user->company_id,
            'name' => 'Karyawan Delete Test',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $this->delete("/operator-perusahaan/role-web-karyawan/{$role->id}")
            ->assertRedirect();
        $this->assertSoftDeleted('roles', ['id' => $role->id]);

        $this->patch("/operator-perusahaan/role-web-karyawan/{$role->id}/restore")
            ->assertRedirect();
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'deleted_at' => null]);
    }
}
