<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\CustInternetPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PembayaranController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $query = CustInternetPayment::query()->with(['custInternetInvc.custInternet.customer', 'createdBy', 'updatedBy'])
            ->whereHas('custInternetInvc.custInternet.customer', fn($q) => $q->where('company_id', $companyId));

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('custInternetInvc.custInternet.customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhere('status_description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $allowedSorts = ['amount_paid', 'payment_method', 'status', 'created_at', 'deleted_at'];
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
                'cust_internet_invc_id' => $item->cust_internet_invc_id,
                'invoice_number' => $item->custInternetInvc?->invoice_number,
                'customer_name' => $item->custInternetInvc?->custInternet?->customer?->name,
                'amount_paid' => $item->amount_paid,
                'payment_method' => $item->payment_method,
                'status' => $item->status,
                'status_description' => $item->status_description,
                'status_reason' => $item->status_reason,
                'provider' => $item->provider,
                'proof_file' => $item->proof_file,
                'dihapus' => $item->trashed(),
                'deleted_at' => $item->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $item->created_at->format('Y-m-d H:i'),
                'updated_at' => $item->updated_at->format('Y-m-d H:i'),
                'created_by' => $item->createdBy?->name,
                'updated_by' => $item->updatedBy?->name,
            ];
        });

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/RiwayatPembayaran', [
            'pembayarans' => $items,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cust_internet_invc_id' => ['required', 'string', 'exists:cust_internet_invcs,id'],
            'amount_paid' => ['required', 'numeric'],
            'payment_method' => ['required', 'string', 'max:100'],
            'provider' => ['required', 'string', 'max:100'],
            'proof_file' => ['nullable', 'file', 'max:2048'],
            'status_description' => ['nullable', 'string', 'max:500'],
        ]);

        $data = $validated;
        if ($request->hasFile('proof_file')) {
            $data['proof_file'] = $request->file('proof_file')->store('payments', 'public');
        }
        $data['status'] = 'paid';

        CustInternetPayment::create($data);

        return back()->with('success', 'Pembayaran berhasil ditambahkan.');
    }

    public function update(Request $request, CustInternetPayment $custInternetPayment): RedirectResponse
    {
        $validated = $request->validate([
            'amount_paid' => ['required', 'numeric'],
            'payment_method' => ['required', 'string', 'max:100'],
            'provider' => ['required', 'string', 'max:100'],
            'status_description' => ['nullable', 'string', 'max:500'],
        ]);

        $custInternetPayment->update($validated);

        return back()->with('success', 'Pembayaran berhasil diperbarui.');
    }

    public function destroy(CustInternetPayment $custInternetPayment): RedirectResponse
    {
        $custInternetPayment->delete();
        return back()->with('success', 'Pembayaran berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $item = CustInternetPayment::withTrashed()->findOrFail($id);
        $item->restore();
        return back()->with('success', 'Pembayaran berhasil dipulihkan.');
    }

    /** Approve payment — persetujuan permission */
    public function approve(string $id): RedirectResponse
    {
        $payment = CustInternetPayment::findOrFail($id);
        $payment->update([
            'status' => 'paid',
            'status_reason' => 'Disetujui oleh admin perusahaan',
        ]);

        // Update invoice status
        if ($payment->cust_internet_invc_id) {
            $payment->custInternetInvc()->update(['payment_status' => 'paid', 'paid_at' => now()]);
        }

        return back()->with('success', 'Pembayaran berhasil disetujui.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada pembayaran yang dipilih.');
        $count = CustInternetPayment::whereIn('id', $ids)->delete();
        return back()->with('success', "{$count} pembayaran berhasil dihapus.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada pembayaran yang dipilih.');
        $count = CustInternetPayment::onlyTrashed()->whereIn('id', $ids)->restore();
        return back()->with('success', "{$count} pembayaran berhasil dipulihkan.");
    }
}
