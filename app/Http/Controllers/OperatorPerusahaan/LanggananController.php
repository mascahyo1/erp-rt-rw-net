<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\CustInternet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LanggananController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $query = CustInternet::query()->with(['customer', 'internetPackage'])
            ->whereHas('customer', fn($q) => $q->where('company_id', $companyId));

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('internet_status', $status);
        }

        $allowedSorts = ['internet_status', 'billing_amount', 'created_at', 'deleted_at'];
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
                'customer_id' => $item->customer_id,
                'customer_name' => $item->customer?->name,
                'internet_package_id' => $item->internet_package_id,
                'internet_package_name' => $item->internetPackage?->name,
                'internet_status' => $item->internet_status,
                'usage_upload_kb' => $item->usage_upload_kb,
                'usage_download_kb' => $item->usage_download_kb,
                'company_notes' => $item->company_notes,
                'account_number' => $item->account_number,
                'dihapus' => $item->trashed(),
                'deleted_at' => $item->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $item->created_at->format('Y-m-d H:i'),
            ];
        });

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/LanggananCustomer', [
            'langganans' => $items,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'string', 'exists:customers,id'],
            'internet_package_id' => ['required', 'string', 'exists:internet_packages,id'],
            'internet_status' => ['required', 'string', Rule::in(['active', 'inactive', 'suspended', 'terminated', 'company_notes'])],
            'usage_upload_kb' => ['nullable', 'numeric'],
            'usage_download_kb' => ['nullable', 'numeric'],
            'company_notes' => ['nullable', 'string', 'max:500'],
        ]);

        CustInternet::create($validated);

        return back()->with('success', 'Langganan berhasil ditambahkan.');
    }

    public function update(Request $request, CustInternet $custInternet): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'string', 'exists:customers,id'],
            'internet_package_id' => ['required', 'string', 'exists:internet_packages,id'],
            'internet_status' => ['required', 'string', Rule::in(['active', 'inactive', 'suspended', 'terminated', 'company_notes'])],
            'usage_upload_kb' => ['nullable', 'numeric'],
            'usage_download_kb' => ['nullable', 'numeric'],
            'company_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $custInternet->update($validated);

        return back()->with('success', 'Langganan berhasil diperbarui.');
    }

    public function destroy(CustInternet $custInternet): RedirectResponse
    {
        $custInternet->delete();
        return back()->with('success', 'Langganan berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $item = CustInternet::withTrashed()->findOrFail($id);
        $item->restore();
        return back()->with('success', 'Langganan berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada langganan yang dipilih.');
        $count = CustInternet::whereIn('id', $ids)->delete();
        return back()->with('success', "{$count} langganan berhasil dihapus.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');
        if (empty($ids) || !in_array($status, ['active', 'inactive'])) {
            return back()->with('error', 'Data tidak valid.');
        }
        $count = CustInternet::whereIn('id', $ids)->update(['internet_status' => $status]);
        $label = $status === 'active' ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "{$count} langganan berhasil {$label}.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada langganan yang dipilih.');
        $count = CustInternet::onlyTrashed()->whereIn('id', $ids)->restore();
        return back()->with('success', "{$count} langganan berhasil dipulihkan.");
    }
}
