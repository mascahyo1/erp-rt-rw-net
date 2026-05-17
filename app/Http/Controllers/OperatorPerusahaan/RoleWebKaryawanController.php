<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleWebKaryawanController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $query = Role::query()
            ->with(['permissions', 'createdBy', 'updatedBy', 'deletedBy', 'restoredBy'])
            ->where('scope', 'karyawan_perusahaan')
            ->where('company_id', $companyId);

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('is_active', $status === 'Aktif');
        }

        if ($sortField = $request->input('sort_field')) {
            $allowedSorts = ['name', 'display_order', 'is_active', 'created_at', 'deleted_at'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $request->input('sort_dir', 'asc'));
            }
        } else {
            $query->orderBy('display_order')->latest();
        }

        $perPage = min((int) $request->input('per_page', 10), 100);

        $roles = $query->paginate($perPage)->through(fn($r) => [
            'id' => $r->id,
            'nama_role' => $r->name,
            'permission_count' => $r->permissions->count(),
            'display_order' => $r->display_order,
            'deskripsi' => $r->description,
            'status' => $r->is_active ? 'Aktif' : 'Nonaktif',
            'is_active' => $r->is_active,
            'dihapus' => $r->trashed(),
            'deleted_at' => $r->deleted_at?->format('Y-m-d H:i'),
            'restored_at' => $r->restored_at?->format('Y-m-d H:i'),
            'created_at' => $r->created_at->format('Y-m-d H:i'),
            'updated_at' => $r->updated_at->format('Y-m-d H:i'),
            'created_by' => $r->createdBy?->name,
            'updated_by' => $r->updatedBy?->name,
        ]);

        $permissions = Permission::where('scope', 'karyawan_perusahaan')
            ->orderBy('display_order')
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'nama' => $p->name, 'deskripsi' => $p->description]);

        return Inertia::render('OperatorPerusahaan/RoleWebKaryawan', [
            'roles' => $roles,
            'permissions' => $permissions,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'nama_role' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['uuid', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'scope' => 'karyawan_perusahaan',
            'company_id' => $companyId,
            'name' => $validated['nama_role'],
            'description' => $validated['deskripsi'] ?? null,
            'is_active' => $validated['status'] === 'Aktif',
            'display_order' => Role::where('scope', 'karyawan_perusahaan')
                ->where('company_id', $companyId)
                ->max('display_order') + 1,
        ]);

        if (!empty($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        return back()->with('success', 'Role web karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'nama_role' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['uuid', 'exists:permissions,id'],
        ]);

        $role->update([
            'name' => $validated['nama_role'],
            'description' => $validated['deskripsi'] ?? null,
            'is_active' => $validated['status'] === 'Aktif',
        ]);

        $role->permissions()->sync($validated['permission_ids'] ?? []);

        return back()->with('success', 'Role web karyawan berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $role->delete();
        return back()->with('success', 'Role web karyawan berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $role = Role::withTrashed()->findOrFail($id);
        $role->restore();
        return back()->with('success', 'Role web karyawan berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada role yang dipilih.');
        $count = Role::whereIn('id', $ids)->delete();
        return back()->with('success', "{$count} role web karyawan berhasil dihapus.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');
        if (empty($ids) || !in_array($status, ['Aktif', 'Nonaktif'])) return back()->with('error', 'Data tidak valid.');
        $count = Role::whereIn('id', $ids)->update(['is_active' => $status === 'Aktif']);
        $label = $status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "{$count} role web karyawan berhasil {$label}.");
    }
}
