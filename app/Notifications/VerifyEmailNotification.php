<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Custom notification untuk "Verifikasi Email" portal Pelanggan.
 *
 * URL pattern: /verifikasi-email-pelanggan/konfirmasi?token={rawToken}&email={email}&company_id={companyId}
 *
 * - token: raw token (plain) — controller hash saat insert ke DB
 * - email: composite "email||company_id" (lookup di tabel email_verifications)
 * - company_id: UUID company tempat customer register
 *
 * Flow:
 * 1. Controller generate random 64-char token (Str::random(64))
 * 2. Hash token pakai Hash::make() → simpan di DB dengan composite key (email, company_id)
 * 3. Kirim email dengan URL berisi RAW token (not hashed)
 * 4. User klik link → controller hash raw token → lookup by composite
 * 5. Valid token + belum expire (60 menit) → set customers.email_verified_at + hapus token
 */
class VerifyEmailNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public ?string $companyId
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = $this->buildVerifyUrl($notifiable);

        return (new MailMessage)
            ->subject('Verifikasi Email Anda - ' . config('app.name'))
            ->markdown('emails.verify-email', [
                'url' => $url,
                'user' => $notifiable,
                'expireMinutes' => 60,
                'appName' => config('app.name'),
            ]);
    }

    /**
     * Build URL: {baseUrl}/verifikasi-email-pelanggan/konfirmasi?token=...&email=...&company_id=...
     */
    private function buildVerifyUrl($notifiable): string
    {
        $params = [
            'token' => $this->token,
            'email' => $notifiable->getEmailForVerification(),
        ];
        if ($this->companyId) {
            $params['company_id'] = $this->companyId;
        }
        return url('/verifikasi-email-pelanggan/konfirmasi?' . http_build_query($params));
    }
}
