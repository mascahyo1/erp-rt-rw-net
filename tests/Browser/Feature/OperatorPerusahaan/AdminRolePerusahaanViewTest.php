<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminRolePerusahaanViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/admin-role-perusahaan')
                ->waitForText('Admin Role Perusahaan', 10)
                ->assertSee('Admin Role Perusahaan')
                ->assertPresent('nav')
                ->pause(500)
                ->screenshot('operator-perusahaan/admin-role-perusahaan/01-page');
        });
    }
}
