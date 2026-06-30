---
name: deploy-dev-env
description: Deploy pattern Laravel dev environment terpisah dgn branch dev + DB dev + bucket dev
metadata: 
  node_type: memory
  type: project
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# Deploy: Dev Environment Pattern

## Setup (per 2026-06-30)

### Infrastruktur
- **Branch**: `dev` di `https://github.com/mascahyo1/erp-rt-rw-net.git`
- **Server path**: `/var/www/dev-erp-rt-rw-net` (TERPISAH dari prod `/var/www/erp-rt-rw-net`)
- **Apache vhost**: `/etc/apache2/sites-available/dev-net.cahyosoft.local.conf`
  - ServerName: `dev-net.cahyosoft.local`
  - ServerAlias: `dev-net.cahyosoft.my.id`
  - DocumentRoot: `/var/www/dev-erp-rt-rw-net/public`
- **/etc/hosts**: `127.0.0.1 dev-net.cahyosoft.my.id dev-net.cahyosoft.local`
- **Cloudflare**: hostname `dev-net.cahyosoft.my.id` → service `http://dev-net.cahyosoft.local:80`

### Database (`erp_rt_rw_net_dev`)
- User: `erp_dev_user@localhost`
- Password: di `.env` server (random 36 char, generated via `openssl rand -base64 | tr`)
- Terpisah dari prod `erp_rt_rw_net_tmp`

### MinIO bucket (`erp-rt-rw-net-dev`)
- Terpisah dari `erp-rt-rw-net` prod
- Anonymous download (perlu di-set private nanti)
- `.env` config: `MINIO_BUCKET=erp-rt-rw-net-dev`, `MINIO_URL=https://minio-api.cahyosoft.my.id`

### .env dev (vs prod)
- `APP_NAME=ERP RT RW NET DEV`
- `APP_ENV=local`
- `APP_DEBUG=true`
- `DB_DATABASE=erp_rt_rw_net_dev`
- `DB_USERNAME=erp_dev_user`
- `MINIO_BUCKET=erp-rt-rw-net-dev`

### Test users (DemoSeeder)
| Email | Password | Portal |
|-------|----------|--------|
| superadmin@demo.test | password123 | operator-saas |
| admin@netsejahtera.com | password123 | operator-perusahaan |
| rbac.full@rtrwnet.id | password | operator-perusahaan |

## Common gotcha

### Permission denied on storage/logs/laravel.log
Setelah `migrate:fresh --seed`, file `laravel.log` di-create sebagai `root` (karena execute via sudo).
Apache `www-data` gak bisa append, returns 500.
**Fix**: `chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache && find storage -type f -exec chmod 664 {} \;`

## How to deploy ulang (after git push)
```bash
# 1. SSH ke server
~/scripts/ssh_run.sh "cd /var/www/dev-erp-rt-rw-net && git pull origin dev"

# 2. Install new deps kalau composer.json/npm/package.json berubah
~/scripts/ssh_run.sh "cd /var/www/dev-erp-rt-rw-net && composer install --no-interaction && npm install && npm run build"

# 3. Migrate (HATI-HATI: --fresh hapus semua data dev!)
~/scripts/ssh_run.sh "cd /var/www/dev-erp-rt-rw-net && php8.5 artisan migrate --force"

# 4. Clear caches
~/scripts/ssh_run.sh "cd /var/www/dev-erp-rt-rw-net && php8.5 artisan config:clear && php8.5 artisan route:clear && php8.5 artisan view:clear"

# 5. Fix storage perms kalau ada 500
~/scripts/ssh_run.sh "chown -R www-data:www-data /var/www/dev-erp-rt-rw-net/storage /var/www/dev-erp-rt-rw-net/bootstrap/cache && chmod -R 775 /var/www/dev-erp-rt-rw-net/storage /var/www/dev-erp-rt-rw-net/bootstrap/cache"
```

## Related
- [deploy-server-state-2026-06-30](deploy-server-state-2026-06-30.md) — state prod
- [deploy-password-policy](deploy-password-policy.md) — 16+ char password
- [no-creds-in-repo](no-creds-in-repo.md) — wrapper scripts di luar repo
- [dotenv-hash-comment-bug](dotenv-hash-comment-bug.md) — quote `#` di .env