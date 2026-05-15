<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminSaas;
use App\Models\Role;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RoleSaaSCRUDTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Role::create(['scope' => 'operator_saas', 'name' => 'SaaS Role A', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'operator_saas', 'name' => 'SaaS Role B', 'is_active' => true, 'display_order' => 2]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-saas')
                ->waitForText('Role SaaS', 10)
                ->assertPresent('table')
                ->assertSee('Tambah Role')
                ->screenshot('operator-saas/role-saas/01-page-render/01-page');
        });
    }

    public function test_02_search(): void
    {
        Role::create(['scope' => 'operator_saas', 'name' => 'SaaS Cari A', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'operator_saas', 'name' => 'SaaS Lain B', 'is_active' => true, 'display_order' => 2]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-saas?per_page=100')
                ->waitForText('Role SaaS', 10)
                ->screenshot('operator-saas/role-saas/02-search/01-before')
                ->type('input[placeholder="Cari role..."]', 'Cari')
                ->keys('input[placeholder="Cari role..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/role-saas/02-search/02-result')
                ->assertSee('SaaS Cari A')
                ->assertDontSee('SaaS Lain B');
        });
    }

    public function test_03_filter_status(): void
    {
        Role::create(['scope' => 'operator_saas', 'name' => 'SaaS Aktif', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'operator_saas', 'name' => 'SaaS Nonaktif', 'is_active' => false, 'display_order' => 2]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-saas?per_page=100')
                ->waitForText('Role SaaS', 10)
                ->screenshot('operator-saas/role-saas/03-filter-status/01-all');

            $browser->select('select:first-of-type', 'Aktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/role-saas/03-filter-status/02-aktif')
                ->assertSee('SaaS Aktif')
                ->assertDontSee('SaaS Nonaktif');

            $browser->select('select:first-of-type', 'Nonaktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/role-saas/03-filter-status/03-nonaktif')
                ->assertSee('SaaS Nonaktif')
                ->assertDontSee('SaaS Aktif');
        });
    }

    public function test_04_sort(): void
    {
        Role::create(['scope' => 'operator_saas', 'name' => 'AAA SaaS', 'is_active' => true, 'display_order' => 1]);
        Role::create(['scope' => 'operator_saas', 'name' => 'ZZZ SaaS', 'is_active' => true, 'display_order' => 2]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-saas?per_page=100')
                ->waitForText('Role SaaS', 10)
                ->screenshot('operator-saas/role-saas/04-sort/01-before');

            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/role-saas/04-sort/02-name-asc');

            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/role-saas/04-sort/03-name-desc')
                ->assertSee('Role SaaS');
        });
    }

    public function test_05_delete(): void
    {
        $role = Role::create(['scope' => 'operator_saas', 'name' => 'Delete SaaS Role', 'is_active' => true, 'display_order' => 1]);

        $this->browse(function (Browser $browser) use ($role) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-saas?per_page=100')
                ->waitForText('Role SaaS', 10)
                ->screenshot('operator-saas/role-saas/05-delete/01-before');

            $browser->type('input[placeholder="Cari role..."]', 'Delete SaaS')
                ->keys('input[placeholder="Cari role..."]', '{enter}')
                ->pause(1500)
                ->waitForText('Delete SaaS', 10);

            $browser->click('button[title="Hapus"]')
                ->waitForText('Hapus Role SaaS?', 10)
                ->screenshot('operator-saas/role-saas/05-delete/02-modal')
                ->press('Hapus')
                ->pause(2000);

            $this->assertSoftDeleted($role);

            $browser->screenshot('operator-saas/role-saas/05-delete/03-deleted');
        });
    }

    public function test_06_bulk_delete(): void
    {
        $a = Role::create(['scope' => 'operator_saas', 'name' => 'Bulk Del SaaS A', 'is_active' => true, 'display_order' => 1]);
        $b = Role::create(['scope' => 'operator_saas', 'name' => 'Bulk Del SaaS B', 'is_active' => true, 'display_order' => 2]);
        $c = Role::create(['scope' => 'operator_saas', 'name' => 'Bulk Del SaaS C', 'is_active' => true, 'display_order' => 3]);

        $this->browse(function (Browser $browser) use ($a, $b, $c) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-saas?per_page=100')
                ->waitForText('Role SaaS', 10);

            $browser->type('input[placeholder="Cari role..."]', 'Bulk Del')
                ->keys('input[placeholder="Cari role..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/role-saas/06-bulk-delete/01-before');

            $browser->assertSee('Bulk Del SaaS A')
                ->assertSee('Bulk Del SaaS B')
                ->assertSee('Bulk Del SaaS C');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-saas/role-saas/06-bulk-delete/02-selected');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Hapus') && b.querySelector('.fa-trash-alt')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/role-saas/06-bulk-delete/03-after')
                ->assertSee('Role SaaS');

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
        $a = Role::create(['scope' => 'operator_saas', 'name' => 'Bulk Stat SaaS X', 'is_active' => true, 'display_order' => 1]);
        $b = Role::create(['scope' => 'operator_saas', 'name' => 'Bulk Stat SaaS Y', 'is_active' => true, 'display_order' => 2]);

        $this->browse(function (Browser $browser) use ($a, $b) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-saas?per_page=100')
                ->waitForText('Role SaaS', 10);

            $browser->type('input[placeholder="Cari role..."]', 'Bulk Stat')
                ->keys('input[placeholder="Cari role..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/role-saas/07-bulk-status/01-before');

            $browser->assertSee('Bulk Stat SaaS X')
                ->assertSee('Bulk Stat SaaS Y');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-saas/role-saas/07-bulk-status/02-selected');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Nonaktifkan')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/role-saas/07-bulk-status/03-nonaktif');

            $this->assertFalse((bool) $a->fresh()->is_active);
            $this->assertFalse((bool) $b->fresh()->is_active);

            $browser->select('select:first-of-type', 'Nonaktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/role-saas/07-bulk-status/04-filter-nonaktif');

            $browser->assertSee('Bulk Stat SaaS X')
                ->assertSee('Bulk Stat SaaS Y');

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
                ->screenshot('operator-saas/role-saas/07-bulk-status/05-aktif');

            $this->assertTrue((bool) $a->fresh()->is_active);
            $this->assertTrue((bool) $b->fresh()->is_active);
        });
    }

    public function test_08_pagination_and_per_page(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Role::create(['scope' => 'operator_saas', 'name' => "SaaS Page {$i}", 'is_active' => true, 'display_order' => $i]);
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web');

            $browser->visit('/operator-saas/role-saas?per_page=5')
                ->waitForText('Role SaaS', 10)
                ->screenshot('operator-saas/role-saas/08-pagination/01-per-5-page-1')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(5, $rowCount[0] ?? 0, 'per_page=5 should show 5 rows');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.trim() === '2') b.click();
                });
            ");
            $browser->pause(1500)
                ->screenshot('operator-saas/role-saas/08-pagination/02-page-2')
                ->assertSee('Role SaaS');

            $browser->script("
                var sel = document.querySelector('select');
                sel.value = '10';
                sel.dispatchEvent(new Event('change', {bubbles: true}));
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/role-saas/08-pagination/03-per-10')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(10, $rowCount[0] ?? 0, 'per_page=10 should show 10 rows');

            $browser->script("
                var sel = document.querySelector('select');
                sel.value = '25';
                sel.dispatchEvent(new Event('change', {bubbles: true}));
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/role-saas/08-pagination/04-per-25')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertGreaterThanOrEqual(1, $rowCount[0] ?? 0, 'per_page=25 should show records');

            $browser->assertSee('Role SaaS');
        });
    }

    public function test_09_bulk_restore(): void
    {
        $c = Role::create(['scope' => 'operator_saas', 'name' => 'Restore C SaaS', 'is_active' => true, 'display_order' => 1]);
        $d = Role::create(['scope' => 'operator_saas', 'name' => 'Restore D SaaS', 'is_active' => true, 'display_order' => 2]);
        $c->delete();
        $d->delete();

        $this->assertSoftDeleted($c);
        $this->assertSoftDeleted($d);

        $this->browse(function (Browser $browser) use ($c, $d) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-saas?per_page=100&terhapus=ya')
                ->waitForText('Role SaaS', 10)
                ->screenshot('operator-saas/role-saas/09-bulk-restore/01-terhapus-list')
                ->assertSee('Restore C')
                ->assertSee('Restore D');

            $browser->check('tbody tr:nth-of-type(1) input[type="checkbox"]')
                ->check('tbody tr:nth-of-type(2) input[type="checkbox"]')
                ->pause(500)
                ->screenshot('operator-saas/role-saas/09-bulk-restore/02-selected')
                ->assertSee('Pulihkan')
                ->press('Pulihkan')
                ->pause(2000)
                ->screenshot('operator-saas/role-saas/09-bulk-restore/03-restored');
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
                ->visit('/operator-saas/role-saas')
                ->waitForText('Role SaaS', 10);

            $browser->press('Tambah Role')
                ->waitForText('Tambah Role SaaS', 10)
                ->screenshot('operator-saas/role-saas/10-create/01-modal');

            $browser->type('input[placeholder="Nama role"]', 'SaaS Create Test')
                ->screenshot('operator-saas/role-saas/10-create/02-filled');

            $browser->press('Simpan')
                ->pause(2000)
                ->screenshot('operator-saas/role-saas/10-create/03-after');
        });

        $this->assertDatabaseHas('roles', [
            'scope' => 'operator_saas',
            'name' => 'SaaS Create Test',
            'is_active' => true,
        ]);
    }

    public function test_11_detail(): void
    {
        Role::create(['scope' => 'operator_saas', 'name' => 'Detail SaaS Test', 'is_active' => true, 'display_order' => 1]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-saas?per_page=100')
                ->waitForText('Role SaaS', 10);

            $browser->type('input[placeholder="Cari role..."]', 'Detail SaaS')
                ->keys('input[placeholder="Cari role..."]', '{enter}')
                ->pause(1500)
                ->assertSee('Detail SaaS Test');

            $browser->click('button[title="Detail"]')
                ->waitForText('Detail Role SaaS', 10)
                ->screenshot('operator-saas/role-saas/11-detail/01-modal');

            $browser->assertSee('Detail SaaS Test')
                ->assertSee('Aktif');

            $browser->press('Tutup')
                ->pause(500)
                ->screenshot('operator-saas/role-saas/11-detail/02-closed');
        });
    }

    public function test_12_edit(): void
    {
        $role = Role::create(['scope' => 'operator_saas', 'name' => 'Edit Before SaaS', 'is_active' => true, 'display_order' => 1]);

        $this->browse(function (Browser $browser) use ($role) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/role-saas?per_page=100')
                ->waitForText('Role SaaS', 10);

            $browser->type('input[placeholder="Cari role..."]', 'Edit Before')
                ->keys('input[placeholder="Cari role..."]', '{enter}')
                ->pause(1500)
                ->assertSee('Edit Before SaaS');

            $browser->click('button[title="Edit"]')
                ->waitForText('Edit Role SaaS', 10)
                ->screenshot('operator-saas/role-saas/12-edit/01-modal');

            $browser->clear('input[placeholder="Nama role"]')
                ->type('input[placeholder="Nama role"]', 'Edit After SaaS');

            $browser->screenshot('operator-saas/role-saas/12-edit/02-modified');

            $browser->press('Update')
                ->pause(2000)
                ->screenshot('operator-saas/role-saas/12-edit/03-after');
        });

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Edit After SaaS',
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
