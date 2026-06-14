<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminCompany;
use App\Models\AdminSaas;
use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller untuk flow "Lupa Password" di 4 portal.
 *
 * Mapping portal → guard + model:
 *   - operator-saas → 'admin-saas' → AdminSaas (single tenant, no company_id)
 *   - perusahaan    → 'admin-company' → AdminCompany (multi-tenant)
 *   - karyawan      → 'employee' → Employee (multi-tenant)
 *   - pelanggan     → 'customer' → Customer (multi-tenant)
 *
 * Flow custom (tidak pakai Password::broker() karena multi-tenant composite key):
 *  1. create($portal): render Vue page (forgot atau reset mode berdasarkan query)
 *  2. store(Request): lookup user by email [+ company_id] → generate token → insert → kirim email
 *  3. update(Request): lookup token by composite + verify hash → update password → delete token
 */
class ForgotPasswordController extends Controller
{
    /** Map portal slug → config tuple [model class, guard name, page component, is_multi_tenant] */
    private const PORTAL_CONFIG = [
        'operator-saas' => [
            'model' => AdminSaas::class,
            'guard' => 'admin-saas',
            'page' => 'Landing/LupaPasswordOperatorSaaS',
            'multiTenant' => false,
            'loginRoute' => 'operator-saas.login',
        ],
        'perusahaan' => [
            'model' => AdminCompany::class,
            'guard' => 'admin-company',
            'page' => 'Landing/LupaPasswordPerusahaan',
            'multiTenant' => true,
            'loginRoute' => 'operator-perusahaan.login',
        ],
        'karyawan' => [
            'model' => Employee::class,
            'guard' => 'employee',
            'page' => 'Karyawan/LupaPassword',
            'multiTenant' => true,
            'loginRoute' => 'employee.login',
        ],
        'pelanggan' => [
            'model' => Customer::class,
            'guard' => 'customer',
            'page' => 'Landing/LupaPasswordPelanggan',
            'multiTenant' => true,
            'loginRoute' => 'customer.login',
        ],
    ];

    /**
     * GET /lupa-password-{portal}
     * Render Vue page forgot/reset. Mode ditentukan dari query string:
     *   - tanpa ?token → form input email
     *   - dengan ?token → form password baru
     *
     * Catatan: $request->query() sudah auto-decode URL-encoded values
     * (PHP parse_str convention). Jangan urldecode() lagi — akan double-decode
     * dan merubah '+' jadi spasi.
     */
    public function create(Request $request, string $portal): Response
    {
        $this->ensureValidPortal($portal);

        return Inertia::render(self::PORTAL_CONFIG[$portal]['page'], [
            'token' => $request->query('token'),
            'email' => $request->query('email'),
            'companyId' => $request->query('company_id'),
        ]);
    }

    /**
     * POST /lupa-password-{portal}
     * Kirim email dengan link reset.
     */
    public function store(Request $request, string $portal): RedirectResponse
    {
        $config = $this->ensureValidPortal($portal);

        $rules = ['email' => ['required', 'string', 'email']];
        if ($config['multiTenant']) {
            $rules['company_id'] = ['required', 'string', 'exists:companies,id'];
        }
        $rules['cf-turnstile-response'] = ['required', new \App\Rules\Turnstile($request->ip())];
        $data = $request->validate($rules);

        // Lookup user by email [+ company_id]
        $query = $config['model']::query()->where('email', $data['email']);
        if ($config['multiTenant']) {
            $query->where('company_id', $data['company_id']);
        }
        $user = $query->first();

        // Security: always return success message, even if user not found
        // (mencegah email enumeration)
        if (! $user) {
            return back()->with('status', 'Jika email terdaftar, link reset sudah dikirim ke email Anda.');
        }

        // Generate random 64-char token
        $rawToken = Str::random(64);

        // Hapus token lama untuk user ini (composite key)
        $this->deleteExistingTokens($user, $config);

        // Insert token baru — composite key (email, company_id, guard).
        // PENTING: gunakan getEmailForPasswordReset() (bukan $user->email) agar
        // composite key match dgn URL query yang dikirim email. Untuk multi-tenant,
        // composite = "{email}||{company_id}". Untuk admin-saas, composite = "{email}||".
        DB::table('password_reset_tokens')->insert([
            'email' => $user->getEmailForPasswordReset(),
            'company_id' => $config['multiTenant'] ? $user->company_id : '',
            'guard' => $config['guard'],
            'token' => Hash::make($rawToken),
            'created_at' => now(),
        ]);

        // Kirim email notifikasi
        $user->sendPasswordResetNotification($rawToken);

        return back()->with('status', 'Link reset password sudah dikirim ke email Anda.');
    }

