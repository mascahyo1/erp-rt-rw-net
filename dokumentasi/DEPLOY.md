# Deployment Guide

> Production deploy: `net.cahyosoft.my.id` (Laravel) — **alias: `jmpgroup.id`**.
> Dev deploy: `dev-net.cahyosoft.my.id` (Laravel) — **alias: `dev.jmpgroup.id`**.
> MinIO + phpMyAdmin **tidak** punya alias — tetap via subdomain `*.cahyosoft.my.id`.
> Semua akses via **Cloudflare Tunnel** — port 80 publik gak langsung dibuka.
>
> **INGAT: jangan commit password / API key / SSH credential apapun.**
> Reference secret di file konfig server (`/var/www/.../.env`, `/root/.secret-*`).

---

## 1. Arsitektur

```
User Browser
    │ HTTPS (443)
    ▼
Cloudflare Edge (proxy/reverse proxy)
    │ Cloudflare Tunnel (token-managed, /etc/cloudflared config di server)
    ▼
Apache 2.4 (localhost:80, server 10.26.26.7 internal)
    ├─ pma.cahyosoft.local       → /var/www/pma/public                  → pma.cahyosoft.my.id
    ├─ net.cahyosoft.local       → /var/www/erp-rt-rw-net/public        → net.cahyosoft.my.id, jmpgroup.id
    ├─ dev-net.cahyosoft.local   → /var/www/dev-erp-rt-rw-net/public    → dev-net.cahyosoft.my.id, dev.jmpgroup.id
    ├─ minio-api.cahyosoft.local :9000 (MinIO API, NOT Apache)          → minio-api.cahyosoft.my.id
    └─ minio-web.cahyosoft.local :9001 (MinIO Console, NOT Apache)      → minio-web.cahyosoft.my.id

Catatan: `jmpgroup.id` & `dev.jmpgroup.id` adalah **alias tambahan** (ServerAlias
di vhost yg sama) — gak ada vhost/listener terpisah. MinIO + phpMyAdmin TIDAK
punya alias; tetap hanya via `*.cahyosoft.my.id`.
```

---

## 2. Server Spec

| Komponen | Versi / Path |
|----------|---------------|
| OS | Ubuntu 26.04 LTS, kernel 7.0.0-27-generic |
| Internal IP | 10.26.26.7 |
| PHP | 8.5.4 (CLI only version di Ubuntu 26.04) |
| Composer | 2.10.1 di `/usr/local/bin/composer` |
| Node | 20.20.2 |
| Yarn | 1.22.22 |
| MySQL | 8.x di 127.0.0.1:3306 (listen localhost only) |
| Apache | 2.4.66, has 5 vhosts (lihat §3) |
| MinIO | RELEASE.2025-09-07T16-13-09Z di :9000/:9001 |
| cloudflared | token-managed config, NO `/etc/cloudflared/config.yml` |

### Storage (HDD 500 GB terpisah)

| Mountpoint | Device | Own | Isi |
|-----------|--------|-----|-----|
| `/mnt/data` | `/dev/sdb1` (465.8G NTFS) | `minio-user:minio-user` | MinIO data |
| `/mnt/data/minio/data` | - | - | MinIO storage |
| `/mnt/data/uploads/` | - | `www-data:www-data` | (reserved, kalau perlu file upload lokal) |

> Root partition (`/`) cuma SSD kecil 50–100GB. **JANGAN** taruh file upload di root.

---

## 3. Apache vhosts

### File layout

```
/etc/apache2/sites-available/
├── pma.cahyosoft.local.conf
├── net.cahyosoft.local.conf
└── dev-net.cahyosoft.local.conf

/etc/apache2/sites-enabled/
├── (symlink ke sites-available, diaktifkan via `a2ensite`)
```

### Template vhost (production Laravel)

```apache
<VirtualHost *:80>
    ServerName net.cahyosoft.local
    ServerAlias net.cahyosoft.my.id jmpgroup.id www.jmpgroup.id
    DocumentRoot /var/www/erp-rt-rw-net/public

    <Directory /var/www/erp-rt-rw-net/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/net-error.log
    CustomLog ${APACHE_LOG_DIR}/net-access.log combined
</VirtualHost>
```

