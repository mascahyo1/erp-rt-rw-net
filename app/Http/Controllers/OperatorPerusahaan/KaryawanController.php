<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KaryawanController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Employee::query()->with(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy'])
            ->where('company_id', auth()->user()->company_id);

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('is_active', $status === 'Aktif' || $status === 'aktif');
        }

        if ($sortField = $request->input('sort_field')) {
            $sortDir = $request->input('sort_dir', 'asc');
            $allowedSorts = ['code', 'name', 'email', 'is_active', 'created_at', 'deleted_at'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDir);
            }
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->input('per_page', 10), 100);

        $karyawans = $query->paginate($perPage)->through(function ($employee) {
            $photoKtpUrl = $employee->photo_ktp
                ? route('file.proxy', ['path' => $employee->photo_ktp, 'disk' => 's3'])
                : null;
            $photoKkUrl = $employee->photo_kk
                ? route('file.proxy', ['path' => $employee->photo_kk, 'disk' => 's3'])
                : null;
            $photoProfileUrl = $employee->photo_profile
                ? route('file.proxy', ['path' => $employee->photo_profile, 'disk' => 's3'])
                : null;

            return [
                'id' => $employee->id,
                'kode' => $employee->code,
                'nama' => $employee->name,
                'email' => $employee->email,
                'kode_negara' => $employee->phone_country_code,
                'no_telp' => $employee->phone_number,
                'no_nik' => $employee->no_nik,
                'no_kk' => $employee->no_kk,
                'photo_ktp' => $employee->photo_ktp,
                'photo_ktp_url' => $photoKtpUrl,
                'photo_kk' => $employee->photo_kk,
                'photo_kk_url' => $photoKkUrl,
                'photo_profile' => $employee->photo_profile,
                'photo_profile_url' => $photoProfileUrl,
                'status' => $employee->is_active ? 'Aktif' : 'Nonaktif',
                'is_active' => $employee->is_active,
                'company_id' => $employee->company_id,
                'dihapus' => $employee->trashed(),
                'deleted_at_raw' => $employee->deleted_at?->toISOString(),
                'deleted_at' => $employee->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $employee->created_at->format('Y-m-d H:i'),
                'updated_at' => $employee->updated_at->format('Y-m-d H:i'),
                'restored_at' => $employee->restored_at?->format('Y-m-d H:i'),
                'created_by' => $employee->createdBy?->name,
                'updated_by' => $employee->updatedBy?->name,
                'deleted_by' => $employee->deletedBy?->name,
                'restored_by' => $employee->restoredBy?->name,
            ];
        });

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/Karyawan', [
            'karyawans' => $karyawans,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => [
                'nullable', 'string', 'max:50',
                Rule::unique('employees', 'code')->where('company_id', auth()->user()->company_id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('employees')->where('company_id', auth()->user()->company_id),
            ],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => ['required', 'string', 'max:20'],
            'no_nik' => ['nullable', 'string', 'max:50'],
            'no_kk' => ['nullable', 'string', 'max:50'],
            'photo_ktp' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_kk' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_profile' => ['nullable', 'file', 'image', 'max:2048'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'kode.unique' => 'Kode karyawan sudah digunakan di perusahaan ini.',
            'email.unique' => 'Email sudah terdaftar di perusahaan ini.',
        ]);

        $photoKtpPath = null;
        $photoKkPath = null;
        $photoProfilePath = null;

        if ($request->hasFile('photo_ktp')) {
            $file = $request->file('photo_ktp');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $photoKtpPath = $file->storeAs('employees/photos', $filename, ['disk' => 's3', 'visibility' => 'private']);
        }
        if ($request->hasFile('photo_kk')) {
            $file = $request->file('photo_kk');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $photoKkPath = $file->storeAs('employees/photos', $filename, ['disk' => 's3', 'visibility' => 'private']);
        }
        if ($request->hasFile('photo_profile')) {
            $file = $request->file('photo_profile');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $photoProfilePath = $file->storeAs('employees/photos', $filename, ['disk' => 's3', 'visibility' => 'public']);
        }

        Employee::create([
            'company_id' => auth()->user()->company_id,
            'code' => $validated['kode'] ?? null,
            'name' => $validated['nama'],
            'email' => $validated['email'],
            'phone_country_code' => $validated['kode_negara'],
            'phone_number' => $validated['no_telp'],
            'no_nik' => $validated['no_nik'] ?? null,
            'no_kk' => $validated['no_kk'] ?? null,
            'photo_ktp' => $photoKtpPath,
            'photo_kk' => $photoKkPath,
            'photo_profile' => $photoProfilePath,
            'is_active' => $validated['status'] === 'Aktif',
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => [
                'nullable', 'string', 'max:50',
                Rule::unique('employees', 'code')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($employee->id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('employees')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($employee->id),
            ],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => ['required', 'string', 'max:20'],
            'no_nik' => ['nullable', 'string', 'max:50'],
            'no_kk' => ['nullable', 'string', 'max:50'],
            'photo_ktp' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_kk' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_profile' => ['nullable', 'file', 'image', 'max:2048'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['nullable', 'string', 'min:8'],
        ], [
            'kode.unique' => 'Kode karyawan sudah digunakan di perusahaan ini.',
            'email.unique' => 'Email sudah terdaftar di perusahaan ini.',
        ]);

        $data = [
            'code' => $validated['kode'] ?? null,
            'name' => $validated['nama'],
            'email' => $validated['email'],
            'phone_country_code' => $validated['kode_negara'],
            'phone_number' => $validated['no_telp'],
            'no_nik' => $validated['no_nik'] ?? null,
            'no_kk' => $validated['no_kk'] ?? null,
            'is_active' => $validated['status'] === 'Aktif',
        ];

        if ($request->hasFile('photo_ktp')) {
            if ($employee->photo_ktp) {
                Storage::disk('s3')->delete($employee->photo_ktp);
            }
            $file = $request->file('photo_ktp');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $data['photo_ktp'] = $file->storeAs('employees/photos', $filename, ['disk' => 's3', 'visibility' => 'private']);
        }

        if ($request->hasFile('photo_kk')) {
            if ($employee->photo_kk) {
                Storage::disk('s3')->delete($employee->photo_kk);
            }
            $file = $request->file('photo_kk');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $data['photo_kk'] = $file->storeAs('employees/photos', $filename, ['disk' => 's3', 'visibility' => 'private']);
        }

        if ($request->hasFile('photo_profile')) {
            if ($employee->photo_profile) {
                Storage::disk('s3')->delete($employee->photo_profile);
            }
            $file = $request->file('photo_profile');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $data['photo_profile'] = $file->storeAs('employees/photos', $filename, ['disk' => 's3', 'visibility' => 'public']);
        }

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $employee->update($data);

        return back()->with('success', 'Karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return back()->with('success', 'Karyawan berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $karyawan = Employee::withTrashed()->findOrFail($id);
        $karyawan->restore();

        return back()->with('success', 'Karyawan berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada karyawan yang dipilih.');
        }

        $count = Employee::whereIn('id', $ids)->delete();

        return back()->with('success', "{$count} karyawan berhasil dihapus.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');

        if (empty($ids) || !in_array($status, ['Aktif', 'Nonaktif'])) {
            return back()->with('error', 'Data tidak valid.');
        }

        $count = Employee::whereIn('id', $ids)->update([
            'is_active' => $status === 'Aktif',
        ]);

        $label = $status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "{$count} karyawan berhasil {$label}.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada karyawan yang dipilih.');
        $count = Employee::onlyTrashed()->whereIn('id', $ids)->restore();
        return back()->with('success', "{$count} karyawan berhasil dipulihkan.");
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;
        $query = Employee::where('company_id', $companyId);

        if ($ids = $request->input('ids')) {
            $query->whereIn('id', explode(',', $ids));
        }

        $karyawans = $query->orderBy('name')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Karyawan');

        $headers = ['Kode', 'Nama', 'Email', 'Kode Negara', 'No. Telepon', 'No. NIK', 'No. KK', 'Status'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
        }

        $row = 2;
        foreach ($karyawans as $k) {
            $col = 1;
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $k->code ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $k->name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $k->email, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $k->phone_country_code ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $k->phone_number ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $k->no_nik ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $k->no_kk ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $k->is_active ? 'Aktif' : 'Nonaktif', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $row++;
        }

        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension($this->excelColumn($i))->setAutoSize(true);
        }

        $file = storage_path('app/temp/karyawan_' . now()->format('YmdHis') . '.xlsx');
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

        $headers = ['Kode', 'Nama', 'Email', 'Kode Negara', 'No. Telepon', 'No. NIK', 'No. KK', 'Status (Aktif/Nonaktif)', 'Password'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
        }

        $example = ['KRY001', 'Nama Karyawan', 'email@perusahaan.id', '+62', '81234567890', '1234567890123456', '1234567890123456', 'Aktif', 'password123'];
        foreach ($example as $i => $v) {
            $sheet->setCellValueExplicit($this->excelColumn($i + 1) . '2', (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension($this->excelColumn($i))->setAutoSize(true);
        }

        $file = storage_path('app/temp/template_karyawan.xlsx');
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($file);

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
        $success = 0;
        $errors = [];
        $inserts = [];

        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $kode = trim($row[0] ?? '');
            $nama = trim($row[1] ?? '');
            $email = trim($row[2] ?? '');
            $kodeNegara = trim($row[3] ?? '') ?: '+62';
            $noTelp = trim($row[4] ?? '');
            $noNik = trim($row[5] ?? '');
            $noKk = trim($row[6] ?? '');
            $status = strtolower(trim($row[7] ?? 'aktif')) === 'nonaktif' ? false : true;
            $password = trim($row[8] ?? '');

            if (empty($nama) || empty($email)) {
                $errors[] = "Baris {$line}: Nama dan Email wajib diisi.";
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Baris {$line}: Format email tidak valid.";
                continue;
            }
            if (Employee::where('company_id', $companyId)->where('email', $email)->exists()) {
                $errors[] = "Baris {$line}: Email {$email} sudah terdaftar.";
                continue;
            }
            if ($kode !== '' && Employee::where('company_id', $companyId)->where('code', $kode)->exists()) {
                $errors[] = "Baris {$line}: Kode karyawan {$kode} sudah digunakan di perusahaan ini.";
                continue;
            }

            $inserts[] = [
                'company_id' => $companyId,
                'code' => $kode !== '' ? $kode : null,
                'name' => $nama,
                'email' => $email,
                'phone_country_code' => $kodeNegara,
                'phone_number' => $noTelp,
                'no_nik' => $noNik ?: null,
                'no_kk' => $noKk ?: null,
                'is_active' => $status,
                'password' => Hash::make($password ?: 'password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $success++;
        }

        foreach (array_chunk($inserts, 500) as $chunk) {
            Employee::insert($chunk);
        }

        $msg = "{$success} karyawan berhasil diimport.";
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
