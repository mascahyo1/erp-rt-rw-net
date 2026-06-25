<?php

namespace App\Http\Controllers\Karyawan;

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

class GangguanController extends Controller
{
    /**
     * Karyawan: lihat semua tiket di company, create on behalf of customer,
     * update (assigned_to, status_pengerjaan, file_bukti_issue_diselesaikan),
     * resolve (tandai resolved + upload bukti). TIDAK bisa verify (khusus admin perusahaan).
     */
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $query = Gangguan::query()->with([
            'custInternet.customer',
            'custInternet.internetPackage',
            'assignedToEmployee',
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
                  ->orWhereHas('custInternet.customer', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }
        if ($sp = $request->input('status_pengerjaan')) $query->where('status_pengerjaan', $sp);
        if ($sv = $request->input('status_verifikasi')) $query->where('status_verifikasi', $sv);
        if ($assigned = $request->input('assigned_to_employee_id')) $query->where('assigned_to_employee_id', $assigned);

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

        return Inertia::render('Karyawan/Gangguan', [
            'gangguans' => $items,
            'custInternets' => $custInternets,
            'employees' => $employees,
            'filters' => $request->only(['search', 'status_pengerjaan', 'status_verifikasi', 'assigned_to_employee_id', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
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
        if (!empty($validated['main_pic_employee_id'])) $data['assigned_to_employee_id'] = $validated['main_pic_employee_id'];

        $uploader = new FileUploadService();
        if ($request->hasFile('file_bukti_issue')) {
            $data['file_bukti_issue'] = $uploader->processImage($request->file('file_bukti_issue'), 'gangguan/issues');
        }

        $gangguan = Gangguan::create($data);

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
            'file_bukti_issue_diselesaikan' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ]);

        $data = [];
        if (array_key_exists('catatan', $validated)) $data['catatan'] = $validated['catatan'];
        if (array_key_exists('status_pengerjaan', $validated)) {
            $data['status_pengerjaan'] = $validated['status_pengerjaan'];
            if ($validated['status_pengerjaan'] === SupportTicketPengerjaanStatus::RESOLVED->value && !$gangguan->issue_diselesaikan_pada) {
                $data['issue_diselesaikan_pada'] = now();
            }
        }
        if (array_key_exists('issue_dimulai_dari', $validated) && $validated['issue_dimulai_dari'] !== null) {
            $data['issue_dimulai_dari'] = $validated['issue_dimulai_dari'];
        }
        if (array_key_exists('main_pic_employee_id', $validated) && $validated['main_pic_employee_id'] !== null) {
            $data['assigned_to_employee_id'] = $validated['main_pic_employee_id'];
        }

        $uploader = new FileUploadService();
        if ($request->hasFile('file_bukti_issue_diselesaikan')) {
            if ($gangguan->file_bukti_issue_diselesaikan) $uploader->deleteFile($gangguan->file_bukti_issue_diselesaikan);
            $data['file_bukti_issue_diselesaikan'] = $uploader->processImage($request->file('file_bukti_issue_diselesaikan'), 'gangguan/resolutions');
        }

        $gangguan->update($data);

        // Sync PICs kalau dikirim
        if (array_key_exists('main_pic_employee_id', $validated) || array_key_exists('additional_pic_employee_ids', $validated)) {
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
     * Mark as resolved: shortcut karyawan untuk tandai tiket selesai + upload bukti sekaligus.
     * (Opsional, sama dengan update tapi lebih simple).
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
            'assigned_to_employee_id' => $g->assigned_to_employee_id,
            'assigned_to_name' => $g->assignedToEmployee?->name,
            'main_pic' => $mainPic ? [
                'id' => $mainPic->id,
                'employee_id' => $mainPic->employee_id,
                'employee_name' => $mainPic->employee?->name,
            ] : null,
            'main_pic_name' => $mainPic?->employee?->name ?? $g->assignedToEmployee?->name,
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
        ];
    }
}