### Template vhost (dev Laravel)

```apache
<VirtualHost *:80>
    ServerName dev-net.cahyosoft.local
    ServerAlias dev-net.cahyosoft.my.id dev.jmpgroup.id www.dev.jmpgroup.id
    DocumentRoot /var/www/dev-erp-rt-rw-net/public

    <Directory /var/www/dev-erp-rt-rw-net/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/dev-net-error.log
    CustomLog ${APACHE_LOG_DIR}/dev-net-access.log combined
</VirtualHost>
```

> `www.jmpgroup.id` & `www.dev.jmpgroup.id` opsional — tambahkan kalau mau
> user yang ngetik `www.` prefix juga resolve. Apex `jmpgroup.id` selalu
> di-serve tanpa prefix.

### Template vhost (phpMyAdmin)

```apache
<VirtualHost *:80>
    ServerName pma.cahyosoft.local
    ServerAlias pma.cahyosoft.my.id pma.local
    DocumentRoot /var/www/pma/public         # NOTE: /public (bukan root)

    <Directory /var/www/pma/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/pma-error.log
    CustomLog ${APACHE_LOG_DIR}/pma-access.log combined
</VirtualHost>
```

> ⚠️ `phpMyAdmin 6.x` DocumentRoot HARUS `/var/www/pma/public` (ada sub-folder `public/`).
> Kalau salah set ke `/var/www/pma`, `/setup/` wizard 404.

### `/etc/hosts` (server)

```
127.0.0.1 localhost
127.0.0.1 pma.local
127.0.0.1 pma.cahyosoft.my.id pma.cahyosoft.local
127.0.0.1 net.cahyosoft.my.id net.cahyosoft.local jmpgroup.id www.jmpgroup.id
127.0.0.1 dev-net.cahyosoft.my.id dev-net.cahyosoft.local dev.jmpgroup.id www.dev.jmpgroup.id
```

> Entry `jmpgroup.id` di `/etc/hosts` WAJIB — tanpa ini Apache vhost
> `ServerAlias jmpgroup.id` gak akan match kalau Cloudflare tunnel
> forward pakai hostname `jmpgroup.id` (lihat §4 Service URL trap).

### Aktivasi

```bash
sudo a2ensite pma.cahyosoft.local
sudo a2ensite net.cahyosoft.local
sudo a2ensite dev-net.cahyosoft.local
sudo systemctl reload apache2
```

---

## 4. Cloudflare Tunnel

### Pattern

- Server `cloudflared` jalan via **systemd token-managed** — TIDAK pakai `/etc/cloudflared/config.yml`.
- Token di-pass via systemd environment (lihat `systemctl status cloudflared`).
- Setiap hostname di dashboard Cloudflare di-route ke Service URL internal (hostname lokal + port).

### Hostname → Service URL mapping (manual di dashboard)

| Hostname (publik) | Service URL (internal) | Backend |
|-------------------|------------------------|---------|
| `pma.cahyosoft.my.id` | `http://pma.local:80` | phpMyAdmin (Apache) |
| `net.cahyosoft.my.id` | `http://net.cahyosoft.local:80` | Laravel prod (Apache) |
| `jmpgroup.id` | `http://net.cahyosoft.local:80` | Laravel prod (alias, Apache) |
| `www.jmpgroup.id` | `http://net.cahyosoft.local:80` | Laravel prod (alias www, Apache) |
| `dev-net.cahyosoft.my.id` | `http://dev-net.cahyosoft.local:80` | Laravel dev (Apache) |
| `dev.jmpgroup.id` | `http://dev-net.cahyosoft.local:80` | Laravel dev (alias, Apache) |
| `www.dev.jmpgroup.id` | `http://dev-net.cahyosoft.local:80` | Laravel dev (alias www, Apache) |
| `minio-api.cahyosoft.my.id` | `http://pma.local:9000` | MinIO API |
| `minio-web.cahyosoft.my.id` | `http://pma.local:9001` | MinIO Console |

