<?php

namespace Tests\Feature\OperatorSaas;

use App\Models\AdminSaas;
use App\Models\Company;
use App\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;


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
        $this->actingAs($this->user, 'web')
            ->get('/operator-saas/role-perusahaan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/RolePerusahaan')
                ->has('roles')
            );
    }

    public function test_can_create_role_perusahaan()
    {
        $this->actingAs($this->user, 'web');
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
        $this->actingAs($this->user, 'web');

        $response = $this->post('/operator-saas/role-perusahaan', [
            'nama_role' => '',
            'status' => 'Aktif',
        ]);

        $response->assertSessionHasErrors(['nama_role', 'company_id']);
    }

    public function test_can_update_role_perusahaan()
    {
        $this->actingAs($this->user, 'web');
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
        $this->actingAs($this->user, 'web');
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
        $this->actingAs($this->user, 'web');
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
        $this->actingAs($this->user, 'web');
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
        $this->actingAs($this->user, 'web');
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
        $this->actingAs($this->user, 'web');
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
        $this->actingAs($this->user, 'web');
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
        $this->actingAs($this->user, 'web');
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
}
