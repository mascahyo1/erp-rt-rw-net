<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Enums\InternalPaymentMethod;
use App\Enums\PaymentProvider;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CustInternetInvc;
use App\Models\CustInternetPayment;
use App\Services\FileUploadService;
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

        if ($invoiceNumber = $request->input('invoice_number')) {
            $query->where('cust_internet_invc_id', $invoiceNumber);
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
            $proofFileUrl = $item->proof_file
                ? route('file.proxy', ['path' => $item->proof_file, 'disk' => 'minio'])
                : null;

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
                'proof_file_url' => $proofFileUrl,
                'dihapus' => $item->trashed(),
                'deleted_at' => $item->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $item->created_at->format('Y-m-d H:i'),
                'updated_at' => $item->updated_at->format('Y-m-d H:i'),
                'restored_at' => is_string($item->restored_at) ? $item->restored_at : $item->restored_at?->format('Y-m-d H:i'),
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
            'code' => ['required', 'string', 'max:50', 'unique:cust_internet_payments,code'],
            'cust_internet_invc_id' => ['required', 'string', 'exists:cust_internet_invcs,id'],
            'amount_paid' => ['required', 'numeric'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['required', Rule::in(InternalPaymentMethod::values())],
            'provider' => ['required', Rule::in(PaymentProvider::values())],
            'proof_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'status_description' => ['nullable', 'string', 'max:500'],
        ]);

        $data = $validated;
        $uploadService = new FileUploadService();
        if ($request->hasFile('proof_file')) {
            $data['proof_file'] = $uploadService->processDocument($request->file('proof_file'), 'payments');
        }
        $data['status'] = 'pending';

        CustInternetPayment::create($data);

        return back()->with('success', 'Pembayaran berhasil ditambahkan.');
    }

    public function update(Request $request, CustInternetPayment $custInternetPayment): RedirectResponse
    {
        // GUARD: Pembayaran non-internal (Midtrans/External/Customer Portal) tidak bisa diedit manual.
        // Untuk Midtrans pending, gunakan tombol "Sinkron Status Midtrans" (verifikasi manual).
        if ($custInternetPayment->provider !== PaymentProvider::INTERNAL->value) {
            return back()->with('error', 'Pembayaran dengan provider non-internal tidak dapat diedit manual. Gunakan tombol Sinkron Status Midtrans untuk data dari payment gateway.');
        }

        $validated = $request->validate([
            'amount_paid' => ['required', 'numeric'],
            'payment_date' => ['nullable', 'date'],
            'payment_method' => ['required', Rule::in(InternalPaymentMethod::values())],
            'provider' => ['required', Rule::in(PaymentProvider::values())],
            'proof_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'status_description' => ['nullable', 'string', 'max:500'],
        ]);

        $data = $validated;
        $uploadService = new FileUploadService();
        if ($request->hasFile('proof_file')) {
            if ($custInternetPayment->proof_file) {
                $uploadService->deleteFile($custInternetPayment->proof_file);
            }
            $data['proof_file'] = $uploadService->processDocument($request->file('proof_file'), 'payments');
        }

        $custInternetPayment->update($data);

        return back()->with('success', 'Pembayaran berhasil diperbarui.');
    }

    public function destroy(CustInternetPayment $custInternetPayment): RedirectResponse
    {
        // GUARD: Pembayaran non-internal tidak boleh dihapus (dibuat otomatis oleh payment gateway/customer portal).
        if ($custInternetPayment->provider !== PaymentProvider::INTERNAL->value) {
            return back()->with('error', 'Pembayaran non-internal tidak dapat dihapus.');
        }
        $custInternetPayment->delete();
        return back()->with('success', 'Pembayaran berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $item = CustInternetPayment::withTrashed()->findOrFail($id);
        $item->restore();
        return back()->with('success', 'Pembayaran berhasil dipulihkan.');
    }

    public function review(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'review_status' => ['required', 'string', Rule::in(['approved', 'rejected'])],
            'review_reason' => ['nullable', 'string', 'max:500'],
            'review_attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ]);

        $payment = CustInternetPayment::findOrFail($id);

        // GUARD: Pembayaran non-internal tidak bisa di-review manual (status final-nya ditentukan payment gateway).
        if ($payment->provider !== PaymentProvider::INTERNAL->value) {
            return back()->with('error', 'Pembayaran non-internal tidak dapat di-review. Gunakan Sinkron Status Midtrans untuk data dari payment gateway.');
        }

        $updateData = [
            'status' => $validated['review_status'] === 'approved' ? 'paid' : 'rejected',
            'status_reason' => $validated['review_reason'] ?: null,
        ];

        $uploadService = new FileUploadService();
        if ($request->hasFile('review_attachment')) {
            $updateData['review_attachment'] = $uploadService->processDocument($request->file('review_attachment'), 'payment-reviews');
        }

        $payment->update($updateData);

        if ($validated['review_status'] === 'approved' && $payment->cust_internet_invc_id) {
            $payment->custInternetInvc()->update(['payment_status' => 'paid', 'paid_at' => now()]);
        }

        $label = $validated['review_status'] === 'approved' ? 'disetujui' : 'ditolak';
        return back()->with('success', "Pembayaran berhasil {$label}.");
    }

    public function bulkReview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'review_status' => ['required', 'string', Rule::in(['approved', 'rejected'])],
            'review_reason' => ['nullable', 'string', 'max:500'],
            'review_attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ]);

        $reviewAttachmentPath = null;
        $uploadService = new FileUploadService();
        if ($request->hasFile('review_attachment')) {
            $reviewAttachmentPath = $uploadService->processDocument($request->file('review_attachment'), 'payment-reviews');
        }

        // GUARD: Bulk review HANYA untuk payment internal. Payment non-internal di-skip + counter.
        $eligibleIds = CustInternetPayment::whereIn('id', $validated['ids'])
            ->where('provider', PaymentProvider::INTERNAL->value)
            ->pluck('id')->all();
        $skipped = count($validated['ids']) - count($eligibleIds);

        $count = CustInternetPayment::whereIn('id', $eligibleIds)->update([
            'status' => $validated['review_status'] === 'approved' ? 'paid' : 'rejected',
            'status_reason' => $validated['review_reason'] ?: null,
            'review_attachment' => $reviewAttachmentPath,
        ]);

        if ($validated['review_status'] === 'approved') {
            $payments = CustInternetPayment::whereIn('id', $validated['ids'])->whereNotNull('cust_internet_invc_id')->get();
            foreach ($payments as $payment) {
                $payment->custInternetInvc()->update(['payment_status' => 'paid', 'paid_at' => now()]);
            }
        }

        $label = $validated['review_status'] === 'approved' ? 'disetujui' : 'ditolak';
        $msg = "{$count} pembayaran berhasil {$label}.";
        if ($skipped > 0) {
            $msg .= " {$skipped} item non-internal dilewati (hanya bisa di-sinkron via Midtrans).";
        }
        return back()->with('success', $msg);
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

        // GUARD: Bulk approve HANYA untuk payment internal.
        $eligibleIds = CustInternetPayment::whereIn('id', $ids)
            ->where('provider', PaymentProvider::INTERNAL->value)
            ->pluck('id')->all();
        $skipped = count($ids) - count($eligibleIds);

        $count = CustInternetPayment::whereIn('id', $eligibleIds)->update([
            'status' => 'paid',
            'status_reason' => 'Disetujui secara bulk oleh admin perusahaan',
        ]);

        $msg = "{$count} pembayaran berhasil disetujui.";
        if ($skipped > 0) {
            $msg .= " {$skipped} item non-internal dilewati.";
        }
        return back()->with('success', $msg);
    }

    /**
     * AJAX endpoint: verifikasi manual status Midtrans untuk payment pending.
     * Delegate ke MidtransPaymentController::verifyStatus (3 portal — operator/karyawan/customer).
     */
    public function verifyMidtrans(Request $request, string $id): \Illuminate\Http\JsonResponse
    {
        $companyId = auth()->user()->company_id;

        // Verify payment milik company ini DAN provider Midtrans
        $payment = CustInternetPayment::whereHas(
            'custInternetInvc.custInternet.customer',
            fn($q) => $q->where('company_id', $companyId)
        )
            ->where('id', $id)
            ->where('provider', PaymentProvider::MIDTRANS->value)
            ->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment Midtrans tidak ditemukan atau bukan milik perusahaan Anda.'], 404);
        }

        return app(\App\Http\Controllers\Customer\MidtransPaymentController::class)
            ->verifyStatus($request, $id);
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

    public function downloadPdf(string $id): BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;
        $company = Company::find($companyId);
        $payment = CustInternetPayment::with(['custInternetInvc.custInternet.customer', 'custInternetInvc.custInternet.internetPackage'])
            ->whereHas('custInternetInvc.custInternet.customer', fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);

        $data = [
            'code' => $payment->code,
            'invoice_number' => $payment->custInternetInvc?->invoice_number,
            'customer_name' => $payment->custInternetInvc?->custInternet?->customer?->name,
            'customer_code' => $payment->custInternetInvc?->custInternet?->customer?->code,
            'customer_email' => $payment->custInternetInvc?->custInternet?->customer?->email,
            'customer_phone' => ($payment->custInternetInvc?->custInternet?->customer?->phone_country_code ?? '') . ' ' . ($payment->custInternetInvc?->custInternet?->customer?->phone_number ?? '-'),
            'kode_paket' => $payment->custInternetInvc?->custInternet?->internetPackage?->code,
            'nama_paket' => $payment->custInternetInvc?->custInternet?->internetPackage?->name,
            'amount_paid' => $payment->amount_paid,
            'payment_date' => $payment->payment_date?->format('Y-m-d'),
            'payment_method' => $payment->payment_method,
            'provider' => $payment->provider,
            'status' => $payment->status,
            'created_at' => $payment->created_at?->format('Y-m-d H:i'),
            // Company info (light logo only — printed on white paper).
            // Logo: base64 data URI (langsung dari kolom companies.logo)
            // agar DomPDF tidak perlu HTTP request ke file.proxy (yang butuh auth).
            'company_name' => $company?->name ?? '-',
            'company_email' => $company?->email ?? '-',
            'company_phone' => ($company?->phone_country_code ?? '') . ' ' . ($company?->phone_number ?? '-'),
            'company_address' => $company?->address ?? '-',
            'company_logo_url' => $company?->getLogoDataUri('logo', 'minio') ?? $company?->logo_url,
        ];

        $html = view('pdf.payment-receipt', $data)->render();
        $domPdf = new \Dompdf\Dompdf();
        $domPdf->loadHtml($html);
        $domPdf->setPaper('A6', 'portrait');
        $domPdf->render();

        $filename = 'pembayaran-' . ($payment->code ?? $id) . '.pdf';
        $tempPath = storage_path("app/exports/{$filename}");
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        file_put_contents($tempPath, $domPdf->output());

        return response()->download($tempPath, $filename, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend(true);
    }

    public function downloadWord(string $id): BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;
        $company = Company::find($companyId);
        $payment = CustInternetPayment::with(['custInternetInvc.custInternet.customer', 'custInternetInvc.custInternet.internetPackage'])
            ->whereHas('custInternetInvc.custInternet.customer', fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);

        $customerPhone = ($payment->custInternetInvc?->custInternet?->customer?->phone_country_code ?? '') . ' ' . ($payment->custInternetInvc?->custInternet?->customer?->phone_number ?? '-');

        $statusMap = ['paid' => 'Lunas', 'pending' => 'Pending', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'];
        $providerMap = ['internal' => 'Internal', 'external' => 'Eksternal'];
        $methodMap = ['tunai' => 'Tunai', 'transfer_manual' => 'Transfer Manual'];

        $companyName = $company?->name ?? 'PERUSAHAAN';
        $companyAddress = !empty($company?->address) ? $company->address : '';
        $companyEmail = !empty($company?->email) ? "Email: {$company->email}<br>" : '';
        $companyPhone = !empty($company?->phone_number) ? "Telp: {$company->phone_country_code} {$company->phone_number}" : '';

        $content = "<html><body>";
        $content .= "<h1 align='center'>BUKTI PEMBAYARAN</h1>";
        $content .= "<p align='center'><strong>{$companyName}</strong><br>";
        $content .= !empty($companyAddress) ? $companyAddress . "<br>" : "";
        $content .= $companyEmail;
        $content .= !empty($companyPhone) ? $companyPhone : "";
        $content .= "</p><hr/>";
        $content .= "<table border='0' cellpadding='5'>";
        $content .= "<tr><td><strong>Kode Pembayaran</strong></td><td>: {$payment->code}</td></tr>";
        $content .= "<tr><td><strong>No. Invoice</strong></td><td>: {$payment->custInternetInvc?->invoice_number}</td></tr>";
        $content .= "<tr><td colspan='2'><hr></td></tr>";
        $content .= "<tr><td><strong>Informasi Pelanggan</strong></td><td></td></tr>";
        $content .= "<tr><td>Nama</td><td>: {$payment->custInternetInvc?->custInternet?->customer?->name}</td></tr>";
        $content .= "<tr><td>Kode Pelanggan</td><td>: {$payment->custInternetInvc?->custInternet?->customer?->customer_code}</td></tr>";
        $content .= "<tr><td>Email</td><td>: {$payment->custInternetInvc?->custInternet?->customer?->email}</td></tr>";
        $content .= "<tr><td>Telepon</td><td>: {$customerPhone}</td></tr>";
        $content .= "<tr><td colspan='2'><hr></td></tr>";
        $content .= "<tr><td><strong>Informasi Paket</strong></td><td></td></tr>";
        $content .= "<tr><td>Kode Paket</td><td>: {$payment->custInternetInvc?->custInternet?->internetPackage?->code}</td></tr>";
        $content .= "<tr><td>Nama Paket</td><td>: {$payment->custInternetInvc?->custInternet?->internetPackage?->name}</td></tr>";
        $content .= "<tr><td colspan='2'><hr></td></tr>";
        $content .= "<tr><td><strong>Provider</strong></td><td>: " . ($providerMap[$payment->provider] ?? $payment->provider) . "</td></tr>";
        $content .= "<tr><td><strong>Metode Pembayaran</strong></td><td>: " . ($methodMap[$payment->payment_method] ?? $payment->payment_method) . "</td></tr>";
        $content .= "<tr><td><strong>Tanggal Bayar</strong></td><td>: {$payment->payment_date?->format('Y-m-d')}</td></tr>";
        $content .= "<tr><td><strong>Status</strong></td><td>: " . ($statusMap[$payment->status] ?? $payment->status) . "</td></tr>";
        $content .= "<tr><td colspan='2'><hr></td></tr>";
        $content .= "<tr><td><strong>Total Pembayaran</strong></td><td>: <strong>Rp " . number_format($payment->amount_paid, 0, ',', '.') . "</strong></td></tr>";
        $content .= "</table>";
        $content .= "<hr/>";
        $content .= "<p>Dicetak pada: " . now()->format('Y-m-d H:i:s') . "</p>";
        $content .= "</body></html>";

        $filename = 'pembayaran-' . ($payment->code ?? $id) . '.doc';
        $tempPath = storage_path("app/exports/{$filename}");
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        file_put_contents($tempPath, $content);

        return response()->download($tempPath, $filename, ['Content-Type' => 'application/msword'])->deleteFileAfterSend(true);
    }
}