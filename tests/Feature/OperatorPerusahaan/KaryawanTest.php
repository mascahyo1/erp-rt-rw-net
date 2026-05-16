<?php

namespace Tests\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KaryawanTest extends TestCase
{
    use RefreshDatabase;

    protected AdminCompany $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'admin_perusahaan');
    }

    public function test_guest_cannot_access_karyawan_page()
    {
        $this->get('/operator-perusahaan/karyawan')
            ->assertRedirect('/login-perusahaan');
    }

    public function test_authenticated_can_view_karyawan_page()
    {
        $this->actingAs($this->user, 'admin-company')
            ->get('/operator-perusahaan/karyawan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/Karyawan')
                ->has('karyawans')
            );
    }

    public function test_can_create_karyawan()
    {
        $this->actingAs($this->user, 'admin-company');

        $response = $this->post('/operator-perusahaan/karyawan', [
            'nama' => 'Karyawan Test',
            'email' => 'karyawan@test.id',
            'kode_negara' => '+62',
            'no_telp' => '81234567890',
            'status' => 'Aktif',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Karyawan berhasil ditambahkan.');
        $this->assertDatabaseHas('employees', ['name' => 'Karyawan Test', 'email' => 'karyawan@test.id']);
    }

    public function test_create_karyawan_validation_fails_with_empty_fields()
    {
        $this->actingAs($this->user, 'admin-company');

        $response = $this->post('/operator-perusahaan/karyawan', [
            'nama' => '',
            'kode_negara' => '',
            'no_telp' => '',
            'status' => 'Aktif',
        ]);

        $response->assertSessionHasErrors(['nama', 'email', 'kode_negara', 'no_telp', 'password']);
    }

    public function test_can_update_karyawan()
    {
        $this->actingAs($this->user, 'admin-company');
        $karyawan = Employee::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->put("/operator-perusahaan/karyawan/{$karyawan->id}", [
            'nama' => 'Updated Karyawan',
            'email' => $karyawan->email,
            'kode_negara' => '+62',
            'no_telp' => '81234567890',
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Karyawan berhasil diperbarui.');
        $this->assertDatabaseHas('employees', ['id' => $karyawan->id, 'name' => 'Updated Karyawan']);
    }

    public function test_can_delete_karyawan()
    {
        $this->actingAs($this->user, 'admin-company');
        $karyawan = Employee::factory()->create(['company_id' => $this->user->company_id]);

        $response = $this->delete("/operator-perusahaan/karyawan/{$karyawan->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Karyawan berhasil dihapus.');
        $this->assertSoftDeleted('employees', ['id' => $karyawan->id]);
    }

    public function test_can_restore_deleted_karyawan()
    {
        $this->actingAs($this->user, 'admin-company');
        $karyawan = Employee::factory()->create(['company_id' => $this->user->company_id]);
        $karyawan->delete();

        $response = $this->patch("/operator-perusahaan/karyawan/{$karyawan->id}/restore");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Karyawan berhasil dipulihkan.');
        $this->assertDatabaseHas('employees', ['id' => $karyawan->id, 'deleted_at' => null]);
    }

    public function test_can_bulk_delete_karyawan()
    {
        $this->actingAs($this->user, 'admin-company');
        $karyawans = Employee::factory()->count(3)->create(['company_id' => $this->user->company_id]);

        $response = $this->post('/operator-perusahaan/karyawan/bulk-delete', [
            'ids' => $karyawans->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '3 karyawan berhasil dihapus.');
        foreach ($karyawans as $k) {
            $this->assertSoftDeleted('employees', ['id' => $k->id]);
        }
    }

    public function test_can_bulk_toggle_status_karyawan()
    {
        $this->actingAs($this->user, 'admin-company');
        $karyawans = Employee::factory()->count(2)->inactive()->create(['company_id' => $this->user->company_id]);

        $response = $this->post('/operator-perusahaan/karyawan/bulk-status', [
            'ids' => $karyawans->pluck('id')->toArray(),
            'status' => 'Aktif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '2 karyawan berhasil diaktifkan.');
        foreach ($karyawans as $k) {
            $this->assertDatabaseHas('employees', ['id' => $k->id, 'is_active' => true]);
        }
    }

    public function test_terhapus_filter_shows_only_trashed_records()
    {
        $this->actingAs($this->user, 'admin-company');
        Employee::factory()->count(2)->create(['company_id' => $this->user->company_id]);
        $trashed = Employee::factory()->create(['company_id' => $this->user->company_id]);
        $trashed->delete();

        $response = $this->get('/operator-perusahaan/karyawan?terhapus=ya');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/Karyawan')
                ->has('karyawans.data', 1)
            );
    }

    public function test_status_filter_filters_correctly()
    {
        $this->actingAs($this->user, 'admin-company');
        Employee::factory()->count(2)->create(['company_id' => $this->user->company_id, 'is_active' => true]);
        Employee::factory()->inactive()->create(['company_id' => $this->user->company_id]);

        $response = $this->get('/operator-perusahaan/karyawan?status=Aktif');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/Karyawan')
                ->has('karyawans.data', 2)
            );
    }

    public function test_search_filter_filters_by_name_email()
    {
        $this->actingAs($this->user, 'admin-company');
        $cid = $this->user->company_id;
        Employee::factory()->create(['company_id' => $cid, 'name' => 'Alpha Karyawan', 'email' => 'alpha@test.id']);
        Employee::factory()->create(['company_id' => $cid, 'name' => 'Beta Karyawan', 'email' => 'beta@test.id']);
        Employee::factory()->create(['company_id' => $cid, 'name' => 'Gamma Karyawan', 'email' => 'gamma@test.id']);

        $response = $this->get('/operator-perusahaan/karyawan?search=beta');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/Karyawan')
                ->has('karyawans.data', 1)
            );
    }
}
