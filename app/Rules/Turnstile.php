<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Validasi Cloudflare Turnstile token.
 *
 * Cara pakai di FormRequest atau controller validation:
 *   'cf-turnstile-response' => ['required', new Turnstile()],
 *
 * Cara kerja: widget Turnstile di frontend auto-populate hidden input
 * `cf-turnstile-response` dengan token one-time saat user solve captcha.
 * Token ini kita verifikasi ke Cloudflare siteverify endpoint.
 *
 * Referensi: https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
 */
class Turnstile implements ValidationRule
{
    /**
     * IP address user (opsional, dikirim ke Cloudflare untuk analisis).
     * Capture sekali saat rule di-instantiate — agar nilainya konsisten
     * selama request yang sama.
     */
    private ?string $remoteIp;

    public function __construct(?string $remoteIp = null)
    {
        $this->remoteIp = $remoteIp;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret_key');
        if (empty($secret)) {
            Log::warning('Turnstile secret_key not configured, skipping verification');
            return; // fail open kalau config missing (development)
        }

        $response = Http::asForm()
            ->withOptions(['verify' => (bool) config('services.turnstile.verify_ssl', true)])
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $value,
                'remoteip' => $this->remoteIp,
            ]);

        if (! $response->successful()) {
            Log::error('Turnstile siteverify HTTP error', ['status' => $response->status()]);
            $fail('Verifikasi captcha gagal (server error). Silakan coba lagi.');
            return;
        }

        $body = $response->json();
        if (! ($body['success'] ?? false)) {
            $errorCodes = $body['error-codes'] ?? ['unknown'];
            Log::warning('Turnstile verification failed', ['errors' => $errorCodes]);
            $fail('Verifikasi captcha gagal. Silakan coba lagi.');
        }
    }
}
