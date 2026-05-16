<?php

namespace Tests\Feature\OperatorSaas;

use App\Models\AdminSaas;
use App\Models\ModelHasRole;
use App\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminRoleSaasTest extends TestCase
{

    protected AdminSaas $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminSaas::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'operator_saas');
    }

    public function test_guest_cannot_access_admin_role_saas_page()
    {
        $this->get('/operator-saas/admin-role-saas')
            ->assertRedirect('/login-operator-saas');
    }

    public function test_authenticated_can_view_admin_role_saas_page()
    {
        $this->actingAs($this->user, 'web')
            ->get('/operator-saas/admin-role-saas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/AdminRoleSaaS')
                ->has('assignments')
            );
    }

    public function test_can_create_admin_role_saas()
    {
        $this->actingAs($this->user, 'web');
        $targetAdmin = AdminSaas::factory()->create();
        $role = Role::create([
            'scope' => 'operator_saas',
            'name' => 'SaaS Admin Role',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $response = $this->post('/operator-saas/admin-role-saas', [
            'admin_id' => $targetAdmin->id,
            'role_id' => $role->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role admin SaaS berhasil ditetapkan.');
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => AdminSaas::class,
            'model_id' => $targetAdmin->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_create_admin_role_saas_validation_fails_with_empty_fields()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->post('/operator-saas/admin-role-saas', [
            'admin_id' => '',
            'role_id' => '',
        ]);

        $response->assertSessionHasErrors(['admin_id', 'role_id']);
    }

    public function test_can_update_admin_role_saas()
    {
        $this->actingAs($this->user, 'web');
        $targetAdmin = AdminSaas::factory()->create();
        $oldRole = Role::create([
            'scope' => 'operator_saas',
            'name' => 'Old SaaS Role',
            'is_active' => true,
            'display_order' => 1,
        ]);
        $newRole = Role::create([
            'scope' => 'operator_saas',
            'name' => 'New SaaS Role',
            'is_active' => true,
            'display_order' => 2,
        ]);

        $assignment = ModelHasRole::create([
            'model_type' => AdminSaas::class,
            'model_id' => $targetAdmin->id,
            'role_id' => $oldRole->id,
        ]);

        $response = $this->put("/operator-saas/admin-role-saas/{$assignment->id}", [
            'role_id' => $newRole->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Role admin SaaS berhasil diperbarui.');
        $this->assertDatabaseHas('model_has_roles', ['id' => $assignment->id, 'role_id' => $newRole->id]);
    }

    public function test_can_delete_admin_role_saas()
    {
        $this->actingAs($this->user, 'web');
        $targetAdmin = AdminSaas::factory()->create();
        $role = Role::create([
            'scope' => 'operator_saas',
            'name' => 'Delete SaaS Role',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $assignment = ModelHasRole::create([
            'model_type' => AdminSaas::class,
            'model_id' => $targetAdmin->id,
            'role_id' => $role->id,
        ]);

        $response = $this->delete("/operator-saas/admin-role-saas/{$assignment->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Penugasan role berhasil dihapus.');
        $this->assertDatabaseMissing('model_has_roles', ['id' => $assignment->id]);
    }

    public function test_can_bulk_delete_admin_role_saas()
    {
        $this->actingAs($this->user, 'web');
        $role = Role::create([
            'scope' => 'operator_saas',
            'name' => 'Bulk SaaS Role',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $assignments = collect(range(1, 3))->map(function () use ($role) {
            $admin = AdminSaas::factory()->create();
            return ModelHasRole::create([
                'model_type' => AdminSaas::class,
                'model_id' => $admin->id,
                'role_id' => $role->id,
            ]);
        });

        $response = $this->post('/operator-saas/admin-role-saas/bulk-delete', [
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
        $role = Role::create([
            'scope' => 'operator_saas',
            'name' => 'Search SaaS Role',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $adminA = AdminSaas::factory()->create(['name' => 'Alpha SaaS Admin', 'is_active' => true]);
        $adminB = AdminSaas::factory()->create(['name' => 'Beta SaaS Admin', 'is_active' => true]);

        ModelHasRole::create(['model_type' => AdminSaas::class, 'model_id' => $adminA->id, 'role_id' => $role->id]);
        ModelHasRole::create(['model_type' => AdminSaas::class, 'model_id' => $adminB->id, 'role_id' => $role->id]);

        $response = $this->get('/operator-saas/admin-role-saas?search=Alpha');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/AdminRoleSaaS')
                ->has('assignments.data', 1)
            );
    }
}
