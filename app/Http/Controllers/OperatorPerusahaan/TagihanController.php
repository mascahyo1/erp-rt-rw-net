<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\CustInternetInvc;
use App\Models\CustInternet;
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

        $headers = ['No. Langganan', 'Awal Usage (YYYY-MM-DD)', 'Akhir Usage (YYYY-MM-DD)', 'Total', 'Diskon', 'Pajak', 'Jatuh Tempo (YYYY-MM-DD)', 'Deskripsi'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setCellValueExplicit('A2', 'ACC-001', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B2', now()->startOfMonth()->format('Y-m-d'), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C2', now()->endOfMonth()->format('Y-m-d'), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D2', '0.00', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E2', '0.00', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('F2', '0.00', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('G2', now()->addDays(30)->format('Y-m-d'), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('H2', 'Contoh deskripsi', DataType::TYPE_STRING);

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
            $accountNumber = trim($row[0] ?? '');
            $usageStart = trim($row[1] ?? '');
            $usageEnd = trim($row[2] ?? '');
            $total = trim($row[3] ?? '0');
            $diskon = trim($row[4] ?? '0');
            $pajak = trim($row[5] ?? '0');
            $jatuhTempo = trim($row[6] ?? '');
            $deskripsi = trim($row[7] ?? '');

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

            $grandTotal = (floatval($total) - floatval($diskon)) + floatval($pajak);

            $inserts[] = [
                'id' => \Illuminate\Support\Str::uuid7(),
                'cust_internet_id' => $custInternet->id,
                'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
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

    public function generate(Request $request): RedirectResponse
    {
        $request->validate([
            'period_year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $companyId = auth()->user()->company_id;
        $year = $request->input('period_year');
        $month = $request->input('period_month');
        $dueDate = $request->input('due_date');

        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $langganans = CustInternet::with('customer')
            ->whereHas('customer', fn($q) => $q->where('company_id', $companyId))
            ->where('internet_status', 'active')
            ->whereBetween('billing_cycle_start', [$startDate, $endDate])
            ->orWhereBetween('billing_cycle_end', [$startDate, $endDate])
            ->get();

        if ($langganans->isEmpty()) {
            return back()->with('error', 'Tidak ada langganan aktif untuk periode tersebut.');
        }

        $count = 0;
        $inserts = [];

        foreach ($langganans as $langganan) {
            $existing = CustInternetInvc::where('cust_internet_id', $langganan->id)
                ->whereMonth('usage_start_date', $month)
                ->whereYear('usage_start_date', $year)
                ->exists();

            if ($existing) continue;

            $grandTotal = $langganan->billing_amount;

            $inserts[] = [
                'id' => \Illuminate\Support\Str::uuid7(),
                'cust_internet_id' => $langganan->id,
                'invoice_number' => 'INV-' . $year . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT),
                'usage_start_date' => $startDate->format('Y-m-d'),
                'usage_end_date' => $endDate->format('Y-m-d'),
                'total_amount' => $grandTotal,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'grand_total' => $grandTotal,
                'due_date' => $dueDate ?: $endDate->copy()->addDays(30)->format('Y-m-d'),
                'payment_status' => 'unpaid',
                'description' => "Tagihan periode " . $startDate->translatedFormat('F Y'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $count++;
        }

        if (!empty($inserts)) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                CustInternetInvc::insert($chunk);
            }
        }

        return back()->with('success', "{$count} tagihan berhasil digenerate untuk periode " . $startDate->translatedFormat('F Y') . ".");
    }
}