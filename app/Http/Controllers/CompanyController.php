<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
                'logo' => $c->logo,
                'logo_url' => $c->logo_url,
                'logo_dark' => $c->logo_dark,
                'logo_dark_url' => $c->logo_dark_url,
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

    public function selectSearch(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $page = (int) $request->input('page', 1);

        $query = Company::where('is_active', true);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $companies = $query->orderBy('name')
            ->paginate(20, ['id', 'name'], 'page', $page)
            ->through(fn ($c) => ['value' => $c->id, 'label' => $c->name]);

        return response()->json($companies);
    }

    public function store(Request $request): RedirectResponse
    {
        // Deprecated — Inertia form submit tidak dipakai lagi. Form pakai AJAX.
        // Lihat CompanyController::storeAjax() untuk implementasi baru.
        abort(410, 'Use POST /api/operator-saas/perusahaan instead.');
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        abort(410, 'Use POST /api/operator-saas/perusahaan/{company} instead.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        abort(410, 'Use POST /api/operator-saas/perusahaan/{company}/delete instead.');
    }

    public function restore(string $id): RedirectResponse
    {
        abort(410, 'Use POST /api/operator-saas/perusahaan/{id}/restore instead.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        abort(410, 'Use POST /api/operator-saas/perusahaan/bulk-delete instead.');
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        abort(410, 'Use POST /api/operator-saas/perusahaan/bulk-status instead.');
    }

    // ============================================================
    // AJAX endpoints (POST + JSON) — untuk form submit di Vue
    // Lihat dokumentasi/CONVENTIONS.md section 2 untuk pattern.
    // ============================================================

    /**
     * POST /api/operator-saas/perusahaan
     * Tambah perusahaan baru via AJAX (multipart/form-data).
     */
    public function storeAjax(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_perusahaan' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:companies'],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'logo_dark' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $uploader = new FileUploadService();
        $logoPath = $request->hasFile('logo') ? $uploader->processLogo($request->file('logo'), 'companies/logos') : null;
        $logoDarkPath = $request->hasFile('logo_dark') ? $uploader->processLogo($request->file('logo_dark'), 'companies/logos') : null;

        $company = Company::create([
            'name' => $validated['nama_perusahaan'],
            'email' => $validated['email'],
            'phone_country_code' => $validated['kode_negara'],
            'phone_number' => $validated['no_telp'],
            'address' => $validated['alamat'],
            'description' => $validated['deskripsi'] ?? null,
            'logo' => $logoPath,
            'logo_dark' => $logoDarkPath,
            'is_active' => $validated['status'] === 'Aktif',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Perusahaan berhasil ditambahkan.',
            'data' => $this->transformCompany($company),
        ], 200);
    }

    /**
     * POST /api/operator-saas/perusahaan/{company}
     * Update perusahaan via AJAX (multipart/form-data).
     * Method POST (bukan PUT) supaya PHP parse multipart body dengan benar.
     */
    public function updateAjax(Request $request, Company $company): JsonResponse
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
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'logo_dark' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $data = [
            'name' => $validated['nama_perusahaan'],
            'email' => $validated['email'],
            'phone_country_code' => $validated['kode_negara'],
            'phone_number' => $validated['no_telp'],
            'address' => $validated['alamat'],
            'description' => $validated['deskripsi'] ?? null,
            'is_active' => $validated['status'] === 'Aktif',
        ];

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
        $company->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Perusahaan berhasil diperbarui.',
            'data' => $this->transformCompany($company),
        ], 200);
    }

    /**
     * POST /api/operator-saas/perusahaan/{company}/delete
     * Soft-delete perusahaan via AJAX.
     */
    public function destroyAjax(Company $company): JsonResponse
    {
        $name = $company->name;
        $company->delete();

        return response()->json([
            'success' => true,
            'message' => "Perusahaan \"{$name}\" berhasil dihapus.",
        ], 200);
    }

    /**
     * POST /api/operator-saas/perusahaan/{id}/restore
     * Restore soft-deleted perusahaan via AJAX.
     */
    public function restoreAjax(string $id): JsonResponse
    {
        $company = Company::withTrashed()->findOrFail($id);
        $name = $company->name;
        $company->restore();

        return response()->json([
            'success' => true,
            'message' => "Perusahaan \"{$name}\" berhasil dipulihkan.",
        ], 200);
    }

    /**
     * POST /api/operator-saas/perusahaan/bulk-delete
     * Bulk delete via AJAX.
     */
    public function bulkDeleteAjax(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada perusahaan yang dipilih.',
            ], 422);
        }

        $count = Company::whereIn('id', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$count} perusahaan berhasil dihapus.",
            'count' => $count,
        ], 200);
    }

    /**
     * POST /api/operator-saas/perusahaan/bulk-status
     * Bulk toggle status via AJAX.
     */
    public function bulkToggleStatusAjax(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');

        if (empty($ids) || !in_array($status, ['Aktif', 'Nonaktif'])) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
            ], 422);
        }

        $count = Company::whereIn('id', $ids)->update([
            'is_active' => $status === 'Aktif',
        ]);

        $label = $status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "{$count} perusahaan berhasil {$label}.",
            'count' => $count,
        ], 200);
    }

    /**
     * Helper: serialize Company to consistent shape for AJAX responses.
     */
    private function transformCompany(Company $c): array
    {
        return [
            'id' => $c->id,
            'nama_perusahaan' => $c->name,
            'alamat' => $c->address,
            'email' => $c->email,
            'kode_negara' => $c->phone_country_code,
            'no_telp' => $c->phone_number,
            'deskripsi' => $c->description,
            'logo' => $c->logo,
            'logo_url' => $c->logo_url,
            'logo_dark' => $c->logo_dark,
            'logo_dark_url' => $c->logo_dark_url,
            'status' => $c->is_active ? 'Aktif' : 'Nonaktif',
            'is_active' => $c->is_active,
            'created_at' => $c->created_at?->format('Y-m-d H:i'),
            'updated_at' => $c->updated_at?->format('Y-m-d H:i'),
        ];
    }
}