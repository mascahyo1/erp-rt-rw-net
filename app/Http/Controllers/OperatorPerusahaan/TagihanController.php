<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\CustInternetInvc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TagihanController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $query = CustInternetInvc::query()->with(['custInternet.customer', 'createdBy', 'updatedBy', 'deletedBy', 'restoredBy'])
            ->whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId));

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('custInternet.customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('payment_status', $status);
        }

        $allowedSorts = ['invoice_number', 'total_amount', 'due_date', 'payment_status', 'created_at', 'deleted_at'];
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
                'invoice_number' => $item->invoice_number,
                'cust_internet_id' => $item->cust_internet_id,
                'customer_name' => $item->custInternet?->customer?->name,
                'account_number' => $item->custInternet?->account_number,
                'total_amount' => $item->total_amount,
                'discount_amount' => $item->discount_amount,
                'tax_amount' => $item->tax_amount,
                'grand_total' => $item->grand_total,
                'due_date' => $item->due_date?->format('Y-m-d'),
                'payment_status' => $item->payment_status,
                'paid_at' => $item->paid_at?->format('Y-m-d H:i'),
                'description' => $item->description,
                'dihapus' => $item->trashed(),
                'deleted_at' => $item->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $item->created_at->format('Y-m-d H:i'),
                'updated_at' => $item->updated_at->format('Y-m-d H:i'),
                'restored_at' => $item->restored_at?->format('Y-m-d H:i'),
                'created_by' => $item->createdBy?->name,
                'updated_by' => $item->updatedBy?->name,
                'deleted_by' => $item->deletedBy?->name,
                'restored_by' => $item->restoredBy?->name,
            ];
        });

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/Tagihan', [
            'tagihans' => $items,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cust_internet_id' => ['required', 'string', 'exists:cust_internets,id'],
            'total_amount' => ['required', 'numeric'],
            'discount_amount' => ['nullable', 'numeric'],
            'tax_amount' => ['nullable', 'numeric'],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $grandTotal = ($validated['total_amount'] ?? 0) - ($validated['discount_amount'] ?? 0) + ($validated['tax_amount'] ?? 0);

        CustInternetInvc::create($validated + [
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . rand(1000, 9999),
            'grand_total' => $grandTotal,
            'payment_status' => 'unpaid',
        ]);

        return back()->with('success', 'Tagihan berhasil ditambahkan.');
    }

    public function update(Request $request, CustInternetInvc $custInternetInvc): RedirectResponse
    {
        $validated = $request->validate([
            'cust_internet_id' => ['required', 'string', 'exists:cust_internets,id'],
            'total_amount' => ['required', 'numeric'],
            'discount_amount' => ['nullable', 'numeric'],
            'tax_amount' => ['nullable', 'numeric'],
            'due_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $grandTotal = ($validated['total_amount'] ?? 0) - ($validated['discount_amount'] ?? 0) + ($validated['tax_amount'] ?? 0);

        $custInternetInvc->update($validated + ['grand_total' => $grandTotal]);

        return back()->with('success', 'Tagihan berhasil diperbarui.');
    }

    public function destroy(CustInternetInvc $custInternetInvc): RedirectResponse
    {
        $custInternetInvc->delete();
        return back()->with('success', 'Tagihan berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $item = CustInternetInvc::withTrashed()->findOrFail($id);
        $item->restore();
        return back()->with('success', 'Tagihan berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada tagihan yang dipilih.');
        $count = CustInternetInvc::whereIn('id', $ids)->delete();
        return back()->with('success', "{$count} tagihan berhasil dihapus.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');
        if (empty($ids) || !in_array($status, ['paid', 'unpaid'])) {
            return back()->with('error', 'Data tidak valid.');
        }
        $count = CustInternetInvc::whereIn('id', $ids)->update([
            'payment_status' => $status,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);
        return back()->with('success', "{$count} tagihan statusnya diubah.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada tagihan yang dipilih.');
        $count = CustInternetInvc::onlyTrashed()->whereIn('id', $ids)->restore();
        return back()->with('success', "{$count} tagihan berhasil dipulihkan.");
    }
}
