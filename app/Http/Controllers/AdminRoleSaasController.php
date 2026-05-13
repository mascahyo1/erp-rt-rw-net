<?php

namespace App\Http\Controllers;

use App\Models\AdminSaas;
use App\Models\ModelHasRole;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRoleSaasController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ModelHasRole::query()
            ->with(['role', 'model'])
            ->where('model_type', AdminSaas::class);

        if ($search = $request->input('search')) {
            $query->whereHas('model', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $sortField = $request->input('sort_field');
        $sortDir = $request->input('sort_dir', 'asc');

        if ($sortField === 'admin_nama') {
            $query->select('model_has_roles.*')
                ->join('admin_saas', 'model_has_roles.model_id', '=', 'admin_saas.id')
                ->orderBy('admin_saas.name', $sortDir);
        } elseif ($sortField === 'admin_email') {
            $query->select('model_has_roles.*')
                ->join('admin_saas', 'model_has_roles.model_id', '=', 'admin_saas.id')
                ->orderBy('admin_saas.email', $sortDir);
        } elseif ($sortField === 'admin_status') {
            $query->select('model_has_roles.*')
                ->join('admin_saas', 'model_has_roles.model_id', '=', 'admin_saas.id')
                ->orderBy('admin_saas.is_active', $sortDir);
        } elseif ($sortField === 'role_nama') {
            $query->select('model_has_roles.*')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->orderBy('roles.name', $sortDir);
        } elseif ($sortField && in_array($sortField, ['id', 'role_id', 'created_at'])) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->input('per_page', 10), 100);

        $assignments = $query->paginate($perPage)->through(fn($m) => [
            'id' => $m->id, 'admin_id' => $m->model_id,
            'admin_nama' => $m->model?->name, 'admin_email' => $m->model?->email,
            'admin_status' => $m->model?->is_active ? 'Aktif' : 'Nonaktif',
            'role_id' => $m->role_id, 'role_nama' => $m->role?->name,
            'created_at' => $m->created_at->format('Y-m-d H:i'),
        ]);

        $admins = AdminSaas::where('is_active', true)->get(['id', 'name', 'email'])->toArray();
        $roles = Role::where('scope', 'operator_saas')->where('is_active', true)->orderBy('display_order')->get(['id', 'name'])->toArray();

        return Inertia::render('OperatorSaas/AdminRoleSaaS', [
            'assignments' => $assignments,
            'filters' => $request->only(['search', 'sort_field', 'sort_dir', 'per_page']),
            'admins' => $admins,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_id' => ['required', 'uuid', 'exists:admin_saas,id'],
            'role_id' => ['required', 'uuid', 'exists:roles,id'],
        ]);

        ModelHasRole::updateOrCreate(
            ['model_type' => AdminSaas::class, 'model_id' => $validated['admin_id']],
            ['role_id' => $validated['role_id']],
        );

        return back()->with('success', 'Role admin SaaS berhasil ditetapkan.');
    }

    public function update(Request $request, ModelHasRole $modelHasRole): RedirectResponse
    {
        $validated = $request->validate(['role_id' => ['required', 'uuid', 'exists:roles,id']]);
        $modelHasRole->update(['role_id' => $validated['role_id']]);
        return back()->with('success', 'Role admin SaaS berhasil diperbarui.');
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
        return back()->with('success', ModelHasRole::whereIn('id', $ids)->delete() . " penugasan role berhasil dihapus.");
    }
}
