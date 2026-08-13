<?php

use App\Http\Controllers\AdminCompanyController;
use App\Http\Controllers\AdminRoleSaasController;
use App\Http\Controllers\AdminSaasController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\RoleAdminPerusahaanController;
use App\Http\Controllers\RolePerusahaanController;
use App\Http\Controllers\RoleSaasController;
use App\Http\Controllers\SaasConfigController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/operator-saas/perusahaan/select-search', [CompanyController::class, 'selectSearch'])
    ->name('operator-saas.perusahaan.select-search');

Route::middleware(['auth:admin-saas', 'ensure.user.active:admin-saas'])->group(function () {
    Route::get('/operator-saas/profil-saya', function () {
        return Inertia::render('OperatorSaas/ProfilSaya');
    });

    Route::put('/operator-saas/profil-saya', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone_country_code' => ['required', 'string', 'regex:/^\+\d{1,4}$/'],
            'phone_number' => ['nullable', 'string', 'regex:/^\d{6,15}$/'],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        // Manual email uniqueness check (UUID v7 key + model has no $keyType=string set)
        $emailTaken = \App\Models\AdminSaas::where('email', $data['email'])
            ->where('id', '!=', $user->id)
            ->exists();
        if ($emailTaken) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => ['Email sudah digunakan oleh user lain.'],
            ]);
        }

        if ($data['password'] ?? null) {
            if (!\Hash::check($data['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
            }
            $user->password = bcrypt($data['password']);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone_country_code = $data['phone_country_code'];
        $user->phone_number = $data['phone_number'] ?? null;
        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    });

    Route::get('/operator-saas/dashboard', function () {
        return Inertia::render('OperatorSaas/Dashboard', [
            'stats' => [
                'perusahaan_aktif' => \App\Models\Company::where('is_active', true)->count(),
                'admin_perusahaan_aktif' => \App\Models\AdminCompany::where('is_active', true)->count(),
                'admin_saas' => \App\Models\AdminSaas::where('is_active', true)->count(),
                'pelanggan_aktif' => \App\Models\Customer::where('is_active', true)->count(),
                'karyawan_aktif' => \App\Models\Employee::where('is_active', true)->count(),
                'langganan_aktif' => \App\Models\CustInternet::where('internet_status', 'active')->count(),
            ],
        ]);
    })->name('operator-saas.dashboard');

    // Admin Perusahaan
    Route::middleware('permission:admin-perusahaan.list')->group(function () {
        Route::get('/operator-saas/admin-perusahaan', [AdminCompanyController::class, 'index'])
            ->name('operator-saas.admin-perusahaan.index');
    });
    Route::middleware('permission:admin-perusahaan.create')->group(function () {
        Route::post('/operator-saas/admin-perusahaan', [AdminCompanyController::class, 'store'])
            ->name('operator-saas.admin-perusahaan.store');
    });
    Route::middleware('permission:admin-perusahaan.edit')->group(function () {
        Route::put('/operator-saas/admin-perusahaan/{adminCompany}', [AdminCompanyController::class, 'update'])
            ->name('operator-saas.admin-perusahaan.update');
        Route::post('/operator-saas/admin-perusahaan/bulk-status', [AdminCompanyController::class, 'bulkToggleStatus'])
            ->name('operator-saas.admin-perusahaan.bulk-status');
    });
    Route::middleware('permission:admin-perusahaan.delete')->group(function () {
        Route::delete('/operator-saas/admin-perusahaan/{adminCompany}', [AdminCompanyController::class, 'destroy'])
            ->name('operator-saas.admin-perusahaan.destroy');
        Route::post('/operator-saas/admin-perusahaan/bulk-delete', [AdminCompanyController::class, 'bulkDelete'])
            ->name('operator-saas.admin-perusahaan.bulk-delete');
    });
    Route::middleware('permission:admin-perusahaan.restore')->group(function () {
        Route::post('/operator-saas/admin-perusahaan/{id}/restore', [AdminCompanyController::class, 'restore'])
            ->name('operator-saas.admin-perusahaan.restore');
    });

    // Perusahaan
    Route::middleware('permission:perusahaan.list')->group(function () {
        Route::get('/operator-saas/perusahaan', [CompanyController::class, 'index'])
            ->name('operator-saas.perusahaan.index');
    });
    // (Inertia POST/PUT/DELETE routes untuk Perusahaan dihapus — form sekarang pakai AJAX endpoint di bawah)

    // Perusahaan — AJAX endpoints (POST + JSON, untuk form submit di modal)
    // Lihat dokumentasi/CONVENTIONS.md section 2.
    Route::prefix('api')->group(function () {
        Route::post('/operator-saas/perusahaan', [CompanyController::class, 'storeAjax'])
            ->middleware('permission:perusahaan.create')
            ->name('api.operator-saas.perusahaan.store');

        Route::post('/operator-saas/perusahaan/{company}', [CompanyController::class, 'updateAjax'])
            ->middleware('permission:perusahaan.edit')
            ->name('api.operator-saas.perusahaan.update');

        Route::post('/operator-saas/perusahaan/{company}/delete', [CompanyController::class, 'destroyAjax'])
            ->middleware('permission:perusahaan.delete')
            ->name('api.operator-saas.perusahaan.destroy');

        Route::post('/operator-saas/perusahaan/{id}/restore', [CompanyController::class, 'restoreAjax'])
            ->middleware('permission:perusahaan.restore')
            ->name('api.operator-saas.perusahaan.restore');

        Route::post('/operator-saas/perusahaan/bulk-delete', [CompanyController::class, 'bulkDeleteAjax'])
            ->middleware('permission:perusahaan.delete')
            ->name('api.operator-saas.perusahaan.bulk-delete');

        Route::post('/operator-saas/perusahaan/bulk-status', [CompanyController::class, 'bulkToggleStatusAjax'])
            ->middleware('permission:perusahaan.edit')
            ->name('api.operator-saas.perusahaan.bulk-status');
    });

    // Role Perusahaan
    Route::middleware('permission:role-perusahaan.list')->group(function () {
        Route::get('/operator-saas/role-perusahaan', [RolePerusahaanController::class, 'index'])
            ->name('operator-saas.role-perusahaan.index');
    });
    Route::middleware('permission:role-perusahaan.create')->group(function () {
        Route::post('/operator-saas/role-perusahaan', [RolePerusahaanController::class, 'store'])
            ->name('operator-saas.role-perusahaan.store');
    });
    Route::middleware('permission:role-perusahaan.edit')->group(function () {
        Route::put('/operator-saas/role-perusahaan/{role}', [RolePerusahaanController::class, 'update'])
            ->name('operator-saas.role-perusahaan.update');
        Route::post('/operator-saas/role-perusahaan/bulk-status', [RolePerusahaanController::class, 'bulkToggleStatus'])
            ->name('operator-saas.role-perusahaan.bulk-status');
    });
    Route::middleware('permission:role-perusahaan.delete')->group(function () {
        Route::delete('/operator-saas/role-perusahaan/{role}', [RolePerusahaanController::class, 'destroy'])
            ->name('operator-saas.role-perusahaan.destroy');
        Route::post('/operator-saas/role-perusahaan/bulk-delete', [RolePerusahaanController::class, 'bulkDelete'])
            ->name('operator-saas.role-perusahaan.bulk-delete');
    });
    Route::middleware('permission:role-perusahaan.restore')->group(function () {
        Route::post('/operator-saas/role-perusahaan/{id}/restore', [RolePerusahaanController::class, 'restore'])
            ->name('operator-saas.role-perusahaan.restore');
    });

    // Role Admin Perusahaan
    Route::middleware('permission:role-admin-perusahaan.list')->group(function () {
        Route::get('/operator-saas/role-admin-perusahaan', [RoleAdminPerusahaanController::class, 'index'])
            ->name('operator-saas.role-admin-perusahaan.index');
        Route::get('/operator-saas/role-admin-perusahaan/admins-by-company', [RoleAdminPerusahaanController::class, 'adminsByCompany'])
            ->name('operator-saas.role-admin-perusahaan.admins-by-company');
        Route::get('/operator-saas/role-admin-perusahaan/roles-by-company', [RoleAdminPerusahaanController::class, 'rolesByCompany'])
            ->name('operator-saas.role-admin-perusahaan.roles-by-company');
    });
    Route::middleware('permission:role-admin-perusahaan.create')->group(function () {
        Route::post('/operator-saas/role-admin-perusahaan', [RoleAdminPerusahaanController::class, 'store'])
            ->name('operator-saas.role-admin-perusahaan.store');
    });
    Route::middleware('permission:role-admin-perusahaan.edit')->group(function () {
        Route::put('/operator-saas/role-admin-perusahaan/{modelHasRole}', [RoleAdminPerusahaanController::class, 'update'])
            ->name('operator-saas.role-admin-perusahaan.update');
    });
    Route::middleware('permission:role-admin-perusahaan.delete')->group(function () {
        Route::delete('/operator-saas/role-admin-perusahaan/{modelHasRole}', [RoleAdminPerusahaanController::class, 'destroy'])
            ->name('operator-saas.role-admin-perusahaan.destroy');
        Route::post('/operator-saas/role-admin-perusahaan/bulk-delete', [RoleAdminPerusahaanController::class, 'bulkDelete'])
            ->name('operator-saas.role-admin-perusahaan.bulk-delete');
    });

    // Konfigurasi
    Route::middleware('permission:konfigurasi.list')->group(function () {
        Route::get('/operator-saas/konfigurasi', [SaasConfigController::class, 'index'])
            ->name('operator-saas.konfigurasi.index');
    });
    Route::middleware('permission:konfigurasi.create')->group(function () {
        Route::post('/operator-saas/konfigurasi', [SaasConfigController::class, 'store'])
            ->name('operator-saas.konfigurasi.store');
    });
    Route::middleware('permission:konfigurasi.edit')->group(function () {
        Route::put('/operator-saas/konfigurasi/{saasConfig}', [SaasConfigController::class, 'update'])
            ->name('operator-saas.konfigurasi.update');
    });
    Route::middleware('permission:konfigurasi.delete')->group(function () {
        Route::delete('/operator-saas/konfigurasi/{saasConfig}', [SaasConfigController::class, 'destroy'])
            ->name('operator-saas.konfigurasi.destroy');
        Route::post('/operator-saas/konfigurasi/bulk-delete', [SaasConfigController::class, 'bulkDelete'])
            ->name('operator-saas.konfigurasi.bulk-delete');
    });
    Route::middleware('permission:konfigurasi.restore')->group(function () {
        Route::post('/operator-saas/konfigurasi/{id}/restore', [SaasConfigController::class, 'restore'])
            ->name('operator-saas.konfigurasi.restore');
        Route::post('/operator-saas/konfigurasi/bulk-restore', [SaasConfigController::class, 'bulkRestore'])
            ->name('operator-saas.konfigurasi.bulk-restore');
    });
    Route::middleware('permission:konfigurasi.export')->group(function () {
        Route::get('/operator-saas/konfigurasi/export', [SaasConfigController::class, 'export'])
            ->name('operator-saas.konfigurasi.export');
        Route::get('/operator-saas/konfigurasi/template', [SaasConfigController::class, 'downloadTemplate'])
            ->name('operator-saas.konfigurasi.template');
    });
    Route::middleware('permission:konfigurasi.import')->group(function () {
        Route::post('/operator-saas/konfigurasi/import', [SaasConfigController::class, 'import'])
            ->name('operator-saas.konfigurasi.import');
    });

    // Role SaaS
    Route::middleware('permission:role-saas.list')->group(function () {
        Route::get('/operator-saas/role-saas', [RoleSaasController::class, 'index'])
            ->name('operator-saas.role-saas.index');
    });
    Route::middleware('permission:role-saas.create')->group(function () {
        Route::post('/operator-saas/role-saas', [RoleSaasController::class, 'store'])
            ->name('operator-saas.role-saas.store');
    });
    Route::middleware('permission:role-saas.edit')->group(function () {
        Route::put('/operator-saas/role-saas/{role}', [RoleSaasController::class, 'update'])
            ->name('operator-saas.role-saas.update');
        Route::post('/operator-saas/role-saas/bulk-status', [RoleSaasController::class, 'bulkToggleStatus'])
            ->name('operator-saas.role-saas.bulk-status');
    });
    Route::middleware('permission:role-saas.delete')->group(function () {
        Route::delete('/operator-saas/role-saas/{role}', [RoleSaasController::class, 'destroy'])
            ->name('operator-saas.role-saas.destroy');
        Route::post('/operator-saas/role-saas/bulk-delete', [RoleSaasController::class, 'bulkDelete'])
            ->name('operator-saas.role-saas.bulk-delete');
    });
    Route::middleware('permission:role-saas.restore')->group(function () {
        Route::post('/operator-saas/role-saas/{id}/restore', [RoleSaasController::class, 'restore'])
            ->name('operator-saas.role-saas.restore');
    });

    // Admin SaaS
    Route::middleware('permission:admin-saas.list')->group(function () {
        Route::get('/operator-saas/admin-saas', [AdminSaasController::class, 'index'])
            ->name('operator-saas.admin-saas.index');
    });
    Route::middleware('permission:admin-saas.create')->group(function () {
        Route::post('/operator-saas/admin-saas', [AdminSaasController::class, 'store'])
            ->name('operator-saas.admin-saas.store');
    });
    Route::middleware('permission:admin-saas.edit')->group(function () {
        Route::put('/operator-saas/admin-saas/{adminSaas}', [AdminSaasController::class, 'update'])
            ->name('operator-saas.admin-saas.update');
        Route::post('/operator-saas/admin-saas/bulk-status', [AdminSaasController::class, 'bulkToggleStatus'])
            ->name('operator-saas.admin-saas.bulk-status');
    });
    Route::middleware('permission:admin-saas.delete')->group(function () {
        Route::delete('/operator-saas/admin-saas/{adminSaas}', [AdminSaasController::class, 'destroy'])
            ->name('operator-saas.admin-saas.destroy');
        Route::post('/operator-saas/admin-saas/bulk-delete', [AdminSaasController::class, 'bulkDelete'])
            ->name('operator-saas.admin-saas.bulk-delete');
    });
    Route::middleware('permission:admin-saas.restore')->group(function () {
        Route::post('/operator-saas/admin-saas/{id}/restore', [AdminSaasController::class, 'restore'])
            ->name('operator-saas.admin-saas.restore');
        Route::post('/operator-saas/admin-saas/bulk-restore', [AdminSaasController::class, 'bulkRestore'])
            ->name('operator-saas.admin-saas.bulk-restore');
    });

    // Admin Role SaaS
    Route::middleware('permission:admin-role-saas.list')->group(function () {
        Route::get('/operator-saas/admin-role-saas', [AdminRoleSaasController::class, 'index'])
            ->name('operator-saas.admin-role-saas.index');
        Route::get('/operator-saas/admin-role-saas/admins', [AdminRoleSaasController::class, 'adminsAjax'])
            ->name('operator-saas.admin-role-saas.admins');
        Route::get('/operator-saas/admin-role-saas/roles', [AdminRoleSaasController::class, 'rolesAjax'])
            ->name('operator-saas.admin-role-saas.roles');
    });
    Route::middleware('permission:admin-role-saas.create')->group(function () {
        Route::post('/operator-saas/admin-role-saas', [AdminRoleSaasController::class, 'store'])
            ->name('operator-saas.admin-role-saas.store');
        Route::post('/operator-saas/admin-role-saas/bulk-assign', [AdminRoleSaasController::class, 'bulkAssign'])
            ->name('operator-saas.admin-role-saas.bulk-assign');
    });
    Route::middleware('permission:admin-role-saas.edit')->group(function () {
        Route::put('/operator-saas/admin-role-saas/{modelHasRole}', [AdminRoleSaasController::class, 'update'])
            ->name('operator-saas.admin-role-saas.update');
        Route::post('/operator-saas/admin-role-saas/bulk-update-role', [AdminRoleSaasController::class, 'bulkUpdateRole'])
            ->name('operator-saas.admin-role-saas.bulk-update-role');
    });
    Route::middleware('permission:admin-role-saas.delete')->group(function () {
        Route::delete('/operator-saas/admin-role-saas/{modelHasRole}', [AdminRoleSaasController::class, 'destroy'])
            ->name('operator-saas.admin-role-saas.destroy');
        Route::post('/operator-saas/admin-role-saas/bulk-delete', [AdminRoleSaasController::class, 'bulkDelete'])
            ->name('operator-saas.admin-role-saas.bulk-delete');
    });
});
