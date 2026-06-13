<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Custom notification untuk "Lupa Password" semua portal.
 *
 * URL pattern: /lupa-password-{portal}?token={rawToken}&email={email}&company_id={companyId}
 *
 * - portal: 'operator-saas', 'perusahaan', 'karyawan', 'pelanggan'
 * - token: raw token (plain) — controller akan hash saat insert ke DB
 * - email: raw email user
 * - company_id: null untuk admin-saas (single tenant)
 *
 * Flow:
 * 1. Controller generate random 64-char token
 * 2. Hash token pakai Hash::make() → simpan di DB dengan composite key
 * 3. Kirim email dengan URL berisi RAW token (not hashed)
 * 4. User klik link → controller hash raw token → lookup by composite
 */
class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $guard,
        public ?string $companyId
    ) {}

    /**
     * Mapping guard → portal URL slug.
     */
    private const GUARD_TO_PORTAL = [
        'admin-saas' => 'operator-saas',
        'admin-company' => 'perusahaan',
        'employee' => 'karyawan',
        'customer' => 'pelanggan',
    ];

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = $this->buildResetUrl($notifiable);
        $expireMinutes = config('auth.passwords.' . $this->getBrokerName() . '.expire', 60);

        return (new MailMessage)
            ->subject('Reset Password - ' . config('app.name'))
            ->markdown('emails.reset-password', [
                'url' => $url,
                'user' => $notifiable,
                'expireMinutes' => $expireMinutes,
                'appName' => config('app.name'),
            ]);
    }

    /**
     * Build URL: {baseUrl}/lupa-password-{portal}?token=...&email=...&company_id=...
     */
    private function buildResetUrl($notifiable): string
    {
        $portal = self::GUARD_TO_PORTAL[$this->guard] ?? 'operator-saas';
        $params = [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ];
        if ($this->companyId) {
            $params['company_id'] = $this->companyId;
        }
        return url('/lupa-password-' . $portal . '?' . http_build_query($params));
    }

    /**
     * Map guard ke broker name di config/auth.php.
     */
    private function getBrokerName(): string
    {
        return match ($this->guard) {
            'admin-saas' => 'admins',
            'admin-company' => 'companies',
            'employee' => 'employees',
            'customer' => 'customers',
            default => 'users',
        };
    }
}
