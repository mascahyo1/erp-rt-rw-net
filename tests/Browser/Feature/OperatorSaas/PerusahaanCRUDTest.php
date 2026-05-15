<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminSaas;
use App\Models\Company;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PerusahaanCRUDTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Company::factory()->count(5)->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/perusahaan')
                ->waitForText('Perusahaan', 10)
                ->assertPresent('table')
                ->assertSee('Tambah Perusahaan')
                ->screenshot('operator-saas/perusahaan/01-page-render/01-page');
        });
    }

    public function test_02_search(): void
    {
        Company::factory()->create(['name' => 'PT Cari Satu', 'email' => 'cari-satu@test.com', 'is_active' => true]);
        Company::factory()->create(['name' => 'PT Lain Dua', 'email' => 'lain-dua@test.com', 'is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/perusahaan?per_page=100')
                ->waitForText('Perusahaan', 10)
                ->screenshot('operator-saas/perusahaan/02-search/01-before')
                ->type('input[placeholder="Cari perusahaan..."]', 'Cari')
                ->keys('input[placeholder="Cari perusahaan..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/perusahaan/02-search/02-result')
                ->assertSee('PT Cari Satu')
                ->assertDontSee('PT Lain Dua');
        });
    }

    public function test_03_filter_status(): void
    {
        Company::factory()->create(['name' => 'Perusahaan Aktif', 'is_active' => true]);
        Company::factory()->create(['name' => 'Perusahaan Nonaktif', 'is_active' => false]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/perusahaan?per_page=100')
                ->waitForText('Perusahaan', 10)
                ->screenshot('operator-saas/perusahaan/03-filter-status/01-all');

            $browser->select('select:first-of-type', 'Aktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/perusahaan/03-filter-status/02-aktif')
                ->assertSee('Perusahaan Aktif')
                ->assertDontSee('Perusahaan Nonaktif');

            $browser->select('select:first-of-type', 'Nonaktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/perusahaan/03-filter-status/03-nonaktif')
                ->assertSee('Perusahaan Nonaktif')
                ->assertDontSee('Perusahaan Aktif');
        });
    }

    public function test_04_sort(): void
    {
        Company::factory()->create(['name' => 'AAA Company', 'is_active' => true]);
        Company::factory()->create(['name' => 'ZZZ Company', 'is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/perusahaan?per_page=100')
                ->waitForText('Perusahaan', 10)
                ->screenshot('operator-saas/perusahaan/04-sort/01-before');

            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/perusahaan/04-sort/02-name-asc');

            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/perusahaan/04-sort/03-name-desc');

            $browser->script("document.querySelectorAll('th')[2].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/perusahaan/04-sort/04-email-asc')
                ->assertSee('Perusahaan');
        });
    }

    public function test_05_delete(): void
    {
        $company = Company::factory()->create(['name' => 'Delete Target PT', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($company) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/perusahaan?per_page=100')
                ->waitForText('Perusahaan', 10)
                ->screenshot('operator-saas/perusahaan/05-delete/01-before');

            $browser->type('input[placeholder="Cari perusahaan..."]', 'Delete Target')
                ->keys('input[placeholder="Cari perusahaan..."]', '{enter}')
                ->pause(1500)
                ->waitForText('Delete Target', 10);

            $browser->click('button[title="Hapus"]')
                ->waitForText('Hapus Perusahaan?', 10)
                ->screenshot('operator-saas/perusahaan/05-delete/02-modal')
                ->press('Hapus')
                ->pause(2000);

            $this->assertSoftDeleted($company);

            $browser->screenshot('operator-saas/perusahaan/05-delete/03-deleted');
        });
    }

    public function test_06_bulk_delete(): void
    {
        $a = Company::factory()->create(['name' => 'Bulk Del A Co', 'is_active' => true]);
        $b = Company::factory()->create(['name' => 'Bulk Del B Co', 'is_active' => true]);
        $c = Company::factory()->create(['name' => 'Bulk Del C Co', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($a, $b, $c) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/perusahaan?per_page=100')
                ->waitForText('Perusahaan', 10);

            $browser->type('input[placeholder="Cari perusahaan..."]', 'Bulk Del')
                ->keys('input[placeholder="Cari perusahaan..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/perusahaan/06-bulk-delete/01-before');

            $browser->assertSee('Bulk Del A')
                ->assertSee('Bulk Del B')
                ->assertSee('Bulk Del C');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-saas/perusahaan/06-bulk-delete/02-selected');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Hapus') && b.querySelector('.fa-trash-alt')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/perusahaan/06-bulk-delete/03-after')
                ->assertSee('Perusahaan');

            $this->assertSoftDeleted($a);
            $this->assertSoftDeleted($b);
            $this->assertSoftDeleted($c);

            $browser->type('input[placeholder="Cari perusahaan..."]', 'Bulk Del')
                ->keys('input[placeholder="Cari perusahaan..."]', '{enter}')
                ->pause(1500)
                ->assertDontSee('Bulk Del A')
                ->assertDontSee('Bulk Del B')
                ->assertDontSee('Bulk Del C');
        });
    }

    public function test_07_bulk_toggle_status(): void
    {
        $a = Company::factory()->create(['name' => 'Bulk Status X Co', 'is_active' => true]);
        $b = Company::factory()->create(['name' => 'Bulk Status Y Co', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($a, $b) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/perusahaan?per_page=100')
                ->waitForText('Perusahaan', 10);

            $browser->type('input[placeholder="Cari perusahaan..."]', 'Bulk Status')
                ->keys('input[placeholder="Cari perusahaan..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/perusahaan/07-bulk-status/01-before');

            $browser->assertSee('Bulk Status X')
                ->assertSee('Bulk Status Y');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-saas/perusahaan/07-bulk-status/02-selected');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Nonaktifkan')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/perusahaan/07-bulk-status/03-nonaktif');

            $this->assertFalse((bool) $a->fresh()->is_active, 'Bulk Status X should be inactive');
            $this->assertFalse((bool) $b->fresh()->is_active, 'Bulk Status Y should be inactive');

            $browser->select('select:first-of-type', 'Nonaktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/perusahaan/07-bulk-status/04-filter-nonaktif');

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
                ->screenshot('operator-saas/perusahaan/07-bulk-status/05-aktif');

            $this->assertTrue((bool) $a->fresh()->is_active, 'Bulk Status X should be active after 2nd toggle');
            $this->assertTrue((bool) $b->fresh()->is_active, 'Bulk Status Y should be active after 2nd toggle');
        });
    }

    public function test_08_pagination_and_per_page(): void
    {
        Company::factory()->count(30)->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web');

            $browser->visit('/operator-saas/perusahaan?per_page=5')
                ->waitForText('Perusahaan', 10)
                ->screenshot('operator-saas/perusahaan/08-pagination/01-per-5-page-1')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(5, $rowCount[0] ?? 0, 'per_page=5 should show 5 rows');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.trim() === '2') b.click();
                });
            ");
            $browser->pause(1500)
                ->screenshot('operator-saas/perusahaan/08-pagination/02-page-2')
                ->assertSee('Perusahaan');

            $browser->script("
                var sel = document.querySelector('select');
                sel.value = '10';
                sel.dispatchEvent(new Event('change', {bubbles: true}));
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/perusahaan/08-pagination/03-per-10')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(10, $rowCount[0] ?? 0, 'per_page=10 should show 10 rows');

            $browser->script("
                var sel = document.querySelector('select');
                sel.value = '25';
                sel.dispatchEvent(new Event('change', {bubbles: true}));
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/perusahaan/08-pagination/04-per-25')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertGreaterThanOrEqual(1, $rowCount[0] ?? 0, 'per_page=25 should show records');

            $browser->assertSee('Perusahaan');
        });
    }

    public function test_09_bulk_restore(): void
    {
        $c = Company::factory()->create(['name' => 'Bulk Rest C Co', 'is_active' => true]);
        $d = Company::factory()->create(['name' => 'Bulk Rest D Co', 'is_active' => true]);
        $c->delete();
        $d->delete();

        $this->assertSoftDeleted($c);
        $this->assertSoftDeleted($d);

        $this->browse(function (Browser $browser) use ($c, $d) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/perusahaan?per_page=100&terhapus=ya')
                ->waitForText('Perusahaan', 10)
                ->screenshot('operator-saas/perusahaan/09-bulk-restore/01-terhapus-list')
                ->assertSee('Bulk Rest C')
                ->assertSee('Bulk Rest D');

            $browser->check('tbody tr:nth-of-type(1) input[type="checkbox"]')
                ->check('tbody tr:nth-of-type(2) input[type="checkbox"]')
                ->pause(500)
                ->screenshot('operator-saas/perusahaan/09-bulk-restore/02-selected')
                ->assertSee('Pulihkan')
                ->press('Pulihkan')
                ->pause(2000)
                ->screenshot('operator-saas/perusahaan/09-bulk-restore/03-restored');
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
                ->visit('/operator-saas/perusahaan')
                ->waitForText('Perusahaan', 10);

            $browser->press('Tambah Perusahaan')
                ->waitForText('Tambah Perusahaan', 10)
                ->screenshot('operator-saas/perusahaan/10-create/01-modal');

            $browser->type('input[placeholder="Nama perusahaan"]', 'PT Test Create')
                ->type('input[placeholder="email@perusahaan.id"]', 'test@perusahaan-test.id')
                ->type('input[placeholder="81234567890"]', '81234567890')
                ->type('input[placeholder="Alamat lengkap"]', 'Jalan Test No 123')
                ->type('input[placeholder="Deskripsi"]', 'Deskripsi Test')
                ->screenshot('operator-saas/perusahaan/10-create/02-filled');

            $browser->press('Simpan')
                ->pause(2000)
                ->screenshot('operator-saas/perusahaan/10-create/03-after');
        });

        $this->assertDatabaseHas('companies', [
            'name' => 'PT Test Create',
            'email' => 'test@perusahaan-test.id',
            'phone_country_code' => '+62',
            'phone_number' => '81234567890',
            'address' => 'Jalan Test No 123',
            'is_active' => true,
        ]);
    }

    public function test_11_detail(): void
    {
        $record = Company::factory()->create([
            'name' => 'PT Detail Test',
            'email' => 'detail@perusahaan-test.id',
            'phone_country_code' => '+62',
            'phone_number' => '81234567891',
            'address' => 'Detail Address 1',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($record) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/perusahaan?per_page=100')
                ->waitForText('Perusahaan', 10);

            $browser->type('input[placeholder="Cari perusahaan..."]', 'Detail Test')
                ->keys('input[placeholder="Cari perusahaan..."]', '{enter}')
                ->pause(1500)
                ->assertSee('PT Detail Test');

            $browser->click('button[title="Detail"]')
                ->waitForText('Detail Perusahaan', 10)
                ->screenshot('operator-saas/perusahaan/11-detail/01-modal');

            $browser->assertSee('PT Detail Test')
                ->assertSee('detail@perusahaan-test.id')
                ->assertSee('Detail Address 1')
                ->assertSee('Aktif');

            $browser->press('Tutup')
                ->pause(500)
                ->screenshot('operator-saas/perusahaan/11-detail/02-closed');
        });
    }

    public function test_12_edit(): void
    {
        $record = Company::factory()->create([
            'name' => 'PT Edit Before',
            'email' => 'edit-before@perusahaan-test.id',
            'phone_country_code' => '+62',
            'phone_number' => '81234567000',
            'address' => 'Old Address',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($record) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/perusahaan?per_page=100')
                ->waitForText('Perusahaan', 10);

            $browser->type('input[placeholder="Cari perusahaan..."]', 'Edit Before')
                ->keys('input[placeholder="Cari perusahaan..."]', '{enter}')
                ->pause(1500)
                ->assertSee('PT Edit Before');

            $browser->click('button[title="Edit"]')
                ->waitForText('Edit Perusahaan', 10)
                ->screenshot('operator-saas/perusahaan/12-edit/01-modal');

            $browser->assertInputValue('input[placeholder="Nama perusahaan"]', 'PT Edit Before')
                ->assertInputValue('input[placeholder="email@perusahaan.id"]', 'edit-before@perusahaan-test.id');

            $browser->clear('input[placeholder="Nama perusahaan"]')
                ->type('input[placeholder="Nama perusahaan"]', 'PT Edit After');

            $browser->clear('input[placeholder="email@perusahaan.id"]')
                ->type('input[placeholder="email@perusahaan.id"]', 'edit-after@perusahaan-test.id');

            $browser->screenshot('operator-saas/perusahaan/12-edit/02-modified');

            $browser->press('Update')
                ->pause(2000)
                ->screenshot('operator-saas/perusahaan/12-edit/03-after');
        });

        $this->assertDatabaseHas('companies', [
            'id' => $record->id,
            'name' => 'PT Edit After',
            'email' => 'edit-after@perusahaan-test.id',
        ]);

        $this->assertDatabaseMissing('companies', [
            'id' => $record->id,
            'name' => 'PT Edit Before',
        ]);
    }
}
