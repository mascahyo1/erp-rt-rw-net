<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\CompanyConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class KonfigurasiPerusahaanController extends Controller
{
    private function excelColumn(int $index): string
    {
        return match ($index) {
            1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F',
            default => 'A',
        };
    }

    private function companyId(Request $request): string
    {
        return (string) $request->user()->company_id;
    }

    public function index(Request $request): Response
    {
        $companyId = $this->companyId($request);

        $query = CompanyConfig::query()
            ->with(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy'])
            ->where('company_id', $companyId);

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('value', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($sortField = $request->input('sort_field')) {
            $sortDir = $request->input('sort_dir', 'asc');
            $allowedSorts = ['key', 'value', 'type', 'created_at'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDir);
            }
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->input('per_page', 10), 100);

        $configs = $query->paginate($perPage)->through(function ($config) {
            return [
                'id' => $config->id,
                'company_id' => $config->company_id,
                'key' => $config->key,
                'type' => $config->type,
                'value' => $config->value,
                'description' => $config->description,
                'dihapus' => $config->trashed(),
                'deleted_at' => $config->deleted_at?->format('Y-m-d H:i'),
                'restored_at' => $config->restored_at ? ($config->restored_at instanceof \DateTimeInterface ? $config->restored_at->format('Y-m-d H:i') : $config->restored_at) : null,
                'created_at' => $config->created_at->format('Y-m-d H:i'),
                'updated_at' => $config->updated_at->format('Y-m-d H:i'),
                'created_by' => $config->createdBy?->name,
                'updated_by' => $config->updatedBy?->name,
                'deleted_by' => $config->deletedBy?->name,
                'restored_by' => $config->restoredBy?->name,
            ];
        });

        return Inertia::render('OperatorPerusahaan/KonfigurasiPerusahaan', [
            'configs' => $configs,
            'filters' => $request->only(['search', 'type', 'terhapus', 'sort_field', 'sort_dir', 'per_page']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255', Rule::unique('company_configs')->where('company_id', $companyId)],
            'type' => ['required', 'in:text,file,number,boolean'],
            'value' => ['required', 'string', 'max:65535'],
            'description' => ['nullable', 'string', 'max:65535'],
        ]);

        CompanyConfig::create($validated + ['company_id' => $companyId]);

        return back()->with('success', 'Konfigurasi berhasil ditambahkan.');
    }

    public function update(Request $request, CompanyConfig $companyConfig): RedirectResponse
    {
        $companyId = $this->companyId($request);

        if ($companyConfig->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('company_configs')
                    ->where('company_id', $companyId)
                    ->ignore($companyConfig->id),
            ],
            'type' => ['required', 'in:text,file,number,boolean'],
            'value' => ['required', 'string', 'max:65535'],
            'description' => ['nullable', 'string', 'max:65535'],
        ]);

        $companyConfig->update($validated);

        return back()->with('success', 'Konfigurasi berhasil diperbarui.');
    }

    public function destroy(Request $request, CompanyConfig $companyConfig): RedirectResponse
    {
        if ($companyConfig->company_id !== $this->companyId($request)) {
            abort(404);
        }

        $companyConfig->delete();

        return back()->with('success', 'Konfigurasi berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $companyId = $this->companyId($request);
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada konfigurasi yang dipilih.');
        }

        $count = CompanyConfig::where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->delete();

        return back()->with('success', "{$count} konfigurasi berhasil dihapus.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $companyId = $this->companyId($request);
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada konfigurasi yang dipilih.');
        }

        $count = CompanyConfig::onlyTrashed()
            ->where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->restore();

        return back()->with('success', "{$count} konfigurasi berhasil dipulihkan.");
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $companyId = $this->companyId($request);
        $query = CompanyConfig::query()->where('company_id', $companyId);

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('value', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($ids = $request->input('ids')) {
            $query->whereIn('id', explode(',', $ids));
        }

        $configs = $query->orderBy('key')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Konfigurasi Perusahaan');

        $headers = ['Key', 'Type', 'Value', 'Description', 'Tgl Dibuat', 'Tgl Diperbarui'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $row = 2;
        foreach ($configs as $c) {
            $col = 1;
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, (string) $c->key, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, (string) $c->type, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, (string) ($c->value ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, (string) ($c->description ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $c->created_at?->format('Y-m-d H:i') ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $c->updated_at?->format('Y-m-d H:i') ?? '-', DataType::TYPE_STRING);
            $row++;
        }

        $filename = 'konfigurasi-perusahaan-' . now()->format('Ymd-His') . '.xlsx';
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
        $sheet->setTitle('Template Konfigurasi');

        $headers = ['Key (wajib unique per company)', 'Type (text|file|number|boolean)', 'Value', 'Description'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setCellValueExplicit('A2', 'company.tagline', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B2', 'text', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C2', 'ISP terbaik di kota Anda', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D2', 'Tagline yang ditampilkan di landing page', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('A3', 'company.max_devices', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B3', 'number', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C3', '5', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D3', 'Maksimum perangkat per pelanggan', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('A4', 'company.is_active', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B4', 'boolean', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C4', 'true', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D4', 'Status aktif perusahaan', DataType::TYPE_STRING);

        $filename = 'template-konfigurasi-perusahaan.xlsx';
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
        $companyId = $this->companyId($request);
        $request->validate(['file' => 'required|file|mimes:xlsx,csv|max:5120']);

        $file = $request->file('file');
        $fullPath = $file->getRealPath();

        $reader = IOFactory::createReaderForFile($fullPath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($fullPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        array_shift($rows);

        $success = 0;
        $errors = [];
        $allowedTypes = ['text', 'file', 'number', 'boolean'];

        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $key = trim((string) ($row[0] ?? ''));
            $type = strtolower(trim((string) ($row[1] ?? '')));
            $value = trim((string) ($row[2] ?? ''));
            $description = trim((string) ($row[3] ?? ''));

            if (empty($key)) {
                $errors[] = "Baris {$line}: Key wajib diisi.";
                continue;
            }

            if (empty($type) || !in_array($type, $allowedTypes, true)) {
                $errors[] = "Baris {$line}: Type '{$type}' tidak valid. Gunakan salah satu: " . implode(', ', $allowedTypes) . '.';
                continue;
            }

            if (CompanyConfig::where('company_id', $companyId)->where('key', $key)->exists()) {
                $errors[] = "Baris {$line}: Key '{$key}' sudah ada di perusahaan ini, lewati.";
                continue;
            }

            $inserts[] = [
                'id' => Str::uuid7(),
                'company_id' => $companyId,
                'key' => $key,
                'type' => $type,
                'value' => $value,
                'description' => $description ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $success++;
        }

        if (!empty($inserts)) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                CompanyConfig::insert($chunk);
            }
        }

        $msg = "{$success} konfigurasi berhasil diimport.";
        if (!empty($errors)) {
            $msg .= " Gagal: " . implode('; ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $msg .= " dan " . (count($errors) - 5) . " lainnya.";
            }
        }

        return back()->with(empty($errors) ? 'success' : 'warning', $msg);
    }
}
