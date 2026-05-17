<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

abstract class CrudController extends Controller
{
    abstract protected function model(): string;
    abstract protected function view(): string;
    abstract protected function scopeColumn(): string;
    abstract protected function searchFields(): array;
    abstract protected function allowedSorts(): array;
    abstract protected function mapItem(mixed $item): array;
    abstract protected function validateRules(string $action): array;
    abstract protected function createItem(array $validated): void;
    abstract protected function updateItem(mixed $model, array $validated): void;
    abstract protected function successMessages(): array;

    public function index(Request $request): Response
    {
        $modelClass = $this->model();
        $query = $modelClass::query()->with($this->eagerLoad());
        $scopeCol = $this->scopeColumn();
        if ($scopeCol === 'company_id') {
            $query->where('company_id', auth()->user()->company_id);
        }

        if ($request->input('terhapus') === 'ya') $query->onlyTrashed();

        if ($search = $request->input('search')) {
            $fields = $this->searchFields();
            $query->where(function ($q) use ($search, $fields) {
                foreach ($fields as $f) $q->orWhere($f, 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $this->applyStatusFilter($query, $status);
        }

        if ($sortField = $request->input('sort_field')) {
            $sortDir = $request->input('sort_dir', 'asc');
            if (in_array($sortField, $this->allowedSorts())) {
                $query->orderBy($sortField, $sortDir);
            }
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $items = $query->paginate($perPage)->through(fn($item) => $this->mapItem($item));

        return Inertia::render($this->view(), [
            'items' => $items,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validateRules('store'));
        $this->createItem($validated);
        $msgs = $this->successMessages();
        return back()->with('success', $msgs['store'] ?? 'Data berhasil ditambahkan.');
    }

    public function update(Request $request, mixed $model): RedirectResponse
    {
        $validated = $request->validate($this->validateRules('update'));
        $this->updateItem($model, $validated);
        $msgs = $this->successMessages();
        return back()->with('success', $msgs['update'] ?? 'Data berhasil diperbarui.');
    }

    public function destroy(mixed $model): RedirectResponse
    {
        $model->delete();
        $msgs = $this->successMessages();
        return back()->with('success', $msgs['delete'] ?? 'Data berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $modelClass = $this->model();
        $item = $modelClass::withTrashed()->findOrFail($id);
        $item->restore();
        $msgs = $this->successMessages();
        return back()->with('success', $msgs['restore'] ?? 'Data berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada data yang dipilih.');
        $modelClass = $this->model();
        $count = $modelClass::whereIn('id', $ids)->delete();
        return back()->with('success', "{$count} data berhasil dihapus.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');
        if (empty($ids) || !in_array($status, ['Aktif', 'Nonaktif'])) return back()->with('error', 'Data tidak valid.');
        $modelClass = $this->model();
        $count = $modelClass::whereIn('id', $ids)->update(['is_active' => $status === 'Aktif']);
        $label = $status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "{$count} data berhasil {$label}.");
    }

    protected function eagerLoad(): array { return ['createdBy', 'updatedBy', 'deletedBy', 'restoredBy']; }
    protected function applyStatusFilter($query, string $status): void {
        $query->where('is_active', $status === 'Aktif' || $status === 'aktif');
    }
}
