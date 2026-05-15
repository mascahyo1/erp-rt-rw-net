<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\Customer;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CustomerCRUDTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        $companyId = $user->company_id;
        Customer::factory()->count(5)->create(['company_id' => $companyId, 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/customer')
                ->waitForText('Pelanggan', 10)
                ->assertPresent('table')
                ->assertSee('Tambah Pelanggan')
                ->screenshot('operator-perusahaan/customer/01-page-render/01-page');
        });
    }

    public function test_02_search(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        $companyId = $user->company_id;
        Customer::factory()->create(['company_id' => $companyId, 'name' => 'Budi Pelanggan', 'email' => 'budi-pelanggan@test.id', 'is_active' => true]);
        Customer::factory()->create(['company_id' => $companyId, 'name' => 'Andi Lain', 'email' => 'andi-lain@test.id', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/customer?per_page=100')
                ->waitForText('Pelanggan', 10)
                ->screenshot('operator-perusahaan/customer/02-search/01-before')
                ->type('input[placeholder="Cari pelanggan..."]', 'Budi')
                ->keys('input[placeholder="Cari pelanggan..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-perusahaan/customer/02-search/02-result')
                ->assertSee('Budi Pelanggan')
                ->assertDontSee('Andi Lain');
        });
    }

    public function test_03_filter_status(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        $companyId = $user->company_id;
        Customer::factory()->create(['company_id' => $companyId, 'name' => 'Aktif Satu', 'is_active' => true]);
        Customer::factory()->create(['company_id' => $companyId, 'name' => 'Nonaktif Satu', 'is_active' => false]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/customer?per_page=100')
                ->waitForText('Pelanggan', 10)
                ->screenshot('operator-perusahaan/customer/03-filter-status/01-all');

            $browser->select('select:first-of-type', 'Aktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-perusahaan/customer/03-filter-status/02-aktif')
                ->assertSee('Aktif Satu')
                ->assertDontSee('Nonaktif Satu');

            $browser->select('select:first-of-type', 'Nonaktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-perusahaan/customer/03-filter-status/03-nonaktif')
                ->assertSee('Nonaktif Satu')
                ->assertDontSee('Aktif Satu');
        });
    }

    public function test_04_sort(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        $companyId = $user->company_id;
        Customer::factory()->create(['company_id' => $companyId, 'name' => 'AAA Customer', 'is_active' => true]);
        Customer::factory()->create(['company_id' => $companyId, 'name' => 'ZZZ Customer', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/customer?per_page=100')
                ->waitForText('Pelanggan', 10)
                ->screenshot('operator-perusahaan/customer/04-sort/01-before');

            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-perusahaan/customer/04-sort/02-name-asc');

            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-perusahaan/customer/04-sort/03-name-desc');

            $browser->script("document.querySelectorAll('th')[2].click()");
            $browser->pause(1500)
                ->screenshot('operator-perusahaan/customer/04-sort/04-email-asc')
                ->assertSee('Pelanggan');
        });
    }

    public function test_05_delete(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        $companyId = $user->company_id;
        $customer = Customer::factory()->create(['company_id' => $companyId, 'name' => 'Delete Target', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user, $customer) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/customer?per_page=100')
                ->waitForText('Pelanggan', 10)
                ->screenshot('operator-perusahaan/customer/05-delete/01-before');

            $browser->type('input[placeholder="Cari pelanggan..."]', 'Delete Target')
                ->keys('input[placeholder="Cari pelanggan..."]', '{enter}')
                ->pause(1500)
                ->waitForText('Delete Target', 10);

            $browser->click('button[title="Hapus"]')
                ->waitForText('Hapus Pelanggan?', 10)
                ->screenshot('operator-perusahaan/customer/05-delete/02-modal')
                ->press('Hapus')
                ->pause(2000);

            $this->assertSoftDeleted($customer);

            $browser->screenshot('operator-perusahaan/customer/05-delete/03-deleted');
        });
    }

    public function test_06_bulk_delete(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        $companyId = $user->company_id;
        $a = Customer::factory()->create(['company_id' => $companyId, 'name' => 'Bulk Del A', 'is_active' => true]);
        $b = Customer::factory()->create(['company_id' => $companyId, 'name' => 'Bulk Del B', 'is_active' => true]);
        $c = Customer::factory()->create(['company_id' => $companyId, 'name' => 'Bulk Del C', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user, $a, $b, $c) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/customer?per_page=100')
                ->waitForText('Pelanggan', 10);

            $browser->type('input[placeholder="Cari pelanggan..."]', 'Bulk Del')
                ->keys('input[placeholder="Cari pelanggan..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-perusahaan/customer/06-bulk-delete/01-before');

            $browser->assertSee('Bulk Del A')
                ->assertSee('Bulk Del B')
                ->assertSee('Bulk Del C');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-perusahaan/customer/06-bulk-delete/02-selected');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Hapus') && b.querySelector('.fa-trash-alt')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-perusahaan/customer/06-bulk-delete/03-after')
                ->assertSee('Pelanggan');

            $this->assertSoftDeleted($a);
            $this->assertSoftDeleted($b);
            $this->assertSoftDeleted($c);

            $browser->type('input[placeholder="Cari pelanggan..."]', 'Bulk Del')
                ->keys('input[placeholder="Cari pelanggan..."]', '{enter}')
                ->pause(1500)
                ->assertDontSee('Bulk Del A')
                ->assertDontSee('Bulk Del B')
                ->assertDontSee('Bulk Del C');
        });
    }

    public function test_07_bulk_toggle_status(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        $companyId = $user->company_id;
        $a = Customer::factory()->create(['company_id' => $companyId, 'name' => 'Bulk Status X', 'is_active' => true]);
        $b = Customer::factory()->create(['company_id' => $companyId, 'name' => 'Bulk Status Y', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user, $a, $b) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/customer?per_page=100')
                ->waitForText('Pelanggan', 10);

            $browser->type('input[placeholder="Cari pelanggan..."]', 'Bulk Status')
                ->keys('input[placeholder="Cari pelanggan..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-perusahaan/customer/07-bulk-status/01-before');

            $browser->assertSee('Bulk Status X')
                ->assertSee('Bulk Status Y');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-perusahaan/customer/07-bulk-status/02-selected');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Nonaktifkan')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-perusahaan/customer/07-bulk-status/03-nonaktif');

            $this->assertFalse((bool) $a->fresh()->is_active, 'Bulk Status X should be inactive');
            $this->assertFalse((bool) $b->fresh()->is_active, 'Bulk Status Y should be inactive');

            $browser->select('select:first-of-type', 'Nonaktif')
                ->press('Filter')
                ->pause(1500)
                ->screenshot('operator-perusahaan/customer/07-bulk-status/04-filter-nonaktif');

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
                ->screenshot('operator-perusahaan/customer/07-bulk-status/05-aktif');

            $this->assertTrue((bool) $a->fresh()->is_active, 'Bulk Status X should be active after 2nd toggle');
            $this->assertTrue((bool) $b->fresh()->is_active, 'Bulk Status Y should be active after 2nd toggle');
        });
    }

    public function test_08_pagination_and_per_page(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        $companyId = $user->company_id;
        Customer::factory()->count(30)->create(['company_id' => $companyId, 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company');

            $browser->visit('/operator-perusahaan/customer?per_page=5')
                ->waitForText('Pelanggan', 10)
                ->screenshot('operator-perusahaan/customer/08-pagination/01-per-5-page-1')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(5, $rowCount[0] ?? 0, 'per_page=5 should show 5 rows');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.trim() === '2') b.click();
                });
            ");
            $browser->pause(1500)
                ->screenshot('operator-perusahaan/customer/08-pagination/02-page-2')
                ->assertSee('Pelanggan');

            $browser->script("
                var sel = document.querySelector('select');
                sel.value = '10';
                sel.dispatchEvent(new Event('change', {bubbles: true}));
            ");
            $browser->pause(2000)
                ->screenshot('operator-perusahaan/customer/08-pagination/03-per-10')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(10, $rowCount[0] ?? 0, 'per_page=10 should show 10 rows');

            $browser->script("
                var sel = document.querySelector('select');
                sel.value = '25';
                sel.dispatchEvent(new Event('change', {bubbles: true}));
            ");
            $browser->pause(2000)
                ->screenshot('operator-perusahaan/customer/08-pagination/04-per-25')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertGreaterThanOrEqual(1, $rowCount[0] ?? 0, 'per_page=25 should show records');

            $browser->assertSee('Pelanggan');
        });
    }

    public function test_09_bulk_restore(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        $companyId = $user->company_id;
        $c = Customer::factory()->create(['company_id' => $companyId, 'name' => 'Bulk Restore C', 'is_active' => true]);
        $d = Customer::factory()->create(['company_id' => $companyId, 'name' => 'Bulk Restore D', 'is_active' => true]);
        $c->delete();
        $d->delete();

        $this->assertSoftDeleted($c);
        $this->assertSoftDeleted($d);

        $this->browse(function (Browser $browser) use ($user, $c, $d) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/customer?per_page=100&terhapus=ya')
                ->waitForText('Pelanggan', 10)
                ->screenshot('operator-perusahaan/customer/09-bulk-restore/01-terhapus-list')
                ->assertSee('Bulk Restore C')
                ->assertSee('Bulk Restore D');

            $browser->check('tbody tr:nth-of-type(1) input[type="checkbox"]')
                ->check('tbody tr:nth-of-type(2) input[type="checkbox"]')
                ->pause(500)
                ->screenshot('operator-perusahaan/customer/09-bulk-restore/02-selected')
                ->assertSee('Pulihkan')
                ->press('Pulihkan')
                ->pause(2000)
                ->screenshot('operator-perusahaan/customer/09-bulk-restore/03-restored');
        });

        $this->assertNotSoftDeleted($c);
        $this->assertNotSoftDeleted($d);
    }

    public function test_10_create(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/customer')
                ->waitForText('Pelanggan', 10);

            $browser->press('Tambah Pelanggan')
                ->waitForText('Tambah Pelanggan', 10)
                ->screenshot('operator-perusahaan/customer/10-create/01-modal');

            $browser->type('input[placeholder="Nama lengkap"]', 'Customer Create Test')
                ->type('input[placeholder="email@rtrwnet.id"]', 'create-cust@test.id')
                ->type('input[placeholder="Minimal 8 karakter"]', 'password123')
                ->type('input[placeholder="81234567890"]', '81234567890')
                ->type('input[placeholder="Nomor NIK"]', '3573011234567890')
                ->type('input[placeholder="Nomor KK"]', '3573011234567891')
                ->type('input[placeholder="Alamat lengkap"]', 'Jl. Test 1')
                ->screenshot('operator-perusahaan/customer/10-create/02-filled');

            $browser->press('Simpan')
                ->pause(2000)
                ->screenshot('operator-perusahaan/customer/10-create/03-after');
        });

        $this->assertDatabaseHas('customers', [
            'name' => 'Customer Create Test',
            'email' => 'create-cust@test.id',
            'phone_country_code' => '+62',
            'phone_number' => '81234567890',
            'no_nik' => '3573011234567890',
            'no_kk' => '3573011234567891',
            'address' => 'Jl. Test 1',
            'is_active' => true,
        ]);
    }

    public function test_11_detail(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        $companyId = $user->company_id;
        $record = Customer::factory()->create([
            'company_id' => $companyId,
            'name' => 'Detail Customer Test',
            'email' => 'detail-cust@test.id',
            'phone_country_code' => '+62',
            'phone_number' => '81234567891',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($user, $record) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/customer?per_page=100')
                ->waitForText('Pelanggan', 10);

            $browser->type('input[placeholder="Cari pelanggan..."]', 'Detail Customer Test')
                ->keys('input[placeholder="Cari pelanggan..."]', '{enter}')
                ->pause(1500)
                ->assertSee('Detail Customer Test');

            $browser->click('button[title="Detail"]')
                ->waitForText('Detail Pelanggan', 10)
                ->screenshot('operator-perusahaan/customer/11-detail/01-modal');

            $browser->assertSee('Detail Customer Test')
                ->assertSee('detail-cust@test.id')
                ->assertSee('+62')
                ->assertSee('81234567891')
                ->assertSee('Aktif');

            $browser->press('Tutup')
                ->pause(500)
                ->screenshot('operator-perusahaan/customer/11-detail/02-closed');
        });
    }

    public function test_12_edit(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        $companyId = $user->company_id;
        $record = Customer::factory()->create([
            'company_id' => $companyId,
            'name' => 'Edit Before Customer',
            'email' => 'edit-before@test.id',
            'phone_country_code' => '+62',
            'phone_number' => '81234567000',
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($user, $record) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/customer?per_page=100')
                ->waitForText('Pelanggan', 10);

            $browser->type('input[placeholder="Cari pelanggan..."]', 'Edit Before Customer')
                ->keys('input[placeholder="Cari pelanggan..."]', '{enter}')
                ->pause(1500)
                ->assertSee('Edit Before Customer');

            $browser->click('button[title="Edit"]')
                ->waitForText('Edit Pelanggan', 10)
                ->screenshot('operator-perusahaan/customer/12-edit/01-modal');

            $browser->assertInputValue('input[placeholder="Nama lengkap"]', 'Edit Before Customer')
                ->assertInputValue('input[placeholder="email@rtrwnet.id"]', 'edit-before@test.id')
                ->assertInputValue('input[placeholder="81234567890"]', '81234567000');

            $browser->clear('input[placeholder="Nama lengkap"]')
                ->type('input[placeholder="Nama lengkap"]', 'Edit After Customer');

            $browser->clear('input[placeholder="email@rtrwnet.id"]')
                ->type('input[placeholder="email@rtrwnet.id"]', 'edit-after@test.id');

            $browser->screenshot('operator-perusahaan/customer/12-edit/02-modified');

            $browser->press('Update')
                ->pause(2000)
                ->screenshot('operator-perusahaan/customer/12-edit/03-after');
        });

        $this->assertDatabaseHas('customers', [
            'id' => $record->id,
            'name' => 'Edit After Customer',
            'email' => 'edit-after@test.id',
        ]);

        $this->assertDatabaseMissing('customers', [
            'id' => $record->id,
            'name' => 'Edit Before Customer',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        Customer::onlyTrashed()->forceDelete();

        Customer::query()
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
