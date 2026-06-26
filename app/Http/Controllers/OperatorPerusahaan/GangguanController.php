<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Enums\SupportTicketPengerjaanStatus;
use App\Enums\SupportTicketVerifikasiStatus;
use App\Http\Controllers\Controller;
use App\Models\CustInternet;
use App\Models\Employee;
use App\Models\Gangguan;
use App\Models\SupportTicketPic;
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
            'pics.employee',
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
        if ($assigned = $request->input('main_pic_employee_id')) {
            $query->whereHas('pics', fn($q) => $q->where('employee_id', $assigned));
        }
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
            'filters' => $request->only(['search', 'status_pengerjaan', 'status_verifikasi', 'main_pic_employee_id', 'cust_internet_id', 'created_start', 'created_end', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
            'statusPengerjaanOptions' => SupportTicketPengerjaanStatus::values(),
            'statusVerifikasiOptions' => SupportTicketVerifikasiStatus::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cust_internet_id' => ['required', 'uuid', 'exists:cust_internets,id'],
            'main_pic_employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'additional_pic_employee_ids' => ['nullable', 'array'],
            'additional_pic_employee_ids.*' => ['uuid', 'exists:employees,id', 'different:main_pic_employee_id'],
            'catatan' => ['required', 'string', 'max:2000'],
            'issue_dimulai_dari' => ['required', 'date'],
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
            'issue_dimulai_dari' => $validated['issue_dimulai_dari'],
        ];

        $uploader = new FileUploadService();
        if ($request->hasFile('file_bukti_issue')) {
            $data['file_bukti_issue'] = $uploader->processImage($request->file('file_bukti_issue'), 'gangguan/issues');
        }

        $gangguan = Gangguan::create($data);

        // Create PICs
        if (!empty($validated['main_pic_employee_id'])) {
            SupportTicketPic::create([
                'support_ticket_id' => $gangguan->id,
                'employee_id' => $validated['main_pic_employee_id'],
                'is_main_pic' => true,
            ]);
        }
        foreach ($validated['additional_pic_employee_ids'] ?? [] as $empId) {
            SupportTicketPic::create([
                'support_ticket_id' => $gangguan->id,
                'employee_id' => $empId,
                'is_main_pic' => false,
            ]);
        }

        return back()->with('success', 'Tiket gangguan berhasil dibuat.');
    }

    public function update(Request $request, Gangguan $gangguan): RedirectResponse
    {
        $companyId = auth()->user()->company_id;
        $ownsTicket = $gangguan->custInternet?->customer?->company_id === $companyId;
        if (!$ownsTicket) return back()->with('error', 'Anda tidak berhak mengubah tiket ini.');

        $validated = $request->validate([
            'main_pic_employee_id' => ['nullable', 'uuid', 'exists:employees,id'],
            'additional_pic_employee_ids' => ['nullable', 'array'],
            'additional_pic_employee_ids.*' => ['uuid', 'exists:employees,id', 'different:main_pic_employee_id'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'status_pengerjaan' => ['nullable', 'string', 'in:open,in_progress,resolved'],
            'issue_dimulai_dari' => ['nullable', 'date'],
            'issue_diselesaikan_pada' => ['nullable', 'date', 'required_if:status_pengerjaan,resolved'],
            'file_bukti_issue' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'file_bukti_issue_diselesaikan' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'alasan_verifikasi' => ['nullable', 'string', 'max:1000'],
            'status_verifikasi' => ['nullable', 'string', 'in:pending,approved,rejected'],
        ]);

        $data = [];
        foreach (['catatan', 'status_pengerjaan', 'alasan_verifikasi', 'status_verifikasi', 'issue_dimulai_dari', 'issue_diselesaikan_pada'] as $k) {
            if (array_key_exists($k, $validated) && $validated[$k] !== null) $data[$k] = $validated[$k];
        }
        // Kalau status_pengerjaan=resolved tapi issue_diselesaikan_pada belum di-set, auto-fill dengan now()
        if (!empty($data['status_pengerjaan']) && $data['status_pengerjaan'] === SupportTicketPengerjaanStatus::RESOLVED->value && empty($data['issue_diselesaikan_pada']) && !$gangguan->issue_diselesaikan_pada) {
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

        // Sync PICs kalau dikirim
        if (array_key_exists('main_pic_employee_id', $validated) || array_key_exists('additional_pic_employee_ids', $validated)) {
            // Hapus semua PIC existing, recreate (sync strategy)
            $gangguan->pics()->forceDelete();
            if (!empty($validated['main_pic_employee_id'])) {
                SupportTicketPic::create([
                    'support_ticket_id' => $gangguan->id,
                    'employee_id' => $validated['main_pic_employee_id'],
                    'is_main_pic' => true,
                ]);
            }
            foreach ($validated['additional_pic_employee_ids'] ?? [] as $empId) {
                SupportTicketPic::create([
                    'support_ticket_id' => $gangguan->id,
                    'employee_id' => $empId,
                    'is_main_pic' => false,
                ]);
            }
        }

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
     * Bulk delete: soft delete banyak tiket sekaligus.
     * Filter hanya tiket milik company caller (security).
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return back()->with('error', 'Pilih minimal 1 tiket.');
        }

        $companyId = auth()->user()->company_id;
        $count = Gangguan::whereIn('id', $ids)
            ->whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))
            ->delete();
        return back()->with('success', "{$count} tiket berhasil dihapus.");
    }

    /**
     * Bulk restore: pulihkan banyak tiket yang sudah di-soft-delete.
     */
    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || count($ids) === 0) {
            return back()->with('error', 'Pilih minimal 1 tiket.');
        }

        $companyId = auth()->user()->company_id;
        $count = Gangguan::onlyTrashed()
            ->whereIn('id', $ids)
            ->whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId))
            ->restore();
        return back()->with('success', "{$count} tiket berhasil dipulihkan.");
    }

    /**
     * Bulk verify: setujui/tolak banyak tiket sekaligus.
     * Hanya tiket yang status_pengerjaan=resolved yang bisa di-verify.
     * Perusahaan only (karyawan gak boleh bulk verify — itu wewenang admin).
     */
    public function bulkVerify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'status_verifikasi' => ['required', 'string', 'in:approved,rejected'],
            'alasan_verifikasi' => ['required', 'string', 'max:1000'],
        ]);

        $companyId = auth()->user()->company_id;
        $resolved = Gangguan::whereIn('id', $validated['ids'])
            ->where('status_pengerjaan', SupportTicketPengerjaanStatus::RESOLVED->value)
            ->whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId));

        $idsToUpdate = $resolved->pluck('id')->all();
        if (count($idsToUpdate) === 0) {
            return back()->with('error', 'Tidak ada tiket berstatus resolved yang bisa di-verify.');
        }

        Gangguan::whereIn('id', $idsToUpdate)->update([
            'status_verifikasi' => $validated['status_verifikasi'],
            'alasan_verifikasi' => $validated['alasan_verifikasi'],
        ]);

        $skipped = count($validated['ids']) - count($idsToUpdate);
        $label = $validated['status_verifikasi'] === 'approved' ? 'disetujui' : 'ditolak';
        $msg = count($idsToUpdate) . " tiket berhasil {$label}.";
        if ($skipped > 0) $msg .= " {$skipped} tiket di-skip (bukan status resolved).";
        return back()->with('success', $msg);
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
            ->with(['custInternet.customer', 'custInternet.internetPackage', 'pics.employee', 'createdBy'])
            ->whereHas('custInternet.customer', fn($q) => $q->where('company_id', $companyId));

        if ($request->input('terhapus') === 'ya') $query->onlyTrashed();
        else $query->whereNull('deleted_at');

        if ($sp = $request->input('status_pengerjaan')) $query->where('status_pengerjaan', $sp);
        if ($sv = $request->input('status_verifikasi')) $query->where('status_verifikasi', $sv);

        $items = $query->orderBy('created_at', 'desc')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Gangguan');

        $headers = ['No', 'Kode', 'Kode Langganan', 'Customer', 'PIC Utama', 'PIC Tambahan', 'Catatan', 'Status Pengerjaan', 'Status Verifikasi', 'Alasan Verifikasi', 'Tgl Mulai', 'Tgl Selesai'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $row = 2;
        $no = 0;
        // Kolom yang di-merge per tiket: No, Kode, Kode Langganan, Customer, PIC Utama, Catatan, Status Pengerjaan, Status Verifikasi, Alasan Verifikasi, Tgl Mulai, Tgl Selesai
        // Hanya PIC Tambahan (col F) yang TIDAK di-merge — 1 PIC per row
        $mergeCols = ['A', 'B', 'C', 'D', 'E', 'G', 'H', 'I', 'J', 'K', 'L'];

        foreach ($items as $g) {
            $no++;
            $mainName = $g->main_pic_name ?? '-';
            $additionalPics = ($g->additional_pics ?? collect())->map(fn($p) => $p->employee?->name ?? '-')->values()->all();
            // Total rows = 1 (main PIC) + N (additional PICs). Minimum 1 row kalau tidak ada PIC sama sekali.
            $totalRows = max(1, 1 + count($additionalPics));
            $firstRow = $row;
            $lastRow = $row + $totalRows - 1;

            // Kolom yang di-merge: set di firstRow saja (value muncul di top cell merged)
            $sheet->setCellValueExplicit("A{$firstRow}", $no, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("B{$firstRow}", $g->code, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$firstRow}", $g->custInternet?->account_number ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$firstRow}", $g->custInternet?->customer?->name ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$firstRow}", $mainName, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$firstRow}", $g->catatan, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$firstRow}", $g->status_pengerjaan?->label() ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("I{$firstRow}", $g->status_verifikasi?->label() ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("J{$firstRow}", $g->alasan_verifikasi ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("K{$firstRow}", $g->issue_dimulai_dari?->format('Y-m-d H:i') ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("L{$firstRow}", $g->issue_diselesaikan_pada?->format('Y-m-d H:i') ?? '-', DataType::TYPE_STRING);

            // Kolom F (PIC Tambahan): 1 row per additional PIC, atau '-' di firstRow
            $picTambahan = empty($additionalPics) ? '-' : $additionalPics[0];
            $sheet->setCellValueExplicit("F{$firstRow}", $picTambahan, DataType::TYPE_STRING);
            for ($i = 1; $i < $totalRows; $i++) {
                $sheet->setCellValueExplicit("F" . ($firstRow + $i), $additionalPics[$i] ?? '-', DataType::TYPE_STRING);
            }

            // Merge kolom yang harus di-merge
            if ($lastRow > $firstRow) {
                foreach ($mergeCols as $mc) {
                    $sheet->mergeCells("{$mc}{$firstRow}:{$mc}{$lastRow}");
                }
            }
            $row = $lastRow + 1;
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
        $pics = $g->pics ?? collect();
        $mainPic = $pics->where('is_main_pic', true)->first();
        $additionalPics = $pics->where('is_main_pic', false)->values();

        return [
            'id' => $g->id,
            'code' => $g->code,
            'cust_internet_id' => $g->cust_internet_id,
            'cust_internet_label' => $g->custInternet?->account_number . ' — ' . ($g->custInternet?->customer?->name ?? '-') . ' — ' . ($g->custInternet?->internetPackage?->name ?? '-'),
            'customer_name' => $g->custInternet?->customer?->name,
            'customer_code' => $g->custInternet?->customer?->code,
            'main_pic' => $mainPic ? [
                'id' => $mainPic->id,
                'employee_id' => $mainPic->employee_id,
                'employee_name' => $mainPic->employee?->name,
            ] : null,
            'main_pic_name' => $mainPic?->employee?->name,
            'additional_pics' => $additionalPics->map(fn($p) => [
                'id' => $p->id,
                'employee_id' => $p->employee_id,
                'employee_name' => $p->employee?->name,
            ])->all(),
            'pics_count' => $pics->count(),
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
