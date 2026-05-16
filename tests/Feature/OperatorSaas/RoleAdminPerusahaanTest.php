<?php

namespace Tests\Feature\OperatorSaas;

use App\Models\AdminCompany;
use App\Models\AdminSaas;
use App\Models\Company;
use App\Models\ModelHasRole;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleAdminPerusahaanTest extends TestCase
{
    use RefreshDatabase;

    protected AdminSaas $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminSaas::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'operator_saas');
    }

    public function test_guest_cannot_access_role_admin_perusahaan_page()
    {
        $this->get('/operator-saas/role-admin-perusahaan')
            ->assertRedirect('/login-operator-saas');
    }

    public function test_authenticated_can_view_role_admin_perusahaan_page()
    {
        $this->actingAs($this->user, 'web')
            ->get('/operator-saas/role-admin-perusahaan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/RoleAdminPerusahaan')
                ->has('assignments')
            );
    }

    public function test_can_create_role_admin_perusahaan()
    {
        $this->actingAs($this->user, 'web');
        $company = Company::factory()->create();
        $admin = AdminCompany::factory()->create(['company_id' => $company->id]);
        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $company->id,
            'name' => 'Role Test',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $response = $this->post('/operator-saas/role-admin-perusahaan', [
            'admin_id' => $admin->id,
            'company_id' => $company->id,
            'role_id' => $role->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role admin perusahaan berhasil ditetapkan.');
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => AdminCompany::class,
            'model_id' => $admin->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_create_role_admin_perusahaan_validation_fails_with_empty_fields()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->post('/operator-saas/role-admin-perusahaan', [
            'admin_id' => '',
            'company_id' => '',
            'role_id' => '',
        ]);

        $response->assertSessionHasErrors(['admin_id', 'company_id', 'role_id']);
    }

    public function test_can_update_role_admin_perusahaan()
    {
        $this->actingAs($this->user, 'web');
        $company = Company::factory()->create();
        $admin = AdminCompany::factory()->create(['company_id' => $company->id]);
        $oldRole = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $company->id,
            'name' => 'Old Role',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $newRole = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $company->id,
            'name' => 'New Role',
            'is_active' => true,
            'display_order' => 2,
        ]);

        $assignment = ModelHasRole::create([
            'model_type' => AdminCompany::class,
            'model_id' => $admin->id,
            'role_id' => $oldRole->id,
        ]);

        $response = $this->put("/operator-saas/role-admin-perusahaan/{$assignment->id}", [
            'role_id' => $newRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role admin perusahaan berhasil diperbarui.');
        $this->assertDatabaseHas('model_has_roles', ['id' => $assignment->id, 'role_id' => $newRole->id]);
    }

    public function test_can_delete_role_admin_perusahaan()
    {
        $this->actingAs($this->user, 'web');
        $company = Company::factory()->create();
        $admin = AdminCompany::factory()->create(['company_id' => $company->id]);
        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $company->id,
            'name' => 'Role Delete',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $assignment = ModelHasRole::create([
            'model_type' => AdminCompany::class,
            'model_id' => $admin->id,
            'role_id' => $role->id,
        ]);

        $response = $this->delete("/operator-saas/role-admin-perusahaan/{$assignment->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Penugasan role berhasil dihapus.');
        $this->assertDatabaseMissing('model_has_roles', ['id' => $assignment->id]);
    }

    public function test_can_bulk_delete_role_admin_perusahaan()
    {
        $this->actingAs($this->user, 'web');
        $company = Company::factory()->create();
        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $company->id,
            'name' => 'Bulk Role',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $assignments = collect(range(1, 3))->map(function () use ($company, $role) {
            $admin = AdminCompany::factory()->create(['company_id' => $company->id]);
            return ModelHasRole::create([
                'model_type' => AdminCompany::class,
                'model_id' => $admin->id,
                'role_id' => $role->id,
            ]);
        });

        $response = $this->post('/operator-saas/role-admin-perusahaan/bulk-delete', [
            'ids' => $assignments->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '3 penugasan role berhasil dihapus.');
        foreach ($assignments as $a) {
            $this->assertDatabaseMissing('model_has_roles', ['id' => $a->id]);
        }
    }

    public function test_search_filter_filters_by_admin_name()
    {
        $this->actingAs($this->user, 'web');
        $company = Company::factory()->create();
        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $company->id,
            'name' => 'Search Role',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $adminA = AdminCompany::factory()->create(['company_id' => $company->id, 'name' => 'Alpha Admin Search']);
        $adminB = AdminCompany::factory()->create(['company_id' => $company->id, 'name' => 'Beta Admin Search']);

        ModelHasRole::create(['model_type' => AdminCompany::class, 'model_id' => $adminA->id, 'role_id' => $role->id]);
        ModelHasRole::create(['model_type' => AdminCompany::class, 'model_id' => $adminB->id, 'role_id' => $role->id]);

        $response = $this->get('/operator-saas/role-admin-perusahaan?search=Beta');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/RoleAdminPerusahaan')
                ->has('assignments.data', 1)
            );
    }
}
