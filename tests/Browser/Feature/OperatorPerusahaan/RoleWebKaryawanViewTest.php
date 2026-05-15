<?php

namespace Tests\Browser\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RoleWebKaryawanViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        $user = AdminCompany::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user, 'admin-company')
                ->visit('/operator-perusahaan/role-web-karyawan')
                ->waitForText('Role Web Karyawan', 10)
                ->pause(500)
                ->screenshot('operator-perusahaan/role-web-karyawan/01-page');
        });
    }
}
