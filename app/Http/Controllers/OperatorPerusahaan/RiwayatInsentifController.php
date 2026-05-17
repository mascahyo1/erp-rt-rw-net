<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\EmpIncentiveLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RiwayatInsentifController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $query = EmpIncentiveLog::query()->with(['empIncentive', 'invoice.custInternet.customer', 'createdBy', 'updatedBy'])
            ->whereHas('empIncentive', fn($q) => $q->where('company_id', $companyId));

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('empIncentive', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('invoice.custInternet.customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('review_status', $status);
        }

        $allowedSorts = ['amount', 'date', 'review_status', 'created_at'];
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
                'emp_incentive_id' => $item->emp_incentive_id,
                'incentive_name' => $item->empIncentive?->name,
                'cust_internet_invcs_id' => $item->cust_internet_invcs_id,
                'invoice_number' => $item->invoice?->invoice_number,
                'customer_name' => $item->invoice?->custInternet?->customer?->name,
                'amount' => $item->amount,
                'date' => $item->date?->format('Y-m-d'),
                'review_status' => $item->review_status,
                'reviewed_at' => $item->reviewed_at?->format('Y-m-d H:i'),
                'dihapus' => $item->trashed(),
                'deleted_at' => $item->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $item->created_at->format('Y-m-d H:i'),
                'updated_at' => $item->updated_at->format('Y-m-d H:i'),
                'created_by' => $item->createdBy?->name,
                'updated_by' => $item->updatedBy?->name,
            ];
        });

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/RiwayatInsentif', [
            'riwayats' => $items,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'emp_incentive_id' => ['required', 'string', 'exists:emp_incentives,id'],
            'cust_internet_invcs_id' => ['required', 'string', 'exists:cust_internet_invcs,id'],
            'amount' => ['required', 'numeric'],
            'date' => ['required', 'date'],
        ]);

        EmpIncentiveLog::create($validated + ['review_status' => 'pending']);

        return back()->with('success', 'Riwayat insentif berhasil ditambahkan.');
    }

    public function update(Request $request, EmpIncentiveLog $empIncentiveLog): RedirectResponse
    {
        $validated = $request->validate([
            'emp_incentive_id' => ['required', 'string', 'exists:emp_incentives,id'],
            'cust_internet_invcs_id' => ['required', 'string', 'exists:cust_internet_invcs,id'],
            'amount' => ['required', 'numeric'],
            'date' => ['required', 'date'],
        ]);

        $empIncentiveLog->update($validated);

        return back()->with('success', 'Riwayat insentif berhasil diperbarui.');
    }

    public function destroy(EmpIncentiveLog $empIncentiveLog): RedirectResponse
    {
        $empIncentiveLog->delete();
        return back()->with('success', 'Riwayat insentif berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $item = EmpIncentiveLog::withTrashed()->findOrFail($id);
        $item->restore();
        return back()->with('success', 'Riwayat insentif berhasil dipulihkan.');
    }

    /** Approve persetujuan */
    public function approve(string $id): RedirectResponse
    {
        $log = EmpIncentiveLog::findOrFail($id);
        $log->update([
            'review_status' => 'approved',
            'reviewed_by_type' => auth()->check() ? get_class(auth()->user()) : null,
            'reviewed_by_id' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Riwayat insentif berhasil disetujui.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada riwayat insentif yang dipilih.');
        $count = EmpIncentiveLog::whereIn('id', $ids)->delete();
        return back()->with('success', "{$count} riwayat insentif berhasil dihapus.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada riwayat insentif yang dipilih.');
        $count = EmpIncentiveLog::onlyTrashed()->whereIn('id', $ids)->restore();
        return back()->with('success', "{$count} riwayat insentif berhasil dipulihkan.");
    }
}
