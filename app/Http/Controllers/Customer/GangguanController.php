<?php

namespace App\Http\Controllers\Customer;

use App\Enums\SupportTicketPengerjaanStatus;
use App\Enums\SupportTicketVerifikasiStatus;
use App\Http\Controllers\Controller;
use App\Models\CustInternet;
use App\Models\Gangguan;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
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
     */
    public function index(Request $request): Response
    {
        $customerId = auth()->user()->id;

        $query = Gangguan::query()->with([
            'custInternet.customer',
            'custInternet.internetPackage',
            'assignedToEmployee',
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
            'catatan' => ['required', 'string', 'max:2000'],
            'file_bukti_issue' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
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
            'issue_dimulai_dari' => now(),
        ];

        $uploader = new FileUploadService();
        if ($request->hasFile('file_bukti_issue')) {
            $data['file_bukti_issue'] = $uploader->processImage($request->file('file_bukti_issue'), 'gangguan/issues');
        }

        Gangguan::create($data);

        return back()->with('success', 'Laporan gangguan berhasil dikirim. Tim kami akan segera menindaklanjuti.');
    }

    public function destroy(Gangguan $gangguan): RedirectResponse
    {
        // Customer hanya bisa delete tiket MILIKNYA sendiri, dan hanya yang masih OPEN
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

    private function serialize(Gangguan $g): array
    {
        return [
            'id' => $g->id,
            'code' => $g->code,
            'cust_internet_id' => $g->cust_internet_id,
            'cust_internet_label' => $g->custInternet?->account_number . ' — ' . ($g->custInternet?->internetPackage?->name ?? '-'),
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
        ];
    }
}
