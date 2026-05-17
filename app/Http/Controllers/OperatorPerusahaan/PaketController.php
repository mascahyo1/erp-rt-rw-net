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
            'is_unlimited'=>$p->is_unlimited, 'is_active'=>$p->is_active, 'status'=>$p->is_active?'Aktif':'Nonaktif',
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
            'is_unlimited'=>['boolean'], 'description'=>['nullable','string'],
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
            'is_unlimited'=>['boolean'], 'description'=>['nullable','string'],
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
}
