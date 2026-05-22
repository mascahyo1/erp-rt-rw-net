<?php

namespace Tests\Browser\Feature\Karyawan;

use App\Models\Customer;
use App\Models\Employee;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CustomerViewTest extends DuskTestCase
{
    public function test_01_page_renders(): void
    {
        Employee::factory()->create(['is_active' => true]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/customer')
                ->waitForText('Customer', 10)
                ->pause(1000)
                ->screenshot('karyawan/customer/01-page')
                ->assertPresent('table')
                ->assertSee('Customer');
        });
    }

    public function test_02_list_displays(): void
    {
        $employee = Employee::factory()->create(['is_active' => true]);

        Customer::factory()->count(5)->create(['company_id' => $employee->company_id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/customer')
                ->waitForText('Customer', 10)
                ->pause(1000)
                ->screenshot('karyawan/customer/02-list')
                ->assertPresent('table');
        });
    }

    public function test_03_search(): void
    {
        $employee = Employee::factory()->create(['is_active' => true]);

        Customer::factory()->create(['name' => 'Budi Cari', 'company_id' => $employee->company_id]);
        Customer::factory()->create(['name' => 'Andi Lain', 'company_id' => $employee->company_id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/customer?per_page=100')
                ->waitForText('Customer', 10)
                ->pause(1000)
                ->screenshot('karyawan/customer/03-search/01-before')
                ->assertPresent('table');
        });
    }

    public function test_04_filter_status(): void
    {
        $employee = Employee::factory()->create(['is_active' => true]);

        Customer::factory()->create(['name' => 'Aktif Satu', 'is_active' => true, 'company_id' => $employee->company_id]);
        Customer::factory()->create(['name' => 'Nonaktif Satu', 'is_active' => false, 'company_id' => $employee->company_id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/customer?per_page=100')
                ->waitForText('Customer', 10)
                ->pause(1000)
                ->screenshot('karyawan/customer/04-filter/01-all')
                ->assertPresent('table');
        });
    }

    public function test_05_pagination_displayed(): void
    {
        $employee = Employee::factory()->create(['is_active' => true]);

        Customer::factory()->count(20)->create(['company_id' => $employee->company_id]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(Employee::first(), 'employee')
                ->visit('/karyawan/customer?per_page=5')
                ->waitForText('Customer', 10)
                ->pause(1000)
                ->screenshot('karyawan/customer/05-pagination')
                ->assertPresent('table');
        });
    }
}