> ⚠️ `jmpgroup.id` & `dev.jmpgroup.id` adalah domain **terpisah** dari
> `cahyosoft.my.id` — keduanya harus di-add ke Cloudflare (zero-trust zone
> atau DNS zone yg sama). DNS record `jmpgroup.id` di-point ke tunnel
> endpoint (CNAME `<tunnel-id>.cfargotunnel.com`) — lihat §11 catatan.
>
> ⚠️ Service URL HARUS hostname internal yang ada di `/etc/hosts`, BUKAN `localhost`.
> Kalau set ke `http://localhost:80` untuk `net.cahyosoft.my.id` → redirect loop / 400.
> `pma.local` ada di `/etc/hosts` jadi boleh dipakai untuk yang non-Laravel (pma, minio).

### Setup token (per server, sekali)

```bash
# (Manual di dashboard Cloudflare Zero Trust → Tunnels → Create)
# Dapatkan token, lalu setup systemd:
sudo systemctl edit cloudflared
# Override ExecStart dengan --token <TOKEN>
sudo systemctl restart cloudflared
```

> **Token** ada di server (env var atau systemd unit), **JANGAN** commit ke repo.

---

## 5. MinIO

### Service

- Binary: `minio` (systemd service `minio.service`)
- Data: `/mnt/data/minio/data` (HDD 500GB, owner `minio-user`)
- Env: `/etc/default/minio` (set `MINIO_ROOT_USER`, `MINIO_ROOT_PASSWORD`, `MINIO_VOLUMES`)

### Buckets

| Bucket | Untuk | Anonymous |
|--------|-------|-----------|
| `erp-rt-rw-net` | Laravel production uploads | `none` (private) |
| `erp-rt-rw-net-dev` | Laravel dev uploads | `none` (private) |
| `rt-rw-net` | legacy (phpMyAdmin?) | `none` |

> ⚠️ File harus private — diakses browser via Laravel route `file.proxy` (signed URL).
> Kalau set anonymous `download` di bucket, file bisa diintip langsung tanpa auth.

### IAM user (untuk Laravel `.env`)

- Access key: `minio_admin` (root-equivalent)
- Secret key: lihat `/etc/default/minio` (atau `/root/.secret-minio`) — **JANGAN** commit.

### Verify

```bash
mc alias set local http://127.0.0.1:9000 minio_admin "$(cat /root/.secret-minio)"
mc ls local/
```

---

## 6. phpMyAdmin

### Install (git clone, BUKAN apt)

```bash
cd /var/www
git clone https://github.com/phpmyadmin/phpmyadmin.git pma
cd pma
# Composer install kalau ada
composer install --no-dev --no-interaction
```

> ⚠️ Pakai `git clone` (bukan `apt install phpmyadmin`) — biar integrasi sama project + version control jelas.

### Apache DocumentRoot

HARUS `/var/www/pma/public` (lihat §3).

### Setup wizard

Akses `https://pma.cahyosoft.my.id/setup/` sekali setelah install:
- Pilih server `127.0.0.1`
- Pilih auth `config` atau `cookie`
- Generate config.inc.php → copy ke `/var/www/pma/config.inc.php`

> `/setup/` URL only available kalau folder `public/setup/` exists + writable.
> Setelah selesai, opsional rename ke `/config/` untuk disable wizard.

### MySQL users

| User | DB | Permissions | Password |
|------|----|-------------|----------|
| `pma` | `pma_test` | db-specific | `/root/.secret-mysql-pma` |
| `erp_user` | `erp_rt_rw_net_tmp` | db-specific | `/root/.secret-mysql-erp` |
| `erp_dev_user` | `erp_rt_rw_net_dev` | db-specific | `/root/.secret-mysql-erp-dev` |

> ⚠️ Password di server, di `/root/.secret-*` (chmod 600), **JANGAN** commit ke repo.

---

## 7. Laravel Environment

### Path

| Env | Path | Branch | APP_ENV | APP_DEBUG |
|-----|------|--------|---------|-----------|
| Production | `/var/www/erp-rt-rw-net` | `main` | `production` | `false` |
| Dev | `/var/www/dev-erp-rt-rw-net` | `dev` | `local` | `true` |

### `.env` schema (per environment)

