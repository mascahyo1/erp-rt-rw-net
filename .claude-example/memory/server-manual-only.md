---
name: server-manual-only
description: "JANGAN oprek/modify server manual via SSH. User handle manual semua perubahan server-side (MinIO config, Apache vhost, PHP ini, Cloudflare dashboard). Saya hanya push code via git."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# Server Changes — USER MANUAL ONLY

## Context
2026-07-01 user: "btw inget ga minio web dan api itu url external kemana url local kemana jangan oprek2 server ya. saya manual aja"

User minta:
- Server config (MinIO, Apache, PHP, Cloudflare, MySQL) — semua manual oleh user
- Saya hanya boleh **push code via git**, deploy standard
- JANGAN oprek server sendiri (jangan SSH untuk random tweaks, jangan edit config files langsung)
- Kalau user minta SSH command, hanya yang eksplisit diminta (bukan inisiatif sendiri)

## Why
- Server punya multiple state yg saya gak sepenuhnya aware (Cloudflare Tunnel, MinIO bucket policy, Apache vhost, PHP-FPM pool, MySQL grants)
- Setiap "fix kecil" yg saya coba di server bisa break hal lain (bukti: PHP `pcache.jit=0` warning udah ada di memory)
- User lebih paham environment prod dan alur kerja mereka
- Audit trail lebih clean kalau cuma user yg modify server

## How to apply

### ❌ JANGAN (user-specified):
```bash
# JANGAN oprek server random
ssh smago "systemctl restart php8.5-fpm"
ssh smago "sed -i 's/X/Y/' /etc/php/8.5/apache2/php.ini"
ssh smago "chown www-data:www-data /var/log/something"
```

### ✅ BOLEH:
```bash
# 1. Pull code (git) — biarkan deploy script/CI yg handle restart
ssh smago "cd /var/www/dev-erp-rt-rw-net && git pull origin dev && npm run build"

# 2. Run migrations (Laravel) — safe
ssh smago "cd /var/www/erp-rt-rw-net && php8.5 artisan migrate --force"

# 3. Cek status (read-only) — boleh
ssh smago "tail -20 /var/log/apache2/dev-net-error.log"
ssh smago "cd /var/www/erp-rt-rw-net && php8.5 artisan tinker --execute='echo App\Models\Customer::count();'"
```

### Kalau ada masalah server-side:
1. **Identifikasi** root cause (via log files, config, test commands)
2. **Rekomendasi fix** ke user dgn step-by-step command
3. **Tunggu user** eksekusi manual
4. **Verify** setelahnya dgn read-only commands

### URL server reference (catat di memory):
- **Laravel prod**: `https://net.cahyosoft.my.id` (alias: `https://jmpgroup.id`)
- **Laravel dev**: `https://dev-net.cahyosoft.my.id` (alias: `https://dev.jmpgroup.id`)
- **MinIO API external**: `https://minio-api.cahyosoft.my.id` → `http://pma.local:9000`
- **MinIO web external**: `https://minio-web.cahyosoft.my.id` → `http://pma.local:9001`
- **MinIO API local**: `http://127.0.0.1:9000`
- **MinIO web local**: `http://127.0.0.1:9001`
- **phpMyAdmin external**: `https://pma.cahyosoft.my.id` → `http://pma.local:80`
- **MySQL local**: `127.0.0.1:3306` (localhost only)
- **Apache local**: `127.0.0.1:80`
- **Cloudflare Tunnel**: token-managed systemd unit, NO config.yml

## Related
- [deploy-server-state-2026-06-30](deploy-server-state-2026-06-30.md) — full server spec
- [deploy-dev-test-before-push](deploy-dev-test-before-push.md) — workflow pattern
- [DEPLOY.md](../../../../dokumentasi/DEPLOY.md) — deployment guide