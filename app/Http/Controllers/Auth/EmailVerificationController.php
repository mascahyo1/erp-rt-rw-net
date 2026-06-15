<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller untuk flow "Verifikasi Email" portal Pelanggan.
 *
 * Flow:
 *   1. form(Request): render Vue "Cek email Anda" (bisa dipakai untuk kirim ulang)
 *   2. send(Request): generate token + simpan hashed + kirim email notifikasi
 *   3. confirm(Request): validate token by composite key + verify hash + set email_verified_at
 *
 * Composite key: email + company_id (multi-tenant — 1 email bisa di beberapa company).
 * Mirip dengan ForgotPasswordController::store/update(), tapi:
 *   - Tabel terpisah (email_verifications) — beda lifecycle dgn password_reset_tokens
 *   - One-time use — token di-delete setelah confirm (tidak bisa diulang)
 *   - Tidak ada guard field — khusus customer
 */
class EmailVerificationController extends Controller
{
    /**
     * GET /verifikasi-email-pelanggan
     * Render Vue "Cek email Anda" page.
     *
     * Query params (opsional): email (composite), company_id
     * - Tanpa query: tampilkan form "Minta link verifikasi" (untuk user yang
     *   register tapi tidak terima email)
     * - Dengan query: tampilkan "Cek email Anda" dengan info target email
     */
    public function form(Request $request): Response
    {
        return Inertia::render('Customer/VerifikasiEmail', [
            'email' => $request->query('email'),
            'companyId' => $request->query('company_id'),
        ]);
    }

    /**
     * POST /kirim-ulang-verifikasi-pelanggan
     * Generate token baru + kirim email. Throttled 5/menit + Turnstile.
     *
     * Email input bisa raw ("foo@bar.com") atau composite ("foo@bar.com||uuid").
     * Selalu lookup customer by raw email + company_id agar konsisten.
     */
    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string'],
            'company_id' => ['required', 'string', 'exists:companies,id'],
            'cf-turnstile-response' => ['required', new \App\Rules\Turnstile($request->ip())],
        ]);

        // Extract raw email (form mungkin composite "email||company_id" atau raw "email")
        $rawEmail = $data['email'];
        $sepPos = strpos($rawEmail, '||');
        if ($sepPos !== false) {
            $rawEmail = substr($rawEmail, 0, $sepPos);
        }

        $customer = Customer::where('email', $rawEmail)
            ->where('company_id', $data['company_id'])
            ->first();

        if (! $customer) {
            return back()->withErrors(['email' => 'Email tidak terdaftar di perusahaan ini.']);
        }

        if ($customer->hasVerifiedEmail()) {
            return back()->withErrors(['email' => 'Email sudah diverifikasi. Silakan login.']);
        }

        // Generate random 64-char token
        $rawToken = Str::random(64);

        // Upsert ke email_verifications dengan composite key (email, company_id).
        // Pakai getEmailForVerification() agar composite key match dgn URL query.
        DB::table('email_verifications')->updateOrInsert(
            [
                'email' => $customer->getEmailForVerification(),
                'company_id' => $customer->company_id,
            ],
            [
                'token' => Hash::make($rawToken),
                'created_at' => now(),
            ]
        );

        // Kirim email notifikasi
        $customer->sendCustomEmailVerificationNotification($rawToken);

        return back()->with('status', 'Link verifikasi sudah dikirim ke email Anda.');
    }

    /**
     * GET /verifikasi-email-pelanggan/konfirmasi
     * Validate token + set customers.email_verified_at + delete token.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            // 'email' = composite key ("raw||company_id"). Hanya validasi 'string' (no 'email' rule).
            'email' => ['required', 'string'],
            'company_id' => ['required', 'string', 'exists:companies,id'],
        ]);

        // Lookup token by composite key
        $tokenRecord = DB::table('email_verifications')
            ->where('email', $data['email'])
            ->where('company_id', $data['company_id'])
            ->first();

        if (! $tokenRecord) {
            return redirect()->route('customer.login')
                ->withErrors(['email' => 'Link verifikasi tidak valid atau sudah kadaluarsa.']);
        }

        // Verify hash
        if (! Hash::check($data['token'], $tokenRecord->token)) {
            return redirect()->route('customer.login')
                ->withErrors(['email' => 'Token verifikasi tidak valid.']);
        }

        // Expiry check (60 menit)
        if ($tokenRecord->created_at && now()->diffInMinutes($tokenRecord->created_at) > 60) {
            DB::table('email_verifications')
                ->where('email', $data['email'])
                ->where('company_id', $data['company_id'])
                ->delete();
            return redirect()->route('customer.login')
                ->withErrors(['email' => 'Link verifikasi sudah kadaluarsa. Silakan minta link baru.']);
        }

        // Extract raw email dari composite, lookup customer
        $rawEmail = $data['email'];
        $sepPos = strpos($rawEmail, '||');
        if ($sepPos !== false) {
            $rawEmail = substr($rawEmail, 0, $sepPos);
        }
        $customer = Customer::where('email', $rawEmail)
            ->where('company_id', $data['company_id'])
            ->first();

        if (! $customer) {
            return redirect()->route('customer.login')
                ->withErrors(['email' => 'Akun tidak ditemukan.']);
        }

        // Set email_verified_at + delete token (one-time use)
        $customer->forceFill(['email_verified_at' => now()])->save();
        DB::table('email_verifications')
            ->where('email', $data['email'])
            ->where('company_id', $data['company_id'])
            ->delete();

        return redirect()->route('customer.login')
            ->with('status', 'Email berhasil diverifikasi. Silakan login.');
    }
}
