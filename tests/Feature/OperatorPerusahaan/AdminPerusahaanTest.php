<?php

namespace Tests\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;


{

    protected AdminCompany $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'admin_perusahaan');
    }

    public function test_guest_cannot_access_admin_perusahaan_page()
    {
        $this->get('/operator-perusahaan/admin-perusahaan')
            ->assertRedirect('/login-perusahaan');
    }

    public function test_authenticated_can_view_admin_perusahaan_page()
    {
        $this->actingAs($this->user, 'admin-company')
            ->get('/operator-perusahaan/admin-perusahaan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/AdminPerusahaan')
                ->has('admins')
            );
    }

    public function test_can_create_admin_perusahaan()
    {
        $this->actingAs($this->user, 'admin-company');

        $response = $this->post('/operator-perusahaan/admin-perusahaan', [
            'nama' => 'Admin Perusahaan Test',
            'email' => 'adminperusahaan@test.id',
            'kode_negara' => '+62',
            'no_telp' => '81234567890',
            'status' => 'Aktif',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin Perusahaan berhasil ditambahkan.');
        $this->assertDatabaseHas('admin_companies', ['name' => 'Admin Perusahaan Test', 'email' => 'adminperusahaan@test.id']);
    }

    public function test_create_admin_perusahaan_validation_fails_with_empty_fields()
    {
        $this->actingAs($this->user, 'admin-company');

        $response = $this->post('/operator-perusahaan/admin-perusahaan', [
            'nama' => '',
            'kode_negara' => '',
            'no_telp' => '',
            'status' => 'Aktif',
        ]);

        $response->assertSessionHasErrors(['nama', 'email', 'kode_negara', 'no_telp', 'password']);
    }

    public function test_can_update_admin_perusahaan()
    {
        $this->actingAs($this->user, 'admin-company');
        $admin = AdminCompany::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->put("/operator-perusahaan/admin-perusahaan/{$admin->id}", [
            'nama' => 'Updated Admin Perusahaan',
            'email' => $admin->email,
            'kode_negara' => '+62',
            'no_telp' => '81234567890',
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin Perusahaan berhasil diperbarui.');
        $this->assertDatabaseHas('admin_companies', ['id' => $admin->id, 'name' => 'Updated Admin Perusahaan']);
    }

    public function test_can_delete_admin_perusahaan()
    {
        $this->actingAs($this->user, 'admin-company');
        $admin = AdminCompany::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->delete("/operator-perusahaan/admin-perusahaan/{$admin->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin Perusahaan berhasil dihapus.');
        $this->assertSoftDeleted('admin_companies', ['id' => $admin->id]);
    }

    public function test_can_restore_deleted_admin_perusahaan()
    {
        $this->actingAs($this->user, 'admin-company');
        $admin = AdminCompany::factory()->create(['company_id' => $this->user->company_id]);
        $admin->delete();

        $response = $this->patch("/operator-perusahaan/admin-perusahaan/{$admin->id}/restore");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Admin Perusahaan berhasil dipulihkan.');
        $this->assertDatabaseHas('admin_companies', ['id' => $admin->id, 'deleted_at' => null]);
    }

    public function test_can_bulk_delete_admin_perusahaan()
    {
        $this->actingAs($this->user, 'admin-company');
        $admins = AdminCompany::factory()->count(3)->create(['company_id' => $this->user->company_id]);

        $response = $this->post('/operator-perusahaan/admin-perusahaan/bulk-delete', [
            'ids' => $admins->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '3 admin perusahaan berhasil dihapus.');
        foreach ($admins as $a) {
            $this->assertSoftDeleted('admin_companies', ['id' => $a->id]);
        }
    }

    public function test_can_bulk_toggle_status_admin_perusahaan()
    {
        $this->actingAs($this->user, 'admin-company');
        $admins = AdminCompany::factory()->count(2)->inactive()->create(['company_id' => $this->user->company_id]);

        $response = $this->post('/operator-perusahaan/admin-perusahaan/bulk-status', [
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
        $this->actingAs($this->user, 'admin-company');
        AdminCompany::factory()->count(2)->create(['company_id' => $this->user->company_id]);
        $trashed = AdminCompany::factory()->create(['company_id' => $this->user->company_id]);
        $trashed->delete();

        $response = $this->get('/operator-perusahaan/admin-perusahaan?terhapus=ya');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/AdminPerusahaan')
                ->has('admins.data', 1)
            );
    }

    public function test_status_filter_filters_correctly()
    {
        $this->actingAs($this->user, 'admin-company');
        AdminCompany::factory()->count(2)->create(['company_id' => $this->user->company_id, 'is_active' => true]);
        AdminCompany::factory()->inactive()->create(['company_id' => $this->user->company_id]);

        $response = $this->get('/operator-perusahaan/admin-perusahaan?status=Aktif');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/AdminPerusahaan')
                ->has('admins.data', 3)
            );
    }

    public function test_search_filter_filters_by_name_email()
    {
        $this->actingAs($this->user, 'admin-company');
        $cid = $this->user->company_id;
        AdminCompany::factory()->create(['company_id' => $cid, 'name' => 'Alpha Admin', 'email' => 'alpha@test.id']);
        AdminCompany::factory()->create(['company_id' => $cid, 'name' => 'Beta Admin', 'email' => 'beta@test.id']);
        AdminCompany::factory()->create(['company_id' => $cid, 'name' => 'Gamma Admin', 'email' => 'gamma@test.id']);

        $response = $this->get('/operator-perusahaan/admin-perusahaan?search=beta');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/AdminPerusahaan')
                ->has('admins.data', 1)
            );
    }
}