```bash
APP_NAME="ERP RT/RW Net"
APP_ENV=production      # atau "local" untuk dev
APP_DEBUG=false         # atau "true" untuk dev
APP_URL=https://jmpgroup.id          # atau https://dev.jmpgroup.id (dev)
#   Alternatif legacy (kalau APP_URL masih nyimpen versi lama):
#     prod: https://net.cahyosoft.my.id
#     dev:  https://dev-net.cahyosoft.my.id
#   Pilih SATU — ini yang dipakai Laravel untuk generate signed URL,
#   password reset link, file proxy URL, dst. Domain lain tetap bisa
#   diakses user karena Apache ServerAlias multiple domain → 1 vhost.

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=erp_rt_rw_net_tmp         # atau erp_rt_rw_net_dev
DB_USERNAME=erp_user                  # atau erp_dev_user
DB_PASSWORD="..."                     # QUOTE jika ada '#' atau '$'

FILESYSTEM_DISK=minio

AWS_ACCESS_KEY_ID=minio_admin
AWS_SECRET_ACCESS_KEY="..."           # QUOTE — lihat dotenv-hash-comment-bug
MINIO_ACCESS_KEY=minio_admin
MINIO_SECRET_KEY="..."
MINIO_REGION=us-east-1
MINIO_BUCKET=erp-rt-rw-net            # atau erp-rt-rw-net-dev
MINIO_ENDPOINT=http://127.0.0.1:9000
MINIO_URL=https://minio-api.cahyosoft.my.id
MINIO_USE_PATH_STYLE_ENDPOINT=true
```

> ⚠️ **Quote `.env` values yang punya `#` atau `$`** — dotenv parser treat sebagai comment delimiter.
> Lihat [no-passwords-in-chat](../.claude/projects/c--laragon-www-erp-rt-rw-net/memory/no-passwords-in-chat.md) untuk rule soal password.

### Permission storage (post-migrate fix)

```bash
sudo chown -R www-data:www-data /var/www/erp-rt-rw-net/storage /var/www/erp-rt-rw-net/bootstrap/cache
sudo chmod -R 775 /var/www/erp-rt-rw-net/storage /var/www/erp-rt-rw-net/bootstrap/cache
sudo find /var/www/erp-rt-rw-net/storage -type f -exec chmod 664 {} \;
sudo find /var/www/erp-rt-rw-net/storage -type d -exec chmod 775 {} \;
```

> Setelah `migrate:fresh --seed` via SSH (sudo), `storage/logs/laravel.log` jadi owned by `root` → Apache 500. WAJIB re-chown.

### First deploy (production)

```bash
cd /var/www
git clone https://github.com/mascahyo1/erp-rt-rw-net.git erp-rt-rw-net
cd erp-rt-rw-net
composer install --no-interaction --prefer-dist
npm install && npm run build
cp .env.example .env  # edit sesuai §7
php8.5 artisan key:generate
php8.5 artisan storage:link
# PENTING: ProductionSeeder, BUKAN DemoSeeder
php8.5 artisan migrate --force
php8.5 artisan db:seed --class=ProductionSeeder --force
sudo chown -R www-data:www-data storage bootstrap/cache
sudo a2ensite net.cahyosoft.local && sudo systemctl reload apache2
```

### First deploy (dev)

```bash
cd /var/www
git clone -b dev https://github.com/mascahyo1/erp-rt-rw-net.git dev-erp-rt-rw-net
cd dev-erp-rt-rw-net
composer install --no-interaction --prefer-dist
npm install && npm run build
cp .env.example .env  # edit sesuai §7 + DB dev + bucket dev
php8.5 artisan key:generate
php8.5 artisan storage:link
php8.5 artisan migrate:fresh --seed --force  # DemoSeeder
sudo chown -R www-data:www-data storage bootstrap/cache
sudo a2ensite dev-net.cahyosoft.local && sudo systemctl reload apache2
```

### Re-deploy (after git push)

```bash
~/scripts/ssh_run.sh "cd /var/www/erp-rt-rw-net && git pull origin main"
~/scripts/ssh_run.sh "cd /var/www/erp-rt-rw-net && composer install --no-interaction && npm install && npm run build"
~/scripts/ssh_run.sh "cd /var/www/erp-rt-rw-net && php8.5 artisan migrate --force"
~/scripts/ssh_run.sh "cd /var/www/erp-rt-rw-net && php8.5 artisan config:clear route:clear view:clear"
```

