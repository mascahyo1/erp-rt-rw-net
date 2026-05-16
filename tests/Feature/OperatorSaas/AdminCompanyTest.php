<?php

namespace Tests\Feature\OperatorSaas;

use App\Models\AdminSaas;
use App\Models\AdminCompany;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected AdminSaas $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminSaas::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'operator_saas');
    }

    public function test_guest_cannot_access_admin_company_page()
    {
        $this->get('/operator-saas/admin-perusahaan')
            ->assertRedirect('/login-operator-saas');
    }

    public function test_authenticated_can_view_admin_company_page()
    {
        $this->actingAs($this->user, 'web')
            ->get('/operator-saas/admin-perusahaan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/AdminPerusahaan')
                ->has('admins')
            );
    }

    public function test_can_create_admin_company()
    {
        $this->actingAs($this->user, 'web');
        $company = Company::factory()->create();

        $response = $this->post('/operator-saas/admin-perusahaan', [
            'company_id' => $company->id,
            'nama' => 'Admin Company Test',
            'email' => 'admincomp@example.com',
            'kode_negara' => '+62',
            'no_telp' => '81234567890',
            'status' => 'Aktif',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin Perusahaan berhasil ditambahkan.');
        $this->assertDatabaseHas('admin_companies', [
            'name' => 'Admin Company Test',
            'email' => 'admincomp@example.com',
            'company_id' => $company->id,
        ]);
    }

    public function test_create_admin_company_validation_fails_with_empty_fields()
    {
        $this->actingAs($this->user, 'web');

        $response = $this->post('/operator-saas/admin-perusahaan', [
            'nama' => '',
            'kode_negara' => '',
            'no_telp' => '',
            'status' => 'Aktif',
        ]);

        $response->assertSessionHasErrors(['company_id', 'nama', 'email', 'kode_negara', 'no_telp', 'password']);
    }

    public function test_can_update_admin_company()
    {
        $this->actingAs($this->user, 'web');
        $admin = AdminCompany::factory()->create();

        $response = $this->put("/operator-saas/admin-perusahaan/{$admin->id}", [
            'company_id' => $admin->company_id,
            'nama' => 'Updated Admin Company',
            'email' => $admin->email,
            'kode_negara' => '+62',
            'no_telp' => '81234567890',
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin Perusahaan berhasil diperbarui.');
        $this->assertDatabaseHas('admin_companies', ['id' => $admin->id, 'name' => 'Updated Admin Company']);
    }

    public function test_can_delete_admin_company()
    {
        $this->actingAs($this->user, 'web');
        $admin = AdminCompany::factory()->create();

        $response = $this->delete("/operator-saas/admin-perusahaan/{$admin->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin Perusahaan berhasil dihapus.');
        $this->assertSoftDeleted('admin_companies', ['id' => $admin->id]);
    }

    public function test_can_restore_deleted_admin_company()
    {
        $this->actingAs($this->user, 'web');
        $admin = AdminCompany::factory()->create();
        $admin->delete();

        $response = $this->post("/operator-saas/admin-perusahaan/{$admin->id}/restore");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin Perusahaan berhasil dipulihkan.');
        $this->assertDatabaseHas('admin_companies', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_can_bulk_delete_admin_company()
    {
        $this->actingAs($this->user, 'web');
        $admins = AdminCompany::factory()->count(3)->create();

        $response = $this->post('/operator-saas/admin-perusahaan/bulk-delete', [
            'ids' => $admins->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '3 admin perusahaan berhasil dihapus.');
        foreach ($admins as $a) {
            $this->assertSoftDeleted('admin_companies', ['id' => $a->id]);
        }
    }

    public function test_can_bulk_toggle_status_admin_company()
    {
        $this->actingAs($this->user, 'web');
        $admins = AdminCompany::factory()->count(2)->inactive()->create();

        $response = $this->post('/operator-saas/admin-perusahaan/bulk-status', [
            'ids' => $admins->pluck('id')->toArray(),
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '2 admin perusahaan berhasil diaktifkan.');
        foreach ($admins as $a) {
            $this->assertDatabaseHas('admin_companies', ['id' => $a->id, 'is_active' => true]);
        }
    }

    public function test_terhapus_filter_shows_only_trashed_records()
    {
        $this->actingAs($this->user, 'web');
        AdminCompany::factory()->count(2)->create();
        $trashed = AdminCompany::factory()->create();
        $trashed->delete();

        $response = $this->get('/operator-saas/admin-perusahaan?terhapus=ya');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/AdminPerusahaan')
                ->has('admins.data', 1)
            );
    }

    public function test_status_filter_filters_correctly()
    {
        $this->actingAs($this->user, 'web');
        AdminCompany::factory()->count(2)->create(['is_active' => true]);
        AdminCompany::factory()->inactive()->create();

        $response = $this->get('/operator-saas/admin-perusahaan?status=Aktif');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/AdminPerusahaan')
                ->has('admins.data', 2)
            );
    }

    public function test_search_filter_filters_by_name_email()
    {
        $this->actingAs($this->user, 'web');
        AdminCompany::factory()->create(['name' => 'Alpha Admin', 'email' => 'alpha@test.id']);
        AdminCompany::factory()->create(['name' => 'Beta Admin', 'email' => 'beta@test.id']);
        AdminCompany::factory()->create(['name' => 'Gamma Admin', 'email' => 'gamma@test.id']);

        $response = $this->get('/operator-saas/admin-perusahaan?search=beta');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/AdminPerusahaan')
                ->has('admins.data', 1)
            );
    }
}
