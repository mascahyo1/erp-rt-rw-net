---
name: php-upload-limits-deploy
description: Default PHP upload_max_filesize=2M + post_max_size=8M TIDAK CUKUP utk file attachment. Bump saat ada fitur upload multi-file.
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# PHP Upload Limits — Default teralu kecil utk multi-file upload

## Context
2026-06-30 deploy multi-file attachment di Gangguan. Default PHP:
- `upload_max_filesize = 2M` (per file)
- `post_max_size = 8M` (total POST body)
- `memory_limit = -1` (no limit)

Image HP modern = 3-5MB. PDF bisa 5MB+. Multiple files (3 × 5MB) = 15MB > post_max_size 8M.

Symptom: Laravel return "X.0 failed to upload" — bukan "file too large", tapi "no file received" karena ditolak PHP SEBELUM masuk Laravel.

## Why
- PHP setting di-set SERVER-level (php.ini atau pool FPM)
- Laravel `max:NNNN` rule (KB) limit di APP-level setelah file upload ke $_FILES
- Kalau file lebih besar dari `upload_max_filesize` → PHP tolak → Laravel detects "no file" → "failed to upload"
- Confusing message: bukan "file too big" tapi "failed to upload"

## How to apply

### Default minimum utk multi-file attachment:
- `upload_max_filesize`: 8M (per file, cover HP foto + PDF)
- `post_max_size`: 64M (kalau 5 files × 8MB + form data masih cukup)
- `memory_limit`: 256M (prevent OOM saat process many files)
- Laravel `max:5120` (KB = 5 MB) di validation rule

### Apply via SSH:
```bash
# Backup + update
sudo cp /etc/php/8.5/apache2/php.ini /etc/php/8.5/apache2/php.ini.bak
sudo sed -i "s|upload_max_filesize = 2M|upload_max_filesize = 8M|" /etc/php/8.5/apache2/php.ini
sudo sed -i "s|post_max_size = 8M|post_max_size = 64M|" /etc/php/8.5/apache2/php.ini
sudo sed -i "s|memory_limit = .*|memory_limit = 256M|" /etc/php/8.5/apache2/php.ini
# CLI juga (utk php artisan tinker/curl)
sudo sed -i "s|upload_max_filesize = 2M|upload_max_filesize = 8M|" /etc/php/8.5/cli/php.ini
sudo sed -i "s|post_max_size = 8M|post_max_size = 64M|" /etc/php/8.5/cli/php.ini
sudo sed -i "s|memory_limit = .*|memory_limit = 256M|" /etc/php/8.5/cli/php.ini
# Restart
sudo systemctl restart apache2
sudo systemctl restart php8.5-fpm
# Verify
php8.5 -i | grep -E "^upload_max|^post_max|^memory_limit"
```

### Laravel validation rule (per file):
```php
'attachments_bukti_issue.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
// max:5120 = 5120 KB = 5 MB per file
// Konsisten dgn `upload_max_filesize = 8M` — PHP limit lebih besar utk safety margin
```

### Cloudflare Tunnel limit (extra layer):
- Free tier: 100MB per request
- Paid tier: 5GB
- Kalau upload > 100MB butuh Cloudflare paid + non-streaming upload

## Related
- [deploy-dev-env](deploy-dev-env.md) — dev server setup
- [deploy-password-policy](deploy-password-policy.md) — server config rules