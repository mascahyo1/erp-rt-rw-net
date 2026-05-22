<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminSaas;
use App\Models\Company;
use App\Models\Role;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RolePerusahaanCRUDTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        $company = Company::factory()->create(['is_active' => true]);
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Role A', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Role B', 'is_active' => true, 'display_order' => 2]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-perusahaan')
                ->waitForText('Role Perusahaan', 10)
                ->assertPresent('table')
                ->assertSee('Tambah Role')
                ->screenshot('operator-saas/role-perusahaan/01-page-render/01-page');
        });
    }

    public function test_02_search(): void
    {
        $company = Company::factory()->create();
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Role Cari A', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Role Lain B', 'is_active' => true, 'display_order' => 2]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-perusahaan?per_page=100')
                ->waitForText('Role Perusahaan', 10)
                ->screenshot('operator-saas/role-perusahaan/02-search/01-before')
                ->type('input[placeholder="Cari role..."]', 'Cari')
                ->keys('input[placeholder="Cari role..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/role-perusahaan/02-search/02-result')
                ->assertSee('Role Cari A')
                ->assertDontSee('Role Lain B');
        });
    }

    public function test_03_filter_status(): void
    {
        $company = Company::factory()->create();
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Role Aktif', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Role Nonaktif', 'is_active' => false, 'display_order' => 2]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-perusahaan?per_page=100')
                ->waitForText('Role Perusahaan', 10)
                ->screenshot('operator-saas/role-perusahaan/03-filter-status/01-all');

            $browser->select('select:first-of-type', 'Aktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/role-perusahaan/03-filter-status/02-aktif')
                ->assertSee('Role Aktif')
                ->assertDontSee('Role Nonaktif');

            $browser->select('select:first-of-type', 'Nonaktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/role-perusahaan/03-filter-status/03-nonaktif')
                ->assertSee('Role Nonaktif')
                ->assertDontSee('Role Aktif');
        });
    }

    public function test_04_sort(): void
    {
        $company = Company::factory()->create();
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'AAA Role', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'ZZZ Role', 'is_active' => true, 'display_order' => 2]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-perusahaan?per_page=100')
                ->waitForText('Role Perusahaan', 10)
                ->screenshot('operator-saas/role-perusahaan/04-sort/01-before');

            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/role-perusahaan/04-sort/02-name-asc');

            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/role-perusahaan/04-sort/03-name-desc');

            $browser->script("document.querySelectorAll('th')[3].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/role-perusahaan/04-sort/04-status-asc')
                ->assertSee('Role Perusahaan');
        });
    }

    public function test_05_delete(): void
    {
        $company = Company::factory()->create();
        $role = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Delete Role Target', 'is_active' => true, 'display_order' => 1]);

        $this->browse(function (Browser $browser) use ($role) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-perusahaan?per_page=100')
                ->waitForText('Role Perusahaan', 10)
                ->screenshot('operator-saas/role-perusahaan/05-delete/01-before');

            $browser->type('input[placeholder="Cari role..."]', 'Delete Role')
                ->keys('input[placeholder="Cari role..."]', '{enter}')
                ->pause(1500)
                ->waitForText('Delete Role', 10);

            $browser->click('button[title="Hapus"]')
                ->waitForText('Hapus Role Perusahaan?', 10)
                ->screenshot('operator-saas/role-perusahaan/05-delete/02-modal')
                ->press('Hapus')
                ->pause(2000);

            $this->assertSoftDeleted($role);

            $browser->screenshot('operator-saas/role-perusahaan/05-delete/03-deleted');
        });
    }

    public function test_06_bulk_delete(): void
    {
        $company = Company::factory()->create();
        $a = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Bulk Del A Role', 'is_active' => true, 'display_order' => 1]);
        $b = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Bulk Del B Role', 'is_active' => true, 'display_order' => 2]);
        $c = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Bulk Del C Role', 'is_active' => true, 'display_order' => 3]);

        $this->browse(function (Browser $browser) use ($a, $b, $c) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-perusahaan?per_page=100')
                ->waitForText('Role Perusahaan', 10);

            $browser->type('input[placeholder="Cari role..."]', 'Bulk Del')
                ->keys('input[placeholder="Cari role..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/role-perusahaan/06-bulk-delete/01-before');

            $browser->assertSee('Bulk Del A')
                ->assertSee('Bulk Del B')
                ->assertSee('Bulk Del C');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-saas/role-perusahaan/06-bulk-delete/02-selected');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Hapus') && b.querySelector('.fa-trash-alt')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/role-perusahaan/06-bulk-delete/03-after')
                ->assertSee('Role Perusahaan');

            $this->assertSoftDeleted($a);
            $this->assertSoftDeleted($b);
            $this->assertSoftDeleted($c);

            $browser->type('input[placeholder="Cari role..."]', 'Bulk Del')
                ->keys('input[placeholder="Cari role..."]', '{enter}')
                ->pause(1500)
                ->assertDontSee('Bulk Del A')
                ->assertDontSee('Bulk Del B')
                ->assertDontSee('Bulk Del C');
        });
    }

    public function test_07_bulk_toggle_status(): void
    {
        $company = Company::factory()->create();
        $a = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Bulk Stat X Role', 'is_active' => true, 'display_order' => 1]);
        $b = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Bulk Stat Y Role', 'is_active' => true, 'display_order' => 2]);

        $this->browse(function (Browser $browser) use ($a, $b) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-perusahaan?per_page=100')
                ->waitForText('Role Perusahaan', 10);

            $browser->type('input[placeholder="Cari role..."]', 'Bulk Stat')
                ->keys('input[placeholder="Cari role..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/role-perusahaan/07-bulk-status/01-before');

            $browser->assertSee('Bulk Stat X')
                ->assertSee('Bulk Stat Y');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-saas/role-perusahaan/07-bulk-status/02-selected');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Nonaktifkan')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/role-perusahaan/07-bulk-status/03-nonaktif');

            $this->assertFalse((bool) $a->fresh()->is_active);
            $this->assertFalse((bool) $b->fresh()->is_active);

            $browser->select('select:first-of-type', 'Nonaktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/role-perusahaan/07-bulk-status/04-filter-nonaktif');

            $browser->assertSee('Bulk Stat X')
                ->assertSee('Bulk Stat Y');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500);

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Aktifkan')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/role-perusahaan/07-bulk-status/05-aktif');

            $this->assertTrue((bool) $a->fresh()->is_active);
            $this->assertTrue((bool) $b->fresh()->is_active);
        });
    }

    public function test_08_pagination_and_per_page(): void
    {
        $company = Company::factory()->create();
        for ($i = 1; $i <= 30; $i++) {
            Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => "Role Page {$i}", 'is_active' => true, 'display_order' => $i]);
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web');

            $browser->visit('/operator-saas/role-perusahaan?per_page=5')
                ->waitForText('Role Perusahaan', 10)
                ->screenshot('operator-saas/role-perusahaan/08-pagination/01-per-5-page-1')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(5, $rowCount[0] ?? 0, 'per_page=5 should show 5 rows');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.trim() === '2') b.click();
                });
            ");
            $browser->pause(1500)
                ->screenshot('operator-saas/role-perusahaan/08-pagination/02-page-2')
                ->assertSee('Role Perusahaan');

            $browser->script("
                var sel = document.querySelector('select');
                sel.value = '10';
                sel.dispatchEvent(new Event('change', {bubbles: true}));
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/role-perusahaan/08-pagination/03-per-10')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(10, $rowCount[0] ?? 0, 'per_page=10 should show 10 rows');

            $browser->script("
                var sel = document.querySelector('select');
                sel.value = '25';
                sel.dispatchEvent(new Event('change', {bubbles: true}));
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/role-perusahaan/08-pagination/04-per-25')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertGreaterThanOrEqual(1, $rowCount[0] ?? 0, 'per_page=25 should show records');

            $browser->assertSee('Role Perusahaan');
        });
    }

    public function test_09_bulk_restore(): void
    {
        $company = Company::factory()->create();
        $c = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Restore C Role', 'is_active' => true, 'display_order' => 1]);
        $d = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Restore D Role', 'is_active' => true, 'display_order' => 2]);
        $c->delete();
        $d->delete();

        $this->assertSoftDeleted($c);
        $this->assertSoftDeleted($d);

        $this->browse(function (Browser $browser) use ($c, $d) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-perusahaan?per_page=100&terhapus=ya')
                ->waitForText('Role Perusahaan', 10)
                ->screenshot('operator-saas/role-perusahaan/09-bulk-restore/01-terhapus-list')
                ->assertSee('Restore C')
                ->assertSee('Restore D');

            $browser->check('tbody tr:nth-of-type(1) input[type="checkbox"]')
                ->check('tbody tr:nth-of-type(2) input[type="checkbox"]')
                ->pause(500)
                ->screenshot('operator-saas/role-perusahaan/09-bulk-restore/02-selected')
                ->assertSee('Pulihkan')
                ->press('Pulihkan')
                ->pause(2000)
                ->screenshot('operator-saas/role-perusahaan/09-bulk-restore/03-restored');
        });

        $this->assertNotSoftDeleted($c);
        $this->assertNotSoftDeleted($d);
    }

    public function test_10_create(): void
    {
        $loginAdmin = AdminSaas::first()
            ?? AdminSaas::factory()->create(['name' => 'Login Admin', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($loginAdmin) {
            $browser->loginAs($loginAdmin, 'web')
                ->visit('/operator-saas/role-perusahaan')
                ->waitForText('Role Perusahaan', 10);

            $browser->press('Tambah Role')
                ->waitForText('Tambah Role Perusahaan', 10)
                ->screenshot('operator-saas/role-perusahaan/10-create/01-modal');

            $browser->type('input[placeholder="Nama role"]', 'Role Create Test')
                ->screenshot('operator-saas/role-perusahaan/10-create/02-filled');

            $browser->press('Simpan')
                ->pause(2000)
                ->screenshot('operator-saas/role-perusahaan/10-create/03-after');
        });

        $this->assertDatabaseHas('roles', [
            'scope' => 'admin_perusahaan',
            'name' => 'Role Create Test',
            'is_active' => true,
        ]);
    }

    public function test_11_detail(): void
    {
        $company = Company::factory()->create();
        $record = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Detail Role Test', 'is_active' => true, 'display_order' => 1]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-perusahaan?per_page=100')
                ->waitForText('Role Perusahaan', 10);

            $browser->type('input[placeholder="Cari role..."]', 'Detail Role')
                ->keys('input[placeholder="Cari role..."]', '{enter}')
                ->pause(1500)
                ->assertSee('Detail Role Test');

            $browser->click('button[title="Detail"]')
                ->waitForText('Detail Role Perusahaan', 10)
                ->screenshot('operator-saas/role-perusahaan/11-detail/01-modal');

            $browser->assertSee('Detail Role Test')
                ->assertSee('Aktif');

            $browser->press('Tutup')
                ->pause(500)
                ->screenshot('operator-saas/role-perusahaan/11-detail/02-closed');
        });
    }

    public function test_12_edit(): void
    {
        $company = Company::factory()->create();
        $record = Role::create(['scope' => 'admin_perusahaan', 'company_id' => $company->id, 'name' => 'Edit Before Role', 'is_active' => true, 'display_order' => 1]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-perusahaan?per_page=100')
                ->waitForText('Role Perusahaan', 10);

            $browser->type('input[placeholder="Cari role..."]', 'Edit Before')
                ->keys('input[placeholder="Cari role..."]', '{enter}')
                ->pause(1500)
                ->assertSee('Edit Before Role');

            $browser->click('button[title="Edit"]')
                ->waitForText('Edit Role Perusahaan', 10)
                ->screenshot('operator-saas/role-perusahaan/12-edit/01-modal');

            $browser->clear('input[placeholder="Nama role"]')
                ->type('input[placeholder="Nama role"]', 'Edit After Role');

            $browser->screenshot('operator-saas/role-perusahaan/12-edit/02-modified');

            $browser->press('Update')
                ->pause(2000)
                ->screenshot('operator-saas/role-perusahaan/12-edit/03-after');
        });

        $this->assertDatabaseHas('roles', [
            'id' => $record->id,
            'name' => 'Edit After Role',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        Role::onlyTrashed()->forceDelete();

        Role::query()
            ->where(function ($q) {
                $q->where('name', 'like', '%Test%')
                  ->orWhere('name', 'like', '%Bulk%')
                  ->orWhere('name', 'like', '%Sort%')
                  ->orWhere('name', 'like', '%Search%')
                  ->orWhere('name', 'like', '%Create%')
                  ->orWhere('name', 'like', '%Delete%')
                  ->orWhere('name', 'like', '%Detail%')
                  ->orWhere('name', 'like', '%Edit%')
                  ->orWhere('name', 'like', '%Aktif%')
                  ->orWhere('name', 'like', '%Nonaktif%');
            })
            ->forceDelete();

        parent::tearDownAfterClass();
    }
}
