<?php

namespace Tests\Feature\OperatorSaas;

use App\Models\AdminSaas;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PerusahaanTest extends TestCase
{
    use RefreshDatabase;

    protected AdminSaas $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = AdminSaas::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->admin, 'operator_saas');
    }

    public function test_guest_cannot_access_perusahaan_page()
    {
        $this->get('/operator-saas/perusahaan')
            ->assertRedirect('/login-operator-saas');
    }

    public function test_authenticated_admin_can_view_perusahaan_page()
    {
        $this->actingAs($this->admin, 'web')
            ->get('/operator-saas/perusahaan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/Perusahaan')
                ->has('companies')
            );
    }

    public function test_admin_can_create_perusahaan()
    {
        $this->actingAs($this->admin, 'web');

        $response = $this->post('/operator-saas/perusahaan', [
            'nama_perusahaan' => 'PT Test Sejahtera',
            'email' => 'test@sejahtera.id',
            'kode_negara' => '+62',
            'no_telp' => '81234567890',
            'alamat' => 'Jl. Merdeka No. 10, Jakarta',
            'deskripsi' => 'Perusahaan test',
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Perusahaan berhasil ditambahkan.');
        $this->assertDatabaseHas('companies', ['name' => 'PT Test Sejahtera', 'email' => 'test@sejahtera.id']);
    }

    public function test_create_perusahaan_validation_fails_with_empty_fields()
    {
        $this->actingAs($this->admin, 'web');

        $response = $this->post('/operator-saas/perusahaan', [
            'nama_perusahaan' => '',
            'kode_negara' => '',
            'no_telp' => '',
            'alamat' => '',
            'status' => 'Aktif',
        ]);

        $response->assertSessionHasErrors(['nama_perusahaan', 'kode_negara', 'no_telp', 'alamat']);
    }

    public function test_admin_can_update_perusahaan()
    {
        $this->actingAs($this->admin, 'web');
        $company = Company::factory()->create();

        $response = $this->put("/operator-saas/perusahaan/{$company->id}", [
            'nama_perusahaan' => 'PT Updated',
            'email' => $company->email,
            'kode_negara' => '+62',
            'no_telp' => '81234567890',
            'alamat' => 'Jl. Baru No. 1',
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Perusahaan berhasil diperbarui.');
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'PT Updated']);
    }

    public function test_admin_can_delete_perusahaan()
    {
        $this->actingAs($this->admin, 'web');
        $company = Company::factory()->create();

        $response = $this->delete("/operator-saas/perusahaan/{$company->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Perusahaan berhasil dihapus.');
        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_admin_can_restore_deleted_perusahaan()
    {
        $this->actingAs($this->admin, 'web');
        $company = Company::factory()->create();
        $company->delete();

        $response = $this->post("/operator-saas/perusahaan/{$company->id}/restore");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Perusahaan berhasil dipulihkan.');
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'deleted_at' => null]);
    }

    public function test_admin_can_bulk_delete_perusahaan()
    {
        $this->actingAs($this->admin, 'web');
        $companies = Company::factory()->count(3)->create();

        $response = $this->post('/operator-saas/perusahaan/bulk-delete', [
            'ids' => $companies->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '3 perusahaan berhasil dihapus.');
        foreach ($companies as $c) {
            $this->assertSoftDeleted('companies', ['id' => $c->id]);
        }
    }

    public function test_admin_can_bulk_activate_perusahaan()
    {
        $this->actingAs($this->admin, 'web');
        $companies = Company::factory()->count(2)->inactive()->create();

        $response = $this->post('/operator-saas/perusahaan/bulk-status', [
            'ids' => $companies->pluck('id')->toArray(),
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '2 perusahaan berhasil diaktifkan.');
        foreach ($companies as $c) {
            $this->assertDatabaseHas('companies', ['id' => $c->id, 'is_active' => true]);
        }
    }

    public function test_terhapus_filter_shows_only_trashed_records()
    {
        $this->actingAs($this->admin, 'web');
        Company::factory()->count(2)->create();
        $trashed = Company::factory()->create();
        $trashed->delete();

        $response = $this->get('/operator-saas/perusahaan?terhapus=ya');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/Perusahaan')
                ->has('companies.data', 1)
            );
    }

    public function test_status_filter_filters_correctly()
    {
        $this->actingAs($this->admin, 'web');
        Company::factory()->count(2)->create(['is_active' => true]);
        Company::factory()->inactive()->create();

        $response = $this->get('/operator-saas/perusahaan?status=Aktif');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/Perusahaan')
                ->has('companies.data', 2)
            );
    }

    public function test_search_filter_filters_by_name_email_alamat()
    {
        $this->actingAs($this->admin, 'web');
        Company::factory()->create(['name' => 'Alpha Corp', 'email' => 'alpha@corp.id', 'address' => 'Jl. Alpha']);
        Company::factory()->create(['name' => 'Beta Ltd', 'email' => 'beta@ltd.id', 'address' => 'Jl. Beta']);
        Company::factory()->create(['name' => 'Gamma Inc', 'email' => 'gamma@inc.id', 'address' => 'Jl. Gamma']);

        $response = $this->get('/operator-saas/perusahaan?search=beta');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorSaas/Perusahaan')
                ->has('companies.data', 1)
            );
    }
}
