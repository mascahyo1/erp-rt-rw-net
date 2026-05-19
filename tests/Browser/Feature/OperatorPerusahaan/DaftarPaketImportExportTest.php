<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\InternetPackage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DaftarPaketImportExportTest extends DuskTestCase
{
    public function test_01_template_download(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->screenshot('operator-perusahaan/daftar-paket/import-export/01-template-before')
                ->assertSee('Template')
                ->clickLink('Template')
                ->pause(1000)
                ->screenshot('operator-perusahaan/daftar-paket/import-export/01-template-after');
        });
    }

    public function test_02_export_all(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        InternetPackage::factory()->count(3)->create(['company_id' => $user->company_id, 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->screenshot('operator-perusahaan/daftar-paket/import-export/02-export-before')
                ->assertSee('Export');

            $browser->visit('/operator-perusahaan/daftar-paket/export')
                ->pause(1000)
                ->screenshot('operator-perusahaan/daftar-paket/import-export/02-export-download');
        });
    }

    public function test_03_export_selected(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'name' => 'Export Selected', 'is_active' => true]);
        InternetPackage::factory()->create(['company_id' => $user->company_id, 'name' => 'Not Selected', 'is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket?per_page=100')
                ->waitForText('Paket Customer', 10)
                ->screenshot('operator-perusahaan/daftar-paket/import-export/03-export-selected-before');

            // Select first checkbox
            $browser->script("document.querySelectorAll('input[type=\"checkbox\"]')[1].click()");
            $browser->pause(1000)
                ->screenshot('operator-perusahaan/daftar-paket/import-export/03-export-selected-checked')
                ->assertSee('data dipilih');
        });
    }

    public function test_04_import_modal(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Paket Customer', 10)
                ->screenshot('operator-perusahaan/daftar-paket/import-export/04-import-before')
                ->assertSee('Import')
                ->press('Import')
                ->pause(1000)
                ->waitForText('Upload file', 10)
                ->screenshot('operator-perusahaan/daftar-paket/import-export/04-import-modal')
                ->assertSee('Download template');
        });
    }
}
