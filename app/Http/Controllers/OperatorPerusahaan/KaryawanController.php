<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KaryawanController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Employee::query()->with(['createdBy', 'updatedBy', 'deletedBy', 'restoredBy'])
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

        $karyawans = $query->paginate($perPage)->through(function ($employee) {
            $photoKtpUrl = $employee->photo_ktp
                ? route('file.proxy', ['path' => $employee->photo_ktp, 'disk' => 's3'])
                : null;
            $photoKkUrl = $employee->photo_kk
                ? route('file.proxy', ['path' => $employee->photo_kk, 'disk' => 's3'])
                : null;
            $photoProfileUrl = $employee->photo_profile
                ? route('file.proxy', ['path' => $employee->photo_profile, 'disk' => 's3'])
                : null;

            return [
                'id' => $employee->id,
                'nama' => $employee->name,
                'email' => $employee->email,
                'kode_negara' => $employee->phone_country_code,
                'no_telp' => $employee->phone_number,
                'no_nik' => $employee->no_nik,
                'no_kk' => $employee->no_kk,
                'photo_ktp' => $employee->photo_ktp,
                'photo_ktp_url' => $photoKtpUrl,
                'photo_kk' => $employee->photo_kk,
                'photo_kk_url' => $photoKkUrl,
                'photo_profile' => $employee->photo_profile,
                'photo_profile_url' => $photoProfileUrl,
                'status' => $employee->is_active ? 'Aktif' : 'Nonaktif',
                'is_active' => $employee->is_active,
                'company_id' => $employee->company_id,
                'dihapus' => $employee->trashed(),
                'deleted_at_raw' => $employee->deleted_at?->toISOString(),
                'deleted_at' => $employee->deleted_at?->format('Y-m-d H:i'),
                'created_at' => $employee->created_at->format('Y-m-d H:i'),
                'updated_at' => $employee->updated_at->format('Y-m-d H:i'),
                'restored_at' => $employee->restored_at?->format('Y-m-d H:i'),
                'created_by' => $employee->createdBy?->name,
                'updated_by' => $employee->updatedBy?->name,
                'deleted_by' => $employee->deletedBy?->name,
                'restored_by' => $employee->restoredBy?->name,
            ];
        });

        return Inertia::render($request->route()->defaults['view'] ?? 'OperatorPerusahaan/Karyawan', [
            'karyawans' => $karyawans,
            'filters' => $request->only(['search', 'status', 'sort_field', 'sort_dir', 'per_page', 'terhapus']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('employees')->where('company_id', auth()->user()->company_id),
            ],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => ['required', 'string', 'max:20'],
            'no_nik' => ['nullable', 'string', 'max:50'],
            'no_kk' => ['nullable', 'string', 'max:50'],
            'photo_ktp' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_kk' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_profile' => ['nullable', 'file', 'image', 'max:2048'],
            'status' => ['required', 'in:Aktif,Nonaktif'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $photoKtpPath = null;
        $photoKkPath = null;
        $photoProfilePath = null;

        if ($request->hasFile('photo_ktp')) {
            $file = $request->file('photo_ktp');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $photoKtpPath = $file->storeAs('employees/photos', $filename, ['disk' => 's3', 'visibility' => 'private']);
        }
        if ($request->hasFile('photo_kk')) {
            $file = $request->file('photo_kk');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $photoKkPath = $file->storeAs('employees/photos', $filename, ['disk' => 's3', 'visibility' => 'private']);
        }
        if ($request->hasFile('photo_profile')) {
            $file = $request->file('photo_profile');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $photoProfilePath = $file->storeAs('employees/photos', $filename, ['disk' => 's3', 'visibility' => 'public']);
        }

        Employee::create([
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
            'is_active' => $validated['status'] === 'Aktif',
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('employees')
                    ->where('company_id', auth()->user()->company_id)
                    ->ignore($employee->id),
            ],
            'kode_negara' => ['required', 'string', 'max:10'],
            'no_telp' => ['required', 'string', 'max:20'],
            'no_nik' => ['nullable', 'string', 'max:50'],
            'no_kk' => ['nullable', 'string', 'max:50'],
            'photo_ktp' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_kk' => ['nullable', 'file', 'image', 'max:2048'],
            'photo_profile' => ['nullable', 'file', 'image', 'max:2048'],
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
            'is_active' => $validated['status'] === 'Aktif',
        ];

        if ($request->hasFile('photo_ktp')) {
            if ($employee->photo_ktp) {
                Storage::disk('s3')->delete($employee->photo_ktp);
            }
            $file = $request->file('photo_ktp');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $data['photo_ktp'] = $file->storeAs('employees/photos', $filename, ['disk' => 's3', 'visibility' => 'private']);
        }

        if ($request->hasFile('photo_kk')) {
            if ($employee->photo_kk) {
                Storage::disk('s3')->delete($employee->photo_kk);
            }
            $file = $request->file('photo_kk');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $data['photo_kk'] = $file->storeAs('employees/photos', $filename, ['disk' => 's3', 'visibility' => 'private']);
        }

        if ($request->hasFile('photo_profile')) {
            if ($employee->photo_profile) {
                Storage::disk('s3')->delete($employee->photo_profile);
            }
            $file = $request->file('photo_profile');
            $filename = (string) Str::uuid7() . '.' . $file->getClientOriginalExtension();
            $data['photo_profile'] = $file->storeAs('employees/photos', $filename, ['disk' => 's3', 'visibility' => 'public']);
        }

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $employee->update($data);

        return back()->with('success', 'Karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return back()->with('success', 'Karyawan berhasil dihapus.');
    }

    public function restore(string $id): RedirectResponse
    {
        $karyawan = Employee::withTrashed()->findOrFail($id);
        $karyawan->restore();

        return back()->with('success', 'Karyawan berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada karyawan yang dipilih.');
        }

        $count = Employee::whereIn('id', $ids)->delete();

        return back()->with('success', "{$count} karyawan berhasil dihapus.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');

        if (empty($ids) || !in_array($status, ['Aktif', 'Nonaktif'])) {
            return back()->with('error', 'Data tidak valid.');
        }

        $count = Employee::whereIn('id', $ids)->update([
            'is_active' => $status === 'Aktif',
        ]);

        $label = $status === 'Aktif' ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "{$count} karyawan berhasil {$label}.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada karyawan yang dipilih.');
        $count = Employee::onlyTrashed()->whereIn('id', $ids)->restore();
        return back()->with('success', "{$count} karyawan berhasil dipulihkan.");
    }
}
