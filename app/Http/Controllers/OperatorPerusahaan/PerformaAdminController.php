<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\AdminCompany;
use App\Models\EmpIncentiveLog;
use App\Models\Gangguan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PerformaAdminController extends Controller
{
    /**
     * Laporan Performa Admin — produktivitas admin perusahaan.
     * Per admin (AdminCompany):
     *   - Insentif disetujui: count + sum of emp_incentive_logs WHERE reviewed_by = this admin AND review_status='approved' AND date BETWEEN
     *   - Insentif ditolak:   count + sum of emp_incentive_logs WHERE reviewed_by = this admin AND review_status='rejected' AND date BETWEEN
     *   - Tiket disetujui:    count of support_tickets WHERE status_verifikasi='approved' AND issue_dimulai_dari BETWEEN
     *   - Tiket ditolak:      count of support_tickets WHERE status_verifikasi='rejected' AND issue_dimulai_dari BETWEEN
     *
     * Filter: dari_tgl, sampai_tgl.
     */
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;
        $dariTgl = $request->input('dari_tgl', now()->startOfMonth()->toDateString());
        $sampaiTgl = $request->input('sampai_tgl', now()->toDateString());

        $performa = $this->computePerforma($companyId, $dariTgl, $sampaiTgl);

        return Inertia::render('OperatorPerusahaan/PerformaAdmin', [
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
        $sheet->setTitle('Performa Admin');

        $headers = [
            'Nama Admin', 'Email',
            'Insentif Disetujui (Jumlah)', 'Insentif Disetujui (Nominal)',
            'Insentif Ditolak (Jumlah)', 'Insentif Ditolak (Nominal)',
            'Tiket Disetujui', 'Tiket Ditolak',
        ];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $row = 2;
        foreach ($performa as $p) {
            $col = 1;
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['name'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['email'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['insentif_disetujui_count'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['insentif_disetujui_total'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['insentif_ditolak_count'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['insentif_ditolak_total'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['tiket_disetujui'], DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $p['tiket_ditolak'], DataType::TYPE_NUMERIC);
            $row++;
        }

        $filename = 'performa-admin-' . $dariTgl . '_to_' . $sampaiTgl . '.xlsx';
        $tempPath = storage_path("app/exports/{$filename}");
        if (!is_dir(dirname($tempPath))) mkdir(dirname($tempPath), 0755, true);
        (new Xlsx($spreadsheet))->save($tempPath);

        return response()->download($tempPath, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    private function computePerforma(string $companyId, string $dariTgl, string $sampaiTgl): array
    {
        $admins = AdminCompany::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        if ($admins->isEmpty()) return [];

        $adminIds = $admins->pluck('id')->all();

        // Insentif disetujui (reviewed_by = this admin)
        $insentifSetuju = DB::table('emp_incentive_logs')
            ->select('reviewed_by_id', DB::raw('COUNT(*) AS cnt'), DB::raw('SUM(amount) AS total'))
            ->where('review_status', 'approved')
            ->where('amount', '>', 0)
            ->where('reviewed_by_type', AdminCompany::class)
            ->whereIn('reviewed_by_id', $adminIds)
            ->whereBetween('date', [$dariTgl, $sampaiTgl])
            ->groupBy('reviewed_by_id')
            ->get()
            ->keyBy('reviewed_by_id');

        // Insentif ditolak (reviewed_by = this admin)
        $insentifTolak = DB::table('emp_incentive_logs')
            ->select('reviewed_by_id', DB::raw('COUNT(*) AS cnt'), DB::raw('SUM(amount) AS total'))
            ->where('review_status', 'rejected')
            ->where('reviewed_by_type', AdminCompany::class)
            ->whereIn('reviewed_by_id', $adminIds)
            ->whereBetween('date', [$dariTgl, $sampaiTgl])
            ->groupBy('reviewed_by_id')
            ->get()
            ->keyBy('reviewed_by_id');

        // Tiket disetujui (status_verifikasi='approved', filter by issue_dimulai_dari)
        // Tidak ada kolom reviewed_by spesifik di support_tickets, jadi count per admin via review log
        // Asumsi: tiket dibuat oleh siapa (created_by), itu yang 'handle' tiket
        // (karena tidak ada field reviewed_by spesifik di tabel support_tickets)
        $tiketSetuju = DB::table('support_tickets')
            ->select('created_by_id', DB::raw('COUNT(*) AS cnt'))
            ->where('status_verifikasi', 'approved')
            ->where('created_by_type', AdminCompany::class)
            ->whereIn('created_by_id', $adminIds)
            ->whereBetween('issue_dimulai_dari', [$dariTgl, $sampaiTgl])
            ->groupBy('created_by_id')
            ->get()
            ->keyBy('created_by_id');

        $tiketTolak = DB::table('support_tickets')
            ->select('created_by_id', DB::raw('COUNT(*) AS cnt'))
            ->where('status_verifikasi', 'rejected')
            ->where('created_by_type', AdminCompany::class)
            ->whereIn('created_by_id', $adminIds)
            ->whereBetween('issue_dimulai_dari', [$dariTgl, $sampaiTgl])
            ->groupBy('created_by_id')
            ->get()
            ->keyBy('created_by_id');

        $result = [];
        foreach ($admins as $a) {
            $result[] = [
                'id' => $a->id,
                'name' => $a->name,
                'email' => $a->email,
                'insentif_disetujui_count' => (int) ($insentifSetuju[$a->id]->cnt ?? 0),
                'insentif_disetujui_total' => (float) ($insentifSetuju[$a->id]->total ?? 0),
                'insentif_ditolak_count' => (int) ($insentifTolak[$a->id]->cnt ?? 0),
                'insentif_ditolak_total' => (float) ($insentifTolak[$a->id]->total ?? 0),
                'tiket_disetujui' => (int) ($tiketSetuju[$a->id]->cnt ?? 0),
                'tiket_ditolak' => (int) ($tiketTolak[$a->id]->cnt ?? 0),
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
