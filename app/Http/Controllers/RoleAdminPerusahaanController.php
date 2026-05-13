<?php

namespace App\Http\Controllers;

use App\Models\AdminCompany;
use App\Models\Company;
use App\Models\ModelHasRole;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleAdminPerusahaanController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ModelHasRole::query()
            ->with(['role.company', 'model'])
            ->where('model_type', AdminCompany::class);

        if ($companyId = $request->input('company')) {
            $rolesForCompany = Role::where('scope', 'admin_perusahaan')
                ->where('company_id', $companyId)->pluck('id');
            $query->whereIn('role_id', $rolesForCompany);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('model', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($sortField = $request->input('sort_field')) {
            $query->orderBy($sortField, $request->input('sort_dir', 'asc'));
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->input('per_page', 10), 100);

        $assignments = $query->paginate($perPage)->through(fn($m) => [
            'id' => $m->id,
            'admin_id' => $m->model_id,
            'admin_nama' => $m->model?->name,
            'admin_email' => $m->model?->email,
            'admin_status' => $m->model?->is_active ? 'Aktif' : 'Nonaktif',
            'role_id' => $m->role_id,
            'role_nama' => $m->role?->name,
            'perusahaan' => $m->role?->company?->name,
            'company_id' => $m->role?->company_id,
            'created_at' => $m->created_at->format('Y-m-d H:i'),
        ]);

        return Inertia::render('OperatorSaas/RoleAdminPerusahaan', [
            'assignments' => $assignments,
            'filters' => $request->only(['search', 'company', 'sort_field', 'sort_dir', 'per_page']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_id' => ['required', 'uuid', 'exists:admin_companies,id'],
            'company_id' => ['required', 'uuid', 'exists:companies,id'],
            'role_id' => ['required', 'uuid', 'exists:roles,id'],
        ]);

        ModelHasRole::updateOrCreate(
            ['model_type' => AdminCompany::class, 'model_id' => $validated['admin_id']],
            ['role_id' => $validated['role_id']],
        );

        return back()->with('success', 'Role admin perusahaan berhasil ditetapkan.');
    }

    public function update(Request $request, ModelHasRole $modelHasRole): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'uuid', 'exists:roles,id'],
        ]);

        $modelHasRole->update(['role_id' => $validated['role_id']]);

        return back()->with('success', 'Role admin perusahaan berhasil diperbarui.');
    }

    public function destroy(ModelHasRole $modelHasRole): RedirectResponse
    {
        $modelHasRole->delete();
        return back()->with('success', 'Penugasan role berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada data yang dipilih.');
        $count = ModelHasRole::whereIn('id', $ids)->delete();
        return back()->with('success', "{$count} penugasan role berhasil dihapus.");
    }

    public function rolesByCompany(Request $request): \Illuminate\Http\JsonResponse
    {
        $companyId = $request->input('company_id');
        $roles = Role::where('scope', 'admin_perusahaan')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($roles);
    }

    public function adminsByCompany(Request $request): \Illuminate\Http\JsonResponse
    {
        $companyId = $request->input('company_id');
        $admins = AdminCompany::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        return response()->json($admins);
    }
}
