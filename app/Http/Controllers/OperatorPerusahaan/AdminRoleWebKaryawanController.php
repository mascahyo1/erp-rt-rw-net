<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\ModelHasRole;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRoleWebKaryawanController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $rolesForCompany = Role::where('scope', 'karyawan_perusahaan')
            ->where('company_id', $companyId)
            ->pluck('id');

        $query = ModelHasRole::query()
            ->with(['role', 'model'])
            ->where('model_type', Employee::class)
            ->whereIn('role_id', $rolesForCompany);

        if ($search = $request->input('search')) {
            $query->whereHas('model', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        if ($sortField = $request->input('sort_field')) {
            $query->orderBy($sortField, $request->input('sort_dir', 'asc'));
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->input('per_page', 10), 100);

        $assignments = $query->paginate($perPage)->through(fn($m) => [
            'id' => $m->id,
            'karyawan_id' => $m->model_id,
            'karyawan_nama' => $m->model?->name,
            'karyawan_email' => $m->model?->email,
            'karyawan_status' => $m->model?->is_active ? 'Aktif' : 'Nonaktif',
            'role_id' => $m->role_id,
            'role_nama' => $m->role?->name,
            'created_at' => $m->created_at->format('Y-m-d H:i'),
        ]);

        $karyawans = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->toArray();

        $roles = Role::where('scope', 'karyawan_perusahaan')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        return Inertia::render('OperatorPerusahaan/AdminRoleWebKaryawan', [
            'assignments' => $assignments,
            'filters' => $request->only(['search', 'sort_field', 'sort_dir', 'per_page']),
            'karyawans' => $karyawans,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'karyawan_id' => ['required', 'uuid', 'exists:employees,id'],
            'role_id' => ['required', 'uuid', 'exists:roles,id'],
        ]);

        ModelHasRole::updateOrCreate(
            ['model_type' => Employee::class, 'model_id' => $validated['karyawan_id']],
            ['role_id' => $validated['role_id']],
        );

        return back()->with('success', 'Role web karyawan berhasil ditetapkan.');
    }

    public function update(Request $request, ModelHasRole $modelHasRole): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'uuid', 'exists:roles,id'],
        ]);

        $modelHasRole->update(['role_id' => $validated['role_id']]);

        return back()->with('success', 'Role web karyawan berhasil diperbarui.');
    }

    public function destroy(ModelHasRole $modelHasRole): RedirectResponse
    {
        $modelHasRole->delete();
        return back()->with('success', 'Penugasan role berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada data yang dipilih.');
        $count = ModelHasRole::whereIn('id', $ids)->delete();
        return back()->with('success', "{$count} penugasan role berhasil dihapus.");
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;
        $rolesForCompany = Role::where('scope', 'karyawan_perusahaan')->where('company_id', $companyId)->pluck('id');

        $query = ModelHasRole::with(['role', 'model'])
            ->where('model_type', Employee::class)
            ->whereIn('role_id', $rolesForCompany);

        if ($ids = $request->input('ids')) {
            $query->whereIn('id', explode(',', $ids));
        }

        $rows = $query->orderBy('created_at')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mapping Karyawan-Role');

        $headers = ['Nama Karyawan', 'Email Karyawan', 'Role', 'Tanggal Ditugaskan'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
        }

        $rowNum = 2;
        foreach ($rows as $r) {
            $col = 1;
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $rowNum, $r->model?->name ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $rowNum, $r->model?->email ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $rowNum, $r->role?->name ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $rowNum, $r->created_at->format('Y-m-d H:i'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $rowNum++;
        }

        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension($this->excelColumn($i))->setAutoSize(true);
        }

        $file = storage_path('app/temp/admin_role_web_karyawan_' . now()->format('YmdHis') . '.xlsx');
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($file);

        return response()->download($file)->deleteFileAfterSend();
    }

    public function template(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import');

        $headers = ['Nama Karyawan', 'Email Karyawan', 'Role'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
        }

        $example = ['Ahmad Fauzi', 'ahmad@netsejahtera.com', 'Default'];
        foreach ($example as $i => $v) {
            $sheet->setCellValueExplicit($this->excelColumn($i + 1) . '2', (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension($this->excelColumn($i))->setAutoSize(true);
        }

        $file = storage_path('app/temp/template_admin_role_web_karyawan.xlsx');
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($file);

        return response()->download($file)->deleteFileAfterSend();
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,csv|max:2048']);

        $file = $request->file('file');
        $fullPath = $file->getRealPath();

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($fullPath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($fullPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        array_shift($rows);

        $companyId = auth()->user()->company_id;
        $roles = Role::where('scope', 'karyawan_perusahaan')->where('company_id', $companyId)->get()->keyBy('name');
        $karyawans = Employee::where('company_id', $companyId)->get()->keyBy('email');

        $success = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $karyawanEmail = trim($row[1] ?? '');
            $roleName = trim($row[2] ?? '');

            if (empty($karyawanEmail) || empty($roleName)) {
                $errors[] = "Baris {$line}: Email Karyawan dan Role wajib diisi.";
                continue;
            }

            $karyawan = $karyawans->get($karyawanEmail);
            if (!$karyawan) {
                $errors[] = "Baris {$line}: Karyawan dengan email '{$karyawanEmail}' tidak ditemukan di perusahaan ini.";
                continue;
            }

            $role = $roles->get($roleName);
            if (!$role) {
                $errors[] = "Baris {$line}: Role '{$roleName}' tidak ditemukan di perusahaan ini.";
                continue;
            }

            ModelHasRole::updateOrCreate(
                ['model_type' => Employee::class, 'model_id' => $karyawan->id],
                ['role_id' => $role->id]
            );
            $success++;
        }

        $msg = "{$success} mapping berhasil diimport.";
        if ($errors) {
            $msg .= ' ' . count($errors) . ' baris error: ' . implode('; ', array_slice($errors, 0, 5));
        }

        return back()->with('success', $msg);
    }

    private function excelColumn(int $n): string
    {
        $col = '';
        while ($n > 0) {
            $n--;
            $col = chr(65 + $n % 26) . $col;
            $n = intdiv($n, 26);
        }
        return $col;
    }
}
