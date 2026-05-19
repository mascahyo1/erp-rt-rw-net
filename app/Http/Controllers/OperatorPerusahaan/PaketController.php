<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\InternetPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PaketController extends Controller
{
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;
        $query = InternetPackage::query()->with(['createdBy','updatedBy'])->where('company_id', $companyId);

        if ($request->input('terhapus') === 'ya') $query->onlyTrashed();
        if ($search = $request->input('search')) {
            $query->where(fn($q) => $q->where('name','like',"%{$search}%")->orWhere('description','like',"%{$search}%"));
        }
        if ($status = $request->input('status')) $query->where('is_active', $status === 'Aktif' || $status === 'aktif');
        if ($sortField = $request->input('sort_field')) {
            $query->orderBy($sortField, $request->input('sort_dir','asc'));
        } else $query->latest();

        $items = $query->paginate(min((int)$request->input('per_page',10),100))->through(fn($p) => [
            'id'=>$p->id, 'name'=>$p->name, 'price'=>$p->price, 'speed_down_kbps'=>$p->speed_down_kbps,
            'speed_up_kbps'=>$p->speed_up_kbps, 'quota_gb'=>$p->quota_gb, 'billing_cycle'=>$p->billing_cycle,
            'is_unlimited'=>$p->is_unlimited, 'max_devices'=>$p->max_devices,
            'fup_quota_down'=>$p->fup_quota_down, 'fup_quota_up'=>$p->fup_quota_up,
            'fup_speed_down_kbps'=>$p->fup_speed_down_kbps, 'fup_speed_up_kbps'=>$p->fup_speed_up_kbps,
            'is_active'=>$p->is_active, 'status'=>$p->is_active?'Aktif':'Nonaktif',
            'description'=>$p->description, 'dihapus'=>$p->trashed(), 'deleted_at'=>$p->deleted_at?->format('Y-m-d H:i'),
            'created_at'=>$p->created_at->format('Y-m-d H:i'), 'created_by'=>$p->createdBy?->name,
        ]);

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/DaftarPaket', [
            'items'=>$items, 'filters'=>$request->only(['search','status','sort_field','sort_dir','per_page','terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'name'=>['required','string','max:255'], 'price'=>['required','numeric'],
            'speed_down_kbps'=>['required','numeric'], 'speed_up_kbps'=>['required','numeric'],
            'quota_gb'=>['required','integer'], 'billing_cycle'=>['required',Rule::in(['daily','weekly','monthly','yearly'])],
            'is_unlimited'=>['boolean'], 'max_devices'=>['nullable','integer'],
            'fup_quota_down'=>['nullable','integer'], 'fup_quota_up'=>['nullable','integer'],
            'fup_speed_down_kbps'=>['nullable','numeric'], 'fup_speed_up_kbps'=>['nullable','numeric'],
            'description'=>['nullable','string'],
        ]);
        InternetPackage::create($v + ['company_id'=>auth()->user()->company_id,'is_active'=>true]);
        return back()->with('success','Paket berhasil ditambahkan.');
    }

    public function update(Request $request, InternetPackage $internetPackage): RedirectResponse
    {
        $v = $request->validate([
            'name'=>['required','string','max:255'], 'price'=>['required','numeric'],
            'speed_down_kbps'=>['required','numeric'], 'speed_up_kbps'=>['required','numeric'],
            'quota_gb'=>['required','integer'], 'billing_cycle'=>['required',Rule::in(['daily','weekly','monthly','yearly'])],
            'is_unlimited'=>['boolean'], 'max_devices'=>['nullable','integer'],
            'fup_quota_down'=>['nullable','integer'], 'fup_quota_up'=>['nullable','integer'],
            'fup_speed_down_kbps'=>['nullable','numeric'], 'fup_speed_up_kbps'=>['nullable','numeric'],
            'description'=>['nullable','string'],
        ]);
        $internetPackage->update($v);
        return back()->with('success','Paket berhasil diperbarui.');
    }

    public function destroy(InternetPackage $internetPackage): RedirectResponse
    {
        $internetPackage->delete();
        return back()->with('success','Paket berhasil dihapus.');
    }
    public function restore(string $id): RedirectResponse
    {
        InternetPackage::withTrashed()->findOrFail($id)->restore();
        return back()->with('success','Paket berhasil dipulihkan.');
    }
    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids',[]);
        if (empty($ids)) return back()->with('error','Tidak ada paket yang dipilih.');
        $c = InternetPackage::whereIn('id',$ids)->delete();
        return back()->with('success',"{$c} paket berhasil dihapus.");
    }
    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids',[]); $s = $request->input('status');
        if (empty($ids) || !in_array($s,['Aktif','Nonaktif'])) return back()->with('error','Data tidak valid.');
        $c = InternetPackage::whereIn('id',$ids)->update(['is_active'=>$s==='Aktif']);
        return back()->with('success',"{$c} paket di".($s==='Aktif'?'aktifkan':'nonaktifkan').".");
    }
    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids',[]);
        if (empty($ids)) return back()->with('error','Tidak ada paket yang dipilih.');
        $c = InternetPackage::onlyTrashed()->whereIn('id',$ids)->restore();
        return back()->with('success',"{$c} paket berhasil dipulihkan.");
    }

    /**
     * Export Excel — semua data atau selected via ?ids=
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $companyId = auth()->user()->company_id;
        $query = InternetPackage::where('company_id', $companyId);

        if ($ids = $request->input('ids')) {
            $query->whereIn('id', explode(',', $ids));
        }

        $items = $query->orderBy('name')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Paket');

        $headers = ['Nama Paket', 'Harga', 'Billing Cycle', 'Speed Down (kbps)', 'Speed Up (kbps)', 'Kuota (GB)', 'Unlimited', 'Max Devices', 'FUP Quota Down', 'FUP Quota Up', 'FUP Speed Down', 'FUP Speed Up', 'Status', 'Deskripsi'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
        }

        $row = 2;
        foreach ($items as $item) {
            $col = 1;
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $item->name, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, (string) $item->price, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue($this->excelColumn($col++) . $row, $item->billing_cycle);
            $sheet->setCellValue($this->excelColumn($col++) . $row, $item->speed_down_kbps);
            $sheet->setCellValue($this->excelColumn($col++) . $row, $item->speed_up_kbps);
            $sheet->setCellValue($this->excelColumn($col++) . $row, $item->quota_gb);
            $sheet->setCellValue($this->excelColumn($col++) . $row, $item->is_unlimited ? 'Ya' : 'Tidak');
            $sheet->setCellValue($this->excelColumn($col++) . $row, $item->max_devices);
            $sheet->setCellValue($this->excelColumn($col++) . $row, $item->fup_quota_down);
            $sheet->setCellValue($this->excelColumn($col++) . $row, $item->fup_quota_up);
            $sheet->setCellValue($this->excelColumn($col++) . $row, $item->fup_speed_down_kbps);
            $sheet->setCellValue($this->excelColumn($col++) . $row, $item->fup_speed_up_kbps);
            $sheet->setCellValue($this->excelColumn($col++) . $row, $item->is_active ? 'Aktif' : 'Nonaktif');
            $sheet->setCellValueExplicit($this->excelColumn($col++) . $row, $item->description ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $row++;
        }

        // Auto-width
        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension($this->excelColumn($i))->setAutoSize(true);
        }

        $file = storage_path('app/temp/paket_' . now()->format('YmdHis') . '.xlsx');
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($file);

        return response()->download($file)->deleteFileAfterSend();
    }

    /**
     * Template import kosong
     */
    public function template(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import');

        $headers = ['Nama Paket', 'Harga', 'Billing Cycle', 'Speed Down (kbps)', 'Speed Up (kbps)', 'Kuota (GB)', 'Unlimited (Ya/Tidak)', 'Max Devices', 'FUP Quota Down', 'FUP Quota Up', 'FUP Speed Down', 'FUP Speed Up', 'Status (Aktif/Nonaktif)', 'Deskripsi'];
        foreach ($headers as $i => $h) {
            $col = $this->excelColumn($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getStyle("{$col}1")->getFont()->setBold(true);
        }

        // Contoh row
        $example = ['Basic 10Mbps', 150000, 'monthly', 10240, 5120, 100, 'Tidak', 5, 50, 25, 5120, 2560, 'Aktif', 'Paket basic'];
        foreach ($example as $i => $v) {
            $sheet->setCellValueExplicit($this->excelColumn($i + 1) . '2', (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimension($this->excelColumn($i))->setAutoSize(true);
        }

        $file = storage_path('app/temp/template_paket.xlsx');
        $dir = dirname($file);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($file);

        return response()->download($file)->deleteFileAfterSend();
    }

    /**
     * Import dari Excel
     */
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

        // Hapus header row
        array_shift($rows);

        $companyId = auth()->user()->company_id;
        $success = 0;
        $errors = [];

        $inserts = [];
        foreach ($rows as $i => $row) {
            $line = $i + 2; // line 1 header, line 2 example/start
            $name = trim($row[0] ?? '');
            if (empty($name)) continue;

            $price = (float) ($row[1] ?? 0);
            $billingCycle = strtolower(trim($row[2] ?? 'monthly'));
            $speedDown = (float) ($row[3] ?? 0);
            $speedUp = (float) ($row[4] ?? 0);
            $quotaGb = (int) ($row[5] ?? 0);
            $isUnlimited = strtolower(trim($row[6] ?? '')) === 'ya';
            $maxDevices = (int) ($row[7] ?? 0) ?: null;
            $status = strtolower(trim($row[12] ?? 'aktif')) === 'nonaktif' ? false : true;

            if (empty($name) || $price <= 0) {
                $errors[] = "Baris {$line}: Nama/Harga tidak valid.";
                continue;
            }
            if (!in_array($billingCycle, ['daily', 'weekly', 'monthly', 'yearly'])) {
                $billingCycle = 'monthly';
            }

            $inserts[] = [
                'id' => \Illuminate\Support\Str::uuid(),
                'company_id' => $companyId,
                'name' => $name,
                'price' => $price,
                'billing_cycle' => $billingCycle,
                'speed_down_kbps' => $speedDown,
                'speed_up_kbps' => $speedUp,
                'quota_gb' => $quotaGb,
                'is_unlimited' => $isUnlimited,
                'max_devices' => $maxDevices,
                'fup_quota_down' => $row[8] ? (int) $row[8] : null,
                'fup_quota_up' => $row[9] ? (int) $row[9] : null,
                'fup_speed_down_kbps' => $row[10] ? (float) $row[10] : null,
                'fup_speed_up_kbps' => $row[11] ? (float) $row[11] : null,
                'is_active' => $status,
                'description' => trim($row[13] ?? '') ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $success++;
        }

        // Batch insert chunk 500
        foreach (array_chunk($inserts, 500) as $chunk) {
            InternetPackage::insert($chunk);
        }

        $msg = "{$success} paket berhasil diimport.";
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
