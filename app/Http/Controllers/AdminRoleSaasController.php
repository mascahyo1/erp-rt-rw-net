<?php

namespace App\Http\Controllers;

use App\Models\AdminSaas;
use App\Models\ModelHasRole;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
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

    public function bulkAssign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_ids' => ['required', 'array', 'min:1'],
            'admin_ids.*' => ['required', 'uuid'],
            'role_id' => ['required', 'uuid'],
        ]);

        $role = Role::where('scope', 'operator_saas')
            ->where('is_active', true)
            ->findOrFail($validated['role_id']);

        $admins = AdminSaas::whereIn('id', $validated['admin_ids'])->pluck('id');

        if ($admins->isEmpty()) {
            return back()->with('error', 'Tidak ada admin valid yang dipilih.');
        }

        $count = 0;
        foreach ($admins as $adminId) {
            ModelHasRole::updateOrCreate(
                ['model_type' => AdminSaas::class, 'model_id' => $adminId],
                ['role_id' => $role->id],
            );
            $count++;
        }

        return back()->with('success', "Role \"{$role->name}\" berhasil ditetapkan ke {$count} admin.");
    }

    public function bulkUpdateRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'uuid'],
            'role_id' => ['required', 'uuid'],
        ]);

        $role = Role::where('scope', 'operator_saas')
            ->where('is_active', true)
            ->findOrFail($validated['role_id']);

        $count = ModelHasRole::whereIn('id', $validated['ids'])
            ->where('model_type', AdminSaas::class)
            ->update(['role_id' => $role->id]);

        return back()->with('success', "Role {$count} mapping berhasil diubah menjadi \"{$role->name}\".");
    }

    public function adminsAjax(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = AdminSaas::where('is_active', true)->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate($perPage)
            ->through(fn($a) => [
                'value' => $a->id,
                'label' => $a->name . ($a->email ? ' — ' . $a->email : ''),
                'email' => $a->email,
            ]);

        return response()->json($items);
    }

    public function rolesAjax(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Role::where('scope', 'operator_saas')
            ->where('is_active', true)
            ->orderBy('display_order');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $items = $query->paginate($perPage)
            ->through(fn($r) => [
                'value' => $r->id,
                'label' => $r->name,
            ]);

        return response()->json($items);
    }
}
