<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminCompany;
use App\Models\AdminSaas;
use App\Models\Company;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminPerusahaanCRUDTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        AdminCompany::factory()->count(5)->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-perusahaan')
                ->waitForText('Admin Perusahaan', 10)
                ->assertPresent('table')
                ->assertSee('Tambah Admin')
                ->screenshot('operator-saas/admin-perusahaan/01-page-render/01-page');
        });
    }

    public function test_02_search(): void
    {
        AdminCompany::factory()->create(['name' => 'Budi Admin Comp', 'email' => 'budi-comp@test.com', 'is_active' => true]);
        AdminCompany::factory()->create(['name' => 'Andi Lain Comp', 'email' => 'andi-other-comp@test.com', 'is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-perusahaan?per_page=100')
                ->waitForText('Admin Perusahaan', 10)
                ->screenshot('operator-saas/admin-perusahaan/02-search/01-before')
                ->type('input[placeholder="Cari admin perusahaan..."]', 'Budi')
                ->keys('input[placeholder="Cari admin perusahaan..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/admin-perusahaan/02-search/02-result')
                ->assertSee('Budi Admin')
                ->assertDontSee('Andi Lain');
        });
    }

    public function test_03_filter_status(): void
    {
        AdminCompany::factory()->create(['name' => 'Aktif Admin Comp', 'is_active' => true]);
        AdminCompany::factory()->create(['name' => 'Nonaktif Admin Comp', 'is_active' => false]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-perusahaan?per_page=100')
                ->waitForText('Admin Perusahaan', 10)
                ->screenshot('operator-saas/admin-perusahaan/03-filter-status/01-all');

            $browser->select('select:first-of-type', 'Aktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/admin-perusahaan/03-filter-status/02-aktif')
                ->assertSee('Aktif Admin')
                ->assertDontSee('Nonaktif Admin');

            $browser->select('select:first-of-type', 'Nonaktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/admin-perusahaan/03-filter-status/03-nonaktif')
                ->assertSee('Nonaktif Admin')
                ->assertDontSee('Aktif Admin');
        });
    }

    public function test_04_sort(): void
    {
        AdminCompany::factory()->create(['name' => 'AAA Comp Sort', 'is_active' => true]);
        AdminCompany::factory()->create(['name' => 'ZZZ Comp Sort', 'is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-perusahaan?per_page=100')
                ->waitForText('Admin Perusahaan', 10)
                ->screenshot('operator-saas/admin-perusahaan/04-sort/01-before');

            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/admin-perusahaan/04-sort/02-name-asc');

            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/admin-perusahaan/04-sort/03-name-desc');

            $browser->script("document.querySelectorAll('th')[2].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/admin-perusahaan/04-sort/04-email-asc')
                ->assertSee('Admin Perusahaan');
        });
    }

    public function test_05_delete(): void
    {
        $admin = AdminCompany::factory()->create(['name' => 'Delete Admin Comp', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-perusahaan?per_page=100')
                ->waitForText('Admin Perusahaan', 10)
                ->screenshot('operator-saas/admin-perusahaan/05-delete/01-before');

            $browser->type('input[placeholder="Cari admin perusahaan..."]', 'Delete Admin')
                ->keys('input[placeholder="Cari admin perusahaan..."]', '{enter}')
                ->pause(1500)
                ->waitForText('Delete Admin', 10);

            $browser->click('button[title="Hapus"]')
                ->waitForText('Hapus Admin Perusahaan?', 10)
                ->screenshot('operator-saas/admin-perusahaan/05-delete/02-modal')
                ->press('Hapus')
                ->pause(2000);

            $this->assertSoftDeleted($admin);

            $browser->screenshot('operator-saas/admin-perusahaan/05-delete/03-deleted');
        });
    }

    public function test_06_bulk_delete(): void
    {
        $a = AdminCompany::factory()->create(['name' => 'Bulk Del A Comp', 'is_active' => true]);
        $b = AdminCompany::factory()->create(['name' => 'Bulk Del B Comp', 'is_active' => true]);
        $c = AdminCompany::factory()->create(['name' => 'Bulk Del C Comp', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($a, $b, $c) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-perusahaan?per_page=100')
                ->waitForText('Admin Perusahaan', 10);

            $browser->type('input[placeholder="Cari admin perusahaan..."]', 'Bulk Del')
                ->keys('input[placeholder="Cari admin perusahaan..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/admin-perusahaan/06-bulk-delete/01-before');

            $browser->assertSee('Bulk Del A')
                ->assertSee('Bulk Del B')
                ->assertSee('Bulk Del C');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-saas/admin-perusahaan/06-bulk-delete/02-selected');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Hapus') && b.querySelector('.fa-trash-alt')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/admin-perusahaan/06-bulk-delete/03-after')
                ->assertSee('Admin Perusahaan');

            $this->assertSoftDeleted($a);
            $this->assertSoftDeleted($b);
            $this->assertSoftDeleted($c);

            $browser->type('input[placeholder="Cari admin perusahaan..."]', 'Bulk Del')
                ->keys('input[placeholder="Cari admin perusahaan..."]', '{enter}')
                ->pause(1500)
                ->assertDontSee('Bulk Del A')
                ->assertDontSee('Bulk Del B')
                ->assertDontSee('Bulk Del C');
        });
    }

    public function test_07_bulk_toggle_status(): void
    {
        $a = AdminCompany::factory()->create(['name' => 'Bulk Status X Comp', 'is_active' => true]);
        $b = AdminCompany::factory()->create(['name' => 'Bulk Status Y Comp', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($a, $b) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-perusahaan?per_page=100')
                ->waitForText('Admin Perusahaan', 10);

            $browser->type('input[placeholder="Cari admin perusahaan..."]', 'Bulk Status')
                ->keys('input[placeholder="Cari admin perusahaan..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/admin-perusahaan/07-bulk-status/01-before');

            $browser->assertSee('Bulk Status X')
                ->assertSee('Bulk Status Y');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-saas/admin-perusahaan/07-bulk-status/02-selected');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Nonaktifkan')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/admin-perusahaan/07-bulk-status/03-nonaktif');

            $this->assertFalse((bool) $a->fresh()->is_active, 'Bulk Status X should be inactive');
            $this->assertFalse((bool) $b->fresh()->is_active, 'Bulk Status Y should be inactive');

            $browser->select('select:first-of-type', 'Nonaktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/admin-perusahaan/07-bulk-status/04-filter-nonaktif');

            $browser->assertSee('Bulk Status X')
                ->assertSee('Bulk Status Y');

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
                ->screenshot('operator-saas/admin-perusahaan/07-bulk-status/05-aktif');

            $this->assertTrue((bool) $a->fresh()->is_active, 'Bulk Status X should be active after 2nd toggle');
            $this->assertTrue((bool) $b->fresh()->is_active, 'Bulk Status Y should be active after 2nd toggle');
        });
    }

    public function test_08_pagination_and_per_page(): void
    {
        AdminCompany::factory()->count(30)->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web');

            $browser->visit('/operator-saas/admin-perusahaan?per_page=5')
                ->waitForText('Admin Perusahaan', 10)
                ->screenshot('operator-saas/admin-perusahaan/08-pagination/01-per-5-page-1')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(5, $rowCount[0] ?? 0, 'per_page=5 should show 5 rows');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.trim() === '2') b.click();
                });
            ");
            $browser->pause(1500)
                ->screenshot('operator-saas/admin-perusahaan/08-pagination/02-page-2')
                ->assertSee('Admin Perusahaan');

            $browser->script("
                var sel = document.querySelector('select');
                sel.value = '10';
                sel.dispatchEvent(new Event('change', {bubbles: true}));
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/admin-perusahaan/08-pagination/03-per-10')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(10, $rowCount[0] ?? 0, 'per_page=10 should show 10 rows');

            $browser->script("
                var sel = document.querySelector('select');
                sel.value = '25';
                sel.dispatchEvent(new Event('change', {bubbles: true}));
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/admin-perusahaan/08-pagination/04-per-25')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertGreaterThanOrEqual(1, $rowCount[0] ?? 0, 'per_page=25 should show records');

            $browser->assertSee('Admin Perusahaan');
        });
    }

    public function test_09_bulk_restore(): void
    {
        $c = AdminCompany::factory()->create(['name' => 'Bulk Rest C Comp', 'is_active' => true]);
        $d = AdminCompany::factory()->create(['name' => 'Bulk Rest D Comp', 'is_active' => true]);
        $c->delete();
        $d->delete();

        $this->assertSoftDeleted($c);
        $this->assertSoftDeleted($d);

        $this->browse(function (Browser $browser) use ($c, $d) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-perusahaan?per_page=100&terhapus=ya')
                ->waitForText('Admin Perusahaan', 10)
                ->screenshot('operator-saas/admin-perusahaan/09-bulk-restore/01-terhapus-list')
                ->assertSee('Bulk Rest C')
                ->assertSee('Bulk Rest D');

            $browser->check('tbody tr:nth-of-type(1) input[type="checkbox"]')
                ->check('tbody tr:nth-of-type(2) input[type="checkbox"]')
                ->pause(500)
                ->screenshot('operator-saas/admin-perusahaan/09-bulk-restore/02-selected')
                ->assertSee('Pulihkan')
                ->press('Pulihkan')
                ->pause(2000)
                ->screenshot('operator-saas/admin-perusahaan/09-bulk-restore/03-restored');
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
                ->visit('/operator-saas/admin-perusahaan')
                ->waitForText('Admin Perusahaan', 10);

            $browser->press('Tambah Admin')
                ->waitForText('Tambah Admin Perusahaan', 10)
                ->screenshot('operator-saas/admin-perusahaan/10-create/01-modal');

            $browser->type('input[placeholder="Nama lengkap"]', 'Admin Comp Create')
                ->type('input[placeholder="email@rtrwnet.id"]', 'comp-create@rtrwnet.id')
                ->type('input[placeholder="Minimal 8 karakter"]', 'password123')
                ->type('input[placeholder="81234567890"]', '81234567890')
                ->screenshot('operator-saas/admin-perusahaan/10-create/02-filled');

            $browser->press('Simpan')
                ->pause(2000)
                ->screenshot('operator-saas/admin-perusahaan/10-create/03-after');
        });

        $this->assertDatabaseHas('admin_companies', [
            'name' => 'Admin Comp Create',
            'email' => 'comp-create@rtrwnet.id',
            'phone_country_code' => '+62',
            'phone_number' => '81234567890',
            'is_active' => true,
        ]);
    }

    public function test_11_detail(): void
    {
        $record = AdminCompany::factory()->create([
            'name' => 'Detail Admin Comp',
            'email' => 'detail-comp@rtrwnet.id',
            'phone_country_code' => '+62',
            'phone_number' => '81234567891',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($record) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-perusahaan?per_page=100')
                ->waitForText('Admin Perusahaan', 10);

            $browser->type('input[placeholder="Cari admin perusahaan..."]', 'Detail Admin')
                ->keys('input[placeholder="Cari admin perusahaan..."]', '{enter}')
                ->pause(1500)
                ->assertSee('Detail Admin Comp');

            $browser->click('button[title="Detail"]')
                ->waitForText('Detail Admin Perusahaan', 10)
                ->screenshot('operator-saas/admin-perusahaan/11-detail/01-modal');

            $browser->assertSee('Detail Admin Comp')
                ->assertSee('detail-comp@rtrwnet.id')
                ->assertSee('+62')
                ->assertSee('81234567891')
                ->assertSee('Aktif');

            $browser->press('Tutup')
                ->pause(500)
                ->screenshot('operator-saas/admin-perusahaan/11-detail/02-closed');
        });
    }

    public function test_12_edit(): void
    {
        $record = AdminCompany::factory()->create([
            'name' => 'Edit Before Comp',
            'email' => 'edit-before-comp@rtrwnet.id',
            'phone_country_code' => '+62',
            'phone_number' => '81234567000',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($record) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-perusahaan?per_page=100')
                ->waitForText('Admin Perusahaan', 10);

            $browser->type('input[placeholder="Cari admin perusahaan..."]', 'Edit Before')
                ->keys('input[placeholder="Cari admin perusahaan..."]', '{enter}')
                ->pause(1500)
                ->assertSee('Edit Before Comp');

            $browser->click('button[title="Edit"]')
                ->waitForText('Edit Admin Perusahaan', 10)
                ->screenshot('operator-saas/admin-perusahaan/12-edit/01-modal');

            $browser->assertInputValue('input[placeholder="Nama lengkap"]', 'Edit Before Comp')
                ->assertInputValue('input[placeholder="email@rtrwnet.id"]', 'edit-before-comp@rtrwnet.id');

            $browser->clear('input[placeholder="Nama lengkap"]')
                ->type('input[placeholder="Nama lengkap"]', 'Edit After Comp');

            $browser->clear('input[placeholder="email@rtrwnet.id"]')
                ->type('input[placeholder="email@rtrwnet.id"]', 'edit-after-comp@rtrwnet.id');

            $browser->screenshot('operator-saas/admin-perusahaan/12-edit/02-modified');

            $browser->press('Update')
                ->pause(2000)
                ->screenshot('operator-saas/admin-perusahaan/12-edit/03-after');
        });

        $this->assertDatabaseHas('admin_companies', [
            'id' => $record->id,
            'name' => 'Edit After Comp',
            'email' => 'edit-after-comp@rtrwnet.id',
        ]);

        $this->assertDatabaseMissing('admin_companies', [
            'id' => $record->id,
            'name' => 'Edit Before Comp',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        AdminCompany::onlyTrashed()->forceDelete();

        AdminCompany::query()
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

        Company::onlyTrashed()->forceDelete();

        Company::query()
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
