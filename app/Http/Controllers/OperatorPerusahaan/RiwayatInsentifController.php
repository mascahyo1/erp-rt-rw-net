<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\EmpIncentiveLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RiwayatInsentifController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $query = EmpIncentiveLog::query()->with(['empIncentive', 'invoice.custInternet.customer', 'createdBy', 'updatedBy', 'deletedBy', 'restoredBy'])
            ->whereHas('empIncentive', fn($q) => $q->where('company_id', $companyId));

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('empIncentive', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhere('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('invoice.custInternet.customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('review_status', $status);
        }

        if ($invoiceNumber = $request->input('invoice_number')) {
            $query->where('invoice_number', 'like', "%{$invoiceNumber}%");
        }

        if ($dueDateStart = $request->input('due_date_start')) {
            $query->whereHas('invoice', fn($q) => $q->whereDate('due_date', '>=', $dueDateStart));
        }
        if ($dueDateEnd = $request->input('due_date_end')) {
            $query->whereHas('invoice', fn($q) => $q->whereDate('due_date', '<=', $dueDateEnd));
        }

        $allowedSorts = ['invoice_number', 'amount', 'date', 'review_status', 'created_at'];
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
                'invoice_number' => $item->invoice_number,
                'customer_name' => $item->invoice?->custInternet?->customer?->name,
                'phone_number' => $item->invoice?->custInternet?->phone_country_code . ' ' . $item->invoice?->custInternet?->phone_number,
                'amount' => $item->amount,
                'date' => $item->date?->format('Y-m-d'),
                'submitted_by_type' => $item->submitted_by_type,
                'submitted_by_id' => $item->submitted_by_id,
                'submitted_by_name' => $item->submitted_by_name,
                'reason' => $item->reason,
                'attachment' => $item->attachment,
                'review_status' => $item->review_status,
                'reviewed_at' => $item->reviewed_at?->format('Y-m-d H:i'),
                'review_reason' => $item->review_reason,
                'review_attachment' => $item->review_attachment,
                'dihapus' => $item->trashed(),
                'deleted_at' => $item->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $item->created_at->format('Y-m-d H:i'),
                'updated_at' => $item->updated_at->format('Y-m-d H:i'),
                'created_by' => $item->createdBy?->name,
                'updated_by' => $item->updatedBy?->name,
                'deleted_by' => $item->deletedBy?->name,
            ];
        });

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/RiwayatInsentif', [
            'riwayats' => $items,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus', 'invoice_number', 'due_date_start', 'due_date_end']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'emp_incentive_id' => ['required', 'string', 'exists:emp_incentives,id'],
            'cust_internet_invcs_id' => ['required', 'string', 'exists:cust_internet_invcs,id'],
            'amount' => ['required', 'numeric'],
            'date' => ['required', 'date'],
            'submitted_by_type' => ['nullable', 'string'],
            'submitted_by_id' => ['nullable', 'string'],
            'submitted_by_name' => ['nullable', 'string'],
            'reason' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('insentif-attachments', 'disk');
        }

        EmpIncentiveLog::create([
            'emp_incentive_id' => $validated['emp_incentive_id'],
            'cust_internet_invcs_id' => $validated['cust_internet_invcs_id'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'invoice_number' => $request->input('invoice_number'),
            'submitted_by_type' => !empty($validated['submitted_by_id']) ? 'employee' : ($validated['submitted_by_type'] ?? null),
            'submitted_by_id' => $validated['submitted_by_id'] ?? null,
            'submitted_by_name' => $validated['submitted_by_name'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'attachment' => $attachmentPath,
            'review_status' => 'pending',
        ]);

        return back()->with('success', 'Riwayat insentif berhasil ditambahkan.');
    }

    public function update(Request $request, EmpIncentiveLog $empIncentiveLog): RedirectResponse
    {
        if ($empIncentiveLog->review_status !== 'pending') {
            return back()->with('error', 'Hanya data dengan status pending yang bisa diedit.');
        }

        $validated = $request->validate([
            'emp_incentive_id' => ['required', 'string', 'exists:emp_incentives,id'],
            'cust_internet_invcs_id' => ['required', 'string', 'exists:cust_internet_invcs,id'],
            'amount' => ['required', 'numeric'],
            'date' => ['required', 'date'],
            'reason' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        $data = [
            'emp_incentive_id' => $validated['emp_incentive_id'],
            'cust_internet_invcs_id' => $validated['cust_internet_invcs_id'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'invoice_number' => $request->input('invoice_number'),
            'submitted_by_type' => !empty($validated['submitted_by_id']) ? 'employee' : ($validated['submitted_by_type'] ?? null),
            'submitted_by_id' => $validated['submitted_by_id'] ?? null,
            'submitted_by_name' => $validated['submitted_by_name'] ?? null,
            'reason' => $validated['reason'] ?? null,
        ];

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('insentif-attachments', 'disk');
        }

        $empIncentiveLog->update($data);

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

    /** Review (approve/reject) single item */
    public function review(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'review_status' => ['required', Rule::in(['approved', 'rejected'])],
            'review_reason' => ['nullable', 'string', 'max:500'],
            'review_attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        $log = EmpIncentiveLog::findOrFail($id);

        if ($log->review_status !== 'pending') {
            return back()->with('error', 'Hanya data dengan status pending yang bisa direview.');
        }

        if ($validated['review_status'] === 'rejected' && empty($validated['review_reason'])) {
            return back()->with('error', 'Alasan penolakan wajib diisi.');
        }

        $data = [
            'review_status' => $validated['review_status'],
            'reviewed_by_type' => get_class(auth()->user()),
            'reviewed_by_id' => auth()->id(),
            'reviewed_at' => now(),
            'review_reason' => $validated['review_reason'] ?? null,
        ];

        if ($request->hasFile('review_attachment')) {
            $data['review_attachment'] = $request->file('review_attachment')->store('insentif-reviews', 'disk');
        }

        $log->update($data);

        $label = $validated['review_status'] === 'approved' ? 'disetujui' : 'ditolak';
        return back()->with('success', "Riwayat insentif berhasil {$label}.");
    }

    /** Bulk review from checklist */
    public function bulkReview(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'review_status' => ['required', Rule::in(['approved', 'rejected'])],
            'review_reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['review_status'] === 'rejected' && empty($validated['review_reason'])) {
            return back()->with('error', 'Alasan penolakan wajib diisi.');
        }

        $logs = EmpIncentiveLog::whereIn('id', $validated['ids'])->where('review_status', 'pending')->get();

        if ($logs->isEmpty()) {
            return back()->with('error', 'Tidak ada data pending yang bisa direview.');
        }

        $data = [
            'review_status' => $validated['review_status'],
            'reviewed_by_type' => get_class(auth()->user()),
            'reviewed_by_id' => auth()->id(),
            'reviewed_at' => now(),
            'review_reason' => $validated['review_reason'] ?? null,
        ];

        if ($request->hasFile('review_attachment')) {
            $data['review_attachment'] = $request->file('review_attachment')->store('insentif-reviews', 'disk');
        }

        $count = 0;
        foreach ($logs as $log) {
            $log->update($data);
            $count++;
        }

        $label = $validated['review_status'] === 'approved' ? 'disetujui' : 'ditolak';
        return back()->with('success', "{$count} riwayat insentif berhasil {$label}.");
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

    public function export(Request $request): BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;

        $query = EmpIncentiveLog::query()
            ->with(['empIncentive', 'invoice.custInternet.customer'])
            ->whereHas('empIncentive', fn($q) => $q->where('company_id', $companyId));

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
                ->whereHas('empIncentive', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                ->orWhere('invoice_number', 'like', "%{$search}%")
                ->orWhereHas('invoice.custInternet.customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
            );
        }

        if ($status = $request->input('status')) {
            $query->where('review_status', $status);
        }

        if ($invoiceNumber = $request->input('invoice_number')) {
            $query->where('invoice_number', 'like', "%{$invoiceNumber}%");
        }

        if ($dueDateStart = $request->input('due_date_start')) {
            $query->whereHas('invoice', fn($q) => $q->whereDate('due_date', '>=', $dueDateStart));
        }
        if ($dueDateEnd = $request->input('due_date_end')) {
            $query->whereHas('invoice', fn($q) => $q->whereDate('due_date', '<=', $dueDateEnd));
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Riwayat Insentif');

        $headers = ['No', 'Nama Insentif', 'No. Invoice', 'Pelanggan', 'Pengaju', 'Jumlah', 'Tanggal', 'Status', 'Alasan', 'Tgl Dibuat'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $statusMap = ['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
        foreach ($logs as $i => $log) {
            $row = $i + 2;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $log->empIncentive?->name ?? '-');
            $sheet->setCellValue("C{$row}", $log->invoice_number ?? '-');
            $sheet->setCellValue("D{$row}", $log->invoice?->custInternet?->customer?->name ?? '-');
            $sheet->setCellValue("E{$row}", $log->submitted_by_name ?? '-');
            $sheet->setCellValue("F{$row}", $log->amount ?? 0);
            $sheet->setCellValue("G{$row}", $log->date?->format('Y-m-d') ?? '-');
            $sheet->setCellValue("H{$row}", $statusMap[$log->review_status] ?? $log->review_status);
            $sheet->setCellValue("I{$row}", $log->reason ?? '-');
            $sheet->setCellValue("J{$row}", $log->created_at?->format('Y-m-d H:i') ?? '-');
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'riwayat-insentif-' . date('Y-m-d-His') . '.xlsx';
        $tempPath = storage_path("app/exports/{$filename}");
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        $writer->save($tempPath);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Riwayat Insentif');

        $headers = ['Nama Insentif', 'No. Invoice', 'Cust Internet Invc ID', 'Amount', 'Date (YYYY-MM-DD)', 'Submitted By Name', 'Reason'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setCellValueExplicit('A2', 'Insentif Penjualan', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B2', 'INV/2025/001', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C2', '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D2', '100000', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E2', date('Y-m-d'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F2', 'John Doe', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('G2', 'Insentif bulanan', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

        $filename = 'template-riwayat-insentif.xlsx';
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
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (count($rows) < 2) {
            return back()->with('error', 'File tidak memiliki data.');
        }

        $header = array_map('strtolower', array_map('trim', $rows[0]));
        $companyId = auth()->user()->company_id;
        $imported = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $idx => $row) {
            if (empty(array_filter($row))) continue;

            $data = array_combine($header, $row);
            $incentiveName = trim($data['nama insentif'] ?? '');
            $invoiceNumber = trim($data['no. invoice'] ?? '');
            $custInternetInvcId = trim($data['cust internet invc id'] ?? '');
            $amount = trim($data['amount'] ?? '');
            $date = trim($data['date (yyyy-mm-dd)'] ?? '');
            $submittedByName = trim($data['submitted by name'] ?? '');
            $reason = trim($data['reason'] ?? '');

            if (!$incentiveName || !$amount || !$date) {
                $errors[] = 'Baris ' . ($idx + 2) . ': Nama Insentif, Amount, dan Date wajib diisi.';
                continue;
            }

            $incentive = \App\Models\EmpIncentive::where('company_id', $companyId)->where('name', $incentiveName)->first();
            if (!$incentive) {
                $errors[] = 'Baris ' . ($idx + 2) . ': Insentif "' . $incentiveName . '" tidak ditemukan.';
                continue;
            }

            \App\Models\EmpIncentiveLog::create([
                'emp_incentive_id' => $incentive->id,
                'cust_internet_invcs_id' => $custInternetInvcId ?: null,
                'invoice_number' => $invoiceNumber ?: null,
                'amount' => (float) $amount,
                'date' => $date,
                'submitted_by_name' => $submittedByName ?: null,
                'reason' => $reason ?: null,
                'review_status' => 'pending',
            ]);
            $imported++;
        }

        if ($imported > 0) {
            return back()->with('success', "{$imported} riwayat insentif berhasil diimport." . (count($errors) > 0 ? ' ' . count($errors) . ' baris dilewati.' : ''));
        }
        return back()->with('error', 'Gagal mengimport riwayat insentif. ' . implode(' ', $errors));
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
