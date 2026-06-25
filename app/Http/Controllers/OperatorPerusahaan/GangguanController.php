<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Enums\SupportTicketPengerjaanStatus;
use App\Enums\SupportTicketVerifikasiStatus;
use App\Http\Controllers\Controller;
use App\Models\CustInternet;
use App\Models\Employee;
use App\Models\Gangguan;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GangguanController extends Controller
{
    /**
     * Admin Perusahaan: full CRUD + verify (approve/reject hasil resolution).
     */
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $query = Gangguan::query()->with([
            'custInternet.customer',
            'custInternet.internetPackage',
            'assignedToEmployee',
            'createdBy',
            'updatedBy',
            'deletedBy',
            'restoredBy',
        ])->whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId));

        if ($request->input('terhapus') === 'ya') $query->onlyTrashed();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('catatan', 'like', "%{$search}%")
                  ->orWhere('alasan_verifikasi', 'like', "%{$search}%")
                  ->orWhereHas('custInternet.customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }
        if ($sp = $request->input('status_pengerjaan')) $query->where('status_pengerjaan', $sp);
        if ($sv = $request->input('status_verifikasi')) $query->where('status_verifikasi', $sv);
        if ($assigned = $request->input('assigned_to_employee_id')) $query->where('assigned_to_employee_id', $assigned);
        if ($custInet = $request->input('cust_internet_id')) $query->where('cust_internet_id', $custInet);
        if ($createdStart = $request->input('created_start')) $query->whereDate('created_at', '>=', $createdStart);
        if ($createdEnd = $request->input('created_end')) $query->whereDate('created_at', '<=', $createdEnd);

        $allowedSorts = ['code', 'status_pengerjaan', 'status_verifikasi', 'created_at', 'issue_dimulai_dari', 'issue_diselesaikan_pada'];
        $sortField = $request->input('sort_field', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        $query->orderBy($sortField, $sortDir);

        $perPage = min((int) $request->input('per_page', 10), 100);
        $items = $query->paginate($perPage)->through(fn($item) => $this->serialize($item));

        $custInternets = CustInternet::with(['customer', 'internetPackage'])
            ->whereHas('customer', fn($q) => $q->where('company_id', $companyId))
            ->get()
            ->map(fn($ci) => [
                'id' => $ci->id,
                'account_number' => $ci->account_number,
                'customer_name' => $ci->customer?->name,
                'package_name' => $ci->internetPackage?->name,
                'display' => $ci->account_number . ' — ' . ($ci->customer?->name ?? '-') . ' — ' . ($ci->internetPackage?->name ?? '-'),
            ]);

        $employees = Employee::where('company_id', $companyId)->where('is_active', true)
            ->get(['id', 'name'])->map(fn($e) => ['id' => $e->id, 'name' => $e->name]);

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/Gangguan', [
            'gangguans' => $items,
            'custInternets' => $custInternets,
            'employees' => $employees,
            'filters' => $request->only(['search', 'status_pengerjaan', 'status_verifikasi', 'assigned_to_employee_id', 'cust_internet_id', 'created_start', 'created_end', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
            'statusPengerjaanOptions' => SupportTicketPengerjaanStatus::values(),
            'statusVerifikasiOptions' => SupportTicketVerifikasiStatus::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cust_internet_id' => ['required', 'uuid', 'exists:cust_internets,id'],
            'assigned_to_employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'catatan' => ['required', 'string', 'max:2000'],
            'file_bukti_issue' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ]);

        $companyId = auth()->user()->company_id;
        $ci = CustInternet::whereHas('customer', fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($validated['cust_internet_id']);

        $data = [
            'code' => 'TKT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
            'cust_internet_id' => $ci->id,
            'catatan' => $validated['catatan'],
            'status_pengerjaan' => SupportTicketPengerjaanStatus::OPEN->value,
            'status_verifikasi' => SupportTicketVerifikasiStatus::PENDING->value,
            'issue_dimulai_dari' => now(),
        ];
        if (!empty($validated['assigned_to_employee_id'])) $data['assigned_to_employee_id'] = $validated['assigned_to_employee_id'];

        $uploader = new FileUploadService();
        if ($request->hasFile('file_bukti_issue')) {
            $data['file_bukti_issue'] = $uploader->processImage($request->file('file_bukti_issue'), 'gangguan/issues');
        }

        Gangguan::create($data);
        return back()->with('success', 'Tiket gangguan berhasil dibuat.');
    }

    public function update(Request $request, Gangguan $gangguan): RedirectResponse
    {
        $companyId = auth()->user()->company_id;
        $ownsTicket = $gangguan->custInternet?->customer?->company_id === $companyId;
        if (!$ownsTicket) return back()->with('error', 'Anda tidak berhak mengubah tiket ini.');

        $validated = $request->validate([
            'assigned_to_employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'status_pengerjaan' => ['nullable', 'string', 'in:open,in_progress,resolved'],
            'file_bukti_issue' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'file_bukti_issue_diselesaikan' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'alasan_verifikasi' => ['nullable', 'string', 'max:1000'],
            'status_verifikasi' => ['nullable', 'string', 'in:pending,approved,rejected'],
        ]);

        $data = [];
        foreach (['assigned_to_employee_id', 'catatan', 'status_pengerjaan', 'alasan_verifikasi', 'status_verifikasi'] as $k) {
            if (array_key_exists($k, $validated) && $validated[$k] !== null) $data[$k] = $validated[$k];
        }
        if (!empty($data['status_pengerjaan']) && $data['status_pengerjaan'] === SupportTicketPengerjaanStatus::RESOLVED->value && !$gangguan->issue_diselesaikan_pada) {
            $data['issue_diselesaikan_pada'] = now();
        }

        $uploader = new FileUploadService();
        if ($request->hasFile('file_bukti_issue')) {
            if ($gangguan->file_bukti_issue) $uploader->deleteFile($gangguan->file_bukti_issue);
            $data['file_bukti_issue'] = $uploader->processImage($request->file('file_bukti_issue'), 'gangguan/issues');
        }
        if ($request->hasFile('file_bukti_issue_diselesaikan')) {
            if ($gangguan->file_bukti_issue_diselesaikan) $uploader->deleteFile($gangguan->file_bukti_issue_diselesaikan);
            $data['file_bukti_issue_diselesaikan'] = $uploader->processImage($request->file('file_bukti_issue_diselesaikan'), 'gangguan/resolutions');
        }

        $gangguan->update($data);
        return back()->with('success', 'Tiket gangguan berhasil diperbarui.');
    }

    /**
     * Verify hasil resolution: approved | rejected.
     * Hanya admin perusahaan. Tiket harus status_pengerjaan=resolved dulu.
     */
    public function verify(Request $request, Gangguan $gangguan): RedirectResponse
    {
        $companyId = auth()->user()->company_id;
        $ownsTicket = $gangguan->custInternet?->customer?->company_id === $companyId;
        if (!$ownsTicket) return back()->with('error', 'Anda tidak berhak memverifikasi tiket ini.');

        $validated = $request->validate([
            'status_verifikasi' => ['required', 'string', 'in:approved,rejected'],
            'alasan_verifikasi' => ['required', 'string', 'max:1000'],
        ]);

        if ($gangguan->status_pengerjaan !== SupportTicketPengerjaanStatus::RESOLVED) {
            return back()->with('error', 'Hanya tiket yang sudah selesai (resolved) yang bisa diverifikasi.');
        }

        $gangguan->update([
            'status_verifikasi' => $validated['status_verifikasi'],
            'alasan_verifikasi' => $validated['alasan_verifikasi'],
        ]);

        $label = $validated['status_verifikasi'] === 'approved' ? 'disetujui' : 'ditolak';
        return back()->with('success', "Tiket berhasil {$label}.");
    }

    public function destroy(Gangguan $gangguan): RedirectResponse
    {
        $companyId = auth()->user()->company_id;
        $ownsTicket = $gangguan->custInternet?->customer?->company_id === $companyId;
        if (!$ownsTicket) return back()->with('error', 'Anda tidak berhak menghapus tiket ini.');
        $gangguan->delete();
        return back()->with('success', 'Tiket berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $gangguan = Gangguan::withTrashed()->findOrFail($id);
        $companyId = auth()->user()->company_id;
        if ($gangguan->custInternet?->customer?->company_id !== $companyId) {
            return back()->with('error', 'Anda tidak berhak memulihkan tiket ini.');
        }
        $gangguan->restore();
        return back()->with('success', 'Tiket berhasil dipulihkan.');
    }

    /**
     * Mark as resolved: shortcut karyawan untuk tandai tiket selesai + upload bukti sekaligus.
     * Dipakai oleh karyawan (route karyawan-gangguan.resolve) — perusahaan tidak perlu ini.
     */
    public function resolve(Request $request, Gangguan $gangguan): RedirectResponse
    {
        $companyId = auth()->user()->company_id;
        $ownsTicket = $gangguan->custInternet?->customer?->company_id === $companyId;
        if (!$ownsTicket) return back()->with('error', 'Anda tidak berhak mengubah tiket ini.');

        $validated = $request->validate([
            'file_bukti_issue_diselesaikan' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ]);

        $data = [
            'status_pengerjaan' => SupportTicketPengerjaanStatus::RESOLVED->value,
            'issue_diselesaikan_pada' => now(),
        ];

        $uploader = new FileUploadService();
        if ($request->hasFile('file_bukti_issue_diselesaikan')) {
            if ($gangguan->file_bukti_issue_diselesaikan) $uploader->deleteFile($gangguan->file_bukti_issue_diselesaikan);
            $data['file_bukti_issue_diselesaikan'] = $uploader->processImage($request->file('file_bukti_issue_diselesaikan'), 'gangguan/resolutions');
        }

        $gangguan->update($data);
        return back()->with('success', 'Tiket ditandai selesai. Menunggu verifikasi admin.');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;

        $query = Gangguan::query()
            ->with(['custInternet.customer', 'custInternet.internetPackage', 'assignedToEmployee', 'createdBy'])
            ->whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId));

        if ($request->input('terhapus') === 'ya') $query->onlyTrashed();
        else $query->whereNull('deleted_at');

        if ($sp = $request->input('status_pengerjaan')) $query->where('status_pengerjaan', $sp);
        if ($sv = $request->input('status_verifikasi')) $query->where('status_verifikasi', $sv);

        $items = $query->orderBy('created_at', 'desc')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Gangguan');

        $headers = ['No', 'Kode', 'Kode Langganan', 'Customer', 'Penanggung Jawab', 'Catatan', 'Status Pengerjaan', 'Status Verifikasi', 'Alasan Verifikasi', 'Tgl Mulai', 'Tgl Selesai'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $row = 2;
        foreach ($items as $g) {
            $col = 1;
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $row - 1, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $g->code, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $g->custInternet?->account_number ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $g->custInternet?->customer?->name ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $g->assignedToEmployee?->name ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $g->catatan, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $g->status_pengerjaan?->label() ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $g->status_verifikasi?->label() ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $g->alasan_verifikasi ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $g->issue_dimulai_dari?->format('Y-m-d H:i') ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $g->issue_diselesaikan_pada?->format('Y-m-d H:i') ?? '-', DataType::TYPE_STRING);
            $row++;
        }

        $filename = 'gangguan-' . date('Y-m-d-His') . '.xlsx';
        $tempPath = storage_path("app/exports/{$filename}");
        if (!is_dir(dirname($tempPath))) mkdir(dirname($tempPath), 0755, true);
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return response()->download($tempPath, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Gangguan');
        $headers = ['Kode Langganan (Account Number)', 'Penanggung Jawab (Nama Karyawan)', 'Catatan'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        // Tambah 1 row contoh
        $sheet->setCellValue('A2', 'CI-XXXXX');
        $sheet->setCellValue('B2', 'Ahmad Karyawan');
        $sheet->setCellValue('C2', 'Internet lambat sejak pagi');

        $filename = 'template-gangguan.xlsx';
        $tempPath = storage_path("app/exports/{$filename}");
        if (!is_dir(dirname($tempPath))) mkdir(dirname($tempPath), 0755, true);
        (new Xlsx($spreadsheet))->save($tempPath);
        return response()->download($tempPath, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240']]);
        $companyId = auth()->user()->company_id;
        $path = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray();
        if (count($rows) < 2) return back()->with('error', 'File tidak memiliki data.');

        $header = array_map(fn($h) => strtolower(trim((string) $h)), $rows[0]);
        array_shift($rows);

        $imported = 0; $errors = [];
        foreach ($rows as $idx => $row) {
            if (empty(array_filter($row))) continue;
            $data = array_combine($header, $row);
            $line = $idx + 2;

            $accountNumber = trim((string) ($data['kode langganan (account number)'] ?? ''));
            $employeeName = trim((string) ($data['penanggung jawab (nama karyawan)'] ?? ''));
            $catatan = trim((string) ($data['catatan'] ?? ''));

            if (!$accountNumber || !$catatan) {
                $errors[] = "Baris {$line}: Kode Langganan dan Catatan wajib diisi.";
                continue;
            }
            $ci = CustInternet::where('account_number', $accountNumber)
                ->whereHas('customer', fn($q) => $q->where('company_id', $companyId))
                ->first();
            if (!$ci) {
                $errors[] = "Baris {$line}: Kode Langganan '{$accountNumber}' tidak ditemukan di company ini.";
                continue;
            }
            $employee = null;
            if ($employeeName) {
                $employee = Employee::where('company_id', $companyId)->where('name', $employeeName)->first();
                if (!$employee) {
                    $errors[] = "Baris {$line}: Karyawan '{$employeeName}' tidak ditemukan di company ini.";
                    continue;
                }
            }

            Gangguan::create([
                'code' => 'TKT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'cust_internet_id' => $ci->id,
                'assigned_to_employee_id' => $employee?->id,
                'catatan' => $catatan,
                'status_pengerjaan' => SupportTicketPengerjaanStatus::OPEN->value,
                'status_verifikasi' => SupportTicketVerifikasiStatus::PENDING->value,
                'issue_dimulai_dari' => now(),
            ]);
            $imported++;
        }

        $msg = "Berhasil import {$imported} tiket.";
        if ($errors) $msg .= ' ' . count($errors) . ' baris gagal: ' . implode(' | ', array_slice($errors, 0, 5));
        return back()->with($errors ? 'warning' : 'success', $msg);
    }

    private function excelColumn(int $index): string
    {
        $letters = '';
        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26);
        }
        return $letters;
    }

    private function serialize(Gangguan $g): array
    {
        return [
            'id' => $g->id,
            'code' => $g->code,
            'cust_internet_id' => $g->cust_internet_id,
            'cust_internet_label' => $g->custInternet?->account_number . ' — ' . ($g->custInternet?->customer?->name ?? '-') . ' — ' . ($g->custInternet?->internetPackage?->name ?? '-'),
            'customer_name' => $g->custInternet?->customer?->name,
            'customer_code' => $g->custInternet?->customer?->code,
            'assigned_to_employee_id' => $g->assigned_to_employee_id,
            'assigned_to_name' => $g->assignedToEmployee?->name,
            'catatan' => $g->catatan,
            'status_pengerjaan' => $g->status_pengerjaan?->value,
            'status_pengerjaan_label' => $g->status_pengerjaan?->label(),
            'status_verifikasi' => $g->status_verifikasi?->value,
            'status_verifikasi_label' => $g->status_verifikasi?->label(),
            'file_bukti_issue_url' => $g->file_bukti_issue_url,
            'file_bukti_issue_diselesaikan_url' => $g->file_bukti_issue_diselesaikan_url,
            'alasan_verifikasi' => $g->alasan_verifikasi,
            'issue_dimulai_dari' => $g->issue_dimulai_dari?->toIso8601String(),
            'issue_diselesaikan_pada' => $g->issue_diselesaikan_pada?->toIso8601String(),
            'created_at' => $g->created_at?->toIso8601String(),
            'updated_at' => $g->updated_at?->toIso8601String(),
            'created_by_name' => $g->createdBy?->name,
            'updated_by_name' => $g->updatedBy?->name,
        ];
    }
}
