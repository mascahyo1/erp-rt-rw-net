<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Company::query()->with(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy']);

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('is_active', $status === 'Aktif' || $status === 'aktif');
        }

        if ($sortField = $request->input('sort_field')) {
            $sortDir = $request->input('sort_dir', 'asc');
            $allowedSorts = ['name', 'email', 'is_active', 'created_at', 'deleted_at'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDir);
            }
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->input('per_page', 10), 100);

        $companies = $query->paginate($perPage)->through(function ($c) {
            return [
                'id' => $c->id,
                'nama_perusahaan' => $c->name,
                'alamat' => $c->address,
                'email' => $c->email,
                'kode_negara' => $c->phone_country_code,
                'no_telp' => $c->phone_number,
                'deskripsi' => $c->description,
                'status' => $c->is_active ? 'Aktif' : 'Nonaktif',
                'is_active' => $c->is_active,
                'dihapus' => $c->trashed(),
                'deleted_at' => $c->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $c->created_at->format('Y-m-d H:i'),
                'updated_at' => $c->updated_at->format('Y-m-d H:i'),
                'restored_at' => $c->restored_at?->format('Y-m-d H:i'),
                'created_by' => $c->createdBy?->name,
                'updated_by' => $c->updatedBy?->name,
                'deleted_by' => $c->deletedBy?->name,
                'restored_by' => $c->restoredBy?->name,
            ];
        });

        return Inertia::render('OperatorSaas/Perusahaan', [
            'companies' => $companies,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_perusahaan' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:companies'],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
        ]);

        Company::create([
            'name' => $validated['nama_perusahaan'],
            'email' => $validated['email'],
            'phone_country_code' => $validated['kode_negara'],
            'phone_number' => $validated['no_telp'],
            'address' => $validated['alamat'],
            'description' => $validated['deskripsi'] ?? null,
            'is_active' => $validated['status'] === 'Aktif',
        ]);

        return back()->with('success', 'Perusahaan berhasil ditambahkan.');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $validated = $request->validate([
            'nama_perusahaan' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('companies')->ignore($company->id),
            ],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
        ]);

        $company->update([
            'name' => $validated['nama_perusahaan'],
            'email' => $validated['email'],
            'phone_country_code' => $validated['kode_negara'],
            'phone_number' => $validated['no_telp'],
            'address' => $validated['alamat'],
            'description' => $validated['deskripsi'] ?? null,
            'is_active' => $validated['status'] === 'Aktif',
        ]);

        return back()->with('success', 'Perusahaan berhasil diperbarui.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->delete();

        return back()->with('success', 'Perusahaan berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $company = Company::withTrashed()->findOrFail($id);
        $company->restore();

        return back()->with('success', 'Perusahaan berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada perusahaan yang dipilih.');
        }

        $count = Company::whereIn('id', $ids)->delete();

        return back()->with('success', "{$count} perusahaan berhasil dihapus.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');

        if (empty($ids) || !in_array($status, ['Aktif', 'Nonaktif'])) {
            return back()->with('error', 'Data tidak valid.');
        }

        $count = Company::whereIn('id', $ids)->update([
            'is_active' => $status === 'Aktif',
        ]);

        $label = $status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "{$count} perusahaan berhasil {$label}.");
    }
}
