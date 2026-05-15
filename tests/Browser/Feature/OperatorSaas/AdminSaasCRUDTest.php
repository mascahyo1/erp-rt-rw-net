<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminSaas;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminSaasCRUDTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        AdminSaas::factory()->count(5)->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-saas')
                ->waitForText('Admin SaaS', 10)
                ->assertPresent('table')
                ->assertSee('Tambah Admin')
                ->screenshot('operator-saas/admin-saas/01-page-render/01-page');
        });
    }

    public function test_02_search(): void
    {
        AdminSaas::factory()->create(['name' => 'Budi Cari', 'email' => 'budi-search@test.com', 'is_active' => true]);
        AdminSaas::factory()->create(['name' => 'Andi Lain', 'email' => 'andi-other@test.com', 'is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-saas?per_page=100')
                ->waitForText('Admin SaaS', 10)
                ->screenshot('operator-saas/admin-saas/02-search/01-before')
                ->type('input[placeholder="Cari admin..."]', 'Budi')
                ->keys('input[placeholder="Cari admin..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/admin-saas/02-search/02-result')
                ->assertSee('Budi Cari')
                ->assertDontSee('Andi Lain');
        });
    }

    public function test_03_filter_status(): void
    {
        AdminSaas::factory()->create(['name' => 'Aktif Satu', 'is_active' => true]);
        AdminSaas::factory()->create(['name' => 'Nonaktif Satu', 'is_active' => false]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-saas?per_page=100')
                ->waitForText('Admin SaaS', 10)
                ->screenshot('operator-saas/admin-saas/03-filter-status/01-all');

            $browser->select('select:first-of-type', 'Aktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/admin-saas/03-filter-status/02-aktif')
                ->assertSee('Aktif Satu')
                ->assertDontSee('Nonaktif Satu');

            $browser->select('select:first-of-type', 'Nonaktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/admin-saas/03-filter-status/03-nonaktif')
                ->assertSee('Nonaktif Satu')
                ->assertDontSee('Aktif Satu');
        });
    }

    public function test_04_sort(): void
    {
        AdminSaas::factory()->create(['name' => 'AAA Sort', 'is_active' => true]);
        AdminSaas::factory()->create(['name' => 'ZZZ Sort', 'is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-saas?per_page=100')
                ->waitForText('Admin SaaS', 10)
                ->screenshot('operator-saas/admin-saas/04-sort/01-before');

            // Asc
            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/admin-saas/04-sort/02-name-asc');

            // Desc
            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/admin-saas/04-sort/03-name-desc');

            // Switch to email sort
            $browser->script("document.querySelectorAll('th')[2].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/admin-saas/04-sort/04-email-asc')
                ->assertSee('Admin SaaS');
        });
    }

    public function test_05_delete(): void
    {
        $admin = AdminSaas::factory()->create(['name' => 'Delete Target', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-saas?per_page=100')
                ->waitForText('Admin SaaS', 10)
                ->screenshot('operator-saas/admin-saas/05-delete/01-before');

            // Search to isolate the target row
            $browser->type('input[placeholder="Cari admin..."]', 'Delete Target')
                ->keys('input[placeholder="Cari admin..."]', '{enter}')
                ->pause(1500)
                ->waitForText('Delete Target', 10);

            $browser->click('button[title="Hapus"]')
                ->waitForText('Hapus Admin?', 10)
                ->screenshot('operator-saas/admin-saas/05-delete/02-modal')
                ->press('Hapus')
                ->pause(2000);

            $this->assertSoftDeleted($admin);

            $browser->screenshot('operator-saas/admin-saas/05-delete/03-deleted');
        });
    }

    public function test_06_bulk_delete(): void
    {
        $a = AdminSaas::factory()->create(['name' => 'Bulk Del A', 'is_active' => true]);
        $b = AdminSaas::factory()->create(['name' => 'Bulk Del B', 'is_active' => true]);
        $c = AdminSaas::factory()->create(['name' => 'Bulk Del C', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($a, $b, $c) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-saas?per_page=100')
                ->waitForText('Admin SaaS', 10);

            // Search to isolate only the bulk-delete targets
            $browser->type('input[placeholder="Cari admin..."]', 'Bulk Del')
                ->keys('input[placeholder="Cari admin..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/admin-saas/06-bulk-delete/01-before');

            // Verify all 3 targets visible
            $browser->assertSee('Bulk Del A')
                ->assertSee('Bulk Del B')
                ->assertSee('Bulk Del C');

            // Select all checkboxes in the filtered tbody
            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-saas/admin-saas/06-bulk-delete/02-selected');

            // Click the bulk delete button
            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Hapus') && b.querySelector('.fa-trash-alt')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/admin-saas/06-bulk-delete/03-after')
                ->assertSee('Admin SaaS');

            // DB assertions: all 3 records must be soft-deleted
            $this->assertSoftDeleted($a);
            $this->assertSoftDeleted($b);
            $this->assertSoftDeleted($c);

            // Verify they no longer appear on page (search again)
            $browser->type('input[placeholder="Cari admin..."]', 'Bulk Del')
                ->keys('input[placeholder="Cari admin..."]', '{enter}')
                ->pause(1500)
                ->assertDontSee('Bulk Del A')
                ->assertDontSee('Bulk Del B')
                ->assertDontSee('Bulk Del C');
        });
    }

    public function test_07_bulk_toggle_status(): void
    {
        $a = AdminSaas::factory()->create(['name' => 'Bulk Status X', 'is_active' => true]);
        $b = AdminSaas::factory()->create(['name' => 'Bulk Status Y', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($a, $b) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-saas?per_page=100')
                ->waitForText('Admin SaaS', 10);

            // Search to isolate the toggle targets
            $browser->type('input[placeholder="Cari admin..."]', 'Bulk Status')
                ->keys('input[placeholder="Cari admin..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/admin-saas/07-bulk-status/01-before');

            $browser->assertSee('Bulk Status X')
                ->assertSee('Bulk Status Y');

            // Select all filtered checkboxes
            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-saas/admin-saas/07-bulk-status/02-selected');

            // ── Toggle 1: Active → Nonaktif ──
            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Nonaktifkan')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/admin-saas/07-bulk-status/03-nonaktif');

            // DB: verify both are now inactive
            $this->assertFalse((bool) $a->fresh()->is_active, 'Bulk Status X should be inactive');
            $this->assertFalse((bool) $b->fresh()->is_active, 'Bulk Status Y should be inactive');

            // ── Toggle 2: Switch filter to Nonaktif, then toggle back to Aktif ──
            $browser->select('select:first-of-type', 'Nonaktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-saas/admin-saas/07-bulk-status/04-filter-nonaktif');

            // Verify records are now visible in nonaktif filter
            $browser->assertSee('Bulk Status X')
                ->assertSee('Bulk Status Y');

            // Select checkboxes again
            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500);

            // Click "Aktifkan"
            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Aktifkan')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/admin-saas/07-bulk-status/05-aktif');

            // DB: verify both are now active again
            $this->assertTrue((bool) $a->fresh()->is_active, 'Bulk Status X should be active after 2nd toggle');
            $this->assertTrue((bool) $b->fresh()->is_active, 'Bulk Status Y should be active after 2nd toggle');
        });
    }

    public function test_08_pagination_and_per_page(): void
    {
        // Create 30 records for clear pagination testing
        AdminSaas::factory()->count(30)->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web');

            // ── per_page = 5 ──
            $browser->visit('/operator-saas/admin-saas?per_page=5')
                ->waitForText('Admin SaaS', 10)
                ->screenshot('operator-saas/admin-saas/08-pagination/01-per-5-page-1')
                ->pause(1000);

            // Count visible data rows (excluding header and "no data" row)
            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(5, $rowCount[0] ?? 0, 'per_page=5 should show 5 rows');

            // Navigate to page 2
            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.trim() === '2') b.click();
                });
            ");
            $browser->pause(1500)
                ->screenshot('operator-saas/admin-saas/08-pagination/02-page-2')
                ->assertSee('Admin SaaS');

            // ── per_page = 10 ──
            $browser->script("
                var sel = document.querySelector('select');
                sel.value = '10';
                sel.dispatchEvent(new Event('change', {bubbles: true}));
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/admin-saas/08-pagination/03-per-10')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(10, $rowCount[0] ?? 0, 'per_page=10 should show 10 rows');

            // ── per_page = 25 ── (all records on one page + some)
            $browser->script("
                var sel = document.querySelector('select');
                sel.value = '25';
                sel.dispatchEvent(new Event('change', {bubbles: true}));
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/admin-saas/08-pagination/04-per-25')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertGreaterThanOrEqual(1, $rowCount[0] ?? 0, 'per_page=25 should show records');

            $browser->assertSee('Admin SaaS');
        });
    }

    public function test_09_bulk_restore(): void
    {
        $c = AdminSaas::factory()->create(['name' => 'Bulk Restore C', 'is_active' => true]);
        $d = AdminSaas::factory()->create(['name' => 'Bulk Restore D', 'is_active' => true]);
        $c->delete();
        $d->delete();

        $this->assertSoftDeleted($c);
        $this->assertSoftDeleted($d);

        $this->browse(function (Browser $browser) use ($c, $d) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-saas?per_page=100&terhapus=ya')
                ->waitForText('Admin SaaS', 10)
                ->screenshot('operator-saas/admin-saas/09-bulk-restore/01-terhapus-list')
                ->assertSee('Bulk Restore C')
                ->assertSee('Bulk Restore D');

            $browser->check('tbody tr:nth-of-type(1) input[type="checkbox"]')
                ->check('tbody tr:nth-of-type(2) input[type="checkbox"]')
                ->pause(500)
                ->screenshot('operator-saas/admin-saas/09-bulk-restore/02-selected')
                ->assertSee('Pulihkan')
                ->press('Pulihkan')
                ->pause(2000)
                ->screenshot('operator-saas/admin-saas/09-bulk-restore/03-restored');
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
                ->visit('/operator-saas/admin-saas')
                ->waitForText('Admin SaaS', 10);

            // Open create modal
            $browser->press('Tambah Admin')
                ->waitForText('Tambah Admin SaaS', 10)
                ->screenshot('operator-saas/admin-saas/10-create/01-modal');

            // Fill form fields (selects default to '+62' and 'Aktif')
            $browser->type('input[placeholder="Nama lengkap"]', 'Admin Create Test')
                ->type('input[placeholder="email@rtrwnet.id"]', 'create-test@rtrwnet.id')
                ->type('input[placeholder="Minimal 8 karakter"]', 'password123')
                ->type('input[placeholder="81234567890"]', '81234567890')
                ->screenshot('operator-saas/admin-saas/10-create/02-filled');

            // Submit
            $browser->press('Simpan')
                ->pause(2000)
                ->screenshot('operator-saas/admin-saas/10-create/03-after');
        });

        // DB assertion: record exists with correct data
        $this->assertDatabaseHas('admin_saas', [
            'name' => 'Admin Create Test',
            'email' => 'create-test@rtrwnet.id',
            'phone_country_code' => '+62',
            'phone_number' => '81234567890',
            'is_active' => true,
        ]);
    }

    public function test_11_detail(): void
    {
        $record = AdminSaas::factory()->create([
            'name' => 'Detail Admin Test',
            'email' => 'detail-test@rtrwnet.id',
            'phone_country_code' => '+62',
            'phone_number' => '81234567891',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($record) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-saas?per_page=100')
                ->waitForText('Admin SaaS', 10);

            // Search to isolate the record
            $browser->type('input[placeholder="Cari admin..."]', 'Detail Admin Test')
                ->keys('input[placeholder="Cari admin..."]', '{enter}')
                ->pause(1500)
                ->assertSee('Detail Admin Test');

            // Open detail modal
            $browser->click('button[title="Detail"]')
                ->waitForText('Detail Admin', 10)
                ->screenshot('operator-saas/admin-saas/11-detail/01-modal');

            // Verify content
            $browser->assertSee('Detail Admin Test')
                ->assertSee('detail-test@rtrwnet.id')
                ->assertSee('+62')
                ->assertSee('81234567891')
                ->assertSee('Aktif');

            // Close modal
            $browser->press('Tutup')
                ->pause(500)
                ->screenshot('operator-saas/admin-saas/11-detail/02-closed');
        });
    }

    public function test_12_edit(): void
    {
        $record = AdminSaas::factory()->create([
            'name' => 'Edit Before Test',
            'email' => 'edit-before@rtrwnet.id',
            'phone_country_code' => '+62',
            'phone_number' => '81234567000',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($record) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/admin-saas?per_page=100')
                ->waitForText('Admin SaaS', 10);

            // Search to isolate the record
            $browser->type('input[placeholder="Cari admin..."]', 'Edit Before Test')
                ->keys('input[placeholder="Cari admin..."]', '{enter}')
                ->pause(1500)
                ->assertSee('Edit Before Test');

            // Open edit modal
            $browser->click('button[title="Edit"]')
                ->waitForText('Edit Admin SaaS', 10)
                ->screenshot('operator-saas/admin-saas/12-edit/01-modal');

            // Verify pre-filled values
            $browser->assertInputValue('input[placeholder="Nama lengkap"]', 'Edit Before Test')
                ->assertInputValue('input[placeholder="email@rtrwnet.id"]', 'edit-before@rtrwnet.id')
                ->assertInputValue('input[placeholder="81234567890"]', '81234567000');

            // Update fields - Nama
            $browser->clear('input[placeholder="Nama lengkap"]')
                ->type('input[placeholder="Nama lengkap"]', 'Edit After Test');

            // Update fields - Email
            $browser->clear('input[placeholder="email@rtrwnet.id"]')
                ->type('input[placeholder="email@rtrwnet.id"]', 'edit-after@rtrwnet.id');

            $browser->screenshot('operator-saas/admin-saas/12-edit/02-modified');

            // Submit update
            $browser->press('Update')
                ->pause(2000)
                ->screenshot('operator-saas/admin-saas/12-edit/03-after');
        });

        // DB assertion: record updated
        $this->assertDatabaseHas('admin_saas', [
            'id' => $record->id,
            'name' => 'Edit After Test',
            'email' => 'edit-after@rtrwnet.id',
        ]);

        $this->assertDatabaseMissing('admin_saas', [
            'id' => $record->id,
            'name' => 'Edit Before Test',
        ]);
    }
}
