<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Customer::query()->with(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy'])
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
            $allowedSorts = ['name', 'email', 'is_active', 'created_at', 'deleted_at'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDir);
            }
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->input('per_page', 10), 100);

        $customers = $query->paginate($perPage)->through(function ($customer) {
            $photoKtpUrl = $customer->photo_ktp
                ? route('file.proxy', ['path' => $customer->photo_ktp, 'disk' => 'minio'])
                : null;
            $photoKkUrl = $customer->photo_kk
                ? route('file.proxy', ['path' => $customer->photo_kk, 'disk' => 'minio'])
                : null;
            $photoProfileUrl = $customer->photo_profile
                ? route('file.proxy', ['path' => $customer->photo_profile, 'disk' => 'minio'])
                : null;

            return [
                'id' => $customer->id,
                'kode' => $customer->code,
                'nama' => $customer->name,
                'email' => $customer->email,
                'kode_negara' => $customer->phone_country_code,
                'no_telp' => $customer->phone_number,
                'no_nik' => $customer->no_nik,
                'no_kk' => $customer->no_kk,
                'photo_ktp' => $customer->photo_ktp,
                'photo_ktp_url' => $photoKtpUrl,
                'photo_kk' => $customer->photo_kk,
                'photo_kk_url' => $photoKkUrl,
                'photo_profile' => $customer->photo_profile,
                'photo_profile_url' => $photoProfileUrl,
                'alamat' => $customer->address,
                'status' => $customer->is_active ? 'Aktif' : 'Nonaktif',
                'is_active' => $customer->is_active,
                'company_id' => $customer->company_id,
                'dihapus' => $customer->trashed(),
                'deleted_at_raw' => $customer->deleted_at?->toISOString(),
                'deleted_at' => $customer->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $customer->created_at->format('Y-m-d H:i'),
                'updated_at' => $customer->updated_at->format('Y-m-d H:i'),
                'restored_at' => $customer->restored_at?->format('Y-m-d H:i'),
                'created_by' => $customer->createdBy?->name,
                'updated_by' => $customer->updatedBy?->name,
                'deleted_by' => $customer->deletedBy?->name,
                'restored_by' => $customer->restoredBy?->name,
            ];
        });

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/Customer', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => [
                'nullable', 'string', 'max:50',
                Rule::unique('customers', 'code')->where('company_id', auth()->user()->company_id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('customers', 'email')->where('company_id', auth()->user()->company_id),
            ],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => [
                'required', 'string', 'max:20',
                Rule::unique('customers', 'phone_number')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('phone_country_code', $request->input('kode_negara')),
            ],
            'no_nik' => ['nullable', 'string', 'max:50'],
            'no_kk' => ['nullable', 'string', 'max:50'],
            'photo_ktp' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_kk' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_profile' => ['nullable', 'file', 'image', 'max:2048'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'kode.unique' => 'Kode pelanggan sudah digunakan.',
            'email.unique' => 'Email sudah terdaftar di perusahaan ini.',
            'no_telp.unique' => 'Nomor telepon sudah terdaftar di perusahaan ini.',
        ]);

        $photoKtpPath = null;
        $photoKkPath = null;
        $photoProfilePath = null;

        if ($request->hasFile('photo_ktp')) {
            $file = $request->file('photo_ktp');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $photoKtpPath = $file->storeAs('customers/photos', $filename, ['disk' => 'minio', 'visibility' => 'private']);
        }
        if ($request->hasFile('photo_kk')) {
            $file = $request->file('photo_kk');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $photoKkPath = $file->storeAs('customers/photos', $filename, ['disk' => 'minio', 'visibility' => 'private']);
        }
        if ($request->hasFile('photo_profile')) {
            $file = $request->file('photo_profile');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $photoProfilePath = $file->storeAs('customers/photos', $filename, ['disk' => 'minio', 'visibility' => 'public']);
        }

        Customer::create([
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
            'address' => $validated['alamat'] ?? null,
            'is_active' => $validated['status'] === 'Aktif',
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => [
                'nullable', 'string', 'max:50',
                Rule::unique('customers', 'code')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($customer->id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('customers', 'email')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($customer->id),
            ],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => [
                'required', 'string', 'max:20',
                Rule::unique('customers', 'phone_number')
                    ->where('company_id', auth()->user()->company_id)
                    ->where('phone_country_code', $request->input('kode_negara'))
                    ->ignore($customer->id),
            ],
            'no_nik' => ['nullable', 'string', 'max:50'],
            'no_kk' => ['nullable', 'string', 'max:50'],
            'photo_ktp' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_kk' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_profile' => ['nullable', 'file', 'image', 'max:2048'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['nullable', 'string', 'min:8'],
        ], [
            'kode.unique' => 'Kode pelanggan sudah digunakan.',
            'email.unique' => 'Email sudah terdaftar di perusahaan ini.',
            'no_telp.unique' => 'Nomor telepon sudah terdaftar di perusahaan ini.',
        ]);

        $data = [
            'code' => $validated['kode'] ?? null,
            'name' => $validated['nama'],
            'email' => $validated['email'],
            'phone_country_code' => $validated['kode_negara'],
            'phone_number' => $validated['no_telp'],
            'no_nik' => $validated['no_nik'] ?? null,
            'no_kk' => $validated['no_kk'] ?? null,
            'address' => $validated['alamat'] ?? null,
            'is_active' => $validated['status'] === 'Aktif',
        ];

        if ($request->hasFile('photo_ktp')) {
            if ($customer->photo_ktp) {
                Storage::disk('minio')->delete($customer->photo_ktp);
            }
            $file = $request->file('photo_ktp');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $data['photo_ktp'] = $file->storeAs('customers/photos', $filename, ['disk' => 'minio', 'visibility' => 'private']);
        }

        if ($request->hasFile('photo_kk')) {
            if ($customer->photo_kk) {
                Storage::disk('minio')->delete($customer->photo_kk);
            }
            $file = $request->file('photo_kk');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $data['photo_kk'] = $file->storeAs('customers/photos', $filename, ['disk' => 'minio', 'visibility' => 'private']);
        }

        if ($request->hasFile('photo_profile')) {
            if ($customer->photo_profile) {
                Storage::disk('minio')->delete($customer->photo_profile);
            }
            $file = $request->file('photo_profile');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $data['photo_profile'] = $file->storeAs('customers/photos', $filename, ['disk' => 'minio', 'visibility' => 'public']);
        }

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $customer->update($data);

        return back()->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return back()->with('success', 'Pelanggan berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $pelanggan = Customer::withTrashed()->findOrFail($id);
        $pelanggan->restore();

        return back()->with('success', 'Pelanggan berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada pelanggan yang dipilih.');
        }

        $count = Customer::whereIn('id', $ids)->delete();

        return back()->with('success', "{$count} pelanggan berhasil dihapus.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');

        if (empty($ids) || !in_array($status, ['Aktif', 'Nonaktif'])) {
            return back()->with('error', 'Data tidak valid.');
        }

        $count = Customer::whereIn('id', $ids)->update([
            'is_active' => $status === 'Aktif',
        ]);

        $label = $status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "{$count} pelanggan berhasil {$label}.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada pelanggan yang dipilih.');
        $count = Customer::onlyTrashed()->whereIn('id', $ids)->restore();
        return back()->with('success', "{$count} pelanggan berhasil dipulihkan.");
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;
        $query = Customer::where('company_id', $companyId);

        if ($ids = $request->input('ids')) {
            $query->whereIn('id', explode(',', $ids));
        }

        $customers = $query->orderBy('name')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Pelanggan');

        $headers = ['Kode', 'Nama', 'Email', 'No. Telepon', 'No. NIK', 'No. KK', 'Alamat', 'Status'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
        }

        $row = 2;
        foreach ($customers as $c) {
            $col = 1;
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $c->code ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $c->name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $c->email, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, ($c->phone_country_code ?? '') . $c->phone_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $c->no_nik ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $c->no_kk ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $c->address ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $c->is_active ? 'Aktif' : 'Nonaktif', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $row++;
        }

        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension($this->excelColumn($i))->setAutoSize(true);
        }

        $file = storage_path('app/temp/customer_' . now()->format('YmdHis') . '.xlsx');
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

        $headers = ['Kode', 'Nama', 'Email', 'Kode Negara', 'No. Telepon', 'No. NIK', 'No. KK', 'Alamat', 'Status (Aktif/Nonaktif)', 'Password'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
        }

        $example = ['CUST-001', 'Nama Pelanggan', 'email@contoh.com', '+62', '8123456789', '1234567890123456', '1234567890123456', 'Jl. Alamat No. 1, RT 01 RW 01', 'Aktif', 'password123'];
        foreach ($example as $i => $v) {
            $sheet->setCellValueExplicit($this->excelColumn($i + 1) . '2', (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension($this->excelColumn($i))->setAutoSize(true);
        }

        $file = storage_path('app/temp/template_customer.xlsx');
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
            $alamat = trim($row[7] ?? '');
            $status = strtolower(trim($row[8] ?? 'aktif')) === 'nonaktif' ? false : true;
            $password = trim($row[9] ?? '');

            if (empty($nama) || empty($email)) {
                $errors[] = "Baris {$line}: Nama dan Email wajib diisi.";
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Baris {$line}: Format email tidak valid.";
                continue;
            }
            if (Customer::where('company_id', $companyId)->where('email', $email)->exists()) {
                $errors[] = "Baris {$line}: Email {$email} sudah terdaftar.";
                continue;
            }

            $inserts[] = [
                'id' => \Illuminate\Support\Str::uuid(),
                'company_id' => $companyId,
                'code' => $kode ?: null,
                'name' => $nama,
                'email' => $email,
                'phone_country_code' => $kodeNegara,
                'phone_number' => $noTelp,
                'no_nik' => $noNik ?: null,
                'no_kk' => $noKk ?: null,
                'address' => $alamat ?: null,
                'is_active' => $status,
                'password' => Hash::make($password ?: 'password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $success++;
        }

        foreach (array_chunk($inserts, 500) as $chunk) {
            Customer::insert($chunk);
        }

        $msg = "{$success} pelanggan berhasil diimport.";
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
