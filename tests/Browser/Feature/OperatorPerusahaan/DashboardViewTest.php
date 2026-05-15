<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/dashboard')
                ->waitForText('Dashboard', 10)
                ->pause(500)
                ->screenshot('operator-perusahaan/dashboard/01-page');
        });
    }

    public function test_02_stats_displayed(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/dashboard')
                ->waitForText('Dashboard', 10)
                ->pause(1000)
                ->screenshot('operator-perusahaan/dashboard/02-stats');
        });
    }
}
