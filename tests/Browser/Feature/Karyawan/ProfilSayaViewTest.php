<?php

namespace Tests\Browser\Feature\Karyawan;

use App\Models\Employee;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProfilSayaViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Employee::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/profil-saya')
                ->waitForText('Profil Saya', 10)
                ->pause(1000)
                ->screenshot('karyawan/profil-saya/01-page')
                ->assertSee('Profil Saya')
                ->assertSee('Edit Profil');
        });
    }
}
