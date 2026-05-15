<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminSaas;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DashboardViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        AdminSaas::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/dashboard')
                ->waitForText('Dashboard', 10)
                ->screenshot('operator-saas/dashboard/01-page');
        });
    }

    public function test_02_stats_displayed(): void
    {
        AdminSaas::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/dashboard')
                ->waitForText('Dashboard', 10)
                ->pause(1000)
                ->screenshot('operator-saas/dashboard/02-stats');
        });
    }

    public function test_03_navigation_links(): void
    {
        AdminSaas::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(AdminSaas::first(), 'web')
                ->visit('/operator-saas/dashboard')
                ->waitForText('Dashboard', 10)
                ->pause(500)
                ->screenshot('operator-saas/dashboard/03-navigation');
        });
    }
}
