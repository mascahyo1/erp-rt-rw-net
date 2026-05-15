<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DaftarPaketViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/daftar-paket')
                ->waitForText('Daftar Paket', 10)
                ->assertSee('Daftar Paket')
                ->assertPresent('nav')
                ->pause(500)
                ->screenshot('operator-perusahaan/daftar-paket/01-page');
        });
    }
}