    /**
     * POST /lupa-password-{portal}/reset
     * Update password baru dengan validasi token.
     */
    public function update(Request $request, string $portal): RedirectResponse
    {
        $config = $this->ensureValidPortal($portal);

        Log::info('ForgotPassword.update START', [
            'portal' => $portal,
            'all' => $request->all(),
        ]);

        $data = $request->validate([
            'token' => ['required', 'string'],
            // 'email' = composite key ("{raw}||{company_id}"), bukan valid email format.
            // Jadi hanya validasi 'required' + 'string' (no 'email' rule).
            'email' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cf-turnstile-response' => ['required', new \App\Rules\Turnstile($request->ip())],
        ]);
        if ($config['multiTenant']) {
            $data['company_id'] = $request->input('company_id');
            if (! $data['company_id']) {
                return back()->withErrors(['company_id' => 'Company ID wajib diisi.']);
            }
        }

        // Lookup token record by composite key
        $tokenQuery = DB::table('password_reset_tokens')
            ->where('email', $data['email'])
            ->where('guard', $config['guard']);
        if ($config['multiTenant']) {
            $tokenQuery->where('company_id', $data['company_id']);
        } else {
            $tokenQuery->where('company_id', '');
        }
        $tokenRecord = $tokenQuery->first();

        Log::info('ForgotPassword update', [
            'email_form' => $data['email'],
            'token_form_prefix' => substr($data['token'], 0, 8) . '...',
            'guard' => $config['guard'],
            'token_record_found' => (bool) $tokenRecord,
        ]);

        if (! $tokenRecord) {
            return back()->withErrors(['email' => 'Token reset tidak valid atau sudah kadaluarsa.']);
        }

        $hashCheck = Hash::check($data['token'], $tokenRecord->token);
        Log::info('ForgotPassword hash check', [
            'match' => $hashCheck,
        ]);

        // Verify hash token (Laravel-style: token yang di-hash cocok dengan raw token dari URL)
        if (! Hash::check($data['token'], $tokenRecord->token)) {
            return back()->withErrors(['email' => 'Token reset tidak valid atau sudah kadaluarsa.']);
        }

        // Expiry check (default 60 menit)
        $expire = config('auth.passwords.' . $this->getBrokerName($config['guard']) . '.expire', 60);
        if ($tokenRecord->created_at && now()->diffInMinutes($tokenRecord->created_at) > $expire) {
            $this->deleteTokenRecord($tokenRecord);
            return back()->withErrors(['email' => 'Token reset sudah kadaluarsa. Silakan minta link baru.']);
        }

        // Lookup user dan update password.
        // Catatan: $data['email'] dari form = composite key ("email||company_id" untuk multi-tenant,
        // "email||" untuk admin-saas). Untuk lookup user, kita butuh extract raw email.
        $rawEmail = $data['email'];
        $sepPos = strpos($rawEmail, '||');
        if ($sepPos !== false) {
            $rawEmail = substr($rawEmail, 0, $sepPos);
        }
        $userQuery = $config['model']::query()->where('email', $rawEmail);
        if ($config['multiTenant']) {
            $userQuery->where('company_id', $data['company_id']);
        }
        $user = $userQuery->first();

        if (! $user) {
            return back()->withErrors(['email' => 'User tidak ditemukan.']);
        }

        // Gunakan forceFill agar bypass 'hashed' cast (kita sudah hash manual)
        $user->forceFill(['password' => Hash::make($data['password'])])->save();

        // Hapus token (one-time use)
        $this->deleteTokenRecord($tokenRecord);

        return redirect()->route($config['loginRoute'])
            ->with('status', 'Password berhasil direset. Silakan login dengan password baru Anda.');
    }

    /**
     * Hapus semua token existing untuk user (composite key).
     * PENTING: pakai getEmailForPasswordReset() agar match dengan composite key di DB.
     */
    private function deleteExistingTokens(object $user, array $config): void
    {
        $query = DB::table('password_reset_tokens')
            ->where('email', $user->getEmailForPasswordReset())
            ->where('guard', $config['guard']);
        if ($config['multiTenant']) {
            $query->where('company_id', $user->company_id);
        } else {
            $query->where('company_id', '');
        }
        $query->delete();
    }

    private function deleteTokenRecord(object $tokenRecord): void
    {
        DB::table('password_reset_tokens')
            ->where('email', $tokenRecord->email)
            ->where('company_id', $tokenRecord->company_id)
            ->where('guard', $tokenRecord->guard)
            ->delete();
    }

    private function ensureValidPortal(string $portal): array
    {
        if (! isset(self::PORTAL_CONFIG[$portal])) {
            abort(404, 'Portal tidak valid.');
        }
        return self::PORTAL_CONFIG[$portal];
    }

    private function getBrokerName(string $guard): string
    {
        return match ($guard) {
            'admin-saas' => 'admins',
            'admin-company' => 'companies',
            'employee' => 'employees',
            'customer' => 'customers',
            default => 'users',
        };
    }
}
