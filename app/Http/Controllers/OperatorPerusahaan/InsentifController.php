<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\EmpIncentive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InsentifController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $query = EmpIncentive::query()->with(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy'])
            ->where('company_id', $companyId);

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('is_active', $status === 'Aktif' || $status === 'aktif');
        }

        $allowedSorts = ['name', 'value', 'type', 'is_active', 'created_at'];
        if ($sortField = $request->input('sort_field')) {
            $sortDir = $request->input('sort_dir', 'asc');
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDir);
            }
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->input('per_page', 10), 100);

        $items = $query->paginate($perPage)->through(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'value' => $item->value,
                'is_active' => $item->is_active,
                'status' => $item->is_active ? 'Aktif' : 'Nonaktif',
                'description' => $item->description,
                'company_id' => $item->company_id,
                'dihapus' => $item->trashed(),
                'deleted_at' => $item->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $item->created_at->format('Y-m-d H:i'),
                'updated_at' => $item->updated_at->format('Y-m-d H:i'),
                'created_by' => $item->createdBy?->name,
                'updated_by' => $item->updatedBy?->name,
                'deleted_by' => $item->deletedBy?->name,
            ];
        });

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/Insentif', [
            'insentifs' => $items,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        EmpIncentive::create([
            'company_id' => auth()->user()->company_id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'is_active' => $validated['status'] === 'Aktif',
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Insentif berhasil ditambahkan.');
    }

    public function update(Request $request, EmpIncentive $empIncentive): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $empIncentive->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'is_active' => $validated['status'] === 'Aktif',
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Insentif berhasil diperbarui.');
    }

    public function destroy(EmpIncentive $empIncentive): RedirectResponse
    {
        $empIncentive->delete();
        return back()->with('success', 'Insentif berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $item = EmpIncentive::withTrashed()->findOrFail($id);
        $item->restore();
        return back()->with('success', 'Insentif berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada insentif yang dipilih.');
        $count = EmpIncentive::whereIn('id', $ids)->delete();
        return back()->with('success', "{$count} insentif berhasil dihapus.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');
        if (empty($ids) || !in_array($status, ['Aktif', 'Nonaktif'])) {
            return back()->with('error', 'Data tidak valid.');
        }
        $count = EmpIncentive::whereIn('id', $ids)->update(['is_active' => $status === 'Aktif']);
        $label = $status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "{$count} insentif berhasil {$label}.");
    }
}
