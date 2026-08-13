<?php

namespace Tests\Feature\OperatorSaas;

use App\Models\AdminSaas;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RolePerusahaanTest extends TestCase
{

    protected AdminSaas $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminSaas::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'operator_saas');
    }

    public function test_guest_cannot_access_role_perusahaan_page()
    {
        $this->get('/operator-saas/role-perusahaan')
            ->assertRedirect('/login-operator-saas');
    }

    public function test_authenticated_can_view_role_perusahaan_page()
    {
        $this->actingAs($this->user, 'admin-saas')
            ->get('/operator-saas/role-perusahaan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/RolePerusahaan')
                ->has('roles')
            );
    }

    public function test_can_create_role_perusahaan()
    {
        $this->actingAs($this->user, 'admin-saas');
        $company = Company::factory()->create();

        $response = $this->post('/operator-saas/role-perusahaan', [
            'nama_role' => 'Role Test',
            'company_id' => $company->id,
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role perusahaan berhasil ditambahkan.');
        $this->assertDatabaseHas('roles', [
            'scope' => 'admin_perusahaan',
            'name' => 'Role Test',
            'company_id' => $company->id,
        ]);
    }

    public function test_create_role_perusahaan_validation_fails_with_empty_fields()
    {
        $this->actingAs($this->user, 'admin-saas');

        $response = $this->post('/operator-saas/role-perusahaan', [
            'nama_role' => '',
            'status' => 'Aktif',
        ]);

        $response->assertSessionHasErrors(['nama_role', 'company_id']);
    }

    public function test_can_update_role_perusahaan()
    {
        $this->actingAs($this->user, 'admin-saas');
        $company = Company::factory()->create();
        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $company->id,
            'name' => 'Old Role',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $response = $this->put("/operator-saas/role-perusahaan/{$role->id}", [
            'nama_role' => 'Updated Role',
            'company_id' => $company->id,
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role perusahaan berhasil diperbarui.');
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Updated Role']);
    }

    public function test_can_delete_role_perusahaan()
    {
        $this->actingAs($this->user, 'admin-saas');
        $company = Company::factory()->create();
        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $company->id,
            'name' => 'Delete Role',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $response = $this->delete("/operator-saas/role-perusahaan/{$role->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role perusahaan berhasil dihapus.');
        $this->assertSoftDeleted('roles', ['id' => $role->id]);
    }

    public function test_can_restore_deleted_role_perusahaan()
    {
        $this->actingAs($this->user, 'admin-saas');
        $company = Company::factory()->create();
        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $company->id,
            'name' => 'Restore Role',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $role->delete();

        $response = $this->post("/operator-saas/role-perusahaan/{$role->id}/restore");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role perusahaan berhasil dipulihkan.');
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'deleted_at' => null]);
    }

    public function test_can_bulk_delete_role_perusahaan()
    {
        $this->actingAs($this->user, 'admin-saas');
        $company = Company::factory()->create();
        $roles = collect(range(1, 3))->map(fn ($i) => Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $company->id,
            'name' => "Bulk Delete Role {$i}",
            'is_active' => true,
            'display_order' => $i,
        ]));

        $response = $this->post('/operator-saas/role-perusahaan/bulk-delete', [
            'ids' => $roles->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '3 role perusahaan berhasil dihapus.');
        foreach ($roles as $r) {
            $this->assertSoftDeleted('roles', ['id' => $r->id]);
        }
    }

    public function test_can_bulk_toggle_status_role_perusahaan()
    {
        $this->actingAs($this->user, 'admin-saas');
        $company = Company::factory()->create();
        $roles = collect(range(1, 2))->map(fn ($i) => Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $company->id,
            'name' => "Bulk Status Role {$i}",
            'is_active' => false,
            'display_order' => $i,
        ]));

        $response = $this->post('/operator-saas/role-perusahaan/bulk-status', [
            'ids' => $roles->pluck('id')->toArray(),
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '2 role perusahaan berhasil diaktifkan.');
        foreach ($roles as $r) {
            $this->assertDatabaseHas('roles', ['id' => $r->id, 'is_active' => true]);
        }
    }

    public function test_terhapus_filter_shows_only_trashed_records()
    {
        $this->actingAs($this->user, 'admin-saas');
        $company = Company::factory()->create();
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Active A', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Active B', 'is_active' => true, 'display_order' => 2]);
        $trashed = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Trashed', 'is_active' => true, 'display_order' => 3]);
        $trashed->delete();

        $response = $this->get('/operator-saas/role-perusahaan?terhapus=ya');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/RolePerusahaan')
                ->has('roles.data', 1)
            );
    }

    public function test_status_filter_filters_correctly()
    {
        $this->actingAs($this->user, 'admin-saas');
        $company = Company::factory()->create();
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Active A', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Active B', 'is_active' => true, 'display_order' => 2]);
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Inactive', 'is_active' => false, 'display_order' => 3]);

        $response = $this->get('/operator-saas/role-perusahaan?status=Aktif');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/RolePerusahaan')
                ->has('roles.data', 2)
            );
    }

    public function test_search_filter_filters_by_name()
    {
        $this->actingAs($this->user, 'admin-saas');
        $company = Company::factory()->create();
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Alpha Role', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Beta Role', 'is_active' => true, 'display_order' => 2]);
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Gamma Role', 'is_active' => true, 'display_order' => 3]);

        $response = $this->get('/operator-saas/role-perusahaan?search=beta');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/RolePerusahaan')
                ->has('roles.data', 1)
            );
    }

    public function test_available_permissions_uses_admin_perusahaan_scope()
    {
        $this->actingAs($this->user, 'admin-saas');

        $adminPerm = Permission::create([
            'name' => 'customer.list',
            'scope' => 'admin_perusahaan',
            'display_order' => 1,
            'description' => 'Lihat daftar customer',
        ]);
        $karyawanPerm = Permission::create([
            'name' => 'karyawan-customer.list',
            'scope' => 'karyawan_perusahaan',
            'display_order' => 2,
            'description' => 'Lihat daftar customer via karyawan',
        ]);

        $response = $this->get('/operator-saas/role-perusahaan');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/RolePerusahaan')
                ->has('availablePermissions', 1)
                ->where('availablePermissions.0.nama', 'customer.list')
                ->where('availablePermissions.0.id', $adminPerm->id)
            );
        $this->assertDatabaseHas('permissions', ['id' => $karyawanPerm->id]);
    }

    public function test_role_payload_includes_permission_ids_and_names()
    {
        $this->actingAs($this->user, 'admin-saas');
        $company = Company::factory()->create();
        $permA = Permission::create([
            'name' => 'customer.list',
            'scope' => 'admin_perusahaan',
            'display_order' => 1,
            'description' => 'Lihat daftar customer',
        ]);
        $permB = Permission::create([
            'name' => 'tagihan.create',
            'scope' => 'admin_perusahaan',
            'display_order' => 2,
            'description' => 'Tambah tagihan',
        ]);

        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $company->id,
            'name' => 'Role With Perms',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $role->permissions()->sync([$permA->id, $permB->id]);

        $response = $this->get('/operator-saas/role-perusahaan');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/RolePerusahaan')
                ->has('roles.data.0.permission_ids', 2)
                ->where('roles.data.0.permission_count', 2)
                ->where('roles.data.0.permission_names', [$permA->name, $permB->name])
            );
    }

    public function test_update_role_syncs_permissions()
    {
        $this->actingAs($this->user, 'admin-saas');
        $company = Company::factory()->create();
        $permA = Permission::create([
            'name' => 'customer.list',
            'scope' => 'admin_perusahaan',
            'display_order' => 1,
            'description' => 'Lihat daftar customer',
        ]);
        $permB = Permission::create([
            'name' => 'tagihan.create',
            'scope' => 'admin_perusahaan',
            'display_order' => 2,
            'description' => 'Tambah tagihan',
        ]);

        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $company->id,
            'name' => 'Sync Role',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $role->permissions()->sync([$permA->id]);

        $response = $this->put("/operator-saas/role-perusahaan/{$role->id}", [
            'nama_role' => 'Sync Role Updated',
            'company_id' => $company->id,
            'status' => 'Aktif',
            'permission_ids' => [$permB->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role perusahaan berhasil diperbarui.');
        $this->assertDatabaseHas('role_permissions', ['role_id' => $role->id, 'permission_id' => $permB->id]);
        $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id, 'permission_id' => $permA->id]);
    }
}

