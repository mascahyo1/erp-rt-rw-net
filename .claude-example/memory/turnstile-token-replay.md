---
name: turnstile-token-replay
description: Cloudflare Turnstile token one-time use (TTL 5 min). Reset widget di onFinish SETIAP submit attempt (success/error) biar token fresh.
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# Cloudflare Turnstile — Token Replay Issue

## Context
2026-06-30 fix login flow. User flow:
1. Input email salah → submit → password fail (captcha OK)
2. Koreksi email + password → submit lagi → "Verifikasi captcha gagal"

Root cause: Cloudflare Turnstile token **one-time use** dengan TTL ~5 min. Submit ulang dgn token yg sama → server reject dgn `timeout-or-duplicate`.

Server log sebelum fix:
```
[2026-06-30 00:49:57] production.WARNING: Turnstile verification failed
  {"errors":["timeout-or-duplicate"]}
```

## Why
- Turnstile = anti-bot, jadi token gak bisa di-replay (justru anti-serangan)
- Setiap submit attempt = token baru. User gak sadar widget harus re-solve
- Server-side (`App\Rules\Turnstile`) cuma verify, gak minta token baru
- Butuh client-side reset SETIAP submit attempt, bukan cuma success

## How to apply

### Pattern Vue 3 + useForm:
```js
form.post(url, {
    onFinish: () => {
        // 1. Reset sensitive fields
        form.reset('password');
        // 2. Clear Turnstile token di form data
        form['cf-turnstile-response'] = '';
        // 3. Reset widget supaya re-solve (Cloudflare API call)
        if (window.turnstile) {
            document.querySelectorAll('.cf-turnstile').forEach(w => {
                try { window.turnstile.reset(w); } catch (e) { /* ignore */ }
            });
        }
    },
});
```

### Disable submit button (UX bonus):
```js
const turnstileSolved = computed(() => !siteKey.value || !!form['cf-turnstile-response']);

// Template
:disabled="form.processing || !turnstileSolved"
```

`siteKey` mungkin empty di dev/testing → `!siteKey.value` short-circuit ke true (always enabled). Kalau siteKey set → enabled hanya kalau token ada.

## Cloudflare API responses (untuk debug)
- `success: true, error-codes: []` — token valid
- `success: false, error-codes: ["timeout-or-duplicate"]` — token sudah dipakai / expired
- `success: false, error-codes: ["invalid-input-response"]` — token malformed (dummy token)
- `success: false, error-codes: ["invalid-input-secret"]` — secret_key salah

## Related
- [php-upload-limits-deploy](php-upload-limits-deploy.md)
- [customer-email-verified-seed](customer-email-verified-seed.md)