---

## 8. Domain URLs Reference

### Public (HTTPS via Cloudflare)

| Service | URL |
|---------|-----|
| Laravel Production | https://net.cahyosoft.my.id (legacy) / https://jmpgroup.id (alias aktif, APP_URL) |
| Laravel Dev | https://dev-net.cahyosoft.my.id (legacy) / https://dev.jmpgroup.id (alias aktif, APP_URL) |
| phpMyAdmin | https://pma.cahyosoft.my.id |
| MinIO Console | https://minio-web.cahyosoft.my.id |
| MinIO API | https://minio-api.cahyosoft.my.id (S3 endpoint, not browser-friendly) |

> Alias `www.jmpgroup.id` & `www.dev.jmpgroup.id` juga resolve kalau
> ServerAlias di vhost di-tambah dan route di Cloudflare dashboard ada.

### Setup `jmpgroup.id` & `dev.jmpgroup.id` (alias step-by-step)

Alias sudah ada di vhost + `/etc/hosts` pattern (lihat §3). Untuk
mengaktifkan end-to-end, kedua sisi harus ikut di-config:

**1. Server-side (per server, sekali):**

```bash
# Backup vhost existing
sudo cp /etc/apache2/sites-available/net.cahyosoft.local.conf \
        /etc/apache2/sites-available/net.cahyosoft.local.conf.bak.$(date +%Y%m%d)
sudo cp /etc/apache2/sites-available/dev-net.cahyosoft.local.conf \
        /etc/apache2/sites-available/dev-net.cahyosoft.local.conf.bak.$(date +%Y%m%d)

# Tambah ServerAlias (pakai sed atau edit manual)
sudo sed -i 's/ServerAlias net.cahyosoft.my.id/ServerAlias net.cahyosoft.my.id jmpgroup.id www.jmpgroup.id/' \
    /etc/apache2/sites-available/net.cahyosoft.local.conf
sudo sed -i 's/ServerAlias dev-net.cahyosoft.my.id/ServerAlias dev-net.cahyosoft.my.id dev.jmpgroup.id www.dev.jmpgroup.id/' \
    /etc/apache2/sites-available/dev-net.cahyosoft.local.conf

# Verify (HARUS ada jmpgroup.id di ServerAlias line net.cahyosoft.local.conf)
grep ServerAlias /etc/apache2/sites-available/net.cahyosoft.local.conf
grep ServerAlias /etc/apache2/sites-available/dev-net.cahyosoft.local.conf

# Tambah /etc/hosts entry
sudo tee -a /etc/hosts > /dev/null << 'EOF'
127.0.0.1 jmpgroup.id www.jmpgroup.id
127.0.0.1 dev.jmpgroup.id www.dev.jmpgroup.id
EOF

# Reload Apache (gak restart, biar gak disrupt existing traffic)
sudo apache2ctl configtest   # WAJIB: "Syntax OK" sebelum reload
sudo systemctl reload apache2
```

**2. Laravel `.env` (per environment):**

```bash
# Di server, edit /var/www/erp-rt-rw-net/.env (prod) & /var/www/dev-erp-rt-rw-net/.env (dev)
# Ganti APP_URL line jadi:
#   prod: APP_URL=https://jmpgroup.id
#   dev:  APP_URL=https://dev.jmpgroup.id
#
# Lalu clear caches biar route cache, signed URL, dsb re-generate pakai host baru:
cd /var/www/erp-rt-rw-net
php8.5 artisan config:clear
php8.5 artisan route:clear
php8.5 artisan view:clear
php8.5 artisan storage:link   # idempotent
```

> Setelah `APP_URL` ganti, **password reset link** & **file proxy URL**
> akan generate pakai host baru. User yg sudah punya reset link dari
> host lama masih valid (signed URL independent of APP_URL setelah
> di-sign), tapi link baru akan pakai host baru.

**3. Cloudflare dashboard (WAJIB — gak bisa di-script):**

`jmpgroup.id` adalah domain **terpisah** dari `cahyosoft.my.id`. Server
gak punya public IP — semua akses HARUS via Cloudflare Tunnel yg sama.

