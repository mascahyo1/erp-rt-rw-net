<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InternetPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search customer — scoped ke company user yang login.
     * Dipakai oleh SearchableSelectAjax.
     */
    public function customers(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $search = $request->input('search');
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Customer::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate($perPage)
            ->through(fn($c) => [
                'value' => $c->id,
                'label' => $c->name,
                'email' => $c->email,
                'phone' => ($c->phone_country_code ?? '') . ' ' . ($c->phone_number ?? ''),
            ]);

        return response()->json($customers);
    }

    /**
     * Search paket internet — scoped ke company user yang login.
     */
    public function packages(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $search = $request->input('search');

        $query = InternetPackage::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->boolean('all')) {
            $items = $query->get()->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ]);
            return response()->json(['items' => $items]);
        }

        $perPage = min((int) $request->input('per_page', 25), 100);
        $packages = $query->paginate($perPage)
            ->through(fn($p) => [
                'value' => $p->id,
                'label' => $p->name . ' — Rp ' . number_format($p->price, 0, ',', '.'),
            ]);

        return response()->json($packages);
    }

    /**
     * Search langganan (cust_internet) — scoped ke company user login.
     * Dipakai saat tambah tagihan — pilih langganan pelanggan.
     */
    public function langganans(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $search = $request->input('search');
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = \App\Models\CustInternet::with('customer')
            ->whereHas('customer', fn($q) => $q->where('company_id', $companyId))
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('account_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $items = $query->paginate($perPage)
            ->through(fn($l) => [
                'value' => $l->id,
                'label' => ($l->customer?->name ?? 'N/A') . ' — ' . ($l->account_number ?? 'No.Acc'),
            ]);

        return response()->json($items);
    }

    /**
     * Search invoice — scoped ke company user login.
     * Dipakai saat tambah pembayaran — pilih invoice yang mau dibayar.
     */
    public function invoices(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $search = $request->input('search');
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = \App\Models\CustInternetInvc::with('custInternet.customer')
            ->whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))
            ->whereIn('payment_status', ['unpaid', 'pending'])
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('custInternet.customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $items = $query->paginate($perPage)
            ->through(fn($inv) => [
                'value' => $inv->id,
                'label' => $inv->invoice_number . ' — ' . ($inv->custInternet?->customer?->name ?? 'N/A') . ' (Rp ' . number_format($inv->grand_total ?? $inv->amount, 0, ',', '.') . ')',
            ]);

        return response()->json($items);
    }

    /**
     * Search insentif — scoped ke company user login.
     */
    public function incentives(Request $request): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $search = $request->input('search');
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = \App\Models\EmpIncentive::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $items = $query->paginate($perPage)
            ->through(fn($i) => [
                'value' => $i->id,
                'label' => $i->name . ' (' . $i->type . ' — ' . number_format($i->value, 0, ',', '.') . ')',
            ]);

        return response()->json($items);
    }
}
