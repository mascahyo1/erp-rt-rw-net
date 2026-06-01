<?php

namespace Tests\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\Employee;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KaryawanTest extends TestCase
{

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
            'kode' => 'KRY100',
            'nama' => 'Karyawan Test',
            'email' => 'karyawan@test.id',
            'kode_negara' => '+62',
            'no_telp' => '81234567890',
            'status' => 'Aktif',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Karyawan berhasil ditambahkan.');
        $this->assertDatabaseHas('employees', [
            'code' => 'KRY100',
            'name' => 'Karyawan Test',
            'email' => 'karyawan@test.id',
        ]);
    }

    public function test_can_create_karyawan_without_kode()
    {
        $this->actingAs($this->user, 'admin-company');

        $response = $this->post('/operator-perusahaan/karyawan', [
            'nama' => 'Tanpa Kode',
            'email' => 'tanpakode@test.id',
            'kode_negara' => '+62',
            'no_telp' => '81234567891',
            'status' => 'Aktif',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Karyawan berhasil ditambahkan.');
        $this->assertDatabaseHas('employees', [
            'name' => 'Tanpa Kode',
            'email' => 'tanpakode@test.id',
            'code' => null,
        ]);
    }

    public function test_create_karyawan_with_duplicate_kode_in_same_company_fails()
    {
        $this->actingAs($this->user, 'admin-company');
        Employee::factory()->create([
            'company_id' => $this->user->company_id,
            'code' => 'KRYDUPE',
            'email' => 'first@test.id',
        ]);

        $response = $this->post('/operator-perusahaan/karyawan', [
            'kode' => 'KRYDUPE',
            'nama' => 'Duplikat',
            'email' => 'dupe@test.id',
            'kode_negara' => '+62',
            'no_telp' => '81234567892',
            'status' => 'Aktif',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('kode');
    }

    public function test_create_karyawan_with_same_kode_in_different_company_succeeds()
    {
        $this->actingAs($this->user, 'admin-company');
        $otherCompany = \App\Models\Company::factory()->create();
        Employee::factory()->create([
            'company_id' => $otherCompany->id,
            'code' => 'KRYSHARED',
            'email' => 'other-co@test.id',
        ]);

        $response = $this->post('/operator-perusahaan/karyawan', [
            'kode' => 'KRYSHARED',
            'nama' => 'Same Kode Other Co',
            'email' => 'mine@test.id',
            'kode_negara' => '+62',
            'no_telp' => '81234567893',
            'status' => 'Aktif',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('employees', [
            'code' => 'KRYSHARED',
            'company_id' => $this->user->company_id,
            'email' => 'mine@test.id',
        ]);
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

    public function test_can_export_karyawan_to_excel()
    {
        $this->actingAs($this->user, 'admin-company');
        $cid = $this->user->company_id;
        Employee::factory()->create(['company_id' => $cid, 'name' => 'Export A', 'email' => 'exportA@test.id']);
        Employee::factory()->create(['company_id' => $cid, 'name' => 'Export B', 'email' => 'exportB@test.id']);

        $response = $this->get('/operator-perusahaan/karyawan/export');

        $response->assertOk();
        $this->assertTrue(
            str_contains((string) $response->headers->get('content-type'), 'spreadsheet')
            || str_contains((string) $response->headers->get('content-disposition'), 'attachment')
        );
    }

    public function test_can_export_karyawan_filtered_by_ids()
    {
        $this->actingAs($this->user, 'admin-company');
        $cid = $this->user->company_id;
        $a = Employee::factory()->create(['company_id' => $cid, 'name' => 'Selected']);
        Employee::factory()->create(['company_id' => $cid, 'name' => 'NotSelected']);

        $response = $this->get('/operator-perusahaan/karyawan/export?ids=' . $a->id);

        $response->assertOk();
    }

    public function test_can_download_karyawan_template()
    {
        $this->actingAs($this->user, 'admin-company');

        $response = $this->get('/operator-perusahaan/karyawan/template');

        $response->assertOk();
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_can_import_karyawan_from_excel()
    {
        $this->actingAs($this->user, 'admin-company');

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Kode', 'Nama', 'Email', 'Kode Negara', 'No. Telepon', 'No. NIK', 'No. KK', 'Status (Aktif/Nonaktif)', 'Password'],
            ['IMP001', 'Imported A', 'impA@test.id', '+62', '8111111111', '1111111111111111', '2222222222222222', 'Aktif', 'password123'],
            ['IMP002', 'Imported B', 'impB@test.id', '+62', '8222222222', '', '', 'Nonaktif', 'password123'],
        ]);

        $file = storage_path('app/temp/test_import_karyawan.xlsx');
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($file);

        $response = $this->post('/operator-perusahaan/karyawan/import', [
            'file' => new \Illuminate\Http\UploadedFile($file, 'karyawan.xlsx', null, null, true),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('employees', ['code' => 'IMP001', 'name' => 'Imported A', 'email' => 'impA@test.id']);
        $this->assertDatabaseHas('employees', ['code' => 'IMP002', 'name' => 'Imported B', 'email' => 'impB@test.id', 'is_active' => false]);

        @unlink($file);
    }

    public function test_import_karyawan_rejects_duplicate_kode_in_same_company()
    {
        $this->actingAs($this->user, 'admin-company');
        $cid = $this->user->company_id;
        Employee::factory()->create(['company_id' => $cid, 'code' => 'DUPCODE', 'email' => 'existing@test.id']);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Kode', 'Nama', 'Email', 'Kode Negara', 'No. Telepon', 'No. NIK', 'No. KK', 'Status (Aktif/Nonaktif)', 'Password'],
            ['DUPCODE', 'Duplikat Kode', 'new@test.id', '+62', '8333333333', '', '', 'Aktif', 'password123'],
        ]);

        $file = storage_path('app/temp/test_import_dup_kode_karyawan.xlsx');
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($file);

        $response = $this->post('/operator-perusahaan/karyawan/import', [
            'file' => new \Illuminate\Http\UploadedFile($file, 'karyawan.xlsx', null, null, true),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('employees', ['email' => 'new@test.id']);

        @unlink($file);
    }

    public function test_import_karyawan_rejects_duplicate_email_in_same_company()
    {
        $this->actingAs($this->user, 'admin-company');
        $cid = $this->user->company_id;
        Employee::factory()->create(['company_id' => $cid, 'email' => 'dup@test.id']);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Nama', 'Email', 'Kode Negara', 'No. Telepon', 'No. NIK', 'No. KK', 'Status (Aktif/Nonaktif)', 'Password'],
            ['Dup Row', 'dup@test.id', '+62', '8333333333', '', '', 'Aktif', 'password123'],
        ]);

        $file = storage_path('app/temp/test_import_dup_karyawan.xlsx');
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($file);

        $response = $this->post('/operator-perusahaan/karyawan/import', [
            'file' => new \Illuminate\Http\UploadedFile($file, 'karyawan.xlsx', null, null, true),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertStringContainsString('error', (string) session()->get('success'));

        @unlink($file);
    }

    public function test_import_karyawan_validation_fails_when_no_file()
    {
        $this->actingAs($this->user, 'admin-company');

        $response = $this->post('/operator-perusahaan/karyawan/import', []);

        $response->assertSessionHasErrors(['file']);
    }

    public function test_user_without_export_permission_cannot_export_karyawan()
    {
        $limitedUser = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($limitedUser, 'admin_perusahaan');
        // Hapus permission karyawan.export dari role
        $role = \App\Models\Role::where('scope', 'admin_perusahaan')->first();
        $perm = \App\Models\Permission::where('name', 'karyawan.export')->first();
        if ($role && $perm) {
            \DB::table('role_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $perm->id)
                ->delete();
        }

        $this->actingAs($limitedUser, 'admin-company')
            ->get('/operator-perusahaan/karyawan/export')
            ->assertStatus(403);
    }

    public function test_user_without_import_permission_cannot_import_karyawan()
    {
        $limitedUser = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($limitedUser, 'admin_perusahaan');
        $role = \App\Models\Role::where('scope', 'admin_perusahaan')->first();
        $perm = \App\Models\Permission::where('name', 'karyawan.import')->first();
        if ($role && $perm) {
            \DB::table('role_permissions')
                ->where('role_id', $role->id)
                ->where('permission_id', $perm->id)
                ->delete();
        }

        $this->actingAs($limitedUser, 'admin-company')
            ->post('/operator-perusahaan/karyawan/import', [])
            ->assertStatus(403);
    }
}
