<?php

namespace App\Http\Controllers;

use App\Models\SaasConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SaasConfigController extends Controller
{
    private function excelColumn(int $index): string
    {
        return match ($index) {
            1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F',
            default => 'A',
        };
    }

    /**
     * Normalize a value for a given type before persisting.
     * - boolean: always store as 'true' / 'false' string (handles 1/0/true/false/on/off/yes/no).
     * - number: store as the canonical string form of the number.
     */
    private function normalizeValue(string $type, mixed $value): string
    {
        if ($type === 'boolean') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }
        if ($type === 'number') {
            return (string) (is_numeric($value) ? 0 + $value : $value);
        }
        return (string) $value;
    }

    public function index(Request $request): Response
    {
        $query = SaasConfig::query()
            ->with(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy']);

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('value', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
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
                'key' => $config->key,
                'type' => $config->type,
                'value' => $config->value,
                'description' => $config->description,
                'dihapus' => $config->trashed(),
                'deleted_at' => $config->deleted_at?->format('Y-m-d H:i'),
                'restored_at' => $config->restored_at instanceof \DateTimeInterface ? $config->restored_at->format('Y-m-d H:i') : $config->restored_at,
                'created_at' => $config->created_at->format('Y-m-d H:i'),
                'updated_at' => $config->updated_at->format('Y-m-d H:i'),
                'created_by' => $config->createdBy?->name,
                'updated_by' => $config->updatedBy?->name,
                'deleted_by' => $config->deletedBy?->name,
                'restored_by' => $config->restoredBy?->name,
            ];
        });

        return Inertia::render('OperatorSaas/Konfigurasi', [
            'configs' => $configs,
            'filters' => $request->only(['search', 'type', 'terhapus', 'sort_field', 'sort_dir', 'per_page']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('saas_configs', 'key')->whereNull('deleted_at'),
            ],
            'type' => ['required', 'in:text,file,number,boolean,kredensial'],
            'value' => ['required', 'string', 'max:65535'],
            'description' => ['nullable', 'string', 'max:65535'],
        ]);

        $validated['value'] = $this->normalizeValue($validated['type'], $validated['value']);

        SaasConfig::create($validated);

        return back()->with('success', 'Konfigurasi berhasil ditambahkan.');
    }

    public function update(Request $request, SaasConfig $saasConfig): RedirectResponse
    {
        $validated = $request->validate([
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('saas_configs', 'key')
                    ->ignore($saasConfig->id, 'id')
                    ->whereNull('deleted_at'),
            ],
            'type' => ['required', 'in:text,file,number,boolean,kredensial'],
            'value' => ['required', 'string', 'max:65535'],
            'description' => ['nullable', 'string', 'max:65535'],
        ]);

        $validated['value'] = $this->normalizeValue($validated['type'], $validated['value']);

        $saasConfig->update($validated);

        return back()->with('success', 'Konfigurasi berhasil diperbarui.');
    }

    public function destroy(SaasConfig $saasConfig): RedirectResponse
    {
        $saasConfig->delete();

        return back()->with('success', 'Konfigurasi berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada konfigurasi yang dipilih.');
        }

        $count = SaasConfig::whereIn('id', $ids)->delete();

        return back()->with('success', "{$count} konfigurasi berhasil dihapus.");
    }

    public function restore(string $id): RedirectResponse
    {
        $config = SaasConfig::withTrashed()->findOrFail($id);
        $config->restore();

        return back()->with('success', 'Konfigurasi berhasil dipulihkan.');
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada konfigurasi yang dipilih.');
        }

        $count = SaasConfig::onlyTrashed()->whereIn('id', $ids)->restore();

        return back()->with('success', "{$count} konfigurasi berhasil dipulihkan.");
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $query = SaasConfig::query();

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('value', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
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
        $sheet->setTitle('Konfigurasi SaaS');

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

        $filename = 'konfigurasi-saas-' . now()->format('Ymd-His') . '.xlsx';
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

        $headers = ['Key (wajib unique)', 'Type (text|file|number|boolean|kredensial)', 'Value', 'Description'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setCellValueExplicit('A2', 'app.tagline', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B2', 'text', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C2', 'ERP RT/RW Net — Multi-tenant ISP Management', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D2', 'Tagline yang ditampilkan di landing page SaaS', DataType::TYPE_STRING);

        $sheet->setCellValueExplicit('A3', 'app.max_companies', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B3', 'number', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C3', '100', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D3', 'Maksimum jumlah perusahaan yang dapat terdaftar', DataType::TYPE_STRING);

        $sheet->setCellValueExplicit('A4', 'app.maintenance_mode', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B4', 'boolean', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C4', 'false', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D4', 'Status mode maintenance platform', DataType::TYPE_STRING);

        $sheet->setCellValueExplicit('A5', 'app.stripe_secret', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B5', 'kredensial', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C5', 'sk_live_xxx_REPLACE_ME', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D5', 'Stripe secret key (disembunyikan default di UI)', DataType::TYPE_STRING);

        $filename = 'template-konfigurasi-saas.xlsx';
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

        $success = 0;
        $errors = [];
        $allowedTypes = ['text', 'file', 'number', 'boolean', 'kredensial'];
        $inserts = [];

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

            if (SaasConfig::where('key', $key)->exists()) {
                $errors[] = "Baris {$line}: Key '{$key}' sudah ada, lewati.";
                continue;
            }

            $inserts[] = [
                'id' => Str::uuid7(),
                'key' => $key,
                'type' => $type,
                'value' => $this->normalizeValue($type, $value),
                'description' => $description ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $success++;
        }

        if (!empty($inserts)) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                SaasConfig::insert($chunk);
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
