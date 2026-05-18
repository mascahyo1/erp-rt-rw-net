<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Customer::query()->with(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy'])
            ->where('company_id', auth()->user()->company_id);

        if ($request->input('terhapus') === 'ya') {
            $query->onlyTrashed();
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('is_active', $status === 'Aktif' || $status === 'aktif');
        }

        if ($sortField = $request->input('sort_field')) {
            $sortDir = $request->input('sort_dir', 'asc');
            $allowedSorts = ['name', 'email', 'is_active', 'created_at', 'deleted_at'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDir);
            }
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->input('per_page', 10), 100);

        $customers = $query->paginate($perPage)->through(function ($customer) {
            $photoKtpUrl = $customer->photo_ktp
                ? Storage::disk('minio')->temporaryUrl($customer->photo_ktp, now()->addMinutes(10))
                : null;
            $photoKkUrl = $customer->photo_kk
                ? Storage::disk('minio')->temporaryUrl($customer->photo_kk, now()->addMinutes(10))
                : null;
            $photoProfileUrl = $customer->photo_profile
                ? Storage::disk('minio')->url($customer->photo_profile)
                : null;

            return [
                'id' => $customer->id,
                'nama' => $customer->name,
                'email' => $customer->email,
                'kode_negara' => $customer->phone_country_code,
                'no_telp' => $customer->phone_number,
                'no_nik' => $customer->no_nik,
                'no_kk' => $customer->no_kk,
                'photo_ktp' => $customer->photo_ktp,
                'photo_ktp_url' => $photoKtpUrl,
                'photo_kk' => $customer->photo_kk,
                'photo_kk_url' => $photoKkUrl,
                'photo_profile' => $customer->photo_profile,
                'photo_profile_url' => $photoProfileUrl,
                'alamat' => $customer->address,
                'status' => $customer->is_active ? 'Aktif' : 'Nonaktif',
                'is_active' => $customer->is_active,
                'company_id' => $customer->company_id,
                'dihapus' => $customer->trashed(),
                'deleted_at_raw' => $customer->deleted_at?->toISOString(),
                'deleted_at' => $customer->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $customer->created_at->format('Y-m-d H:i'),
                'updated_at' => $customer->updated_at->format('Y-m-d H:i'),
                'restored_at' => $customer->restored_at?->format('Y-m-d H:i'),
                'created_by' => $customer->createdBy?->name,
                'updated_by' => $customer->updatedBy?->name,
                'deleted_by' => $customer->deletedBy?->name,
                'restored_by' => $customer->restoredBy?->name,
            ];
        });

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/Customer', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('customers')->where('company_id', auth()->user()->company_id),
            ],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => ['required', 'string', 'max:20'],
            'no_nik' => ['nullable', 'string', 'max:50'],
            'no_kk' => ['nullable', 'string', 'max:50'],
            'photo_ktp' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_kk' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_profile' => ['nullable', 'file', 'image', 'max:2048'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $photoKtpPath = null;
        $photoKkPath = null;
        $photoProfilePath = null;

        if ($request->hasFile('photo_ktp')) {
            $file = $request->file('photo_ktp');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $photoKtpPath = $file->storeAs('customers/photos', $filename, ['disk' => 'minio', 'visibility' => 'private']);
        }
        if ($request->hasFile('photo_kk')) {
            $file = $request->file('photo_kk');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $photoKkPath = $file->storeAs('customers/photos', $filename, ['disk' => 'minio', 'visibility' => 'private']);
        }
        if ($request->hasFile('photo_profile')) {
            $file = $request->file('photo_profile');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $photoProfilePath = $file->storeAs('customers/photos', $filename, ['disk' => 'minio', 'visibility' => 'public']);
        }

        Customer::create([
            'company_id' => auth()->user()->company_id,
            'name' => $validated['nama'],
            'email' => $validated['email'],
            'phone_country_code' => $validated['kode_negara'],
            'phone_number' => $validated['no_telp'],
            'no_nik' => $validated['no_nik'] ?? null,
            'no_kk' => $validated['no_kk'] ?? null,
            'photo_ktp' => $photoKtpPath,
            'photo_kk' => $photoKkPath,
            'photo_profile' => $photoProfilePath,
            'address' => $validated['alamat'] ?? null,
            'is_active' => $validated['status'] === 'Aktif',
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('customers')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($customer->id),
            ],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => ['required', 'string', 'max:20'],
            'no_nik' => ['nullable', 'string', 'max:50'],
            'no_kk' => ['nullable', 'string', 'max:50'],
            'photo_ktp' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_kk' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_profile' => ['nullable', 'file', 'image', 'max:2048'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $data = [
            'name' => $validated['nama'],
            'email' => $validated['email'],
            'phone_country_code' => $validated['kode_negara'],
            'phone_number' => $validated['no_telp'],
            'no_nik' => $validated['no_nik'] ?? null,
            'no_kk' => $validated['no_kk'] ?? null,
            'address' => $validated['alamat'] ?? null,
            'is_active' => $validated['status'] === 'Aktif',
        ];

        if ($request->hasFile('photo_ktp')) {
            if ($customer->photo_ktp) {
                Storage::disk('minio')->delete($customer->photo_ktp);
            }
            $file = $request->file('photo_ktp');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $data['photo_ktp'] = $file->storeAs('customers/photos', $filename, ['disk' => 'minio', 'visibility' => 'private']);
        }

        if ($request->hasFile('photo_kk')) {
            if ($customer->photo_kk) {
                Storage::disk('minio')->delete($customer->photo_kk);
            }
            $file = $request->file('photo_kk');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $data['photo_kk'] = $file->storeAs('customers/photos', $filename, ['disk' => 'minio', 'visibility' => 'private']);
        }

        if ($request->hasFile('photo_profile')) {
            if ($customer->photo_profile) {
                Storage::disk('minio')->delete($customer->photo_profile);
            }
            $file = $request->file('photo_profile');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $data['photo_profile'] = $file->storeAs('customers/photos', $filename, ['disk' => 'minio', 'visibility' => 'public']);
        }

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $customer->update($data);

        return back()->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return back()->with('success', 'Pelanggan berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $pelanggan = Customer::withTrashed()->findOrFail($id);
        $pelanggan->restore();

        return back()->with('success', 'Pelanggan berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada pelanggan yang dipilih.');
        }

        $count = Customer::whereIn('id', $ids)->delete();

        return back()->with('success', "{$count} pelanggan berhasil dihapus.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');

        if (empty($ids) || !in_array($status, ['Aktif', 'Nonaktif'])) {
            return back()->with('error', 'Data tidak valid.');
        }

        $count = Customer::whereIn('id', $ids)->update([
            'is_active' => $status === 'Aktif',
        ]);

        $label = $status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "{$count} pelanggan berhasil {$label}.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada pelanggan yang dipilih.');
        $count = Customer::onlyTrashed()->whereIn('id', $ids)->restore();
        return back()->with('success', "{$count} pelanggan berhasil dipulihkan.");
    }
}
