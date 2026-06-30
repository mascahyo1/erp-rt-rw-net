<?php

namespace App\Http\Controllers\Karyawan;

use App\Enums\FileAttachmentType;
use App\Enums\SupportTicketPengerjaanStatus;
use App\Enums\SupportTicketVerifikasiStatus;
use App\Http\Controllers\Controller;
use App\Models\CustInternet;
use App\Models\Employee;
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
     * Karyawan: lihat semua tiket di company, create on behalf of customer,
     * update (assigned_to, status_pengerjaan, multi-file bukti_issue_selesai),
     * resolve (tandai resolved + upload bukti).
     *
     * Attachments (file_bukti_issue & file_bukti_issue_diselesaikan) sekarang
     * multi-file via polymorphic {@see FileAttachment}. User bisa upload banyak
     * file sekaligus + kasih label (file_name) + caption (file_description).
     */
    public function index(Request $request): Response
    {
        $companyId = auth()->user()->company_id;

        $query = Gangguan::query()->with([
            'custInternet.customer',
            'custInternet.internetPackage',
            'pics.employee',
            'attachments',
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
        if ($assigned = $request->input('main_pic_employee_id')) {
            $query->whereHas('pics', fn($q) => $q->where('employee_id', $assigned));
        }

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
            'filters' => $request->only(['search', 'status_pengerjaan', 'status_verifikasi', 'main_pic_employee_id', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
            'statusPengerjaanOptions' => SupportTicketPengerjaanStatus::values(),
            'statusVerifikasiOptions' => SupportTicketVerifikasiStatus::values(),
            'attachmentTypeOptions' => collect(FileAttachmentType::cases())->map(fn($c) => ['value' => $c->value, 'label' => $c->label()])->values()->all(),
        ]);
    }

    /**
     * Form contract (multipart/form-data):
     *
     *   cust_internet_id                 : uuid (required)
     *   main_pic_employee_id             : uuid (nullable)
     *   additional_pic_employee_ids[]    : uuid[] (nullable)
     *   catatan                          : string (required, max 2000)
     *   issue_dimulai_dari               : date (required)
     *
     *   # Multi-file attachments (BuktiIssue), parallel arrays, index-aligned
     *   attachments_bukti_issue[]        : file[] (jpg,jpeg,png,webp,pdf|max:5120) (nullable)
     *   attachment_names[]               : string[] (parallel; user-facing label per file)
     *   attachment_descriptions[]        : string[] (parallel, nullable)
     */
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
            'attachments_bukti_issue.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'attachment_names' => ['nullable', 'array'],
            'attachment_names.*' => ['nullable', 'string', 'max:255'],
            'attachment_descriptions' => ['nullable', 'array'],
            'attachment_descriptions.*' => ['nullable', 'string', 'max:1000'],
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

        $gangguan = Gangguan::create($data);

        // Sync PICs
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

        return back()->with('success', 'Tiket gangguan berhasil dibuat.');
    }

    /**
     * Update flow:
     *   - semua field dari update() existing
     *   - attachments_to_keep[] (existing IDs yang tetap di-keep; others dihapus)
     *   - new uploads via attachments_bukti_issue_selesai[] + names/descriptions
     */
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
            'attachments_to_keep' => ['nullable', 'array'],
            'attachments_to_keep.*' => ['string', 'uuid'],
            'attachments_bukti_issue_selesai' => ['nullable', 'array'],
            'attachments_bukti_issue_selesai.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'attachment_names' => ['nullable', 'array'],
            'attachment_names.*' => ['nullable', 'string', 'max:255'],
            'attachment_descriptions' => ['nullable', 'array'],
            'attachment_descriptions.*' => ['nullable', 'string', 'max:1000'],
            // Existing BuktiIssue edits (kadang customer update tiket mereka)
            'attachments_bukti_issue' => ['nullable', 'array'],
            'attachments_bukti_issue.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
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

        $gangguan->update($data);

        // Sync attachments: hapus yg gak di-keep, attach yg baru
        if ($request->has('attachments_to_keep') || $request->hasFile('attachments_bukti_issue_selesai') || $request->hasFile('attachments_bukti_issue')) {
            $keepIds = $validated['attachments_to_keep'] ?? [];
            $this->syncAttachmentsByType($gangguan, FileAttachmentType::BuktiIssue, $keepIds, $validated);
            $this->syncAttachmentsByType($gangguan, FileAttachmentType::BuktiIssueSelesai, $keepIds, $validated);
        }

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
     * Mark as resolved.
     */
    public function resolve(Request $request, Gangguan $gangguan): RedirectResponse
    {
        $companyId = auth()->user()->company_id;
        $ownsTicket = $gangguan->custInternet?->customer?->company_id === $companyId;
        if (!$ownsTicket) return back()->with('error', 'Anda tidak berhak mengubah tiket ini.');

        $validated = $request->validate([
            'attachments_to_keep' => ['nullable', 'array'],
            'attachments_to_keep.*' => ['string', 'uuid'],
            'attachments_bukti_issue_selesai' => ['nullable', 'array'],
            'attachments_bukti_issue_selesai.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'attachment_names' => ['nullable', 'array'],
            'attachment_names.*' => ['nullable', 'string', 'max:255'],
            'attachment_descriptions' => ['nullable', 'array'],
            'attachment_descriptions.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $gangguan->update([
            'status_pengerjaan' => SupportTicketPengerjaanStatus::RESOLVED->value,
            'issue_diselesaikan_pada' => now(),
        ]);

        $this->syncAttachmentsByType($gangguan, FileAttachmentType::BuktiIssueSelesai, $validated['attachments_to_keep'] ?? [], $validated);

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

    /**
     * Hapus 1 attachment. Endpoint dipanggil dari Vue (button X di list).
     */
    public function destroyAttachment(Request $request, Gangguan $gangguan, FileAttachment $attachment): RedirectResponse
    {
        $companyId = auth()->user()->company_id;
        $ownsTicket = $gangguan->custInternet?->customer?->company_id === $companyId;
        if (!$ownsTicket) return back()->with('error', 'Anda tidak berhak menghapus attachment ini.');

        // Pastikan attachment milik gangguan ini (polymorphic safety)
        if ($attachment->attachable_type !== Gangguan::class || $attachment->attachable_id !== $gangguan->id) {
            return back()->with('error', 'Attachment bukan milik tiket ini.');
        }

        $attachment->deleteFromStorage();
        return back()->with('success', 'Attachment berhasil dihapus.');
    }

    /**
     * Attach new uploads dari form ke model dengan type tertentu.
     * Files parallel dengan attachment_names[] & attachment_descriptions[].
     */
    private function attachFromRequest(Gangguan $gangguan, array $validated, FileAttachmentType $type): void
    {
        $field = $type === FileAttachmentType::BuktiIssue ? 'attachments_bukti_issue' : 'attachments_bukti_issue_selesai';
        $files = $request = request()->file($field) ?? [];

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

    /**
     * Sync attachments by type: keep existing IDs, hapus yg lain, attach new uploads.
     */
    private function syncAttachmentsByType(Gangguan $gangguan, FileAttachmentType $type, array $keepIds, array $validated): void
    {
        // Hapus attachment type ini yg gak di-keep
        $gangguan->attachmentsByType($type)->get()->each(function ($att) use ($keepIds) {
            if (!in_array($att->id, $keepIds, true)) {
                $att->deleteFromStorage();
            }
        });

        // Attach new uploads (kalau ada)
        $fieldMap = [
            FileAttachmentType::BuktiIssue->value => 'attachments_bukti_issue',
            FileAttachmentType::BuktiIssueSelesai->value => 'attachments_bukti_issue_selesai',
        ];
        $field = $fieldMap[$type->value] ?? null;
        if (!$field) return;

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
            'cust_internet_label' => $g->custInternet?->account_number . ' — ' . ($g->custInternet?->customer?->name ?? '-') . ' — ' . ($g->custInternet?->internetPackage?->name ?? '-'),
            'customer_name' => $g->custInternet?->customer?->name,
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
            // Legacy compat (single file URL — first match)
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
