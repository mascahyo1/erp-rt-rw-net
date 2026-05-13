<?php

namespace App\Http\Controllers;

use App\Models\AdminSaas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminSaasController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AdminSaas::query()->with(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy']);

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
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

        $admins = $query->paginate($perPage)->through(function ($admin) {
            return [
                'id' => $admin->id,
                'nama' => $admin->name,
                'email' => $admin->email,
                'kode_negara' => $admin->phone_country_code,
                'no_telp' => $admin->phone_number,
                'status' => $admin->is_active ? 'Aktif' : 'Nonaktif',
                'is_active' => $admin->is_active,
                'deleted_at_raw' => $admin->deleted_at?->toISOString(),
                'deleted_at' => $admin->deleted_at?->format('Y-m-d H:i'),
                'dihapus' => $admin->trashed(),
                'created_at' => $admin->created_at->format('Y-m-d H:i'),
                'updated_at' => $admin->updated_at->format('Y-m-d H:i'),
                'restored_at' => $admin->restored_at?->format('Y-m-d H:i'),
                'created_by' => $admin->createdBy?->name,
                'updated_by' => $admin->updatedBy?->name,
                'deleted_by' => $admin->deletedBy?->name,
                'restored_by' => $admin->restoredBy?->name,
            ];
        });

        return Inertia::render('OperatorSaas/AdminSaaS', [
            'admins' => $admins,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:admin_saas'],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => ['required', 'string', 'max:20'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        AdminSaas::create([
            'name' => $validated['nama'],
            'email' => $validated['email'],
            'phone_country_code' => $validated['kode_negara'],
            'phone_number' => $validated['no_telp'],
            'is_active' => $validated['status'] === 'Aktif',
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Admin SaaS berhasil ditambahkan.');
    }

    public function update(Request $request, AdminSaas $adminSaas): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('admin_saas')->ignore($adminSaas->id),
            ],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => ['required', 'string', 'max:20'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $data = [
            'name' => $validated['nama'],
            'email' => $validated['email'],
            'phone_country_code' => $validated['kode_negara'],
            'phone_number' => $validated['no_telp'],
            'is_active' => $validated['status'] === 'Aktif',
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $adminSaas->update($data);

        return back()->with('success', 'Admin SaaS berhasil diperbarui.');
    }

    public function destroy(AdminSaas $adminSaas): RedirectResponse
    {
        $adminSaas->delete();

        return back()->with('success', 'Admin SaaS berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $admin = AdminSaas::withTrashed()->findOrFail($id);
        $admin->restore();

        return back()->with('success', 'Admin SaaS berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada admin yang dipilih.');
        }

        $count = AdminSaas::whereIn('id', $ids)->delete();

        return back()->with('success', "{$count} admin SaaS berhasil dihapus.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');

        if (empty($ids) || !in_array($status, ['Aktif', 'Nonaktif'])) {
            return back()->with('error', 'Data tidak valid.');
        }

        $count = AdminSaas::whereIn('id', $ids)->update([
            'is_active' => $status === 'Aktif',
        ]);

        $label = $status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "{$count} admin SaaS berhasil {$label}.");
    }
}
