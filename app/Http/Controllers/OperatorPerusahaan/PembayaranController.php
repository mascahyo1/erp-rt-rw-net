<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Enums\InternalPaymentMethod;
use App\Enums\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Models\CustInternetInvc;
use App\Models\CustInternetPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PembayaranController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $query = CustInternetPayment::query()->with([
            'custInternetInvc.custInternet.customer',
            'custInternetInvc.custInternet.internetPackage',
            'createdBy',
            'updatedBy',
            'deletedBy',
            'restoredBy',
        ])->whereHas('custInternetInvc.custInternet.customer', fn($q) => $q->where('company_id', $companyId));

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('custInternetInvc.custInternet.customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhereHas('custInternetInvc', fn($sq) => $sq->where('invoice_number', 'like', "%{$search}%"));
            });
        }

        if ($provider = $request->input('provider')) {
            $query->where('provider', $provider);
        }

        if ($paymentMethod = $request->input('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($createdStart = $request->input('created_start')) {
            $query->whereDate('created_at', '>=', $createdStart);
        }

        if ($createdEnd = $request->input('created_end')) {
            $query->whereDate('created_at', '<=', $createdEnd);
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
                'code' => $item->code,
                'cust_internet_invc_id' => $item->cust_internet_invc_id,
                'invoice_number' => $item->custInternetInvc?->invoice_number,
                'kode_paket' => $item->custInternetInvc?->custInternet?->internetPackage?->code,
                'nama_paket' => $item->custInternetInvc?->custInternet?->internetPackage?->name,
                'customer_name' => $item->custInternetInvc?->custInternet?->customer?->name,
                'customer_code' => $item->custInternetInvc?->custInternet?->customer?->customer_code,
                'account_number' => $item->custInternetInvc?->custInternet?->account_number,
                'phone_number' => $item->custInternetInvc?->custInternet?->phone_country_code . ' ' . $item->custInternetInvc?->custInternet?->phone_number,
                'email' => $item->custInternetInvc?->custInternet?->customer?->email,
                'amount_paid' => $item->amount_paid,
                'payment_date' => $item->payment_date?->format('Y-m-d'),
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
                'restored_at' => $item->restored_at?->format('Y-m-d H:i'),
                'created_by' => $item->createdBy?->name,
                'updated_by' => $item->updatedBy?->name,
                'deleted_by' => $item->deletedBy?->name,
                'restored_by' => $item->restoredBy?->name,
            ];
        });

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/RiwayatPembayaran', [
            'pembayarans' => $items,
            'filters' => $request->only([
                'search', 'provider', 'payment_method', 'created_start', 'created_end', 'status',
                'sort_field', 'sort_dir', 'per_page', 'terhapus',
            ]),
            'providerOptions' => PaymentProvider::values(),
            'paymentMethodOptions' => InternalPaymentMethod::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cust_internet_invc_id' => ['required', 'string', 'exists:cust_internet_invcs,id'],
            'amount_paid' => ['required', 'numeric'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['required', Rule::in(InternalPaymentMethod::values())],
            'provider' => ['required', Rule::in(PaymentProvider::values())],
            'proof_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'status_description' => ['nullable', 'string', 'max:500'],
        ]);

        $data = $validated;
        if ($request->hasFile('proof_file')) {
            $data['proof_file'] = $request->file('proof_file')->store('payments', 'public');
        }
        $data['status'] = 'pending';
        $data['code'] = 'BYR-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));

        CustInternetPayment::create($data);

        return back()->with('success', 'Pembayaran berhasil ditambahkan.');
    }

    public function update(Request $request, CustInternetPayment $custInternetPayment): RedirectResponse
    {
        $validated = $request->validate([
            'amount_paid' => ['required', 'numeric'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['required', Rule::in(InternalPaymentMethod::values())],
            'provider' => ['required', Rule::in(PaymentProvider::values())],
            'proof_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'status_description' => ['nullable', 'string', 'max:500'],
        ]);

        $data = $validated;
        if ($request->hasFile('proof_file')) {
            $data['proof_file'] = $request->file('proof_file')->store('payments', 'public');
        }

        $custInternetPayment->update($data);

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

    public function approve(string $id): RedirectResponse
    {
        $payment = CustInternetPayment::findOrFail($id);
        $payment->update([
            'status' => 'paid',
            'status_reason' => 'Disetujui oleh admin perusahaan',
        ]);

        if ($payment->cust_internet_invc_id) {
            $payment->custInternetInvc()->update(['payment_status' => 'paid', 'paid_at' => now()]);
        }

        return back()->with('success', 'Pembayaran berhasil disetujui.');
    }

    public function reject(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'status_reason' => ['required', 'string', 'max:500'],
        ]);

        $payment = CustInternetPayment::findOrFail($id);
        $payment->update([
            'status' => 'rejected',
            'status_reason' => $validated['status_reason'],
        ]);

        return back()->with('success', 'Pembayaran berhasil ditolak.');
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

    public function bulkApprove(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada pembayaran yang dipilih.');

        $count = CustInternetPayment::whereIn('id', $ids)->update([
            'status' => 'paid',
            'status_reason' => 'Disetujui secara bulk oleh admin perusahaan',
        ]);

        return back()->with('success', "{$count} pembayaran berhasil disetujui.");
    }

    public function export(Request $request): BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;

        $query = CustInternetPayment::query()
            ->with(['custInternetInvc.custInternet.customer', 'custInternetInvc.custInternet.internetPackage', 'createdBy'])
            ->whereHas('custInternetInvc.custInternet.customer', fn($q) => $q->where('company_id', $companyId));

        if ($ids = $request->input('ids')) {
            $idsArr = is_array($ids) ? $ids : explode(',', $ids);
            $query->whereIn('id', $idsArr);
        }

        if ($terhapus = $request->input('terhapus')) {
            $terhapus === 'ya' ? $query->onlyTrashed() : $query->whereNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        if ($search = $request->input('search')) {
            $query->where(fn($q) => $q
                ->whereHas('custInternetInvc.custInternet.customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                ->orWhere('payment_method', 'like', "%{$search}%")
                ->orWhereHas('custInternetInvc', fn($sq) => $sq->where('invoice_number', 'like', "%{$search}%"))
            );
        }

        if ($provider = $request->input('provider')) {
            $query->where('provider', $provider);
        }

        if ($paymentMethod = $request->input('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($createdStart = $request->input('created_start')) {
            $query->whereDate('created_at', '>=', $createdStart);
        }

        if ($createdEnd = $request->input('created_end')) {
            $query->whereDate('created_at', '<=', $createdEnd);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Riwayat Pembayaran');

        $headers = ['No', 'Kode Pembayaran', 'Kode Tagihan', 'Kode Paket', 'Nama Paket', 'Kode Pelanggan', 'Nama Pelanggan', 'Email Pelanggan', 'Telp Pelanggan', 'Provider', 'Metode Pembayaran', 'Tgl Bayar', 'Tgl Dibuat', 'Status', 'Nominal'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $statusMap = ['pending' => 'Pending', 'paid' => 'Lunas', 'cancelled' => 'Dibatalkan', 'rejected' => 'Ditolak', 'expired' => 'Kedaluwarsa'];
        $providerMap = ['internal' => 'Internal', 'external' => 'Eksternal'];
        $methodMap = ['tunai' => 'Tunai', 'transfer_manual' => 'Transfer Manual'];

        $row = 2;
        foreach ($payments as $p) {
            $col = 1;
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $row - 1, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p->code ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p->custInternetInvc?->invoice_number ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p->custInternetInvc?->custInternet?->internetPackage?->code ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p->custInternetInvc?->custInternet?->internetPackage?->name ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p->custInternetInvc?->custInternet?->customer?->customer_code ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p->custInternetInvc?->custInternet?->customer?->name ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p->custInternetInvc?->custInternet?->customer?->email ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, ($p->custInternetInvc?->custInternet?->phone_country_code ?? '') . ' ' . ($p->custInternetInvc?->custInternet?->phone_number ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $providerMap[$p->provider] ?? $p->provider, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $methodMap[$p->payment_method] ?? $p->payment_method, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p->payment_date?->format('Y-m-d') ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p->created_at?->format('Y-m-d H:i') ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $statusMap[$p->status] ?? $p->status, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, number_format($p->amount_paid ?? 0, 0, ',', '.'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p->status_description ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p->status_reason ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p->created_at?->format('Y-m-d H:i') ?? '-', DataType::TYPE_STRING);
            $row++;
        }

        $filename = 'riwayat-pembayaran-' . date('Y-m-d-His') . '.xlsx';
        $tempPath = storage_path("app/exports/{$filename}");
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Riwayat Pembayaran');

        $headers = ['No. Invoice', 'Jumlah Bayar', 'Payment Date (YYYY-MM-DD)', 'Provider', 'Metode Pembayaran', 'Status Deskripsi', 'Alasan'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setCellValueExplicit('A2', 'INV-2025-0001', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B2', '150000', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C2', date('Y-m-d'), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D2', 'internal', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E2', 'tunai', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F2', 'Pembayaran lunas', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('G2', '', DataType::TYPE_STRING);

        $sheet->setCellValueExplicit('D3', 'internal / external', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E3', 'tunai / transfer_manual', DataType::TYPE_STRING);

        $filename = 'template-riwayat-pembayaran.xlsx';
        $tempPath = storage_path("app/temp/{$filename}");
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $path = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (count($rows) < 2) {
            return back()->with('error', 'File tidak memiliki data.');
        }

        $header = array_map('strtolower', array_map('trim', $rows[0]));
        array_shift($rows);

        $companyId = auth()->user()->company_id;
        $imported = 0;
        $errors = [];

        foreach ($rows as $idx => $row) {
            if (empty(array_filter($row))) continue;

            $data = array_combine($header, $row);
            $line = $idx + 2;

            $invoiceNumber = trim($data['no. invoice'] ?? '');
            $amountPaid = trim($data['jumlah bayar'] ?? '');
            $paymentDate = trim($data['payment date (yyyy-mm-dd)'] ?? '');
            $provider = trim($data['provider'] ?? '');
            $paymentMethod = trim($data['metode pembayaran'] ?? '');
            $statusDescription = trim($data['status deskripsi'] ?? '');
            $statusReason = trim($data['alasan'] ?? '');

            if (!$invoiceNumber || !$amountPaid) {
                $errors[] = "Baris {$line}: No. Invoice dan Jumlah Bayar wajib diisi.";
                continue;
            }

            if (!in_array($provider, PaymentProvider::values())) {
                $errors[] = "Baris {$line}: Provider tidak valid (internal/external).";
                continue;
            }

            if (!in_array($paymentMethod, InternalPaymentMethod::values())) {
                $errors[] = "Baris {$line}: Metode pembayaran tidak valid (tunai/transfer_manual).";
                continue;
            }

            $invoice = CustInternetInvc::where('invoice_number', $invoiceNumber)
                ->whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))
                ->first();

            if (!$invoice) {
                $errors[] = "Baris {$line}: Invoice {$invoiceNumber} tidak ditemukan.";
                continue;
            }

            CustInternetPayment::create([
                'cust_internet_invc_id' => $invoice->id,
                'amount_paid' => (float) $amountPaid,
                'payment_date' => $paymentDate ?: now(),
                'provider' => $provider,
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'status_description' => $statusDescription ?: null,
                'status_reason' => $statusReason ?: null,
            ]);
            $imported++;
        }

        if ($imported > 0) {
            $msg = "{$imported} pembayaran berhasil diimport.";
            if (!empty($errors)) {
                $msg .= " " . count($errors) . " baris dilewati.";
            }
            return back()->with(empty($errors) ? 'success' : 'warning', $msg);
        }

        return back()->with('error', 'Gagal mengimport pembayaran. ' . implode(' ', array_slice($errors, 0, 5)));
    }

    private function excelColumn(int $index): string
    {
        $col = '';
        while ($index > 0) {
            $index--;
            $col = chr(65 + ($index % 26)) . $col;
            $index = intdiv($index, 26);
        }
        return $col;
    }
}