Step:
1. **Add jmpgroup.id ke Cloudflare account** (Free plan cukup):
   - Dashboard → Add Site → `jmpgroup.id` → Free plan
   - Cloudflare akan kasih 2 nameserver (`xxx.ns.cloudflare.com`)
2. **Update nameserver di registrar `jmpgroup.id`** ke ns Cloudflare di atas
   (propagasi DNS 5 menit – 24 jam)
3. **Add public hostname di tunnel existing** (Zero Trust → Networks → Tunnels → tunnel existing → Configure → Public Hostname):
   | Subdomain | Type | Domain | Service URL |
   |-----------|------|--------|-------------|
   | (apex) | HTTPS | `jmpgroup.id` | `http://net.cahyosoft.local:80` |
   | www | HTTPS | `jmpgroup.id` | `http://net.cahyosoft.local:80` |
   | dev | HTTPS | `jmpgroup.id` | `http://dev-net.cahyosoft.local:80` |
   | (apex dev) | HTTPS | `dev.jmpgroup.id` | `http://dev-net.cahyosoft.local:80` |
   | www dev | HTTPS | `dev.jmpgroup.id` | `http://dev-net.cahyosoft.local:80` |
   - TIDAK perlu setting TLS di tunnel (Cloudflare auto-issue cert)
   - HTTP→HTTPS: di tab "Additional application settings" → TLS → set ke "OFF" (default) atau "ON" sesuai preferensi
4. **Verify**:
   ```bash
   # Dari workstation, setelah DNS propagasi:
   curl -sIL https://jmpgroup.id/login-perusahaan | grep -E "HTTP|location"
   # Expected: 200 OK langsung (atau 302 ke /login)
   # WRONG: redirect loop ke host yg sama
   ```

⚠️ **Konsiderasi:**
- Kalau `jmpgroup.id` ditambah di **Cloudflare account yg sama** dengan
  `cahyosoft.my.id`, tunnel & DNS lebih simpel (tinggal add public
  hostname). Kalau beda account, tunnel Cloudflare cuma bisa route ke
  Zone yg sama — perlu pindah `jmpgroup.id` ke account yg sama atau
  bikin tunnel baru.
- `MinIO` & `phpMyAdmin` **TIDAK** punya alias — tetap di
  `*.cahyosoft.my.id` (gak diubah).

### Internal (server-local, Apache)

| Service | URL |
|---------|-----|
| Laravel Production | http://net.cahyosoft.local |
| Laravel Dev | http://dev-net.cahyosoft.local |
| phpMyAdmin | http://pma.local (atau http://pma.cahyosoft.local) |
| MinIO API | http://127.0.0.1:9000 |
| MinIO Console | http://127.0.0.1:9001 |

### Local dev (Windows + Laragon)

```
http://erp-rt-rw-net.test/
```

(untuk development biasa tanpa Cloudflare — Laragon auto-resolve `.test` TLD.)

---

## 9. Test Users

### Production (per ProductionSeeder)

| Email | Password | Portal |
|-------|----------|--------|
| `superadmin@rtrwnet.id` | (lihat `/root/.secret-admin-saas`) | operator-saas |
| `admin@rtrwnet.id` | (lihat `/root/.secret-admin-perusahaan`) | operator-perusahaan |

> Password ada di `/root/.secret-*` di server. User bisa ambil sendiri via SSH.

### Dev (per DemoSeeder)

| Email | Password | Portal |
|-------|----------|--------|
| `superadmin@demo.test` | `password123` | operator-saas |
| `admin@netsejahtera.com` | `password123` | operator-perusahaan |
| `rbac.full@rtrwnet.id` | `password` | operator-perusahaan |

> Dev pakai password lemah by design (testing only). **JANGAN** pakai kredensial dev di production.

---

## 10. Setup SSH Helper (Local Windows)

Wrapper script harus **di LUAR repo** (lihat [no-creds-in-repo](../.claude/projects/c--laragon-www-erp-rt-rw-net/memory/no-creds-in-repo.md)):

```
C:\Users\<user>\.ssh\
├── askpass_ssh_sudo.sh      # SSH password (SSH_ASKPASS)
├── askpass_sudo.sh          # Sudo password on server (SUDO_ASKPASS)
└── config                   # SSH config (Host=smago + cloudflared ProxyCommand)

C:\Users\<user>\scripts\
└── ssh_run.sh               # wrapper untuk "ssh + sudo"
```

