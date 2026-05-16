<?php

namespace Tests\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\Customer;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerTest extends TestCase
{

    protected AdminCompany $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'admin_perusahaan');
    }

    public function test_guest_cannot_access_customer_page()
    {
        $this->get('/operator-perusahaan/customer')
            ->assertRedirect('/login-perusahaan');
    }

    public function test_authenticated_can_view_customer_page()
    {
        $this->actingAs($this->user, 'admin-company')
            ->get('/operator-perusahaan/customer')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/Customer')
                ->has('customers')
            );
    }

    public function test_can_create_customer()
    {
        $this->actingAs($this->user, 'admin-company');

        $response = $this->post('/operator-perusahaan/customer', [
            'nama' => 'Pelanggan Test',
            'email' => 'pelanggan@test.id',
            'kode_negara' => '+62',
            'no_telp' => '81234567890',
            'no_nik' => '3573011234567890',
            'no_kk' => '3573011234567891',
            'alamat' => 'Jl. Test No. 1',
            'status' => 'Aktif',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pelanggan berhasil ditambahkan.');
        $this->assertDatabaseHas('customers', [
            'name' => 'Pelanggan Test',
            'email' => 'pelanggan@test.id',
            'no_nik' => '3573011234567890',
            'no_kk' => '3573011234567891',
            'address' => 'Jl. Test No. 1',
        ]);
    }

    public function test_create_customer_validation_fails_with_empty_fields()
    {
        $this->actingAs($this->user, 'admin-company');

        $response = $this->post('/operator-perusahaan/customer', [
            'nama' => '',
            'kode_negara' => '',
            'no_telp' => '',
            'status' => 'Aktif',
        ]);

        $response->assertSessionHasErrors(['nama', 'email', 'kode_negara', 'no_telp', 'password']);
    }

    public function test_can_update_customer()
    {
        $this->actingAs($this->user, 'admin-company');
        $customer = Customer::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->put("/operator-perusahaan/customer/{$customer->id}", [
            'nama' => 'Updated Pelanggan',
            'email' => $customer->email,
            'kode_negara' => '+62',
            'no_telp' => '81234567890',
            'no_nik' => '3573019876543210',
            'no_kk' => '3573019876543211',
            'alamat' => 'Jl. Updated No. 2',
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pelanggan berhasil diperbarui.');
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Pelanggan',
            'no_nik' => '3573019876543210',
            'address' => 'Jl. Updated No. 2',
        ]);
    }

    public function test_can_delete_customer()
    {
        $this->actingAs($this->user, 'admin-company');
        $customer = Customer::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->delete("/operator-perusahaan/customer/{$customer->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pelanggan berhasil dihapus.');
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_can_restore_deleted_customer()
    {
        $this->actingAs($this->user, 'admin-company');
        $customer = Customer::factory()->create(['company_id' => $this->user->company_id]);
        $customer->delete();

        $response = $this->patch("/operator-perusahaan/customer/{$customer->id}/restore");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pelanggan berhasil dipulihkan.');
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'deleted_at' => null]);
    }

    public function test_can_bulk_delete_customer()
    {
        $this->actingAs($this->user, 'admin-company');
        $customers = Customer::factory()->count(3)->create(['company_id' => $this->user->company_id]);

        $response = $this->post('/operator-perusahaan/customer/bulk-delete', [
            'ids' => $customers->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '3 pelanggan berhasil dihapus.');
        foreach ($customers as $c) {
            $this->assertSoftDeleted('customers', ['id' => $c->id]);
        }
    }

    public function test_can_bulk_toggle_status_customer()
    {
        $this->actingAs($this->user, 'admin-company');
        $customers = Customer::factory()->count(2)->inactive()->create(['company_id' => $this->user->company_id]);

        $response = $this->post('/operator-perusahaan/customer/bulk-status', [
            'ids' => $customers->pluck('id')->toArray(),
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '2 pelanggan berhasil diaktifkan.');
        foreach ($customers as $c) {
            $this->assertDatabaseHas('customers', ['id' => $c->id, 'is_active' => true]);
        }
    }

    public function test_terhapus_filter_shows_only_trashed_records()
    {
        $this->actingAs($this->user, 'admin-company');
        Customer::factory()->count(2)->create(['company_id' => $this->user->company_id]);
        $trashed = Customer::factory()->create(['company_id' => $this->user->company_id]);
        $trashed->delete();

        $response = $this->get('/operator-perusahaan/customer?terhapus=ya');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/Customer')
                ->has('customers.data', 1)
            );
    }

    public function test_status_filter_filters_correctly()
    {
        $this->actingAs($this->user, 'admin-company');
        Customer::factory()->count(2)->create(['company_id' => $this->user->company_id, 'is_active' => true]);
        Customer::factory()->inactive()->create(['company_id' => $this->user->company_id]);

        $response = $this->get('/operator-perusahaan/customer?status=Aktif');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/Customer')
                ->has('customers.data', 2)
            );
    }

    public function test_search_filter_filters_by_name_email()
    {
        $this->actingAs($this->user, 'admin-company');
        $cid = $this->user->company_id;
        Customer::factory()->create(['company_id' => $cid, 'name' => 'Alpha Customer', 'email' => 'alpha@test.id']);
        Customer::factory()->create(['company_id' => $cid, 'name' => 'Beta Customer', 'email' => 'beta@test.id']);
        Customer::factory()->create(['company_id' => $cid, 'name' => 'Gamma Customer', 'email' => 'gamma@test.id']);

        $response = $this->get('/operator-perusahaan/customer?search=beta');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/Customer')
                ->has('customers.data', 1)
            );
    }
}
