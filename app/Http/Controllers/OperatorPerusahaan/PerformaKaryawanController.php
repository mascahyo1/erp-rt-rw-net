<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Enums\SupportTicketPengerjaanStatus;
use App\Enums\SupportTicketVerifikasiStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmpIncentiveLog;
use App\Models\Gangguan;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PerformaKaryawanController extends Controller
{
    /**
     * Laporan Performa Karyawan — gabungan data insentif + gangguan yang diselesaikan.
     * Filter: dari_tgl, sampai_tgl.
     * Per karyawan (Employee):
     *   - Kode Karyawan
     *   - Nama Karyawan
     *   - Jumlah Insentif: count of emp_incentive_logs.amount > 0 + review_status=approved + date BETWEEN
     *   - Nominal Insentif: SUM(amount) dari records yang sama
     *   - Gangguan solved (PJ Utama): count of support_ticket_pics.is_main_pic=true
     *     JOIN support_tickets.status_pengerjaan='resolved' AND status_verifikasi='approved'
     *     AND issue_dimulai_dari BETWEEN
     *   - Gangguan solved (PJ Lain): same but is_main_pic=false
     */
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;
        $dariTgl = $request->input('dari_tgl', now()->startOfMonth()->toDateString());
        $sampaiTgl = $request->input('sampai_tgl', now()->toDateString());

        $performa = $this->computePerforma($companyId, $dariTgl, $sampaiTgl);

        return Inertia::render('OperatorPerusahaan/PerformaKaryawan', [
            'performa' => $performa,
            'filters' => ['dari_tgl' => $dariTgl, 'sampai_tgl' => $sampaiTgl],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;
        $dariTgl = $request->input('dari_tgl', now()->startOfMonth()->toDateString());
        $sampaiTgl = $request->input('sampai_tgl', now()->toDateString());

        $performa = $this->computePerforma($companyId, $dariTgl, $sampaiTgl);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Performa Karyawan');

        $headers = ['Kode Karyawan', 'Nama Karyawan', 'Jumlah Insentif', 'Nominal Insentif', 'Gangguan solved (PJ Utama)', 'Gangguan solved (PJ Lain)', 'Total Solved'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $row = 2;
        foreach ($performa as $p) {
            $col = 1;
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['code'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['name'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['jumlah_insentif'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['nominal_insentif'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['gangguan_solved_pj_utama'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['gangguan_solved_pj_lain'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['gangguan_solved_pj_utama'] + $p['gangguan_solved_pj_lain'], DataType::TYPE_NUMERIC);
            $row++;
        }

        $filename = 'performa-karyawan-' . $dariTgl . '_to_' . $sampaiTgl . '.xlsx';
        $tempPath = storage_path("app/exports/{$filename}");
        if (!is_dir(dirname($tempPath))) mkdir(dirname($tempPath), 0755, true);
        (new Xlsx($spreadsheet))->save($tempPath);

        return response()->download($tempPath, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    /**
     * Compute performa aggregate per karyawan.
     */
    private function computePerforma(string $companyId, string $dariTgl, string $sampaiTgl): array
    {
        $employees = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        if ($employees->isEmpty()) return [];

        $empIds = $employees->pluck('id')->all();

        // Insentif aggregate per employee (subquery)
        $insentifAgg = EmpIncentiveLog::query()
            ->select('submitted_by_id', DB::raw('COUNT(*) AS cnt'), DB::raw('SUM(amount) AS total'))
            ->where('amount', '>', 0)
            ->where('review_status', 'approved')
            ->whereBetween('date', [$dariTgl, $sampaiTgl])
            ->where('submitted_by_type', Employee::class)
            ->whereIn('submitted_by_id', $empIds)
            ->groupBy('submitted_by_id')
            ->get()
            ->keyBy('submitted_by_id');

        // Gangguan aggregate per employee (subquery, via PIC pivot)
        $gangguanAggUtama = DB::table('support_ticket_pics AS pic')
            ->join('support_tickets AS g', 'g.id', '=', 'pic.support_ticket_id')
            ->select('pic.employee_id', DB::raw('COUNT(*) AS cnt'))
            ->where('pic.is_main_pic', true)
            ->where('g.status_pengerjaan', SupportTicketPengerjaanStatus::RESOLVED->value)
            ->where('g.status_verifikasi', SupportTicketVerifikasiStatus::APPROVED->value)
            ->whereBetween('g.issue_dimulai_dari', [$dariTgl, $sampaiTgl])
            ->whereIn('pic.employee_id', $empIds)
            ->groupBy('pic.employee_id')
            ->get()
            ->keyBy('employee_id');

        $gangguanAggLain = DB::table('support_ticket_pics AS pic')
            ->join('support_tickets AS g', 'g.id', '=', 'pic.support_ticket_id')
            ->select('pic.employee_id', DB::raw('COUNT(*) AS cnt'))
            ->where('pic.is_main_pic', false)
            ->where('g.status_pengerjaan', SupportTicketPengerjaanStatus::RESOLVED->value)
            ->where('g.status_verifikasi', SupportTicketVerifikasiStatus::APPROVED->value)
            ->whereBetween('g.issue_dimulai_dari', [$dariTgl, $sampaiTgl])
            ->whereIn('pic.employee_id', $empIds)
            ->groupBy('pic.employee_id')
            ->get()
            ->keyBy('employee_id');

        $result = [];
        foreach ($employees as $emp) {
            $ins = $insentifAgg->get($emp->id);
            $gUtama = $gangguanAggUtama->get($emp->id);
            $gLain = $gangguanAggLain->get($emp->id);
            $result[] = [
                'id' => $emp->id,
                'code' => $emp->code,
                'name' => $emp->name,
                'jumlah_insentif' => (int) ($ins->cnt ?? 0),
                'nominal_insentif' => (float) ($ins->total ?? 0),
                'gangguan_solved_pj_utama' => (int) ($gUtama->cnt ?? 0),
                'gangguan_solved_pj_lain' => (int) ($gLain->cnt ?? 0),
            ];
        }
        return $result;
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
}
