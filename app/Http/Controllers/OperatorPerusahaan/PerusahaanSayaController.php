<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PerusahaanSayaController extends Controller
{
    public function index(): Response
    {
        $company = auth()->user()->company;

        $data = $company ? $company->toArray() : null;
        if ($data) {
            $data['logo_url'] = $company->logo_url;
            $data['logo_dark_url'] = $company->logo_dark_url;
        }

        return Inertia::render('OperatorPerusahaan/PerusahaanSaya', [
            'company' => $data,
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('companies')->ignore($company->id),
            ],
            'phone_country_code' => ['required', 'string', 'max:10'],
            'phone_number' => ['required', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'logo_dark' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $data = $validated;
        $uploader = new FileUploadService();

        if ($request->hasFile('logo')) {
            if ($company->logo) $uploader->deleteFile($company->logo);
            $data['logo'] = $uploader->processLogo($request->file('logo'), 'companies/logos');
        }
        if ($request->hasFile('logo_dark')) {
            if ($company->logo_dark) $uploader->deleteFile($company->logo_dark);
            $data['logo_dark'] = $uploader->processLogo($request->file('logo_dark'), 'companies/logos');
        }

        $company->update($data);

        return back()->with('success', 'Data perusahaan berhasil diperbarui.');
    }
}
