<?php

namespace App\Http\Controllers\OperatorPerusahaan;

use App\Http\Controllers\Controller;
use App\Models\SaasConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KonfigurasiPerusahaanController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SaasConfig::query()->with(['createdBy', 'updatedBy']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('value', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($sortField = $request->input('sort_field')) {
            $sortDir = $request->input('sort_dir', 'asc');
            $allowedSorts = ['key', 'value', 'type', 'created_at'];
            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDir);
            }
        } else {
            $query->latest();
        }

        $perPage = min((int) $request->input('per_page', 10), 100);

        $configs = $query->paginate($perPage)->through(function ($config) {
            return [
                'id' => $config->id,
                'key' => $config->key,
                'type' => $config->type,
                'value' => $config->value,
                'descripton' => $config->descripton,
                'created_at' => $config->created_at->format('Y-m-d H:i'),
                'updated_at' => $config->updated_at->format('Y-m-d H:i'),
                'created_by' => $config->createdBy?->name,
                'updated_by' => $config->updatedBy?->name,
            ];
        });

        return Inertia::render('OperatorPerusahaan/KonfigurasiPerusahaan', [
            'configs' => $configs,
            'filters' => $request->only(['search', 'type', 'sort_field', 'sort_dir', 'per_page']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255', 'unique:saas_configs'],
            'type' => ['required', 'in:text,file'],
            'value' => ['required', 'string', 'max:65535'],
            'descripton' => ['nullable', 'string', 'max:65535'],
        ]);

        SaasConfig::create($validated);

        return back()->with('success', 'Konfigurasi berhasil ditambahkan.');
    }

    public function update(Request $request, SaasConfig $saasConfig): RedirectResponse
    {
        $validated = $request->validate([
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('saas_configs')->ignore($saasConfig->id),
            ],
            'type' => ['required', 'in:text,file'],
            'value' => ['required', 'string', 'max:65535'],
            'descripton' => ['nullable', 'string', 'max:65535'],
        ]);

        $saasConfig->update($validated);

        return back()->with('success', 'Konfigurasi berhasil diperbarui.');
    }

    public function destroy(SaasConfig $saasConfig): RedirectResponse
    {
        $saasConfig->delete();

        return back()->with('success', 'Konfigurasi berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Tidak ada konfigurasi yang dipilih.');
        }

        $count = SaasConfig::whereIn('id', $ids)->delete();

        return back()->with('success', "{$count} konfigurasi berhasil dihapus.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return back()->with('error', 'Tidak ada konfigurasi yang dipilih.');
        $count = SaasConfig::onlyTrashed()->whereIn('id', $ids)->restore();
        return back()->with('success', "{$count} konfigurasi berhasil dipulihkan.");
    }
}
