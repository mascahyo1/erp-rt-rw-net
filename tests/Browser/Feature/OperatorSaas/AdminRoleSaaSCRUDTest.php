<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminSaas;
use App\Models\ModelHasRole;
use App\Models\Role;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminRoleSaaSCRUDTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        $role = Role::create(['scope' => 'operator_saas', 'name' => 'SaaS Role', 'is_active' => true, 'display_order' => 1]);
        $admin = AdminSaas::factory()->create(['name' => 'Assigned Admin', 'is_active' => true]);
        ModelHasRole::create(['model_type' => AdminSaas::class, 'model_id' => $admin->id, 'role_id' => $role->id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-role-saas')
                ->waitForText('Role Admin SaaS', 10)
                ->assertPresent('table')
                ->assertSee('Tambah Role Admin')
                ->screenshot('operator-saas/admin-role-saas/01-page-render/01-page');
        });
    }

    public function test_02_search(): void
    {
        $role = Role::create(['scope' => 'operator_saas', 'name' => 'Search SaaS Role', 'is_active' => true, 'display_order' => 1]);
        $adminA = AdminSaas::factory()->create(['name' => 'Budi SaaS Admin', 'is_active' => true]);
        $adminB = AdminSaas::factory()->create(['name' => 'Andi Other SaaS', 'is_active' => true]);
        ModelHasRole::create(['model_type' => AdminSaas::class, 'model_id' => $adminA->id, 'role_id' => $role->id]);
        ModelHasRole::create(['model_type' => AdminSaas::class, 'model_id' => $adminB->id, 'role_id' => $role->id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-role-saas?per_page=100')
                ->waitForText('Role Admin SaaS', 10)
                ->screenshot('operator-saas/admin-role-saas/02-search/01-before')
                ->type('input[placeholder="Cari..."]', 'Budi')
                ->keys('input[placeholder="Cari..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/admin-role-saas/02-search/02-result')
                ->assertSee('Budi SaaS')
                ->assertDontSee('Andi Other');
        });
    }

    public function test_03_delete(): void
    {
        $role = Role::create(['scope' => 'operator_saas', 'name' => 'Del SaaS Role', 'is_active' => true, 'display_order' => 1]);
        $admin = AdminSaas::factory()->create(['name' => 'Delete Target SaaS', 'is_active' => true]);
        $assignment = ModelHasRole::create(['model_type' => AdminSaas::class, 'model_id' => $admin->id, 'role_id' => $role->id]);

        $this->browse(function (Browser $browser) use ($assignment) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-role-saas?per_page=100')
                ->waitForText('Role Admin SaaS', 10)
                ->screenshot('operator-saas/admin-role-saas/03-delete/01-before');

            $browser->type('input[placeholder="Cari..."]', 'Delete Target')
                ->keys('input[placeholder="Cari..."]', '{enter}')
                ->pause(1500)
                ->waitForText('Delete Target', 10);

            $browser->click('button[title="Hapus"]')
                ->waitForText('Hapus Role Admin SaaS?', 10)
                ->screenshot('operator-saas/admin-role-saas/03-delete/02-modal')
                ->press('Hapus')
                ->pause(2000);

            $this->assertDatabaseMissing('model_has_roles', ['id' => $assignment->id]);

            $browser->screenshot('operator-saas/admin-role-saas/03-delete/03-deleted');
        });
    }

    public function test_04_bulk_delete(): void
    {
        $role = Role::create(['scope' => 'operator_saas', 'name' => 'Bulk SaaS Role', 'is_active' => true, 'display_order' => 1]);

        $a = ModelHasRole::create(['model_type' => AdminSaas::class, 'model_id' => AdminSaas::factory()->create(['name' => 'Bulk A SaaS'])->id, 'role_id' => $role->id]);
        $b = ModelHasRole::create(['model_type' => AdminSaas::class, 'model_id' => AdminSaas::factory()->create(['name' => 'Bulk B SaaS'])->id, 'role_id' => $role->id]);
        $c = ModelHasRole::create(['model_type' => AdminSaas::class, 'model_id' => AdminSaas::factory()->create(['name' => 'Bulk C SaaS'])->id, 'role_id' => $role->id]);

        $this->browse(function (Browser $browser) use ($a, $b, $c) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-role-saas?per_page=100')
                ->waitForText('Role Admin SaaS', 10);

            $browser->type('input[placeholder="Cari..."]', 'Bulk')
                ->keys('input[placeholder="Cari..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/admin-role-saas/04-bulk-delete/01-before');

            $browser->assertSee('Bulk A')
                ->assertSee('Bulk B')
                ->assertSee('Bulk C');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-saas/admin-role-saas/04-bulk-delete/02-selected');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Hapus') && b.querySelector('.fa-trash-alt')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/admin-role-saas/04-bulk-delete/03-after')
                ->assertSee('Role Admin SaaS');

            $this->assertDatabaseMissing('model_has_roles', ['id' => $a->id]);
            $this->assertDatabaseMissing('model_has_roles', ['id' => $b->id]);
            $this->assertDatabaseMissing('model_has_roles', ['id' => $c->id]);
        });
    }

    public function test_05_create(): void
    {
        Role::create(['scope' => 'operator_saas', 'name' => 'SaaS Assign Role', 'is_active' => true, 'display_order' => 1]);
        AdminSaas::factory()->create(['name' => 'Admin To Assign SaaS', 'is_active' => true]);

        $loginAdmin = AdminSaas::first()
            ?? AdminSaas::factory()->create(['name' => 'Login Admin', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($loginAdmin) {
            $browser->loginAs($loginAdmin, 'web')
                ->visit('/operator-saas/admin-role-saas')
                ->waitForText('Role Admin SaaS', 10);

            $browser->press('Tambah Role Admin')
                ->waitForText('Tambah Role Admin SaaS', 10)
                ->screenshot('operator-saas/admin-role-saas/05-create/01-modal');

            $browser->screenshot('operator-saas/admin-role-saas/05-create/02-filled');
        });
    }
}
