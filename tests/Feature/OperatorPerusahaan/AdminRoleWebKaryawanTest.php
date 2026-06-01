<?php

namespace Tests\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\Employee;
use App\Models\ModelHasRole;
use App\Models\Role;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminRoleWebKaryawanTest extends TestCase
{

    protected AdminCompany $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'admin_perusahaan');
    }

    public function test_guest_cannot_access_admin_role_web_karyawan_page()
    {
        $this->get('/operator-perusahaan/admin-role-web-karyawan')
            ->assertRedirect('/login-perusahaan');
    }

    public function test_authenticated_can_view_admin_role_web_karyawan_page()
    {
        $this->actingAs($this->user, 'admin-company')
            ->get('/operator-perusahaan/admin-role-web-karyawan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/AdminRoleWebKaryawan')
                ->has('assignments')
                ->has('karyawans')
                ->has('roles')
            );
    }

    public function test_can_create_mapping_karyawan_role()
    {
        $this->actingAs($this->user, 'admin-company');
        $karyawan = Employee::factory()->create(['company_id' => $this->user->company_id, 'is_active' => true]);
        $role = Role::create([
            'scope' => 'karyawan_perusahaan',
            'company_id' => $this->user->company_id,
            'name' => 'Test Karyawan Role',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $response = $this->post('/operator-perusahaan/admin-role-web-karyawan', [
            'karyawan_id' => $karyawan->id,
            'role_id' => $role->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => Employee::class,
            'model_id' => $karyawan->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_create_mapping_validates_required_fields()
    {
        $this->actingAs($this->user, 'admin-company');
        $response = $this->post('/operator-perusahaan/admin-role-web-karyawan', []);
        $response->assertSessionHasErrors(['karyawan_id', 'role_id']);
    }

    public function test_can_update_mapping_karyawan_role()
    {
        $this->actingAs($this->user, 'admin-company');
        $karyawan = Employee::factory()->create(['company_id' => $this->user->company_id, 'is_active' => true]);
        $role1 = Role::create(['scope' => 'karyawan_perusahaan', 'company_id' => $this->user->company_id, 'name' => 'KRole1', 'is_active' => true, 'display_order' => 1]);
        $role2 = Role::create(['scope' => 'karyawan_perusahaan', 'company_id' => $this->user->company_id, 'name' => 'KRole2', 'is_active' => true, 'display_order' => 2]);
        $mapping = ModelHasRole::create([
            'id' => Str::uuid()->toString(),
            'role_id' => $role1->id,
            'model_type' => Employee::class,
            'model_id' => $karyawan->id,
        ]);

        $this->put("/operator-perusahaan/admin-role-web-karyawan/{$mapping->id}", [
            'role_id' => $role2->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('model_has_roles', [
            'id' => $mapping->id,
            'role_id' => $role2->id,
        ]);
    }

    public function test_can_delete_mapping_karyawan()
    {
        $this->actingAs($this->user, 'admin-company');
        $karyawan = Employee::factory()->create(['company_id' => $this->user->company_id, 'is_active' => true]);
        $role = Role::create(['scope' => 'karyawan_perusahaan', 'company_id' => $this->user->company_id, 'name' => 'KRoleDel', 'is_active' => true, 'display_order' => 1]);
        $mapping = ModelHasRole::create([
            'id' => Str::uuid()->toString(),
            'role_id' => $role->id,
            'model_type' => Employee::class,
            'model_id' => $karyawan->id,
        ]);

        $this->delete("/operator-perusahaan/admin-role-web-karyawan/{$mapping->id}")
            ->assertRedirect();
        $this->assertDatabaseMissing('model_has_roles', ['id' => $mapping->id]);
    }

    public function test_can_export_excel_karyawan()
    {
        $this->actingAs($this->user, 'admin-company');
        $karyawan = Employee::factory()->create(['company_id' => $this->user->company_id, 'is_active' => true, 'name' => 'Export Karyawan', 'email' => 'export-karyawan@test.id']);
        $role = Role::create(['scope' => 'karyawan_perusahaan', 'company_id' => $this->user->company_id, 'name' => 'KExportRole', 'is_active' => true, 'display_order' => 1]);
        ModelHasRole::create(['id' => Str::uuid()->toString(), 'role_id' => $role->id, 'model_type' => Employee::class, 'model_id' => $karyawan->id]);

        $response = $this->get('/operator-perusahaan/admin-role-web-karyawan/export');
        $response->assertOk();
    }

    public function test_can_download_template_karyawan()
    {
        $this->actingAs($this->user, 'admin-company');
        $response = $this->get('/operator-perusahaan/admin-role-web-karyawan/template');
        $response->assertOk();
    }

    public function test_can_import_mapping_karyawan_from_excel()
    {
        $this->actingAs($this->user, 'admin-company');
        $karyawan = Employee::factory()->create(['company_id' => $this->user->company_id, 'is_active' => true, 'name' => 'Import Karyawan', 'email' => 'import-karyawan@test.id']);
        $role = Role::create(['scope' => 'karyawan_perusahaan', 'company_id' => $this->user->company_id, 'name' => 'KImportRole', 'is_active' => true, 'display_order' => 1]);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Nama Karyawan', 'Email Karyawan', 'Role'],
            ['Import Karyawan', 'import-karyawan@test.id', 'KImportRole'],
        ]);
        $file = storage_path('app/temp/test_import_karyawan_role.xlsx');
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($file);

        $response = $this->post('/operator-perusahaan/admin-role-web-karyawan/import', [
            'file' => new \Illuminate\Http\UploadedFile($file, 'karyawan_role.xlsx', null, null, true),
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => Employee::class,
            'model_id' => $karyawan->id,
            'role_id' => $role->id,
        ]);

        @unlink($file);
    }

    public function test_user_without_export_permission_cannot_export_karyawan()
    {
        $limited = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($limited, 'admin_perusahaan');
        $role = \App\Models\Role::where('scope', 'admin_perusahaan')->first();
        $perm = \App\Models\Permission::where('name', 'admin-role-web-karyawan.export')->first();
        if ($role && $perm) {
            \DB::table('role_permissions')->where('role_id', $role->id)->where('permission_id', $perm->id)->delete();
        }

        $this->actingAs($limited, 'admin-company')
            ->get('/operator-perusahaan/admin-role-web-karyawan/export')
            ->assertStatus(403);
    }

    public function test_user_without_import_permission_cannot_import_karyawan()
    {
        $limited = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($limited, 'admin_perusahaan');
        $role = \App\Models\Role::where('scope', 'admin_perusahaan')->first();
        $perm = \App\Models\Permission::where('name', 'admin-role-web-karyawan.import')->first();
        if ($role && $perm) {
            \DB::table('role_permissions')->where('role_id', $role->id)->where('permission_id', $perm->id)->delete();
        }

        $this->actingAs($limited, 'admin-company')
            ->post('/operator-perusahaan/admin-role-web-karyawan/import', [])
            ->assertStatus(403);
    }
}
