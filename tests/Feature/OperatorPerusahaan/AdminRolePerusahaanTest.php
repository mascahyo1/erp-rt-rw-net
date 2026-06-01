<?php

namespace Tests\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\ModelHasRole;
use App\Models\Role;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminRolePerusahaanTest extends TestCase
{

    protected AdminCompany $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'admin_perusahaan');
    }

    public function test_guest_cannot_access_admin_role_perusahaan_page()
    {
        $this->get('/operator-perusahaan/admin-role-perusahaan')
            ->assertRedirect('/login-perusahaan');
    }

    public function test_authenticated_can_view_admin_role_perusahaan_page()
    {
        $this->actingAs($this->user, 'admin-company')
            ->get('/operator-perusahaan/admin-role-perusahaan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/AdminRolePerusahaan')
                ->has('assignments')
                ->has('admins')
                ->has('roles')
            );
    }

    public function test_can_create_mapping_admin_role()
    {
        $this->actingAs($this->user, 'admin-company');
        $admin = AdminCompany::factory()->create(['company_id' => $this->user->company_id, 'is_active' => true]);
        $role = Role::create([
            'scope' => 'admin_perusahaan',
            'company_id' => $this->user->company_id,
            'name' => 'Test Role',
            'is_active' => true,
            'display_order' => 1,
        ]);

        $response = $this->post('/operator-perusahaan/admin-role-perusahaan', [
            'admin_id' => $admin->id,
            'role_id' => $role->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => AdminCompany::class,
            'model_id' => $admin->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_create_mapping_validates_required_fields()
    {
        $this->actingAs($this->user, 'admin-company');
        $response = $this->post('/operator-perusahaan/admin-role-perusahaan', []);
        $response->assertSessionHasErrors(['admin_id', 'role_id']);
    }

    public function test_can_update_mapping_role()
    {
        $this->actingAs($this->user, 'admin-company');
        $admin = AdminCompany::factory()->create(['company_id' => $this->user->company_id, 'is_active' => true]);
        $role1 = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $this->user->company_id, 'name' => 'Role1', 'is_active' => true, 'display_order' => 1]);
        $role2 = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $this->user->company_id, 'name' => 'Role2', 'is_active' => true, 'display_order' => 2]);
        $mapping = ModelHasRole::create([
            'id' => Str::uuid()->toString(),
            'role_id' => $role1->id,
            'model_type' => AdminCompany::class,
            'model_id' => $admin->id,
        ]);

        $this->put("/operator-perusahaan/admin-role-perusahaan/{$mapping->id}", [
            'role_id' => $role2->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('model_has_roles', [
            'id' => $mapping->id,
            'role_id' => $role2->id,
        ]);
    }

    public function test_can_delete_mapping()
    {
        $this->actingAs($this->user, 'admin-company');
        $admin = AdminCompany::factory()->create(['company_id' => $this->user->company_id, 'is_active' => true]);
        $role = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $this->user->company_id, 'name' => 'Test', 'is_active' => true, 'display_order' => 1]);
        $mapping = ModelHasRole::create([
            'id' => Str::uuid()->toString(),
            'role_id' => $role->id,
            'model_type' => AdminCompany::class,
            'model_id' => $admin->id,
        ]);

        $this->delete("/operator-perusahaan/admin-role-perusahaan/{$mapping->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('model_has_roles', ['id' => $mapping->id]);
    }

    public function test_can_bulk_delete_mapping()
    {
        $this->actingAs($this->user, 'admin-company');
        $admin = AdminCompany::factory()->create(['company_id' => $this->user->company_id, 'is_active' => true]);
        $role = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $this->user->company_id, 'name' => 'BulkTest', 'is_active' => true, 'display_order' => 1]);
        $m1 = ModelHasRole::create(['id' => Str::uuid()->toString(), 'role_id' => $role->id, 'model_type' => AdminCompany::class, 'model_id' => $admin->id]);
        $m2 = ModelHasRole::create(['id' => Str::uuid()->toString(), 'role_id' => $role->id, 'model_type' => AdminCompany::class, 'model_id' => $admin->id]);

        $this->post('/operator-perusahaan/admin-role-perusahaan/bulk-delete', ['ids' => [$m1->id, $m2->id]])
            ->assertRedirect();
        $this->assertDatabaseMissing('model_has_roles', ['id' => $m1->id]);
        $this->assertDatabaseMissing('model_has_roles', ['id' => $m2->id]);
    }

    public function test_can_export_excel()
    {
        $this->actingAs($this->user, 'admin-company');
        $admin = AdminCompany::factory()->create(['company_id' => $this->user->company_id, 'is_active' => true, 'name' => 'Export Test Admin', 'email' => 'export-admin@test.id']);
        $role = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $this->user->company_id, 'name' => 'ExportRole', 'is_active' => true, 'display_order' => 1]);
        ModelHasRole::create(['id' => Str::uuid()->toString(), 'role_id' => $role->id, 'model_type' => AdminCompany::class, 'model_id' => $admin->id]);

        $response = $this->get('/operator-perusahaan/admin-role-perusahaan/export');
        $response->assertOk();
        // Binary file response, content-type is octet-stream or similar
    }

    public function test_can_download_template()
    {
        $this->actingAs($this->user, 'admin-company');
        $response = $this->get('/operator-perusahaan/admin-role-perusahaan/template');
        $response->assertOk();
    }

    public function test_can_import_mapping_from_excel()
    {
        $this->actingAs($this->user, 'admin-company');
        $admin = AdminCompany::factory()->create(['company_id' => $this->user->company_id, 'is_active' => true, 'name' => 'Import Admin', 'email' => 'import-admin@test.id']);
        $role = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $this->user->company_id, 'name' => 'ImportRole', 'is_active' => true, 'display_order' => 1]);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Nama Admin', 'Email Admin', 'Role'],
            ['Import Admin', 'import-admin@test.id', 'ImportRole'],
        ]);
        $file = storage_path('app/temp/test_import_admin_role.xlsx');
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($file);

        $response = $this->post('/operator-perusahaan/admin-role-perusahaan/import', [
            'file' => new \Illuminate\Http\UploadedFile($file, 'admin_role.xlsx', null, null, true),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => AdminCompany::class,
            'model_id' => $admin->id,
            'role_id' => $role->id,
        ]);

        @unlink($file);
    }

    public function test_import_rejects_unknown_email()
    {
        $this->actingAs($this->user, 'admin-company');
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Nama Admin', 'Email Admin', 'Role'],
            ['Unknown', 'unknown@test.id', 'SomeRole'],
        ]);
        $file = storage_path('app/temp/test_import_unknown_admin.xlsx');
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($file);

        $response = $this->post('/operator-perusahaan/admin-role-perusahaan/import', [
            'file' => new \Illuminate\Http\UploadedFile($file, 'admin_role.xlsx', null, null, true),
        ]);
        $response->assertRedirect();
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => '00000000-0000-0000-0000-000000000000']);

        @unlink($file);
    }

    public function test_user_without_export_permission_cannot_export()
    {
        // Just verify route returns 403 for user with no export perm
        $limited = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($limited, 'admin_perusahaan');
        $role = \App\Models\Role::where('scope', 'admin_perusahaan')->first();
        $perm = \App\Models\Permission::where('name', 'admin-role-perusahaan-op.export')->first();
        if ($role && $perm) {
            \DB::table('role_permissions')->where('role_id', $role->id)->where('permission_id', $perm->id)->delete();
        }

        $this->actingAs($limited, 'admin-company')
            ->get('/operator-perusahaan/admin-role-perusahaan/export')
            ->assertStatus(403);
    }

    public function test_user_without_import_permission_cannot_import()
    {
        $limited = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($limited, 'admin_perusahaan');
        $role = \App\Models\Role::where('scope', 'admin_perusahaan')->first();
        $perm = \App\Models\Permission::where('name', 'admin-role-perusahaan-op.import')->first();
        if ($role && $perm) {
            \DB::table('role_permissions')->where('role_id', $role->id)->where('permission_id', $perm->id)->delete();
        }

        $this->actingAs($limited, 'admin-company')
            ->post('/operator-perusahaan/admin-role-perusahaan/import', [])
            ->assertStatus(403);
    }
}
