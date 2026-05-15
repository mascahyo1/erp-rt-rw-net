<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProfilSayaViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/profil-saya')
                ->waitForText('Profil Saya', 10)
                ->assertSee('Profil Saya')
                ->assertPresent('nav')
                ->pause(500)
                ->screenshot('operator-perusahaan/profil-saya/01-page');
        });
    }
}
