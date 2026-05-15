<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminCompany;
use App\Models\AdminSaas;
use App\Models\Company;
use App\Models\ModelHasRole;
use App\Models\Role;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RoleAdminPerusahaanCRUDTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        $company = Company::factory()->create(['is_active' => true]);
        $role = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Role Test', 'is_active' => true, 'display_order' => 1]);
        $admin = AdminCompany::factory()->create(['company_id' => $company->id, 'is_active' => true]);
        ModelHasRole::create(['model_type' => AdminCompany::class, 'model_id' => $admin->id, 'role_id' => $role->id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-admin-perusahaan')
                ->waitForText('Role Admin Perusahaan', 10)
                ->assertPresent('table')
                ->assertSee('Tambah Role Admin')
                ->screenshot('operator-saas/role-admin-perusahaan/01-page-render/01-page');
        });
    }

    public function test_02_search(): void
    {
        $company = Company::factory()->create();
        $role = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Search Role', 'is_active' => true, 'display_order' => 1]);
        $adminA = AdminCompany::factory()->create(['company_id' => $company->id, 'name' => 'Budi Search Admin', 'is_active' => true]);
        $adminB = AdminCompany::factory()->create(['company_id' => $company->id, 'name' => 'Andi Other Admin', 'is_active' => true]);
        ModelHasRole::create(['model_type' => AdminCompany::class, 'model_id' => $adminA->id, 'role_id' => $role->id]);
        ModelHasRole::create(['model_type' => AdminCompany::class, 'model_id' => $adminB->id, 'role_id' => $role->id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-admin-perusahaan?per_page=100')
                ->waitForText('Role Admin Perusahaan', 10)
                ->screenshot('operator-saas/role-admin-perusahaan/02-search/01-before')
                ->type('input[placeholder="Cari..."]', 'Budi')
                ->keys('input[placeholder="Cari..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/role-admin-perusahaan/02-search/02-result')
                ->assertSee('Budi Search')
                ->assertDontSee('Andi Other');
        });
    }

    public function test_03_delete(): void
    {
        $company = Company::factory()->create();
        $role = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Del Role', 'is_active' => true, 'display_order' => 1]);
        $admin = AdminCompany::factory()->create(['company_id' => $company->id, 'name' => 'Delete Target Admin', 'is_active' => true]);
        $assignment = ModelHasRole::create(['model_type' => AdminCompany::class, 'model_id' => $admin->id, 'role_id' => $role->id]);

        $this->browse(function (Browser $browser) use ($assignment) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-admin-perusahaan?per_page=100')
                ->waitForText('Role Admin Perusahaan', 10)
                ->screenshot('operator-saas/role-admin-perusahaan/03-delete/01-before');

            $browser->type('input[placeholder="Cari..."]', 'Delete Target')
                ->keys('input[placeholder="Cari..."]', '{enter}')
                ->pause(1500)
                ->waitForText('Delete Target', 10);

            $browser->click('button[title="Hapus"]')
                ->waitForText('Hapus Role Admin Perusahaan?', 10)
                ->screenshot('operator-saas/role-admin-perusahaan/03-delete/02-modal')
                ->press('Hapus')
                ->pause(2000);

            $this->assertDatabaseMissing('model_has_roles', ['id' => $assignment->id]);

            $browser->screenshot('operator-saas/role-admin-perusahaan/03-delete/03-deleted');
        });
    }

    public function test_04_bulk_delete(): void
    {
        $company = Company::factory()->create();
        $role = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Bulk Role', 'is_active' => true, 'display_order' => 1]);

        $a = ModelHasRole::create(['model_type' => AdminCompany::class, 'model_id' => AdminCompany::factory()->create(['company_id' => $company->id, 'name' => 'Bulk A Admin'])->id, 'role_id' => $role->id]);
        $b = ModelHasRole::create(['model_type' => AdminCompany::class, 'model_id' => AdminCompany::factory()->create(['company_id' => $company->id, 'name' => 'Bulk B Admin'])->id, 'role_id' => $role->id]);
        $c = ModelHasRole::create(['model_type' => AdminCompany::class, 'model_id' => AdminCompany::factory()->create(['company_id' => $company->id, 'name' => 'Bulk C Admin'])->id, 'role_id' => $role->id]);

        $this->browse(function (Browser $browser) use ($a, $b, $c) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-admin-perusahaan?per_page=100')
                ->waitForText('Role Admin Perusahaan', 10);

            $browser->type('input[placeholder="Cari..."]', 'Bulk')
                ->keys('input[placeholder="Cari..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/role-admin-perusahaan/04-bulk-delete/01-before');

            $browser->assertSee('Bulk A')
                ->assertSee('Bulk B')
                ->assertSee('Bulk C');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-saas/role-admin-perusahaan/04-bulk-delete/02-selected');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Hapus') && b.querySelector('.fa-trash-alt')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/role-admin-perusahaan/04-bulk-delete/03-after')
                ->assertSee('Role Admin Perusahaan');

            $this->assertDatabaseMissing('model_has_roles', ['id' => $a->id]);
            $this->assertDatabaseMissing('model_has_roles', ['id' => $b->id]);
            $this->assertDatabaseMissing('model_has_roles', ['id' => $c->id]);
        });
    }

    public function test_05_create(): void
    {
        $company = Company::factory()->create(['name' => 'Company For Role']);
        $role = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Assign Role', 'is_active' => true, 'display_order' => 1]);
        AdminCompany::factory()->create(['company_id' => $company->id, 'name' => 'Admin To Assign', 'is_active' => true]);

        $loginAdmin = AdminSaas::first()
            ?? AdminSaas::factory()->create(['name' => 'Login Admin', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($loginAdmin) {
            $browser->loginAs($loginAdmin, 'web')
                ->visit('/operator-saas/role-admin-perusahaan')
                ->waitForText('Role Admin Perusahaan', 10);

            $browser->press('Tambah Role Admin')
                ->waitForText('Tambah Role Admin Perusahaan', 10)
                ->screenshot('operator-saas/role-admin-perusahaan/05-create/01-modal');

            $browser->screenshot('operator-saas/role-admin-perusahaan/05-create/02-filled');
        });
    }
}