`~/scripts/ssh_run.sh`:

```bash
#!/bin/bash
# SSH + sudo via SSH_ASKPASS + SUDO_ASKPASS
CMD="$1"
HOST="smago"

# Upload askpass ke server
DISPLAY=:0 SSH_ASKPASS=~/.ssh/askpass_ssh_sudo.sh SSH_ASKPASS_REQUIRE=force \
  ssh "$HOST" 'cat > /tmp/sudoask.sh << "EOF"
#!/bin/bash
echo "<ASKPASS_PASSWORD>"   # password di /root/.askpass, JANGAN echo literal
EOF
chmod +x /tmp/sudoask.sh'

# Run cmd via sudo
B64=$(printf '%s' "$CMD" | base64 -w 0)
DISPLAY=:0 SSH_ASKPASS=~/.ssh/askpass_ssh_sudo.sh SSH_ASKPASS_REQUIRE=force \
  ssh "$HOST" "echo $B64 | base64 -d > /tmp/_cmd.sh && chmod +x /tmp/_cmd.sh && \
    SUDO_ASKPASS=/tmp/sudoask.sh sudo -A /tmp/_cmd.sh"
```

> Replace `<ASKPASS_PASSWORD>` dengan `$(cat ~/.ssh/askpass_sudo_password)` (password lokal di `~/.ssh/`, chmod 600).

---

## 11. Common Gotchas

### Laravel 500 di dev setelah migrate:fresh

```bash
# storage/logs/laravel.log owned by root (karena sudo)
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Upload ke MinIO "SignatureDoesNotMatch"

`.env` punya `#` di secret key → di-truncate oleh dotenv parser. **Quote** nilainya:

```bash
# ❌ MINIO_SECRET_KEY=<SECRET_WITH_HASH>             # truncated di '#'
# ✅ MINIO_SECRET_KEY="<SECRET_WITH_HASH>"           # full key dipakai
```

Lihat memory `dotenv-hash-comment-bug` di `.claude/` projects folder.

### Cloudflare dashboard Service URL salah

Service URL HARUS hostname internal (`pma.local`, `net.cahyosoft.local`) yang ada di `/etc/hosts`. Kalau set `http://localhost:80` ke `net.cahyosoft.my.id` → redirect loop / 400.

### Apache DocumentRoot phpMyAdmin

`/var/www/pma/public` (bukan `/var/www/pma`). Kalau salah → `/setup/` wizard 404.

### cloudflared config.yml

TIDAK BOLEH buat `/etc/cloudflared/config.yml` — pakai token-managed systemd unit. Kalau double-config, conflict dan tunnel gak jalan.

---

## 12. Security Checklist (per deploy)

- [ ] `.env` chmod 600, owner `www-data`
- [ ] `storage/` + `bootstrap/cache` owner `www-data`, chmod 775
- [ ] Bucket MinIO anonymous = `none` (private)
- [ ] Cloudflare Service URL pakai hostname internal, bukan `localhost`
- [ ] Password baru min 16 char, mix upper/lower/digit/symbol (lihat `deploy-password-policy`)
- [ ] **Password JANGAN** di chat, JANGAN di git, JANGAN di memory file
- [ ] `.gitignore` exclude `/ssh_*.sh`, `/tmp_*.sh`, `/tmp_*.b64`
- [ ] Wrapper script di `~/scripts/` atau `~/.ssh/` (LUAR repo)

---

## Related

- [CONVENTIONS.md](CONVENTIONS.md) — coding conventions
- Memory `.claude/projects/.../memory/`:
  - `deploy-server-state-2026-06-30.md` — server state per 2026-06-30
  - `deploy-dev-env.md` — dev env setup detail
  - `deploy-password-policy.md` — 16+ char password rule
  - `deploy-step-by-step.md` — stop per step workflow
  - `no-creds-in-repo.md` — credentials di LUAR repo
  - `no-passwords-in-chat.md` — JANGAN echo password
  - `dotenv-hash-comment-bug.md` — quote `#` di .env