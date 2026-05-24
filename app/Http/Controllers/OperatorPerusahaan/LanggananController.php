<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustInternet;
use App\Models\InternetPackage;
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
                $q->whereHas('customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhere('account_number', 'like', "%{$search}%");
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
                'account_number' => $item->account_number,
                'router_sn' => $item->router_sn,
                'customer_address' => $item->customer_address,
                'customer_address_long' => $item->customer_address_long,
                'customer_address_lat' => $item->customer_address_lat,
                'internet_status' => $item->internet_status,
                'usage_upload_kb' => $item->usage_upload_kb,
                'usage_download_kb' => $item->usage_download_kb,
                'company_notes' => $item->company_notes,
                'billing_amount' => $item->billing_amount,
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
        $companyId = auth()->user()->company_id;
        $validated = $request->validate([
            'customer_id' => ['required', 'string', 'exists:customers,id'],
            'internet_package_id' => ['required', 'string', 'exists:internet_packages,id'],
            'account_number' => ['required', 'string', 'max:50', Rule::unique('cust_internets', 'account_number')->where('customer_id', $request->input('customer_id'))],
            'router_sn' => ['nullable', 'string', 'max:100'],
            'customer_address' => ['nullable', 'string', 'max:255'],
            'customer_address_long' => ['nullable', 'string'],
            'customer_address_lat' => ['nullable', 'numeric'],
            'internet_status' => ['required', 'string', Rule::in(['active', 'inactive', 'suspended', 'terminated'])],
            'usage_upload_kb' => ['nullable', 'numeric', 'min:0'],
            'usage_download_kb' => ['nullable', 'numeric', 'min:0'],
            'company_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'account_number.unique' => 'Nomor akun sudah digunakan untuk pelanggan ini.',
        ]);

        CustInternet::create($validated);

        return back()->with('success', 'Langganan berhasil ditambahkan.');
    }

    public function update(Request $request, CustInternet $custInternet): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'string', 'exists:customers,id'],
            'internet_package_id' => ['required', 'string', 'exists:internet_packages,id'],
            'account_number' => ['required', 'string', 'max:50', Rule::unique('cust_internets', 'account_number')->where('customer_id', $request->input('customer_id'))->ignore($custInternet->id)],
            'router_sn' => ['nullable', 'string', 'max:100'],
            'customer_address' => ['nullable', 'string', 'max:255'],
            'customer_address_long' => ['nullable', 'string'],
            'customer_address_lat' => ['nullable', 'numeric'],
            'internet_status' => ['required', 'string', Rule::in(['active', 'inactive', 'suspended', 'terminated'])],
            'usage_upload_kb' => ['nullable', 'numeric', 'min:0'],
            'usage_download_kb' => ['nullable', 'numeric', 'min:0'],
            'company_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'account_number.unique' => 'Nomor akun sudah digunakan untuk pelanggan ini.',
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

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;
        $query = CustInternet::with(['customer', 'internetPackage'])
            ->whereHas('customer', fn($q) => $q->where('company_id', $companyId));

        if ($ids = $request->input('ids')) {
            $query->whereIn('id', explode(',', $ids));
        }

        if ($status = $request->input('status')) {
            $query->where('internet_status', $status);
        }

        $items = $query->orderBy('created_at', 'desc')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Langganan');

        $headers = ['No. Akun', 'Nama Customer', 'Nama Paket', 'Router SN', 'Status', 'Usage Upload (KB)', 'Usage Download (KB)', 'Tagihan', 'Catatan', 'Tanggal Dibuat'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
        }

        $row = 2;
        foreach ($items as $item) {
            $col = 1;
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $item->account_number ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $item->customer?->name ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $item->internetPackage?->name ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $item->router_sn ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $statusLabel = match($item->internet_status) { 'active' => 'Aktif', 'inactive' => 'Nonaktif', 'suspended' => 'Suspend', 'terminated' => 'Terminasi', default => $item->internet_status };
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $statusLabel, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue($this->excelColumn($col++) . $row, $item->usage_upload_kb);
            $sheet->setCellValue($this->excelColumn($col++) . $row, $item->usage_download_kb);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, (string) ($item->billing_amount ? number_format((float)$item->billing_amount, 0, ',', '.') : '0'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $item->company_notes ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $item->created_at->format('Y-m-d H:i'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $row++;
        }

        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension($this->excelColumn($i))->setAutoSize(true);
        }

        $file = storage_path('app/temp/langganan_' . now()->format('YmdHis') . '.xlsx');
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($file);

        return response()->download($file)->deleteFileAfterSend();
    }

    public function template(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import');

        $headers = ['No. Akun', 'Customer ID (UUID)', 'Paket ID (UUID)', 'Router SN', 'Status (active/inactive/suspended/terminated)', 'Usage Upload (KB)', 'Usage Download (KB)', 'Catatan'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
        }

        $example = ['ACC-001', '', '', '', 'active', '0', '0', ''];
        foreach ($example as $i => $v) {
            $sheet->setCellValueExplicit($this->excelColumn($i + 1) . '2', (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension($this->excelColumn($i))->setAutoSize(true);
        }

        $file = storage_path('app/temp/template_langganan.xlsx');
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($file);

        return response()->download($file)->deleteFileAfterSend();
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,csv,xls|max:2048']);

        $file = $request->file('file');
        $fullPath = $file->getRealPath();

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
        $rows = $spreadsheet->getActiveSheet()->toArray();
        if (empty($rows)) return back()->with('error', 'File kosong.');

        array_shift($rows);

        $companyId = auth()->user()->company_id;
        $success = 0;
        $errors = [];

        $customerIds = Customer::where('company_id', $companyId)->pluck('id')->toArray();
        $packageIds = InternetPackage::where('company_id', $companyId)->pluck('id')->toArray();

        $inserts = [];
        foreach ($rows as $idx => $row) {
            if (empty(array_filter($row))) continue;

            $accountNumber = trim($row[0] ?? '');
            $customerId = trim($row[1] ?? '');
            $packageId = trim($row[2] ?? '');
            $routerSn = trim($row[3] ?? '');
            $status = strtolower(trim($row[4] ?? '')) ?: 'active';
            $usageUpload = is_numeric($row[5] ?? '') ? (float)$row[5] : 0;
            $usageDownload = is_numeric($row[6] ?? '') ? (float)$row[6] : 0;
            $companyNotes = trim($row[7] ?? '');

            if (!$accountNumber || !$customerId || !$packageId) {
                $errors[] = "Baris " . ($idx + 2) . ": data tidak lengkap";
                continue;
            }

            if (!in_array($customerId, $customerIds)) {
                $errors[] = "Baris " . ($idx + 2) . ": customer ID tidak valid";
                continue;
            }
            if (!in_array($packageId, $packageIds)) {
                $errors[] = "Baris " . ($idx + 2) . ": paket ID tidak valid";
                continue;
            }
            if (!in_array($status, ['active', 'inactive', 'suspended', 'terminated'])) {
                $status = 'active';
            }

            $exists = CustInternet::where('account_number', $accountNumber)
                ->where('customer_id', $customerId)->exists();
            if ($exists) {
                $errors[] = "Baris " . ($idx + 2) . ": no. akun sudah ada";
                continue;
            }

            $inserts[] = [
                'id' => \App\Support\Str::uuidV7(),
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'internet_package_id' => $packageId,
                'account_number' => $accountNumber,
                'router_sn' => $routerSn ?: null,
                'internet_status' => $status,
                'usage_upload_kb' => $usageUpload,
                'usage_download_kb' => $usageDownload,
                'company_notes' => $companyNotes ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $success++;
        }

        foreach (array_chunk($inserts, 500) as $chunk) {
            CustInternet::insert($chunk);
        }

        $msg = "{$success} langganan berhasil diimport.";
        if ($errors) {
            $msg .= ' ' . count($errors) . ' baris error: ' . implode('; ', array_slice($errors, 0, 5));
        }

        return back()->with('success', $msg);
    }

    private function excelColumn(int $index): string
    {
        $col = '';
        while ($index > 0) {
            $index--;
            $col = chr(65 + ($index % 26)) . $col;
            $index = (int) floor($index / 26);
        }
        return $col;
    }
}
