---
name: dotenv-hash-comment-bug
description: "PHP dotenv parser treats '#' as comment delimiter - truncate secret keys with '#'. Always quote values with '#' in .env"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# PHP Dotenv Bug: `#` di secret key di-truncate jadi comment

## Context
2026-06-30 deploy server: upload ke MinIO via Laravel `Storage::disk('minio')->put()` selalu error `SignatureDoesNotMatch`. Padahal:
- boto3 (Python) dgn key sama works
- mc (MinIO client) works
- Tambah `'http' => ['verify' => false]` di filesystems.php gak ngaruh
- Tambah `AWS_VERIFY_SSL=false` di .env gak ngaruh

Root cause: secret `NewMinIO@2026!Str0ng#PwdZx9` di `.env` di-truncate jadi `NewMinIO@2026!Str0ng` karena PHP dotenv parser anggap `#` sebagai start of comment. Tanpa quote di .env, `#PwdZx9` jadi hilang.

## Why
- Dotenv format: `#` = inline comment delimiter (kayak `# this is comment`)
- Kalau value punya `#` di tengah tanpa quote → bagian setelah `#` di-strip
- Laravel pakai vlucas/phpdotenv parser (default behavior)
- Tidak ada warning/error → silent corruption

## How to apply

### Saat setup .env dgn password yg punya `#`:
```bash
# ❌ WRONG (truncated at #)
MINIO_SECRET_KEY=NewMinIO@2026!Str0ng#PwdZx9

# ✅ RIGHT (quoted)
MINIO_SECRET_KEY="NewMinIO@2026!Str0ng#PwdZx9"
```

### Verifikasi cepat:
```bash
php artisan tinker --execute='echo \Config::get("filesystems.disks.minio.secret").PHP_EOL;'
# Harus match persis dengan .env (case-sensitive, no truncation)
```

### Password policy [deploy-password-policy]:
- Hindari `#` di password kalau bisa (ganti dgn `*` atau `^` atau `+` atau alphanumeric)
- Tapi kalau pakai `#` (atau `$`, dll yang special untuk shell/dotenv), **WAJIB quote** di .env

### Test chars yg trigger dotenv comment:
- `#` → comment delimiter (PALING BAHAYA, silent truncation)
- Spasi tanpa quote → mis-parse

## Related
- [deploy-password-policy](deploy-password-policy.md) — policy 16+ char password
- [deploy-server-state-2026-06-30](deploy-server-state-2026-06-30.md) — server state