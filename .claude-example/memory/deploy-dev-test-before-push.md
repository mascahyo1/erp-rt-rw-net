---
name: deploy-dev-test-before-push
description: "Untuk perubahan dev, BUILD + TEST di LOCAL dulu sebelum push ke branch dev. Workflow: edit → build → curl/Playwright → push → deploy."
metadata: 
  node_type: memory
  type: feedback
  originSessionId: a2f224ae-68c6-4186-b7f3-3c83ef161066
---

# Dev: TEST BEFORE PUSH (Local-first workflow)

## Context
2026-06-30 deploy dev: saya edit RiwayatInsentifController (4 'disk' → 'minio') + RiwayatInsentif.vue (default date) terus LANGSUNG push + deploy tanpa test di local.

User tegur:
> "ok gini ya coba kamu build di local test ya dilocal kalau udah bener baru push dev terus deploy saya cek belum tuh"

## Why
- Local = real-time feedback loop (no SSH roundtrip)
- Local Nginx/Apache = Laragon (instant reload)
- Local DB = seeded, predictable
- Kalau build error / typo / Vue gak compile → user gak kecewa dgn broken UI
- Save satu round-trip (commit → push → SSH pull → rebuild) kalau di-local udah fail

## How to apply

### WAJIB sebelum `git push origin dev`:
1. `cd c:\laragon\www\erp-rt-rw-net`
2. `npm run build` → harus exit 0, no errors
3. Quick smoke test:
   - `curl -sI http://erp-rt-rw-net.test/login-karyawan` → expect HTTP 200
   - `curl -sI http://erp-rt-rw-net.test/<route-yang-berubah>` → expect HTTP 200 atau 302 (kalau gated)
4. (Opsional) Playwright headed: login + buka halaman + cek behavior
5. Baru `git add` + `git commit` + `git push`

### Kalau pake Playwright headed (optional tapi powerful):
```bash
node tests/Browser/Playwright/Feature/.../test.cjs
# Verify visual behavior di headed browser
```

### Kalau udah terlanjur push:
- Tenang, gak fatal
- Fix di local, **Jangan** amend (git amend + push -f bahaya kalau user udah pull)
- New commit on top
- Re-deploy

### Workflow checklist:
```
[ ] Edit file (controller/view/vue)
[ ] npm run build (verify exit 0)
[ ] curl smoke test (200/302 di route yg berubah)
[ ] (optional) Playwright headed verify
[ ] git add + commit + push origin dev
[ ] (server-side, via SSH) git pull + npm run build
[ ] Verify di https://dev-net.cahyosoft.my.id/<route>
```

### Local DB setup (kalau pertama kali di local):
```bash
php artisan migrate:fresh --seed
# Default test users:
#   superadmin@demo.test / password123
#   admin@netsejahtera.com / password123
#   admin@digitalmedia.id / (lihat DB)
#   rbac.full@rtrwnet.id / password
```

## Related
- [deploy-dev-env](deploy-dev-env.md) — dev env setup detail
- [testing-with-headed-browser](testing-with-headed-browser.md) — Playwright headed pattern
- [verify skill](/) — `verify` skill buat confirm change works