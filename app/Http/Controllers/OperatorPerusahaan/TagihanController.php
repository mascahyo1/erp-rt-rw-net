<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\CustInternetInvc;
use App\Models\CustInternet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TagihanController extends Controller
{
    private function excelColumn(int $index): string
    {
        return match ($index) {
            1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F', 7 => 'G', 8 => 'H',
            9 => 'I', 10 => 'J', 11 => 'K', 12 => 'L', 13 => 'M', 14 => 'N', 15 => 'O', 16 => 'P',
            default => 'A',
        };
    }

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

        if ($paket = $request->input('paket')) {
            $query->whereHas('custInternet', fn($q) => $q->where('internet_package_id', $paket));
        }

        if ($dueDateStart = $request->input('due_date_start')) {
            $query->where('due_date', '>=', $dueDateStart);
        }

        if ($dueDateEnd = $request->input('due_date_end')) {
            $query->where('due_date', '<=', $dueDateEnd);
        }

        $allowedSorts = ['invoice_number', 'total_amount', 'due_date', 'payment_status', 'created_at', 'deleted_at', 'usage_start_date', 'usage_end_date'];
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
                'customer_code' => $item->custInternet?->customer?->customer_code,
                'account_number' => $item->custInternet?->account_number,
                'phone_country_code' => $item->custInternet?->customer?->phone_country_code,
                'phone_number' => $item->custInternet?->customer?->phone_number,
                'email' => $item->custInternet?->customer?->email,
                'usage_start_date' => $item->usage_start_date?->format('Y-m-d'),
                'usage_end_date' => $item->usage_end_date?->format('Y-m-d'),
                'total_amount' => $item->total_amount,
                'discount_amount' => $item->discount_amount,
                'tax_amount' => $item->tax_amount,
                'grand_total' => $item->grand_total,
                'due_date' => $item->due_date?->format('Y-m-d'),
                'payment_status' => $item->payment_status,
                'payment_status_label' => $item->payment_status_label,  // computed: paid/partial/unpaid
                'total_paid' => (float) $item->total_paid,             // sum of approved payments
                'remaining' => (float) $item->remaining,               // grand_total - total_paid (min 0)
                'paid_at' => $item->paid_at?->format('Y-m-d H:i'),
                'description' => $item->description,
                'dihapus' => $item->trashed(),
                'deleted_at' => $item->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $item->created_at->format('Y-m-d H:i'),
                'updated_at' => $item->updated_at->format('Y-m-d H:i'),
                'restored_at' => $item->restored_at ? ($item->restored_at instanceof \DateTimeInterface ? $item->restored_at->format('Y-m-d H:i') : $item->restored_at) : null,
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
            'usage_start_date' => ['nullable', 'date'],
            'usage_end_date' => ['nullable', 'date', 'after_or_equal:usage_start_date'],
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
            'usage_start_date' => ['nullable', 'date'],
            'usage_end_date' => ['nullable', 'date', 'after_or_equal:usage_start_date'],
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

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;
        $query = CustInternetInvc::query()
            ->with('custInternet.customer')
            ->whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId));

        if ($ids = $request->input('ids')) {
            $query->whereIn('id', explode(',', $ids));
        }

        $tagihans = $query->orderBy('created_at', 'desc')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Tagihan');

        $headers = ['No. Invoice', 'No. Langganan', 'Kode Pelanggan', 'Nama Pelanggan', 'No. Telp Pelanggan', 'Email Pelanggan', 'Awal Usage', 'Akhir Usage', 'Total', 'Diskon', 'Pajak', 'Grand Total', 'Jatuh Tempo', 'Status'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $row = 2;
        foreach ($tagihans as $t) {
            $col = 1;
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $t->invoice_number ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $t->custInternet?->account_number ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $t->custInternet?->customer?->customer_code ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $t->custInternet?->customer?->name ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, ($t->custInternet?->customer?->phone_country_code ?? '') . ' ' . ($t->custInternet?->customer?->phone_number ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $t->custInternet?->customer?->email ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $t->usage_start_date?->format('Y-m-d') ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $t->usage_end_date?->format('Y-m-d') ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, number_format($t->total_amount ?? 0, 2, '.', ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, number_format($t->discount_amount ?? 0, 2, '.', ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, number_format($t->tax_amount ?? 0, 2, '.', ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, number_format($t->grand_total ?? 0, 2, '.', ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $t->due_date?->format('Y-m-d') ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $t->payment_status === 'paid' ? 'Lunas' : 'Belum Bayar', DataType::TYPE_STRING);
            $row++;
        }

        $filename = 'tagihan-' . now()->format('Ymd-His') . '.xlsx';
        $tempPath = storage_path("app/temp/{$filename}");
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Tagihan');

        $headers = ['No. Invoice', 'No. Langganan', 'Awal Usage (YYYY-MM-DD)', 'Akhir Usage (YYYY-MM-DD)', 'Total', 'Diskon', 'Pajak', 'Jatuh Tempo (YYYY-MM-DD)', 'Deskripsi'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setCellValueExplicit('A2', 'INV-2025-0001', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B2', 'ACC-001', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C2', now()->startOfMonth()->format('Y-m-d'), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D2', now()->endOfMonth()->format('Y-m-d'), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E2', '0.00', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F2', '0.00', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('G2', '0.00', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('H2', now()->addDays(30)->format('Y-m-d'), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('I2', 'Contoh deskripsi', DataType::TYPE_STRING);

        $filename = 'template-tagihan.xlsx';
        $tempPath = storage_path("app/temp/{$filename}");
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,csv|max:5120']);

        $file = $request->file('file');
        $fullPath = $file->getRealPath();

        $reader = IOFactory::createReaderForFile($fullPath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($fullPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        array_shift($rows);

        $companyId = auth()->user()->company_id;
        $success = 0;
        $errors = [];
        $inserts = [];

        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $invoiceNumber = trim($row[0] ?? '');
            $accountNumber = trim($row[1] ?? '');
            $usageStart = trim($row[2] ?? '');
            $usageEnd = trim($row[3] ?? '');
            $total = trim($row[4] ?? '0');
            $diskon = trim($row[5] ?? '0');
            $pajak = trim($row[6] ?? '0');
            $jatuhTempo = trim($row[7] ?? '');
            $deskripsi = trim($row[8] ?? '');

            if (empty($accountNumber)) {
                $errors[] = "Baris {$line}: No. Langganan wajib diisi.";
                continue;
            }

            $custInternet = CustInternet::where('account_number', $accountNumber)
                ->whereHas('customer', fn($q) => $q->where('company_id', $companyId))
                ->first();

            if (!$custInternet) {
                $errors[] = "Baris {$line}: Langganan {$accountNumber} tidak ditemukan.";
                continue;
            }

            // Use provided invoice number or auto-generate
            $finalInvoiceNumber = $invoiceNumber ?: ('INV-' . now()->format('Ymd') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT));

            // Check if invoice number already exists
            $invoiceExists = CustInternetInvc::where('invoice_number', $finalInvoiceNumber)->exists();
            if ($invoiceExists) {
                $errors[] = "Baris {$line}: No. Invoice {$finalInvoiceNumber} sudah ada.";
                continue;
            }

            $grandTotal = (floatval($total) - floatval($diskon)) + floatval($pajak);

            $inserts[] = [
                'id' => \Illuminate\Support\Str::uuid7(),
                'cust_internet_id' => $custInternet->id,
                'invoice_number' => $finalInvoiceNumber,
                'usage_start_date' => $usageStart ?: null,
                'usage_end_date' => $usageEnd ?: null,
                'total_amount' => floatval($total),
                'discount_amount' => floatval($diskon),
                'tax_amount' => floatval($pajak),
                'grand_total' => $grandTotal,
                'due_date' => $jatuhTempo ?: null,
                'payment_status' => 'unpaid',
                'description' => $deskripsi ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $success++;
        }

        if (!empty($inserts)) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                CustInternetInvc::insert($chunk);
            }
        }

        $msg = "{$success} tagihan berhasil diimport.";
        if (!empty($errors)) {
            $msg .= " Gagal: " . implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) $msg .= " dan " . (count($errors) - 5) . " lainnya.";
        }

        return back()->with(empty($errors) ? 'success' : 'warning', $msg);
    }

    public function generate(Request $request, \App\Services\InvoiceGeneratorService $service): RedirectResponse
    {
        $request->validate([
            'period_year' => ['required', 'integer', 'min:2020'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'due_date' => ['nullable', 'date'],
        ]);

        $companyId = auth()->user()->company_id;
        $year = (int) $request->input('period_year');
        $month = (int) $request->input('period_month');
        $dueDate = $request->input('due_date');

        // Convert due_date string to due_days override (days from end of period)
        $dueDaysOverride = null;
        if ($dueDate) {
            $endOfMonth = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();
            $parsed = \Carbon\Carbon::parse($dueDate);
            $dueDaysOverride = max(0, (int) $endOfMonth->diffInDays($parsed, false));
        }

        $result = $service->generate($companyId, $year, $month, $dueDaysOverride);

        $periodLabel = \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y');

        if ($result['generated'] === 0 && $result['skipped_existing'] === 0 && $result['skipped_cycle'] === 0) {
            return back()->with('error', "Tidak ada langganan aktif untuk periode {$periodLabel}.");
        }

        $msg = "{$result['generated']} tagihan berhasil digenerate untuk periode {$periodLabel}.";
        if ($result['skipped_existing'] > 0) {
            $msg .= " {$result['skipped_existing']} dilewati (sudah ada).";
        }
        if ($result['skipped_cycle'] > 0) {
            $msg .= " {$result['skipped_cycle']} dilewati (cycle non-monthly).";
        }

        return back()->with('success', $msg);
    }

    public function exportPdf(string $id): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;
        $company = auth()->user()->company;
        $invoice = CustInternetInvc::with(['custInternet.customer', 'custInternet.internetPackage', 'createdBy', 'updatedBy'])
            ->whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);

        // Logo: ambil dari kolom companies.logo (light variant) sebagai base64 data URI
        // agar DomPDF tidak perlu HTTP request ke file.proxy (yang butuh auth).
        $logoUrl = $company?->getLogoDataUri('logo', 'minio');

        $html = $this->buildInvoiceHtml($invoice, $company, $logoUrl);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Invoice-' . $invoice->invoice_number . '.pdf';
        $path = 'invoices/' . date('Y/m') . '/' . $filename;

        \Illuminate\Support\Facades\Storage::disk('minio')->put($path, $dompdf->output(), ['visibility' => 'private']);

        $tempPath = storage_path("app/temp/{$filename}");
        file_put_contents($tempPath, \Illuminate\Support\Facades\Storage::disk('minio')->get($path));

        return response()->download($tempPath, $filename, ['Content-Type' => 'application/pdf'])->deleteFileAfterSend(true);
    }

    public function exportWord(string $id): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;
        $company = auth()->user()->company;
        $invoice = CustInternetInvc::with(['custInternet.customer', 'custInternet.internetPackage', 'createdBy', 'updatedBy'])
            ->whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);

        $customer = $invoice->custInternet?->customer;
        $langganan = $invoice->custInternet;
        $package = $langganan?->internetPackage;

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontSize(11);

        // Company Header
        if ($company) {
            $section = $phpWord->addSection();
            $companyName = $company->name ?? '-';
            $companyEmail = $company->email ?? '-';
            $companyAddress = $company->address ?? '-';
            $companyPhone = ($company->phone_country_code ?? '') . ' ' . ($company->phone_number ?? '-');
            $section->addText($companyName, ['bold' => true, 'size' => 16]);
            $section->addText($companyAddress, ['size' => 9]);
            $section->addText('Email: ' . $companyEmail . ' | Telp: ' . $companyPhone, ['size' => 9]);
            $section->addText('', []);
            $section->addText('INVOICE', ['bold' => true, 'size' => 22, 'align' => 'center']);
            $section->addText($invoice->invoice_number, ['bold' => true, 'size' => 12, 'align' => 'center']);
        } else {
            $section = $phpWord->addSection();
            $section->addText('INVOICE', ['bold' => true, 'size' => 24, 'align' => 'center']);
            $section->addText($invoice->invoice_number, ['bold' => true, 'size' => 14, 'align' => 'center']);
        }
        $section->addText('');

        $table = $section->addTable(['borderSize' => 1, 'borderColor' => '999999']);
        $table->addRow();
        $table->addCell(2500)->addText('No. Invoice', ['bold' => true]);
        $table->addCell(5000)->addText($invoice->invoice_number ?? '-');
        $table->addRow();
        $table->addCell(2500)->addText('No. Langganan', ['bold' => true]);
        $table->addCell(5000)->addText($langganan->account_number ?? '-');
        $table->addRow();
        $table->addCell(2500)->addText('Kode Pelanggan', ['bold' => true]);
        $table->addCell(5000)->addText($customer->code ?? '-');
        $table->addRow();
        $table->addCell(2500)->addText('Nama Pelanggan', ['bold' => true]);
        $table->addCell(5000)->addText($customer->name ?? '-');
        $table->addRow();
        $table->addCell(2500)->addText('No. Telp Pelanggan', ['bold' => true]);
        $table->addCell(5000)->addText(($customer->phone_country_code ?? '') . ' ' . ($customer->phone_number ?? '-'));
        $table->addRow();
        $table->addCell(2500)->addText('Email Pelanggan', ['bold' => true]);
        $table->addCell(5000)->addText($customer->email ?? '-');
        $table->addRow();
        $table->addCell(2500)->addText('Paket Internet', ['bold' => true]);
        $table->addCell(5000)->addText($package->name ?? '-' . ' (' . ($package->speed ?? '-') . ' Mbps)');
        $table->addRow();
        $table->addCell(2500)->addText('Periode Usage', ['bold' => true]);
        $table->addCell(5000)->addText(($invoice->usage_start_date?->format('d/m/Y') ?? '-') . ' - ' . ($invoice->usage_end_date?->format('d/m/Y') ?? '-'));
        $table->addRow();
        $table->addCell(2500)->addText('Total Tagihan', ['bold' => true]);
        $table->addCell(5000)->addText('Rp ' . number_format($invoice->total_amount ?? 0, 0, ',', '.'));
        $table->addRow();
        $table->addCell(2500)->addText('Diskon', ['bold' => true]);
        $table->addCell(5000)->addText('Rp ' . number_format($invoice->discount_amount ?? 0, 0, ',', '.'));
        $table->addRow();
        $table->addCell(2500)->addText('Pajak', ['bold' => true]);
        $table->addCell(5000)->addText('Rp ' . number_format($invoice->tax_amount ?? 0, 0, ',', '.'));
        $table->addRow();
        $table->addCell(2500)->addText('Grand Total', ['bold' => true]);
        $table->addCell(5000)->addText('Rp ' . number_format($invoice->grand_total ?? 0, 0, ',', '.'), ['bold' => true]);
        $table->addRow();
        $table->addCell(2500)->addText('Jatuh Tempo', ['bold' => true]);
        $table->addCell(5000)->addText($invoice->due_date?->format('d/m/Y') ?? '-');
        $table->addRow();
        $table->addCell(2500)->addText('Status Pembayaran', ['bold' => true]);
        $table->addCell(5000)->addText($invoice->payment_status === 'paid' ? 'LUNAS' : 'BELUM BAYAR');
        $table->addRow();
        $table->addCell(2500)->addText('Deskripsi', ['bold' => true]);
        $table->addCell(5000)->addText($invoice->description ?? '-');
        $section->addText('');

        $filename = 'Invoice-' . $invoice->invoice_number . '.docx';
        $path = 'invoices/' . date('Y/m') . '/' . $filename;
        $tempPath = storage_path("app/temp/{$filename}");

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        // Store to object storage
        $content = file_get_contents($tempPath);
        \Illuminate\Support\Facades\Storage::disk('minio')->put($path, $content, ['visibility' => 'private']);

        return response()->download($tempPath, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->deleteFileAfterSend(true);
    }

    /**
     * AJAX: ambil approved payments (status='paid') untuk 1 invoice.
     * Dipakai oleh Tagihan.vue detail modal untuk tampilkan Riwayat Pembayaran.
     */
    public function paymentsAjax(CustInternetInvc $custInternetInvc): \Illuminate\Http\JsonResponse
    {
        $payments = $custInternetInvc->approvedPayments()
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'amount_paid' => (float) $p->amount_paid,
                'payment_date' => $p->payment_date?->format('Y-m-d'),
                'payment_method' => $p->payment_method,
                'provider' => $p->provider,
                'status' => $p->status,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'invoice_id' => $custInternetInvc->id,
                'grand_total' => (float) $custInternetInvc->grand_total,
                'total_paid' => (float) $custInternetInvc->total_paid,
                'remaining' => (float) $custInternetInvc->remaining,
                'payment_status_label' => $custInternetInvc->payment_status_label,
                'payments' => $payments,
            ],
        ], 200);
    }

    private function buildInvoiceHtml($invoice, $company = null, $logoUrl = null): string
    {
        $customer = $invoice->custInternet?->customer;
        $langganan = $invoice->custInternet;
        $package = $langganan?->internetPackage;

        $dueDateFormatted = $invoice->due_date ? $invoice->due_date->format('d F Y') : '-';
        $usageStartFormatted = $invoice->usage_start_date ? $invoice->usage_start_date->format('d/m/Y') : '-';
        $usageEndFormatted = $invoice->usage_end_date ? $invoice->usage_end_date->format('d/m/Y') : '-';
        $paidAtFormatted = $invoice->paid_at ? $invoice->paid_at->format('d F Y H:i') : null;
        $updatedAtFormatted = $invoice->updated_at ? $invoice->updated_at->format('d F Y H:i:s') : now()->format('d F Y H:i:s');
        $customerName = $customer->name ?? '-';
        $customerEmail = $customer->email ?? '-';
        $customerPhone = ($customer->phone_country_code ?? '') . ' ' . ($customer->phone_number ?? '-');
        $customerAddress = $customer->address ?? '-';
        $customerCode = $customer->code ?? '-';
        $accountNumber = $langganan->account_number ?? '-';
        $packageName = $package->name ?? '-';
        $packageSpeed = $package->speed ?? '-';
        $totalAmountFormatted = number_format($invoice->total_amount ?? 0, 0, ',', '.');
        $discountAmountFormatted = number_format($invoice->discount_amount ?? 0, 0, ',', '.');
        $taxAmountFormatted = number_format($invoice->tax_amount ?? 0, 0, ',', '.');
        $grandTotalFormatted = number_format($invoice->grand_total ?? 0, 0, ',', '.');
        $paymentStatusText = match ($invoice->payment_status_label) {
            'paid' => 'Lunas pada ' . $paidAtFormatted,
            'partial' => 'Sudah dibayar sebagian (Total: Rp ' . number_format((float) $invoice->total_paid, 0, ',', '.') . ' dari Rp ' . $grandTotalFormatted . ')',
            'unpaid' => 'Belum Bayar',
        };
        $description = $invoice->description ?? '-';

        // Riwayat Pembayaran — hanya payment approved (status='paid'), diurutkan terlama → terbaru
        $approvedPayments = $invoice->approvedPayments()->get();
        $totalPaid = (float) $invoice->total_paid;
        $remaining = (float) $invoice->remaining;
        $grandTotalFloat = (float) $invoice->grand_total;
        $totalPaidFormatted = number_format($totalPaid, 0, ',', '.');
        $remainingFormatted = number_format($remaining, 0, ',', '.');
        $paymentStatusComputed = $invoice->payment_status_label; // 'paid' | 'partial' | 'unpaid'
        $paymentStatusLabelText = match ($paymentStatusComputed) {
            'paid' => '<span style="color:#166534;font-weight:bold;">LUNAS</span>',
            'partial' => '<span style="color:#b45309;font-weight:bold;">SEBAGIAN</span>',
            'unpaid' => '<span style="color:#991b1b;font-weight:bold;">BELUM BAYAR</span>',
        };

        // Build riwayat pembayaran rows (jika ada)
        $paymentHistoryRows = '';
        $providerMap = ['internal' => 'Internal', 'external' => 'Eksternal'];
        $methodMap = ['tunai' => 'Tunai', 'transfer_manual' => 'Transfer Manual'];
        foreach ($approvedPayments as $idx => $pay) {
            $payAmountFormatted = number_format((float) $pay->amount_paid, 0, ',', '.');
            $payDateFormatted = $pay->payment_date ? $pay->payment_date->format('d/m/Y') : '-';
            $payCode = htmlspecialchars($pay->code ?? '-', ENT_QUOTES);
            $payProvider = htmlspecialchars($providerMap[$pay->provider] ?? ($pay->provider ?? '-'), ENT_QUOTES);
            $payMethod = htmlspecialchars($methodMap[$pay->payment_method] ?? ($pay->payment_method ?? '-'), ENT_QUOTES);
            $paymentHistoryRows .= '<tr>'
                . '<td style="text-align:center;">' . ($idx + 1) . '</td>'
                . '<td>' . $payCode . '</td>'
                . '<td style="text-align:center;">' . $payDateFormatted . '</td>'
                . '<td style="text-align:center;">' . $payProvider . '</td>'
                . '<td style="text-align:center;">' . $payMethod . '</td>'
                . '<td class="text-right">Rp ' . $payAmountFormatted . '</td>'
                . '</tr>';
        }
        if ($paymentHistoryRows === '') {
            $paymentHistoryRows = '<tr><td colspan="6" style="text-align:center;color:#666;font-style:italic;padding:10px;">Belum ada pembayaran.</td></tr>';
        }

        // Company info
        $companyName = $company?->name ?? '-';
        $companyEmail = $company?->email ?? '-';
        $companyAddress = $company?->address ?? '-';
        $companyPhone = ($company?->phone_country_code ?? '') . ' ' . ($company?->phone_number ?? '-');
        $logoImage = $logoUrl ? '<img src="' . $logoUrl . '" alt="Logo" style="max-height:50px;max-width:150px;object-fit:contain;">' : '';

        $footerText = '<p>Dicetak pada ' . $updatedAtFormatted . '</p>';

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; margin: 20px; font-size: 11px; color: #333; line-height: 1.3; }
    /* DomPDF tidak support flex — pakai table-based layout */
    .company-header { width: 100%; border-bottom: 2px solid #1e40af; margin-bottom: 8px; padding-bottom: 8px; }
    .company-header td { vertical-align: middle;border:0px solid white; }
    .company-info h1 { font-size: 16px; color: #1e40af; margin: 0; }
    .company-info p { margin: 1px 0; font-size: 9px; color: #666; }
    .company-logo { text-align: right; width: 180px; }
    .company-logo img { max-height: 60px; max-width: 160px; }
    .invoice-title { text-align: center; margin: 8px 0 4px 0; }
    .invoice-title h2 { font-size: 16px; color: #1e40af; margin: 0; font-weight: bold; letter-spacing: 1.5px; }
    .invoice-meta-row { width: 100%; margin-bottom: 8px;border:0px solid white; }
    .invoice-meta-row td { vertical-align: middle; font-size: 10px; color: #666;border:0px solid white; }
    .invoice-meta-row .status-cell { text-align: right; }
    .info-grid { display: table; width: 100%; margin-bottom: 10px; }
    .info-box { display: table-cell; width: 50%; padding: 6px; }
    .info-box:first-child { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 4px 0 0 4px; }
    .info-box:last-child { background: #f9fafb; border: 1px solid #e5e7eb; border-left: none; border-radius: 0 4px 4px 0; }
    .info-box h3 { font-size: 9px; color: #666; text-transform: uppercase; margin-bottom: 3px; }
    .info-box p { margin-bottom: 1px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; font-size: 10px; }
    th, td { border: 1px solid #ddd; padding: 5px 6px; }
    th { background: #f3f4f6; font-weight: bold; }
    .text-right { text-align: right; }
    .total-row { background: #f3f4f6; font-weight: bold; }
    .footer { margin-top: 12px; text-align: center; font-size: 8px; color: #666; border-top: 1px solid #ddd; padding-top: 6px; }
</style>
</head>
<body>
<!-- Pakai HTML <table> (DomPDF ga support flex) -->
<table class="company-header" cellspacing="0" cellpadding="0">
    <tr>
        <td class="company-info">
            <h1>{$companyName}</h1>
            <p>{$companyAddress}</p>
            <p>Email: {$companyEmail} | Telp: {$companyPhone}</p>
        </td>
        <td class="company-logo">{$logoImage}</td>
    </tr>
</table>
<div class="invoice-title">
    <h2>INVOICE</h2>
</div>
<table class="invoice-meta-row" cellspacing="0" cellpadding="0">
    <tr>
        <td>{$invoice->invoice_number} - Tagihan Internet {$dueDateFormatted}</td>
    </tr>
</table>

<div class="info-grid">
    <div class="info-box">
        <h3>Informasi Pelanggan</h3>
        <p><strong>{$customerName}</strong> ({$customerCode})</p>
        <p>{$customerEmail}</p>
        <p>{$customerPhone}</p>
        <p>{$customerAddress}</p>
    </div>
    <div class="info-box">
        <h3>Informasi Langganan</h3>
        <p><strong>No. Langganan:</strong> {$accountNumber}</p>
        <p><strong>Paket:</strong> {$packageName} ({$packageSpeed} Mbps)</p>
        <p><strong>Periode:</strong> {$usageStartFormatted} - {$usageEndFormatted}</p>
    </div>
</div>

<table>
    <tr>
        <th style="width:70%">Deskripsi</th>
        <th class="text-right">Jumlah</th>
    </tr>
    <tr>
        <td>Total Tagihan</td>
        <td class="text-right">Rp {$totalAmountFormatted}</td>
    </tr>
    <tr>
        <td>Diskon</td>
        <td class="text-right">- Rp {$discountAmountFormatted}</td>
    </tr>
    <tr>
        <td>Pajak</td>
        <td class="text-right">+ Rp {$taxAmountFormatted}</td>
    </tr>
    <tr class="total-row">
        <td>GRAND TOTAL</td>
        <td class="text-right">Rp {$grandTotalFormatted}</td>
    </tr>
</table>

<table>
    <tr>
        <th colspan="6" style="background:#1e40af;color:#fff;text-align:left;padding:6px;">RIWAYAT PEMBAYARAN</th>
    </tr>
    <tr>
        <th style="width:4%">No.</th>
        <th style="width:25%">Kode Pembayaran</th>
        <th style="width:15%">Tgl Bayar</th>
        <th style="width:14%">Provider</th>
        <th style="width:14%">Metode</th>
        <th class="text-right" style="width:28%">Nominal</th>
    </tr>
    {$paymentHistoryRows}
    <tr class="total-row" style="background:#fef3c7;">
        <td colspan="5" style="text-align:right;">Total Pembayaran (status: paid):</td>
        <td class="text-right">Rp {$totalPaidFormatted}</td>
    </tr>
    <tr class="total-row" style="background:#fee2e2;">
        <td colspan="5" style="text-align:right;">Sisa yang belum dibayar:</td>
        <td class="text-right">Rp {$remainingFormatted}</td>
    </tr>
</table>

<table>
    <tr>
        <th style="width:50%">Keterangan</th>
        <th>Informasi Pembayaran</th>
    </tr>
    <tr>
        <td style="vertical-align:top">{$description}</td>
        <td>
            <p><strong>Jatuh Tempo:</strong> {$dueDateFormatted}</p>
            <p><strong>Status:</strong> {$paymentStatusText}</p>
        </td>
    </tr>
</table>

<div class="footer">
    {$footerText}
</div>

</body>
</html>
HTML;

        return $html;
    }
}