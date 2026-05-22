<?php

namespace Tests\Browser\Feature\OperatorSaas;

use App\Models\AdminSaas;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProfilSayaTest extends DuskTestCase
{
    private AdminSaas $user;
    private ?string $originalName = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminSaas::first();
    }

    protected function tearDown(): void
    {
        if ($this->originalName !== null) {
            AdminSaas::where('id', $this->user->id)->update(['name' => $this->originalName]);
            $this->originalName = null;
        }
        parent::tearDown();
    }

    public function test_01_page_renders(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user, 'web')
                ->visit('/operator-saas/profil-saya')
                ->pause(500)
                ->waitForText('Profil Saya')
                ->assertSee('Nama')
                ->assertSee('Email')
                ->screenshot('operator-saas/profil-saya/01-page');
        });
    }

    public function test_02_update_profile_name(): void
    {
        $this->originalName = $this->user->name;

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user, 'web')
                ->visit('/operator-saas/profil-saya')
                ->pause(500)
                ->waitForText('Profil Saya')
                ->type('input[name="name"]', '')
                ->type('input[name="name"]', 'Updated Name Test')
                ->pause(500)
                ->press('Simpan')
                ->pause(500)
                ->screenshot('operator-saas/profil-saya/02-update-name');
        });
    }

    public function test_03_expand_password_section(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user, 'web')
                ->visit('/operator-saas/profil-saya')
                ->pause(500)
                ->waitForText('Profil Saya')
                ->press('Ubah Password')
                ->pause(500)
                ->assertPresent('input[name="current_password"]')
                ->assertPresent('input[name="password"]')
                ->assertPresent('input[name="password_confirmation"]')
                ->screenshot('operator-saas/profil-saya/03-password-fields');
        });
    }

    public function test_04_access_via_dropdown_navigation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user, 'web')
                ->visit('/operator-saas/dashboard')
                ->pause(800)
                ->click('.fa-chevron-up, .fa-chevron-down')
                ->pause(500)
                ->assertSee('Profil Saya')
                ->clickLink('Profil Saya')
                ->pause(500)
                ->waitForText('Profil Saya')
                ->screenshot('operator-saas/profil-saya/04-from-dropdown');
        });
    }
}
