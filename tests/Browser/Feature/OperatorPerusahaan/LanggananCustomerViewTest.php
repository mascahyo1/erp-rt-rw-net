<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LanggananCustomerViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/langganan-customer')
                ->waitForText('Langganan Customer', 10)
                ->pause(500)
                ->screenshot('operator-perusahaan/langganan-customer/01-page');
        });
    }
}
