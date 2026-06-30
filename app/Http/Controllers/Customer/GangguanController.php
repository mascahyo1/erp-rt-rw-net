<?php

namespace App\Http\Controllers\Customer;

use App\Enums\FileAttachmentType;
use App\Enums\SupportTicketPengerjaanStatus;
use App\Enums\SupportTicketVerifikasiStatus;
use App\Http\Controllers\Controller;
use App\Models\CustInternet;
use App\Models\FileAttachment;
use App\Models\Gangguan;
use App\Models\SupportTicketPic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GangguanController extends Controller
{
    /**
     * Customer hanya bisa lihat & buat tiket untuk cust_internet MILIKNYA sendiri.
     * Read-only setelah create — tidak bisa update/delete (kecuali karyawan/perusahaan).
     *
     * Attachments (BuktiIssue) sekarang multi-file via polymorphic {@see FileAttachment}.
     */
    public function index(Request $request): Response
    {
        $customerId = auth()->user()->id;

        $query = Gangguan::query()->with([
            'custInternet.customer',
            'custInternet.internetPackage',
            'pics.employee',
            'attachments',
            'createdBy',
            'updatedBy',
        ])->whereHas('custInternet', fn($q) => $q->where('customer_id', $customerId));

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('catatan', 'like', "%{$search}%");
            });
        }
        if ($statusPengerjaan = $request->input('status_pengerjaan')) {
            $query->where('status_pengerjaan', $statusPengerjaan);
        }
        if ($statusVerifikasi = $request->input('status_verifikasi')) {
            $query->where('status_verifikasi', $statusVerifikasi);
        }
        if ($terhapus = $request->input('terhapus')) {
            $terhapus === 'ya' ? $query->onlyTrashed() : $query;
        }

        $allowedSorts = ['code', 'status_pengerjaan', 'status_verifikasi', 'created_at'];
        $sortField = $request->input('sort_field', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        $query->orderBy($sortField, $sortDir);

        $perPage = min((int) $request->input('per_page', 10), 100);
        $items = $query->paginate($perPage)->through(fn($item) => $this->serialize($item));

        // List cust_internet milik customer untuk dropdown "Kode Langganan" di form
        $custInternets = CustInternet::with('internetPackage')
            ->where('customer_id', $customerId)
            ->where('internet_status', 'active')
            ->get()
            ->map(fn($ci) => [
                'id' => $ci->id,
                'account_number' => $ci->account_number,
                'package_name' => $ci->internetPackage?->name,
                'display' => $ci->account_number . ' — ' . ($ci->internetPackage?->name ?? 'Tanpa Paket'),
            ]);

        return Inertia::render('Customer/Gangguan', [
            'gangguans' => $items,
            'custInternets' => $custInternets,
            'filters' => $request->only(['search', 'status_pengerjaan', 'status_verifikasi', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
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
            'attachments_bukti_issue' => ['nullable', 'array'],
            'attachments_bukti_issue.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'attachment_names' => ['nullable', 'array'],
            'attachment_names.*' => ['nullable', 'string', 'max:255'],
            'attachment_descriptions' => ['nullable', 'array'],
            'attachment_descriptions.*' => ['nullable', 'string', 'max:1000'],
        ]);

        // Guard: cust_internet harus milik customer yang login
        $customerId = auth()->user()->id;
        $ci = CustInternet::where('customer_id', $customerId)
            ->where('id', $validated['cust_internet_id'])
            ->firstOrFail();

        $data = [
            'code' => 'TKT-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
            'cust_internet_id' => $ci->id,
            'catatan' => $validated['catatan'],
            'status_pengerjaan' => SupportTicketPengerjaanStatus::OPEN->value,
            'status_verifikasi' => SupportTicketVerifikasiStatus::PENDING->value,
            'issue_dimulai_dari' => $validated['issue_dimulai_dari'],
        ];

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

        $this->attachFromRequest($gangguan, $validated, FileAttachmentType::BuktiIssue);

        return back()->with('success', 'Laporan gangguan berhasil dikirim. Tim kami akan segera menindaklanjuti.');
    }

    public function destroy(Gangguan $gangguan): RedirectResponse
    {
        $customerId = auth()->user()->id;
        $ownsTicket = $gangguan->custInternet?->customer_id === $customerId;
        if (!$ownsTicket) {
            return back()->with('error', 'Anda tidak berhak menghapus tiket ini.');
        }
        if ($gangguan->status_pengerjaan !== SupportTicketPengerjaanStatus::OPEN) {
            return back()->with('error', 'Tiket yang sudah diproses tidak dapat dihapus.');
        }
        $gangguan->delete();
        return back()->with('success', 'Laporan gangguan berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $gangguan = Gangguan::withTrashed()->findOrFail($id);
        $customerId = auth()->user()->id;
        if ($gangguan->custInternet?->customer_id !== $customerId) {
            return back()->with('error', 'Anda tidak berhak memulihkan tiket ini.');
        }
        $gangguan->restore();
        return back()->with('success', 'Laporan gangguan berhasil dipulihkan.');
    }

    /**
     * Hapus 1 attachment (untuk customer yang punya akses ke tiketnya).
     */
    public function destroyAttachment(Request $request, Gangguan $gangguan, FileAttachment $attachment): RedirectResponse
    {
        $customerId = auth()->user()->id;
        $ownsTicket = $gangguan->custInternet?->customer_id === $customerId;
        if (!$ownsTicket) {
            return back()->with('error', 'Anda tidak berhak menghapus attachment ini.');
        }
        if ($attachment->attachable_type !== Gangguan::class || $attachment->attachable_id !== $gangguan->id) {
            return back()->with('error', 'Attachment bukan milik tiket ini.');
        }

        $attachment->deleteFromStorage();
        return back()->with('success', 'Attachment berhasil dihapus.');
    }

    /**
     * Attach parallel arrays: files + names + descriptions.
     */
    private function attachFromRequest(Gangguan $gangguan, array $validated, FileAttachmentType $type): void
    {
        $field = $type === FileAttachmentType::BuktiIssue ? 'attachments_bukti_issue' : 'attachments_bukti_issue_selesai';
        $files = request()->file($field) ?? [];
        if (!$files) return;

        $names = $validated['attachment_names'] ?? [];
        $descs = $validated['attachment_descriptions'] ?? [];

        foreach ($files as $i => $file) {
            $gangguan->attachFile(
                file: $file,
                type: $type,
                fileName: $names[$i] ?? null,
                fileDescription: $descs[$i] ?? null,
            );
        }
    }

    private function serialize(Gangguan $g): array
    {
        $pics = $g->pics ?? collect();
        $mainPic = $pics->where('is_main_pic', true)->first();
        $additionalPics = $pics->where('is_main_pic', false)->values();

        $attachments = $g->attachments ?? collect();
        $attachmentsByType = [];
        foreach ($attachments as $att) {
            $type = $att->type?->value ?? 'unknown';
            $attachmentsByType[$type][] = [
                'id' => $att->id,
                'type' => $type,
                'file_name' => $att->file_name,
                'file_description' => $att->file_description,
                'url' => $att->url,
                'created_at' => $att->created_at?->toIso8601String(),
            ];
        }

        return [
            'id' => $g->id,
            'code' => $g->code,
            'cust_internet_id' => $g->cust_internet_id,
            'cust_internet_label' => $g->custInternet?->account_number . ' — ' . ($g->custInternet?->internetPackage?->name ?? '-'),
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
            'attachments' => $attachmentsByType,
            'attachment_count' => $attachments->count(),
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
