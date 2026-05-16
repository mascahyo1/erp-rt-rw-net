<?php

namespace Tests\Feature\OperatorSaas;

use App\Models\AdminSaas;
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

    public function test_guest_cannot_access_admin_saas_page()
    {
        $this->get('/operator-saas/admin-saas')
            ->assertRedirect('/login-operator-saas');
    }

    public function test_authenticated_can_view_admin_saas_page()
    {
        $this->actingAs($this->user, 'web')
            ->get('/operator-saas/admin-saas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/AdminSaaS')
                ->has('admins')
            );
    }

    public function test_can_create_admin_saas()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->post('/operator-saas/admin-saas', [
            'nama' => 'Admin Test',
            'email' => 'admintest@example.com',
            'kode_negara' => '+62',
            'no_telp' => '81234567890',
            'status' => 'Aktif',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin SaaS berhasil ditambahkan.');
        $this->assertDatabaseHas('admin_saas', ['name' => 'Admin Test', 'email' => 'admintest@example.com']);
    }

    public function test_create_admin_saas_validation_fails_with_empty_fields()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->post('/operator-saas/admin-saas', [
            'nama' => '',
            'kode_negara' => '',
            'no_telp' => '',
            'status' => 'Aktif',
        ]);

        $response->assertSessionHasErrors(['nama', 'email', 'kode_negara', 'no_telp', 'password']);
    }

    public function test_can_update_admin_saas()
    {
        $this->actingAs($this->user, 'web');
        $admin = AdminSaas::factory()->create();

        $response = $this->put("/operator-saas/admin-saas/{$admin->id}", [
            'nama' => 'Updated Admin',
            'email' => $admin->email,
            'kode_negara' => '+62',
            'no_telp' => '81234567890',
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin SaaS berhasil diperbarui.');
        $this->assertDatabaseHas('admin_saas', ['id' => $admin->id, 'name' => 'Updated Admin']);
    }

    public function test_can_delete_admin_saas()
    {
        $this->actingAs($this->user, 'web');
        $admin = AdminSaas::factory()->create();

        $response = $this->delete("/operator-saas/admin-saas/{$admin->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin SaaS berhasil dihapus.');
        $this->assertSoftDeleted('admin_saas', ['id' => $admin->id]);
    }

    public function test_can_restore_deleted_admin_saas()
    {
        $this->actingAs($this->user, 'web');
        $admin = AdminSaas::factory()->create();
        $admin->delete();

        $response = $this->post("/operator-saas/admin-saas/{$admin->id}/restore");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin SaaS berhasil dipulihkan.');
        $this->assertDatabaseHas('admin_saas', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_can_bulk_delete_admin_saas()
    {
        $this->actingAs($this->user, 'web');
        $admins = AdminSaas::factory()->count(3)->create();

        $response = $this->post('/operator-saas/admin-saas/bulk-delete', [
            'ids' => $admins->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '3 admin SaaS berhasil dihapus.');
        foreach ($admins as $a) {
            $this->assertSoftDeleted('admin_saas', ['id' => $a->id]);
        }
    }

    public function test_can_bulk_toggle_status_admin_saas()
    {
        $this->actingAs($this->user, 'web');
        $admins = AdminSaas::factory()->count(2)->inactive()->create();

        $response = $this->post('/operator-saas/admin-saas/bulk-status', [
            'ids' => $admins->pluck('id')->toArray(),
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '2 admin SaaS berhasil diaktifkan.');
        foreach ($admins as $a) {
            $this->assertDatabaseHas('admin_saas', ['id' => $a->id, 'is_active' => true]);
        }
    }

    public function test_terhapus_filter_shows_only_trashed_records()
    {
        $this->actingAs($this->user, 'web');
        AdminSaas::factory()->count(2)->create();
        $trashed = AdminSaas::factory()->create();
        $trashed->delete();

        $response = $this->get('/operator-saas/admin-saas?terhapus=ya');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/AdminSaaS')
                ->has('admins.data', 1)
            );
    }

    public function test_status_filter_filters_correctly()
    {
        $this->actingAs($this->user, 'web');
        AdminSaas::factory()->count(2)->create(['is_active' => true]);
        AdminSaas::factory()->inactive()->create();

        $response = $this->get('/operator-saas/admin-saas?status=Aktif');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/AdminSaaS')
                ->has('admins.data', 3)
            );
    }

    public function test_search_filter_filters_by_name_email()
    {
        $this->actingAs($this->user, 'web');
        AdminSaas::factory()->create(['name' => 'Alpha Admin', 'email' => 'alpha@test.id']);
        AdminSaas::factory()->create(['name' => 'Beta Admin', 'email' => 'beta@test.id']);
        AdminSaas::factory()->create(['name' => 'Gamma Admin', 'email' => 'gamma@test.id']);

        $response = $this->get('/operator-saas/admin-saas?search=beta');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/AdminSaaS')
                ->has('admins.data', 1)
            );
    }
}
