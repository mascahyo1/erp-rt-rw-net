<?php

namespace Tests\Feature\OperatorPerusahaan;

use App\Models\AdminCompany;
use App\Models\Company;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PerusahaanSayaTest extends TestCase
{

    protected AdminCompany $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AdminCompany::factory()->create(['is_active' => true]);
        $this->assignDefaultRole($this->user, 'admin_perusahaan');
    }

    public function test_guest_cannot_access_perusahaan_saya()
    {
        $this->get('/operator-perusahaan/perusahaan-saya')
            ->assertRedirect('/login-perusahaan');
    }

    public function test_authenticated_can_view_perusahaan_saya()
    {
        $company = Company::factory()->create(['id' => $this->user->company_id]);

        $this->actingAs($this->user, 'admin-company')
            ->get('/operator-perusahaan/perusahaan-saya')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('OperatorPerusahaan/PerusahaanSaya')
                ->has('company')
            );
    }

    public function test_perusahaan_saya_response_includes_logo_urls()
    {
        $company = Company::factory()->create([
            'id' => $this->user->company_id,
            'logo' => 'companies/logos/light.webp',
            'logo_dark' => 'companies/logos/dark.webp',
        ]);

        $response = $this->actingAs($this->user, 'admin-company')->get('/operator-perusahaan/perusahaan-saya');
        $response->assertOk();
        $data = $response->original->getData()['page']['props'];
        $this->assertSame('companies/logos/light.webp', $data['company']['logo']);
        $this->assertSame('companies/logos/dark.webp', $data['company']['logo_dark']);
        $this->assertNotEmpty($data['company']['logo_url']);
        $this->assertNotEmpty($data['company']['logo_dark_url']);
    }

    public function test_can_update_perusahaan_saya_without_logo()
    {
        $company = Company::factory()->create(['id' => $this->user->company_id]);

        $response = $this->actingAs($this->user, 'admin-company')
            ->post("/operator-perusahaan/perusahaan-saya/{$company->id}", [
                '_method' => 'PUT',
                'name' => $company->name,
                'email' => $company->email,
                'phone_country_code' => '+62',
                'phone_number' => '81234567890',
                'address' => 'Updated',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'address' => 'Updated']);
    }

    public function test_can_update_perusahaan_saya_with_new_logo()
    {
        $company = Company::factory()->create([
            'id' => $this->user->company_id,
            'logo' => 'companies/logos/old.webp',
        ]);

        $newLogo = \Illuminate\Http\UploadedFile::fake()->image('new.png', 200, 200);

        $response = $this->actingAs($this->user, 'admin-company')
            ->post("/operator-perusahaan/perusahaan-saya/{$company->id}", [
                '_method' => 'PUT',
                'name' => $company->name,
                'email' => $company->email,
                'phone_country_code' => '+62',
                'phone_number' => '81234567890',
                'address' => 'Updated',
                'logo' => $newLogo,
            ]);

        $response->assertRedirect();
        $company->refresh();
        $this->assertNotSame('companies/logos/old.webp', $company->logo);
        $this->assertNotNull($company->logo);
    }

    public function test_can_update_perusahaan_saya_with_svg_logo()
    {
        $company = Company::factory()->create(['id' => $this->user->company_id]);

        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50"><circle cx="25" cy="25" r="20" fill="red"/></svg>';
        $svgFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('logo.svg', $svgContent);

        $response = $this->actingAs($this->user, 'admin-company')
            ->post("/operator-perusahaan/perusahaan-saya/{$company->id}", [
                '_method' => 'PUT',
                'name' => $company->name,
                'email' => $company->email,
                'phone_country_code' => '+62',
                'phone_number' => '81234567890',
                'logo' => $svgFile,
            ]);

        $response->assertRedirect();
        $company->refresh();
        $this->assertNotNull($company->logo);
        $this->assertStringEndsWith('.svg', $company->logo);
    }

    public function test_validation_fails_for_non_logo_file_type()
    {
        $company = Company::factory()->create(['id' => $this->user->company_id]);

        $pdfFile = \Illuminate\Http\UploadedFile::fake()->create('doc.pdf', 100);

        $response = $this->actingAs($this->user, 'admin-company')
            ->post("/operator-perusahaan/perusahaan-saya/{$company->id}", [
                '_method' => 'PUT',
                'name' => $company->name,
                'email' => $company->email,
                'phone_country_code' => '+62',
                'phone_number' => '81234567890',
                'logo' => $pdfFile,
            ]);

        $response->assertSessionHasErrors(['logo']);
    }
}
