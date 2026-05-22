<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class KonfigurasiPerusahaanViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/konfigurasi-perusahaan')
                ->waitForText('Konfigurasi', 10)
                ->assertSee('Konfigurasi')
                ->assertPresent('nav')
                ->pause(500)
                ->screenshot('operator-perusahaan/konfigurasi-perusahaan/01-page');
        });
    }
}
