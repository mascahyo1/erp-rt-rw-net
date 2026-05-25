<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\EmpIncentive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class InsentifController extends Controller
{
    private function excelColumn(int $index): string
    {
        $col = '';
        while ($index > 0) {
            $index--;
            $col = chr(65 + ($index % 26)) . $col;
            $index = (int) ($index / 26);
        }
        return $col;
    }

    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $query = EmpIncentive::query()->with(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy'])
            ->where('company_id', $companyId);

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('is_active', $status === 'Aktif' || $status === 'aktif');
        }

        $allowedSorts = ['name', 'value', 'type', 'is_active', 'created_at'];
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
                'name' => $item->name,
                'type' => $item->type,
                'value' => $item->value,
                'is_active' => $item->is_active,
                'status' => $item->is_active ? 'Aktif' : 'Nonaktif',
                'description' => $item->description,
                'company_id' => $item->company_id,
                'dihapus' => $item->trashed(),
                'deleted_at' => $item->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $item->created_at->format('Y-m-d H:i'),
                'updated_at' => $item->updated_at->format('Y-m-d H:i'),
                'created_by' => $item->createdBy?->name,
                'updated_by' => $item->updatedBy?->name,
                'deleted_by' => $item->deletedBy?->name,
            ];
        });

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/Insentif', [
            'insentifs' => $items,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        EmpIncentive::create([
            'company_id' => auth()->user()->company_id,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'is_active' => $validated['status'] === 'Aktif',
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Insentif berhasil ditambahkan.');
    }

    public function update(Request $request, EmpIncentive $empIncentive): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['percentage', 'fixed'])],
            'value' => ['required', 'numeric'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $empIncentive->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'is_active' => $validated['status'] === 'Aktif',
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('success', 'Insentif berhasil diperbarui.');
    }

    public function destroy(EmpIncentive $empIncentive): RedirectResponse
    {
        $empIncentive->delete();
        return back()->with('success', 'Insentif berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $item = EmpIncentive::withTrashed()->findOrFail($id);
        $item->restore();
        return back()->with('success', 'Insentif berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada insentif yang dipilih.');
        $count = EmpIncentive::whereIn('id', $ids)->delete();
        return back()->with('success', "{$count} insentif berhasil dihapus.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');
        if (empty($ids) || !in_array($status, ['Aktif', 'Nonaktif'])) {
            return back()->with('error', 'Data tidak valid.');
        }
        $count = EmpIncentive::whereIn('id', $ids)->update(['is_active' => $status === 'Aktif']);
        $label = $status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "{$count} insentif berhasil {$label}.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada insentif yang dipilih.');
        $count = EmpIncentive::onlyTrashed()->whereIn('id', $ids)->restore();
        return back()->with('success', "{$count} insentif berhasil dipulihkan.");
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;
        $query = EmpIncentive::query()->where('company_id', $companyId);

        if ($ids = $request->input('ids')) {
            $query->whereIn('id', explode(',', $ids));
        }

        if ($search = $request->input('search')) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"));
        }
        if ($status = $request->input('status')) {
            $query->where('is_active', $status === 'Aktif');
        }
        if ($terhapus = $request->input('terhapus')) {
            $terhapus === 'ya' ? $query->onlyTrashed() : $query->whereNull('deleted_at');
        }

        $insentifs = $query->orderBy('created_at', 'desc')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Insentif');

        $headers = ['Nama', 'Tipe', 'Nilai', 'Status', 'Deskripsi'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $row = 2;
        foreach ($insentifs as $i) {
            $col = 1;
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $i->name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $i->type === 'percentage' ? 'Persentase' : 'Tetap', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $i->type === 'percentage' ? $i->value . '%' : number_format($i->value ?? 0, 2, '.', ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $i->is_active ? 'Aktif' : 'Nonaktif', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $i->description ?? '-', DataType::TYPE_STRING);
            $row++;
        }

        $filename = 'insentif-' . now()->format('Ymd-His') . '.xlsx';
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
        $sheet->setTitle('Template Insentif');

        $headers = ['Nama', 'Tipe (percentage/fixed)', 'Nilai', 'Status (Aktif/Nonaktif)', 'Deskripsi'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setCellValueExplicit('A2', 'Insentif Penjualan', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('B2', 'percentage', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('C2', '10', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('D2', 'Aktif', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('E2', 'Insentif berdasarkan persentase penjualan', DataType::TYPE_STRING);

        $filename = 'template-insentif.xlsx';
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
        $companyId = auth()->user()->company_id;
        $imported = 0;
        $errors = [];

        foreach (array_slice($rows, 1) as $idx => $row) {
            if (empty(array_filter($row))) continue;

            $data = array_combine($header, $row);
            $name = trim($data['nama'] ?? '');
            $type = strtolower(trim($data['tipe (percentage/fixed)'] ?? 'percentage'));
            $value = trim($data['nilai'] ?? '');
            $status = ucfirst(strtolower(trim($data['status (aktif/nonaktif)'] ?? 'Aktif')));
            $description = trim($data['deskripsi'] ?? '');

            if (!$name || !$value) {
                $errors[] = 'Baris ' . ($idx + 2) . ': Nama dan Nilai wajib diisi.';
                continue;
            }
            if (!in_array($type, ['percentage', 'fixed'])) {
                $errors[] = 'Baris ' . ($idx + 2) . ': Tipe harus "percentage" atau "fixed".';
                continue;
            }
            if (!in_array($status, ['Aktif', 'Nonaktif'])) {
                $status = 'Aktif';
            }

            EmpIncentive::create([
                'company_id' => $companyId,
                'name' => $name,
                'type' => $type,
                'value' => (float) $value,
                'is_active' => $status === 'Aktif',
                'description' => $description ?: null,
            ]);
            $imported++;
        }

        if ($imported > 0) {
            return back()->with('success', "{$imported} insentif berhasil diimport." . (count($errors) > 0 ? ' ' . count($errors) . ' baris dilewati.' : ''));
        }
        return back()->with('error', 'Gagal mengimport insentif. ' . implode(' ', $errors));
    }
}
