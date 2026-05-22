<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminSaas;
use App\Models\SaasConfig;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class KonfigurasiCRUDTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        SaasConfig::create(['key' => 'app_name', 'type' => 'text', 'value' => 'ERP']);
        SaasConfig::create(['key' => 'app_logo', 'type' => 'file', 'value' => 'logo.png']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/konfigurasi')
                ->waitForText('Konfigurasi', 10)
                ->assertPresent('table')
                ->assertSee('Tambah Konfigurasi')
                ->screenshot('operator-saas/konfigurasi/01-page-render/01-page');
        });
    }

    public function test_02_search(): void
    {
        SaasConfig::create(['key' => 'site_name', 'type' => 'text', 'value' => 'Alpha']);
        SaasConfig::create(['key' => 'site_url', 'type' => 'text', 'value' => 'Beta URL']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/konfigurasi?per_page=100')
                ->waitForText('Konfigurasi', 10)
                ->screenshot('operator-saas/konfigurasi/02-search/01-before')
                ->type('input[placeholder="Cari konfigurasi..."]', 'Alpha')
                ->keys('input[placeholder="Cari konfigurasi..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/konfigurasi/02-search/02-result')
                ->assertSee('Alpha')
                ->assertDontSee('Beta');
        });
    }

    public function test_03_sort(): void
    {
        SaasConfig::create(['key' => 'aaa_key', 'type' => 'text', 'value' => 'A']);
        SaasConfig::create(['key' => 'zzz_key', 'type' => 'text', 'value' => 'Z']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/konfigurasi?per_page=100')
                ->waitForText('Konfigurasi', 10)
                ->screenshot('operator-saas/konfigurasi/03-sort/01-before');

            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/konfigurasi/03-sort/02-key-asc');

            $browser->script("document.querySelectorAll('th')[1].click()");
            $browser->pause(1500)
                ->screenshot('operator-saas/konfigurasi/03-sort/03-key-desc')
                ->assertSee('Konfigurasi');
        });
    }

    public function test_04_delete(): void
    {
        $config = SaasConfig::create(['key' => 'delete_me', 'type' => 'text', 'value' => 'Delete']);

        $this->browse(function (Browser $browser) use ($config) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/konfigurasi?per_page=100')
                ->waitForText('Konfigurasi', 10)
                ->screenshot('operator-saas/konfigurasi/04-delete/01-before');

            $browser->type('input[placeholder="Cari konfigurasi..."]', 'delete_me')
                ->keys('input[placeholder="Cari konfigurasi..."]', '{enter}')
                ->pause(1500)
                ->waitForText('delete_me', 10);

            $browser->click('button[title="Hapus"]')
                ->waitForText('Hapus Konfigurasi?', 10)
                ->screenshot('operator-saas/konfigurasi/04-delete/02-modal')
                ->press('Hapus')
                ->pause(2000);

            $this->assertDatabaseMissing('saas_configs', ['id' => $config->id]);

            $browser->screenshot('operator-saas/konfigurasi/04-delete/03-deleted');
        });
    }

    public function test_05_bulk_delete(): void
    {
        $a = SaasConfig::create(['key' => 'bulk_a', 'type' => 'text', 'value' => 'A']);
        $b = SaasConfig::create(['key' => 'bulk_b', 'type' => 'text', 'value' => 'B']);
        $c = SaasConfig::create(['key' => 'bulk_c', 'type' => 'text', 'value' => 'C']);

        $this->browse(function (Browser $browser) use ($a, $b, $c) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/konfigurasi?per_page=100')
                ->waitForText('Konfigurasi', 10);

            $browser->type('input[placeholder="Cari konfigurasi..."]', 'bulk_')
                ->keys('input[placeholder="Cari konfigurasi..."]', '{enter}')
                ->pause(1500)
                ->screenshot('operator-saas/konfigurasi/05-bulk-delete/01-before');

            $browser->assertSee('bulk_a')
                ->assertSee('bulk_b')
                ->assertSee('bulk_c');

            $browser->script("
                document.querySelectorAll('tbody input[type=\"checkbox\"]').forEach(function(cb) { cb.click(); });
            ");
            $browser->pause(500)
                ->screenshot('operator-saas/konfigurasi/05-bulk-delete/02-selected');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.includes('Hapus') && b.querySelector('.fa-trash-alt')) b.click();
                });
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/konfigurasi/05-bulk-delete/03-after')
                ->assertSee('Konfigurasi');

            $this->assertDatabaseMissing('saas_configs', ['id' => $a->id]);
            $this->assertDatabaseMissing('saas_configs', ['id' => $b->id]);
            $this->assertDatabaseMissing('saas_configs', ['id' => $c->id]);
        });
    }

    public function test_06_pagination_and_per_page(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            SaasConfig::create(['key' => "config_{$i}", 'type' => 'text', 'value' => "Value {$i}"]);
        }

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web');

            $browser->visit('/operator-saas/konfigurasi?per_page=5')
                ->waitForText('Konfigurasi', 10)
                ->screenshot('operator-saas/konfigurasi/06-pagination/01-per-5-page-1')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(5, $rowCount[0] ?? 0, 'per_page=5 should show 5 rows');

            $browser->script("
                document.querySelectorAll('button').forEach(function(b) {
                    if (b.textContent.trim() === '2') b.click();
                });
            ");
            $browser->pause(1500)
                ->screenshot('operator-saas/konfigurasi/06-pagination/02-page-2')
                ->assertSee('Konfigurasi');

            $browser->script("
                var sel = document.querySelector('select');
                sel.value = '10';
                sel.dispatchEvent(new Event('change', {bubbles: true}));
            ");
            $browser->pause(2000)
                ->screenshot('operator-saas/konfigurasi/06-pagination/03-per-10')
                ->pause(1000);

            $rowCount = $browser->script("return document.querySelectorAll('tbody tr:not(:only-child)').length");
            $this->assertEquals(10, $rowCount[0] ?? 0, 'per_page=10 should show 10 rows');
        });
    }

    public function test_07_create(): void
    {
        $loginAdmin = AdminSaas::first()
            ?? AdminSaas::factory()->create(['name' => 'Login Admin', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($loginAdmin) {
            $browser->loginAs($loginAdmin, 'web')
                ->visit('/operator-saas/konfigurasi')
                ->waitForText('Konfigurasi', 10);

            $browser->press('Tambah Konfigurasi')
                ->waitForText('Tambah Konfigurasi', 10)
                ->screenshot('operator-saas/konfigurasi/07-create/01-modal');

            $browser->type('input[name="key"]', 'new_config_key')
                ->type('input[name="value"]', 'New Config Value')
                ->screenshot('operator-saas/konfigurasi/07-create/02-filled');

            $browser->press('Simpan')
                ->pause(2000)
                ->screenshot('operator-saas/konfigurasi/07-create/03-after');
        });

        $this->assertDatabaseHas('saas_configs', [
            'key' => 'new_config_key',
            'type' => 'text',
            'value' => 'New Config Value',
        ]);
    }

    public function test_08_detail(): void
    {
        SaasConfig::create(['key' => 'detail_key', 'type' => 'text', 'value' => 'Detail Value']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/konfigurasi?per_page=100')
                ->waitForText('Konfigurasi', 10);

            $browser->type('input[placeholder="Cari konfigurasi..."]', 'detail_key')
                ->keys('input[placeholder="Cari konfigurasi..."]', '{enter}')
                ->pause(1500)
                ->assertSee('detail_key');

            $browser->click('button[title="Detail"]')
                ->waitForText('Detail Konfigurasi', 10)
                ->screenshot('operator-saas/konfigurasi/08-detail/01-modal');

            $browser->assertSee('detail_key')
                ->assertSee('Detail Value')
                ->assertSee('text');

            $browser->press('Tutup')
                ->pause(500)
                ->screenshot('operator-saas/konfigurasi/08-detail/02-closed');
        });
    }

    public function test_09_edit(): void
    {
        $config = SaasConfig::create(['key' => 'edit_before', 'type' => 'text', 'value' => 'Old Value']);

        $this->browse(function (Browser $browser) use ($config) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/konfigurasi?per_page=100')
                ->waitForText('Konfigurasi', 10);

            $browser->type('input[placeholder="Cari konfigurasi..."]', 'edit_before')
                ->keys('input[placeholder="Cari konfigurasi..."]', '{enter}')
                ->pause(1500)
                ->assertSee('edit_before');

            $browser->click('button[title="Edit"]')
                ->waitForText('Edit Konfigurasi', 10)
                ->screenshot('operator-saas/konfigurasi/09-edit/01-modal');

            $browser->clear('input[name="key"]')
                ->type('input[name="key"]', 'edit_after');

            $browser->clear('input[name="value"]')
                ->type('input[name="value"]', 'New Value After');

            $browser->screenshot('operator-saas/konfigurasi/09-edit/02-modified');

            $browser->press('Update')
                ->pause(2000)
                ->screenshot('operator-saas/konfigurasi/09-edit/03-after');
        });

        $this->assertDatabaseHas('saas_configs', [
            'id' => $config->id,
            'key' => 'edit_after',
            'value' => 'New Value After',
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        SaasConfig::onlyTrashed()->forceDelete();

        SaasConfig::where('key', 'like', '%test%')
            ->orWhere('key', 'like', '%update%')
            ->orWhere('key', 'like', '%config%')
            ->forceDelete();

        parent::tearDownAfterClass();
    }
}
