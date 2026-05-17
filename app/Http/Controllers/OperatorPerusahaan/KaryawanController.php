<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KaryawanController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Employee::query()->with(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy'])
            ->where('company_id', auth()->user()->company_id);

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

        $karyawans = $query->paginate($perPage)->through(function ($employee) {
            return [
                'id' => $employee->id,
                'nama' => $employee->name,
                'email' => $employee->email,
                'kode_negara' => $employee->phone_country_code,
                'no_telp' => $employee->phone_number,
                'status' => $employee->is_active ? 'Aktif' : 'Nonaktif',
                'is_active' => $employee->is_active,
                'company_id' => $employee->company_id,
                'dihapus' => $employee->trashed(),
                'deleted_at_raw' => $employee->deleted_at?->toISOString(),
                'deleted_at' => $employee->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $employee->created_at->format('Y-m-d H:i'),
                'updated_at' => $employee->updated_at->format('Y-m-d H:i'),
                'restored_at' => $employee->restored_at?->format('Y-m-d H:i'),
                'created_by' => $employee->createdBy?->name,
                'updated_by' => $employee->updatedBy?->name,
                'deleted_by' => $employee->deletedBy?->name,
                'restored_by' => $employee->restoredBy?->name,
            ];
        });

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/Karyawan', [
            'karyawans' => $karyawans,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('employees')->where('company_id', auth()->user()->company_id),
            ],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => ['required', 'string', 'max:20'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        Employee::create([
            'company_id' => auth()->user()->company_id,
            'name' => $validated['nama'],
            'email' => $validated['email'],
            'phone_country_code' => $validated['kode_negara'],
            'phone_number' => $validated['no_telp'],
            'is_active' => $validated['status'] === 'Aktif',
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('employees')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($employee->id),
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

        $employee->update($data);

        return back()->with('success', 'Karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return back()->with('success', 'Karyawan berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $karyawan = Employee::withTrashed()->findOrFail($id);
        $karyawan->restore();

        return back()->with('success', 'Karyawan berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada karyawan yang dipilih.');
        }

        $count = Employee::whereIn('id', $ids)->delete();

        return back()->with('success', "{$count} karyawan berhasil dihapus.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');

        if (empty($ids) || !in_array($status, ['Aktif', 'Nonaktif'])) {
            return back()->with('error', 'Data tidak valid.');
        }

        $count = Employee::whereIn('id', $ids)->update([
            'is_active' => $status === 'Aktif',
        ]);

        $label = $status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "{$count} karyawan berhasil {$label}.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada karyawan yang dipilih.');
        $count = Employee::onlyTrashed()->whereIn('id', $ids)->restore();
        return back()->with('success', "{$count} karyawan berhasil dipulihkan.");
    }
}
