---
name: deploy-server-state-2026-06-30
description: Current state server production net.cahyosoft.my.id (after 2026-06-30 deploy session)
metadata: 
  node_type: memory
  type: reference
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# Server production state (per 2026-06-30)

## OS
- **OS**: Ubuntu 26.04 LTS
- **Kernel**: 7.0.0-27-generic
- **IP internal**: 10.26.26.7
- **PHP**: 8.5.4 CLI (only version available in Ubuntu 26.04)
- **Composer**: 2.10.1 (installed at /usr/local/bin/composer)
- **Node**: 20.20.2
- **Yarn**: 1.22.22
- **MySQL**: 8.x (running on port 3306, listening only 127.0.0.1)
- **Apache**: 2.4.66 (running, has 2 vhosts: pma.cahyosoft.my.id + net.cahyosoft.my.id)
- **MinIO**: RELEASE.2025-09-07T16-13-09Z (running on 9000/9001, data at /mnt/data/minio/data)
- **cloudflared**: running as token-managed config, no local /etc/cloudflared/config.yml

## Storage
- **/dev/sdb1** (465.8G NTFS) mounted at **/mnt/data** (uid=999, gid=983 for minio-user)
- /mnt/data/minio/data = MinIO data
- /mnt/data/uploads/ = reserved for Laravel uploads
- /mnt/data/laravel-storage/ = reserved

## /etc/hosts (server)
```
127.0.0.1 localhost
127.0.0.1 pma.local
127.0.0.1 net.cahyosoft.my.id laravel.local
127.0.0.1 (any others?)
```

## MySQL users
- **pma** (pma_test DB) - PMaTest2026
- **erp_user** (erp_rt_rw_net DB) - ErpDBS3cur3!2026XyZ

## Laravel project
- **Path**: /var/www/erp-rt-rw-net
- **Owner**: www-data
- **.env**: chown www-data, chmod 600
- **Key files**:
  - .env (DB, MinIO config)
  - config/filesystems.php (minio disk with http.verify=false)
  - database/seeders/ProductionSeeder.php (admin saas, admin perusahaan)

## MinIO users
- **minio_admin** - NewMinIO@2026!Str0ng#PwdZx9 (strong, 27 char)
- Bucket: **erp-rt-rw-net** (for Laravel uploads)
- Bucket: **rt-rw-net** (for phpMyAdmin - legacy, may not be needed)

## Cloudflare Dashboard routes (user must verify/update)
| Hostname | Service URL (current) | Action |
|---|---|---|
| pma.cahyosoft.my.id | http://pma.local:80 | ✅ OK |
| minio-api.cahyosoft.my.id | http://pma.local:9000 | ✅ OK |
| minio-web.cahyosoft.my.id | http://pma.local:9001 | ✅ OK |
| net.cahyosoft.my.id | http://net.cahyosoft.my.id (self-ref) | ⚠️ USER MUST FIX → http://net.cahyosoft.local:80 |

## Login credentials (per ProductionSeeder)
- **Admin Saas**: superadmin@rtrwnet.id / P@ssw0rd!2026
- **Admin Perusahaan**: admin@rtrwnet.id / P@ssw0rd!2026

## Known issues (unresolved)
- **File upload to MinIO via Laravel Storage::put()** — fails with "Unable to write file at location: debug-XXX.txt" even though:
  - mc put via pipe works
  - boto3 with verify=false works
  - Laravel config has http.verify = false
  - Even boto3 with default signature works
  - Possible cause: Laravel League\Flysystem\AwsS3V3 not passing the http config properly, or session/cache issue
  - User reported: edit form saves successfully but file doesn't appear in MinIO

## Step-by-step deploy order
1. phpmyadmin (apt install + git clone + setup wizard)
2. minio (download + setup systemd + env + config)
3. pma vhost fixed (DocumentRoot=/public)
4. net.cahyosoft.my.id vhost created (DocumentRoot=/public, ServerName)
5. MySQL databases (pma_test, erp_rt_rw_net)
6. .env (MINIO_*, DB_*)
7. composer install
8. npm build
9. migrate + ProductionSeeder
10. key:generate + storage:link

## MinIO uploader bug debug commands (saving for later)
```bash
# Quick test from server
ssh -o ProxyCommand="cloudflared access ssh --hostname %h" cahyo@ssh-smago-ubuntu.cahyosoft.my.id

# Test mc upload
mc alias set local http://127.0.0.1:9000 minio_admin 'NewMinIO@2026!Str0ng#PwdZx9'
echo "hello" | mc pipe local/erp-rt-rw-net/test.txt

# Test Laravel upload (fails)
cd /var/www/erp-rt-rw-net
php8.5 artisan tinker --execute='try { Storage::disk("minio")->put("test.txt", "hi", "public"); } catch (\\Throwable $e) { echo $e->getMessage(); }'
```

## Next step (pending)
- Step 4: dev-net.cahyosoft.my.id (Laravel dev environment)

## Related
- [deploy-password-policy](deploy-password-policy.md) — min 16 char password
- [deploy-step-by-step](deploy-step-by-step.md) — stop per step
- [deploy-when-confused-ask](deploy-when-confused-ask.md) — ask if confused
