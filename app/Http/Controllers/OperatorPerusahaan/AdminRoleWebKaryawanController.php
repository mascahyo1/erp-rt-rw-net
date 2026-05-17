<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ModelHasRole;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRoleWebKaryawanController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $rolesForCompany = Role::where('scope', 'karyawan_perusahaan')
            ->where('company_id', $companyId)
            ->pluck('id');

        $query = ModelHasRole::query()
            ->with(['role', 'model'])
            ->where('model_type', Employee::class)
            ->whereIn('role_id', $rolesForCompany);

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
            'karyawan_id' => $m->model_id,
            'karyawan_nama' => $m->model?->name,
            'karyawan_email' => $m->model?->email,
            'karyawan_status' => $m->model?->is_active ? 'Aktif' : 'Nonaktif',
            'role_id' => $m->role_id,
            'role_nama' => $m->role?->name,
            'created_at' => $m->created_at->format('Y-m-d H:i'),
        ]);

        $karyawans = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->toArray();

        $roles = Role::where('scope', 'karyawan_perusahaan')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        return Inertia::render('OperatorPerusahaan/AdminRoleWebKaryawan', [
            'assignments' => $assignments,
            'filters' => $request->only(['search', 'sort_field', 'sort_dir', 'per_page']),
            'karyawans' => $karyawans,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'karyawan_id' => ['required', 'uuid', 'exists:employees,id'],
            'role_id' => ['required', 'uuid', 'exists:roles,id'],
        ]);

        ModelHasRole::updateOrCreate(
            ['model_type' => Employee::class, 'model_id' => $validated['karyawan_id']],
            ['role_id' => $validated['role_id']],
        );

        return back()->with('success', 'Role web karyawan berhasil ditetapkan.');
    }

    public function update(Request $request, ModelHasRole $modelHasRole): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'uuid', 'exists:roles,id'],
        ]);

        $modelHasRole->update(['role_id' => $validated['role_id']]);

        return back()->with('success', 'Role web karyawan berhasil diperbarui.');
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
}
